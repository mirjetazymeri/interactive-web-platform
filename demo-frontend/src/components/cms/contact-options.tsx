"use client";

import { useEffect, useId, useRef, useState, useSyncExternalStore, type KeyboardEvent, type ReactNode } from "react";
import { contactChoiceIds, type ContactChoice } from "@/lib/cms/contact-options";
import type { MeetingFormat } from "@/lib/cms/meetings";
import styles from "./contact-options.module.css";

type Card = { title: string };
type Props = {
  locale: string;
  order: [ContactChoice, ContactChoice];
  cards: Record<ContactChoice, Card>;
  reach: "both" | MeetingFormat;
  panels: Record<ContactChoice, ReactNode>;
};

/** Interface copy (English and Albanian, like the forms); card titles come from the CMS widgets. */
function copy(sq: boolean) {
  return sq ? {
    heading: "Si dëshironi të na kontaktoni?", hint: "Zgjidhni një",
    bestFor: { message: "Për pyetje, oferta dhe përshkrime projektesh", booking: "Për ta diskutuar drejtpërdrejt me ekipin tonë" } as Record<ContactChoice, string>,
    message: "Përgjigje me shkrim me email",
    booking: { both: "Thirrje video ose në zyrën tonë · zgjidhni orën", online: "Thirrje video · zgjidhni orën", in_person: "Në zyrën tonë · zgjidhni orën" },
  } : {
    heading: "How would you like to reach us?", hint: "Choose one",
    bestFor: { message: "Questions, quotes and project briefs", booking: "Talking it through live with our team" } as Record<ContactChoice, string>,
    message: "Written reply by email",
    booking: { both: "Video call or at our office · pick a time", online: "Video call · pick a time", in_person: "At our office · pick a time" },
  };
}

/** The choice linked in the URL hash (#message, #book-a-meeting), if any. */
const subscribeHash = (onChange: () => void) => { window.addEventListener("hashchange", onChange); return () => window.removeEventListener("hashchange", onChange); };
const hashChoice = (): ContactChoice | null =>
  (Object.entries(contactChoiceIds).find(([, id]) => `#${id}` === window.location.hash)?.[0] as ContactChoice | undefined) ?? null;

/**
 * "How would you like to reach us?": two cards (send a message / book a
 * meeting) acting as tabs over the two widgets. Both panels stay mounted, so
 * switching never loses what a visitor typed; the choice is kept in the URL hash.
 */
export function ContactOptions({ locale, order, cards, reach, panels }: Props) {
  const t = copy(locale === "sq");
  const id = useId();
  const linked = useSyncExternalStore(subscribeHash, hashChoice, () => null);
  const [chosen, setChosen] = useState<ContactChoice | null>(null);
  const active = chosen ?? linked ?? order[0];
  // A followed link (#message, #book-a-meeting) wins over an earlier click.
  useEffect(() => subscribeHash(() => setChosen(null)), []);
  const tabs = useRef<Record<ContactChoice, HTMLButtonElement | null>>({ message: null, booking: null });

  function choose(choice: ContactChoice, focus = false) {
    setChosen(choice);
    // Shareable without scrolling the page or adding history entries.
    window.history.replaceState(null, "", `#${contactChoiceIds[choice]}`);
    if (focus) tabs.current[choice]?.focus();
  }
  /** On phones the cards fill the screen, so show the form that just opened (after it has rendered). */
  function reveal(choice: ContactChoice) {
    if (!window.matchMedia("(max-width: 767px)").matches) return;
    const behavior = window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth";
    requestAnimationFrame(() => requestAnimationFrame(() => document.getElementById(`${id}-panel-${choice}`)?.scrollIntoView({ behavior, block: "start" })));
  }
  function onKeyDown(event: KeyboardEvent<HTMLButtonElement>) {
    if (!["ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown", "Home", "End"].includes(event.key)) return;
    event.preventDefault();
    const index = order.indexOf(active);
    const next = event.key === "Home" ? order[0] : event.key === "End" ? order[1] : order[(index + 1) % 2];
    choose(next, true);
  }

  const cue = (choice: ContactChoice) => choice === "message" ? t.message : t.booking[reach];

  return <>
    <div className={`container ${styles.chooser}`}>
      {/* Link targets (#message, #book-a-meeting) land on the cards, so both options are in view. */}
      {order.map((choice) => <span key={choice} id={contactChoiceIds[choice]} className={styles.anchor} aria-hidden="true" />)}
      <div className={styles.head}>
        <h2 className={styles.heading} id={`${id}-heading`}>{t.heading}</h2>
        <span className={styles.hint}>{t.hint}</span>
      </div>
      <div className={styles.cards} role="tablist" aria-labelledby={`${id}-heading`}>
        {order.map((choice) => <button
          key={choice}
          ref={(node) => { tabs.current[choice] = node; }}
          type="button"
          role="tab"
          id={`${id}-tab-${choice}`}
          aria-selected={active === choice}
          aria-controls={`${id}-panel-${choice}`}
          tabIndex={active === choice ? 0 : -1}
          className={styles.card}
          onClick={() => { choose(choice); reveal(choice); }}
          onKeyDown={onKeyDown}
        >
          <span className={styles.icon} aria-hidden="true">{choice === "message" ? <MessageIcon /> : <CalendarIcon />}</span>
          <span className={styles.text}>
            <strong>{cards[choice].title}</strong>
            <span className={styles.description}>{t.bestFor[choice]}</span>
            <span className={styles.cue}>{cue(choice)}</span>
          </span>
          <span className={styles.mark} aria-hidden="true" />
        </button>)}
      </div>
    </div>
    {order.map((choice) => <div key={choice} id={`${id}-panel-${choice}`} role="tabpanel" aria-labelledby={`${id}-tab-${choice}`} hidden={active !== choice} className={styles.panel}>
      {panels[choice]}
    </div>)}
  </>;
}

function MessageIcon() {
  return <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
    <rect x="3" y="5" width="18" height="14" rx="2.5" /><path d="m4 7 8 6 8-6" />
  </svg>;
}

function CalendarIcon() {
  return <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
    <rect x="3" y="5" width="18" height="16" rx="2.5" /><path d="M3 10h18M8 3v4M16 3v4M8 14h2M14 14h2M8 17h2" />
  </svg>;
}
