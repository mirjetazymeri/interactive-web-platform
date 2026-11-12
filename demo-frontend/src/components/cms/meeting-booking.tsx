"use client";

import { useEffect, useId, useRef, useState, useSyncExternalStore, type FormEvent, type ReactNode } from "react";
import {
  bookingResponseSchema, dateKey, dayLabel, durationOf, formatsOf, groupSlots, isTimeZone, rangeFrom, slotsResponseSchema, timeRange,
  type BookingResult, type MeetingConfig, type MeetingFormat, type Slot,
} from "@/lib/cms/meetings";
import { safeUrl } from "@/lib/cms/links";
import { track } from "@/lib/tracking/events";
import base from "./contact-form.module.css";
import styles from "./meeting-booking.module.css";

type Props = { locale: string; path: string; widgetId: number; config: MeetingConfig };
type Step = "service" | "format" | "time" | "details" | "review";
type Details = { name: string; email: string; phone: string; company: string; projectDetails: string };
type SlotsEntry = { status: "ready" | "error" | "unavailable"; slots: Slot[] };

/** Days of availability fetched per page of the date strip. */
const PAGE_DAYS = 14;

/** Interface copy (English and Albanian, like the contact form); the widget's own text comes from the CMS. */
function copy(sq: boolean) {
  return sq ? {
    steps: { service: "Lloji i takimit", format: "Mënyra", time: "Data dhe ora", details: "Të dhënat", review: "Konfirmimi" } as Record<Step, string>,
    stepOf: (n: number, total: number) => `Hapi ${n} nga ${total}`,
    serviceTitle: "Zgjidhni llojin e takimit", serviceHint: "Zgjidhni një për të vazhduar.", formatTitle: "Si dëshironi të takohemi?", timeTitle: "Zgjidhni datën dhe orën",
    detailsTitle: "Na tregoni për ju dhe projektin", reviewTitle: "Kontrolloni dhe konfirmoni",
    minutes: (m: number) => `${m} min`, back: "Kthehu", next: "Vazhdo", edit: "Ndrysho",
    timesIn: (tz: string) => `Oraret në ${tz}`, showBusiness: (tz: string) => `Shfaq në ${tz}`, showLocal: "Shfaq në orën time",
    freeTimes: (n: number) => `${n} ${n === 1 ? "orë e lirë" : "orë të lira"}`,
    loading: "Duke ngarkuar oraret…", noSlots: "Nuk ka orare të lira në këto ditë.", later: "Ditët në vijim", earlier: "Ditët e mëparshme",
    slotsFailed: "Oraret nuk u ngarkuan. Provoni përsëri.", retry: "Provo përsëri", unavailable: "Rezervimi nuk është i disponueshëm përkohësisht. Provoni përsëri pas pak.",
    name: "Emri", email: "Email", phone: "Telefoni", company: "Kompania", project: "Projekti", optional: "Opsionale",
    namePlaceholder: "Emri dhe mbiemri", emailPlaceholder: "emri@kompania.com", projectPlaceholder: "Çfarë doni të arrini? Afati, buxheti, çdo gjë që na ndihmon të përgatitemi.",
    service: "Takimi", format: "Mënyra", when: "Kur", you: "Ju",
    online: "Online", inPerson: "Fizikisht", sending: "Duke rezervuar…",
    taken: "Kjo orë sapo u zu. Ju lutemi zgjidhni një tjetër.", failed: "Rezervimi nuk u krye. Kontrolloni të dhënat dhe provoni përsëri.",
    reference: "Referenca", linkReady: "Lidhja e takimit është në email-in tuaj.", linkPending: "Lidhja e takimit do t’ju dërgohet me email para takimit.",
    directions: "Merr udhëzimet", pending: "Kërkesa juaj u mor dhe do të konfirmohet së shpejti.", another: "Rezervo një takim tjetër",
  } : {
    steps: { service: "Meeting type", format: "Format", time: "Date & time", details: "Your details", review: "Confirm" } as Record<Step, string>,
    stepOf: (n: number, total: number) => `Step ${n} of ${total}`,
    serviceTitle: "Choose a meeting type", serviceHint: "Select one to continue.", formatTitle: "How would you like to meet?", timeTitle: "Pick a date and time",
    detailsTitle: "Tell us about you and your project", reviewTitle: "Review and confirm",
    minutes: (m: number) => `${m} min`, back: "Back", next: "Continue", edit: "Change",
    timesIn: (tz: string) => `Times in ${tz}`, showBusiness: (tz: string) => `Show in ${tz}`, showLocal: "Show in my time",
    freeTimes: (n: number) => `${n} ${n === 1 ? "time" : "times"}`,
    loading: "Loading times…", noSlots: "No free times on these days.", later: "Later dates", earlier: "Earlier dates",
    slotsFailed: "Times could not be loaded. Please try again.", retry: "Try again", unavailable: "Booking is temporarily unavailable. Please try again shortly.",
    name: "Name", email: "Email", phone: "Phone", company: "Company", project: "Project", optional: "Optional",
    namePlaceholder: "Your full name", emailPlaceholder: "name@company.com", projectPlaceholder: "What would you like to achieve? Timeline, budget, anything that helps us prepare.",
    service: "Meeting", format: "Format", when: "When", you: "You",
    online: "Online", inPerson: "In person", sending: "Booking…",
    taken: "That time was just taken. Please pick another one.", failed: "The booking didn’t go through. Check your details and try again.",
    reference: "Reference", linkReady: "Your meeting link is in your email.", linkPending: "Your meeting link will be emailed to you before the meeting.",
    directions: "Get directions", pending: "Your request was received and will be confirmed shortly.", another: "Book another meeting",
  };
}

/** The browser's timezone; null while rendering on the server. */
const subscribeNever = () => () => {};
const browserTimeZone = () => {
  const zone = Intl.DateTimeFormat().resolvedOptions().timeZone;
  return zone && isTimeZone(zone) ? zone : null;
};

/**
 * Meeting booking: service → format → date & time → details → review. The
 * service and format steps are skipped when there is only one choice. Times
 * load per type, format and two-week page, cached for the visit; the booking
 * route re-checks everything and answers 409 when a time was just taken.
 */
export function MeetingBooking({ locale, path, widgetId, config }: Props) {
  const t = copy(locale === "sq");
  const id = useId();
  const { copy: cms, types } = config;
  const single = types.length === 1 ? types[0] : null;

  const [typeSlug, setTypeSlug] = useState<string | null>(single?.slug ?? null);
  const type = types.find((option) => option.slug === typeSlug) ?? null;
  const [format, setFormat] = useState<MeetingFormat | null>(single?.default_format ?? null);
  const steps: Step[] = [
    ...(single ? [] : ["service" as const]),
    // Before a type is picked, count the format step only if some type offers a choice.
    ...((type ? formatsOf(type).length > 1 : types.some((option) => option.formats.length > 1)) ? ["format" as const] : []),
    "time", "details", "review",
  ];
  const [step, setStep] = useState<Step>(steps[0]);
  const stepIndex = Math.max(0, steps.indexOf(step));

  // Times show in the visitor's timezone unless they switch to the business one.
  const visitorZone = useSyncExternalStore(subscribeNever, browserTimeZone, () => null);
  const [businessTime, setBusinessTime] = useState(false);
  const zone = (!businessTime && visitorZone) || config.timezone;

  const [page, setPage] = useState(0);
  const [results, setResults] = useState<Record<string, SlotsEntry>>({});
  const [reload, setReload] = useState(0);
  const requested = useRef(new Set<string>());
  const key = type && format ? `${type.slug}|${format}|${page}` : null;
  const entry = key ? results[key] : undefined;
  const days = entry ? groupSlots(entry.slots, zone, locale) : [];
  const [slotStart, setSlotStart] = useState<string | null>(null);
  const [day, setDay] = useState<string | null>(null);
  const selectedDay = days.find((d) => d.date === day) ?? days.find((d) => slotStart && d.date === dateKey(slotStart, zone)) ?? days[0] ?? null;
  const slot = entry?.slots.find((s) => s.start === slotStart) ?? null;

  const [details, setDetails] = useState<Details | null>(null);
  const [pending, setPending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [booked, setBooked] = useState<BookingResult | null>(null);

  // Load times as soon as a type and format are known (the format step preselects one, so this prefetches).
  useEffect(() => {
    if (!key || !type || !format || requested.current.has(key)) return;
    requested.current.add(key);
    const controller = new AbortController();
    const { from, to } = rangeFrom(new Date(Date.now() + page * PAGE_DAYS * 86_400_000), PAGE_DAYS);
    const query = new URLSearchParams({ locale, path, widgetId: String(widgetId), type: type.slug, format, from, to });
    fetch(`/api/meetings/availability?${query}`, { signal: controller.signal })
      .then(async (response): Promise<SlotsEntry> => response.ok
        ? { status: "ready", slots: slotsResponseSchema.parse(await response.json()).slots }
        : { status: response.status === 503 ? "unavailable" : "error", slots: [] })
      .catch((): SlotsEntry | null => controller.signal.aborted ? null : { status: "error", slots: [] })
      .then((loaded) => {
        if (loaded) setResults((previous) => ({ ...previous, [key]: loaded }));
        else requested.current.delete(key);
      });
    return () => controller.abort();
  }, [key, type, format, page, locale, path, widgetId, reload]);

  // Move focus to the new step's heading, but not on first render.
  const heading = useRef<HTMLHeadingElement>(null);
  const moved = useRef(false);
  useEffect(() => {
    if (moved.current) heading.current?.focus();
    moved.current = true;
  }, [step, booked]);

  const go = (next: Step) => { setError(null); setStep(next); };
  const forward = () => go(steps[Math.min(stepIndex + 1, steps.length - 1)]);
  const backward = () => go(steps[Math.max(stepIndex - 1, 0)]);

  function chooseType(slug: string) {
    const option = types.find((o) => o.slug === slug)!;
    setTypeSlug(slug);
    setFormat(option.default_format ?? option.formats[0].format);
    setPage(0); setSlotStart(null); setDay(null);
  }
  function chooseFormat(next: MeetingFormat) {
    setFormat(next); setPage(0); setSlotStart(null); setDay(null);
  }
  /** Forget loaded times for this type and format (after one was taken) and load them again. */
  function refreshTimes() {
    if (!type || !format) return;
    const prefix = `${type.slug}|${format}|`;
    requested.current.forEach((k) => { if (k.startsWith(prefix)) requested.current.delete(k); });
    setResults((previous) => Object.fromEntries(Object.entries(previous).filter(([k]) => !k.startsWith(prefix))));
    setReload((n) => n + 1);
  }

  function saveDetails(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    if (String(data.get("website") ?? "")) return; // honeypot
    const value = (name: string) => String(data.get(name) ?? "").trim();
    setDetails({ name: value("name"), email: value("email"), phone: value("phone"), company: value("company"), projectDetails: value("projectDetails") });
    forward();
  }

  async function confirm() {
    if (!type || !format || !slot || !details) return;
    setPending(true); setError(null);
    try {
      const response = await fetch("/api/meetings/bookings", {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ locale, path, widgetId, type: type.slug, format, start: slot.start, visitorTimezone: visitorZone ?? config.timezone, consent: true, ...details,
          phone: details.phone || undefined, company: details.company || undefined }),
      });
      if (response.status === 201) {
        setBooked(bookingResponseSchema.parse(await response.json()));
        track("generate_lead", { form_type: "meeting", service: type.slug });
        return;
      }
      if (response.status === 409) {
        setSlotStart(null); refreshTimes(); setStep("time"); setError(t.taken);
        return;
      }
      setError(response.status === 503 ? t.unavailable : t.failed);
    } catch {
      setError(t.failed);
    } finally {
      setPending(false);
    }
  }

  function startOver() {
    setBooked(null); setDetails(null); setSlotStart(null); setDay(null); setPage(0);
    if (!single) { setTypeSlug(null); setFormat(null); }
    refreshTimes(); go(steps[0]);
  }

  const formatName = (f: MeetingFormat) => f === "online" ? t.online : t.inPerson;
  const canContinue = step === "service" ? !!type : step === "format" ? !!format : step === "time" ? !!slot : true;
  const when = slot ? `${dayLabel(dateKey(slot.start, zone), locale, "long")}, ${timeRange(slot.start, slot.end, zone, locale)}` : "";

  return <section className={base.contact} aria-labelledby={`${id}-title`}>
    <header className={base.intro}>
      {config.label && <p className={base.eyebrow}>{config.label}</p>}
      <h2 className={base.title} id={`${id}-title`}>{cms.title}</h2>
      {cms.subtitle && <p className={base.subtitle}>{cms.subtitle}</p>}
      {!booked && <ol className={styles.progress} aria-label={t.stepOf(stepIndex + 1, steps.length)}>
        {steps.map((s, i) => <li key={s} data-state={i < stepIndex ? "done" : i === stepIndex ? "current" : "next"} aria-current={i === stepIndex ? "step" : undefined}>
          <span>{t.steps[s]}</span>
          {i < stepIndex && <small>{s === "service" ? type?.name : s === "format" && format ? formatName(format) : s === "time" ? when : s === "details" ? details?.name : null}</small>}
        </li>)}
      </ol>}
    </header>

    <div className={base.form} aria-live="polite">
      {booked ? done(booked) : <>
        {step === "service" && panel(t.serviceTitle, <>
          <p className={styles.hint}>{t.serviceHint}</p>
          <div className={styles.services} role="radiogroup" aria-label={t.serviceTitle}>
            {types.map((option) => <label className={styles.service} key={option.slug}>
              <input type="radio" name={`${id}-type`} value={option.slug} checked={typeSlug === option.slug} onChange={() => chooseType(option.slug)} />
              <span>
                <strong>{option.name}</strong>
                {option.description && <span className={styles.muted}>{option.description}</span>}
                <span className={styles.meta}>{option.formats.map((f) => `${formatName(f.format)} · ${t.minutes(f.duration)}`).join("  /  ")}</span>
              </span>
            </label>)}
          </div>
        </>)}

        {step === "format" && type && panel(t.formatTitle, <>
          <div className={styles.cards} role="radiogroup" aria-label={t.formatTitle}>
            {formatsOf(type).map((option) => <label className={styles.card} key={option}>
              <input type="radio" name={`${id}-format`} value={option} checked={format === option} onChange={() => chooseFormat(option)} />
              <span>
                <span className={styles.icon} aria-hidden="true">{option === "online" ? <VideoIcon /> : <OfficeIcon />}</span>
                <strong>{option === "online" ? cms.online_title : cms.in_person_title}</strong>
                <span>{option === "online" ? cms.online_line : cms.in_person_line}</span>
                <span className={styles.muted}>{option === "online" ? cms.online_note : cms.in_person_note}</span>
                <span className={styles.meta}>{t.minutes(durationOf(type, option) ?? 0)}</span>
              </span>
            </label>)}
          </div>
        </>)}

        {step === "time" && panel(t.timeTitle, <>
          <div className={styles.zone}>
            <span>{t.timesIn(zone)}</span>
            {visitorZone && visitorZone !== config.timezone && <button type="button" className={styles.link} onClick={() => setBusinessTime((b) => !b)}>
              {businessTime ? t.showLocal : t.showBusiness(config.timezone)}
            </button>}
          </div>
          {!entry ? <p className={styles.status}>{t.loading}</p>
            : entry.status !== "ready" ? <p className={styles.status} role="alert">{entry.status === "unavailable" ? t.unavailable : t.slotsFailed} <button type="button" className={styles.link} onClick={refreshTimes}>{t.retry}</button></p>
            : <>
              {days.length ? <>
                <div className={styles.days} role="radiogroup" aria-label={t.timeTitle}>
                  {days.map((d) => <label className={styles.day} key={d.date}>
                    <input type="radio" name={`${id}-day`} checked={selectedDay?.date === d.date} onChange={() => setDay(d.date)} />
                    <span>{dayLabel(d.date, locale)}<small>{t.freeTimes(d.slots.length)}</small></span>
                  </label>)}
                </div>
                {selectedDay && <div className={base.choices} role="radiogroup" aria-label={dayLabel(selectedDay.date, locale, "long")}>
                  {selectedDay.slots.map((s) => <label className={base.choice} key={s.start}>
                    <input type="radio" name={`${id}-slot`} value={s.start} checked={slotStart === s.start} onChange={() => setSlotStart(s.start)} />
                    <span>{s.time}</span>
                  </label>)}
                </div>}
              </> : <p className={styles.status}>{t.noSlots}</p>}
              <div className={styles.pager}>
                {page > 0 && <button type="button" className={styles.link} onClick={() => setPage((p) => p - 1)}>← {t.earlier}</button>}
                <button type="button" className={styles.link} onClick={() => setPage((p) => p + 1)}>{t.later} →</button>
              </div>
            </>}
        </>)}

        {step === "details" && panel(t.detailsTitle, <>
          <form id={`${id}-details`} onSubmit={saveDetails}>
            <div className={base.fields}>
              <Field id={`${id}-name`} label={t.name}><input id={`${id}-name`} name="name" autoComplete="name" required maxLength={100} defaultValue={details?.name} placeholder={t.namePlaceholder} /></Field>
              <Field id={`${id}-email`} label={t.email}><input id={`${id}-email`} name="email" type="email" autoComplete="email" required maxLength={191} defaultValue={details?.email} placeholder={t.emailPlaceholder} /></Field>
              <Field id={`${id}-phone`} label={t.phone}><input id={`${id}-phone`} name="phone" type="tel" autoComplete="tel" maxLength={30} defaultValue={details?.phone} placeholder={t.optional} /></Field>
              <Field id={`${id}-company`} label={t.company}><input id={`${id}-company`} name="company" autoComplete="organization" maxLength={150} defaultValue={details?.company} placeholder={t.optional} /></Field>
              <Field id={`${id}-project`} label={t.project} wide><textarea id={`${id}-project`} name="projectDetails" required minLength={10} maxLength={5000} rows={5} defaultValue={details?.projectDetails} placeholder={t.projectPlaceholder} /></Field>
            </div>
            <div className={styles.trap} aria-hidden="true"><input name="website" tabIndex={-1} autoComplete="off" /></div>
            <label className={`${base.choice} ${styles.consent}`}>
              <input type="checkbox" name="consent" required defaultChecked={!!details} />
              <span>{cms.consent_text}</span>
            </label>
          </form>
        </>)}

        {step === "review" && type && format && slot && details && panel(t.reviewTitle, <>
          <dl className={styles.review}>
            {([
              ["service", t.service, type.name],
              ["format", t.format, `${formatName(format)} · ${t.minutes(durationOf(type, format) ?? 0)}`],
              ["time", t.when, `${when} (${zone})`],
              ["details", t.you, [details.name, details.email, details.company].filter(Boolean).join(" · ")],
            ] as const).map(([target, label, value]) => <div key={label}>
              <dt>{label}</dt>
              <dd>{value}</dd>
              {steps.includes(target) && <button type="button" className={styles.link} onClick={() => go(target)}>{t.edit}</button>}
            </div>)}
          </dl>
          <p className={styles.muted}>{format === "online" ? cms.online_note : cms.in_person_note}</p>
        </>)}

        <div className={`${base.actions} ${styles.nav}`}>
          {stepIndex > 0 && <button type="button" className={styles.back} onClick={backward} disabled={pending}>← {t.back}</button>}
          {step === "details"
            ? <button className={`button ${base.submit}`} type="submit" form={`${id}-details`}>{t.next}<span aria-hidden="true" data-arrow="next">→</span></button>
            : step === "review"
              ? <button className={`button ${base.submit}`} type="button" onClick={confirm} disabled={pending}>{pending ? t.sending : <>{cms.submit_label}<span aria-hidden="true">↗</span></>}</button>
              : <button className={`button ${base.submit}`} type="button" onClick={forward} disabled={!canContinue}>{t.next}<span aria-hidden="true" data-arrow="next">→</span></button>}
          {error && <p className={base.feedback} data-ok={false} role="alert"><span aria-hidden="true">!</span>{error}</p>}
        </div>
      </>}
    </div>
  </section>;

  function panel(title: string, children: ReactNode) {
    return <div className={styles.panel}>
      <p className={styles.counter}>{t.stepOf(stepIndex + 1, steps.length)}</p>
      <h3 className={styles.heading} ref={heading} tabIndex={-1}>{title}</h3>
      {children}
    </div>;
  }

  function done(result: BookingResult) {
    const location = result.location;
    const maps = location.kind === "in_person" ? safeUrl(location.maps_url) : null;
    return <div className={styles.panel}>
      <p className={base.feedback} data-ok={true} role="status"><span aria-hidden="true">✓</span>{result.status === "pending_approval" ? t.pending : cms.success_title}</p>
      <h3 className={styles.heading} ref={heading} tabIndex={-1}>{type?.name}</h3>
      <p>{dayLabel(dateKey(result.start, zone), locale, "long")}, {timeRange(result.start, result.end, zone, locale)} ({zone})</p>
      <p className={styles.muted}>{cms.success_message}</p>
      {location.kind === "online"
        ? <p>{location.link_status === "ready" ? t.linkReady : t.linkPending}</p>
        : <div className={styles.office}>
          {location.name && <strong>{location.name}</strong>}
          {location.address && <span>{location.address}</span>}
          {location.instructions.map((line) => <span className={styles.muted} key={line}>{line}</span>)}
          {maps && <a className={styles.link} href={maps} target="_blank" rel="noopener noreferrer">{t.directions} ↗</a>}
        </div>}
      <p className={styles.muted}>{t.reference}: {result.reference}</p>
      <button type="button" className={styles.link} onClick={startOver}>{t.another}</button>
    </div>;
  }
}

function Field({ id, label, wide = false, children }: { id: string; label: string; wide?: boolean; children: ReactNode }) {
  return <div className={`${base.field}${wide ? ` ${base.wide}` : ""}`}>
    <label className={base.label} htmlFor={id}>{label}</label>
    {children}
  </div>;
}

function VideoIcon() {
  return <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
    <rect x="3" y="6" width="13" height="12" rx="2.5" /><path d="m16 10.5 5-3v9l-5-3" />
  </svg>;
}

function OfficeIcon() {
  return <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
    <path d="M4 21V5.5A1.5 1.5 0 0 1 5.5 4h7A1.5 1.5 0 0 1 14 5.5V21" /><path d="M14 10h4.5A1.5 1.5 0 0 1 20 11.5V21" /><path d="M3 21h18M8 8h2M8 12h2M8 16h2M17 14h0M17 17h0" />
  </svg>;
}
