"use client";

import { useEffect, useId, useRef, useState, type FormEvent, type ReactNode } from "react";
import { contactServiceOptions } from "@/lib/cms/contact-services";
import { track } from "@/lib/tracking/events";
import styles from "./contact-form.module.css";

export type ContactProps = { locale: string; path: string; widgetId: number; label?: string; title: string; subtitle: string; showPhone: boolean; showSubject: boolean; submitLabel: string; successMessage: string };

/** Interface copy for the contact experience (English and Albanian, like the rest of the site chrome). */
function copy(sq: boolean) {
  return sq ? {
    interests: "Me çfarë mund t’ju ndihmojmë?", interestsHint: "Zgjidhni një ose më shumë.",
    details: "Të dhënat tuaja", name: "Emri", email: "Email", phone: "Telefoni", subject: "Subjekti", message: "Mesazhi",
    namePlaceholder: "Emri dhe mbiemri", emailPlaceholder: "emri@kompania.com", phonePlaceholder: "Opsionale", subjectPlaceholder: "Për çfarë bëhet fjalë?",
    messagePlaceholder: "Na tregoni për projektin, qëllimet dhe afatin kohor.",
    about: (topic: string) => `Dëshiroj të di më shumë për: ${topic}.\n\n`,
    steps: ["Na tregoni çfarë keni në mendje", "E shqyrtojmë dhe ju kthejmë përgjigje", "Planifikojmë së bashku hapin e ardhshëm"], stepsTitle: "Si funksionon",
    sending: "Duke dërguar…", sent: "Mesazhi u dërgua.", failed: "Mesazhi nuk u dërgua. Kontrolloni të dhënat dhe provoni përsëri më vonë.",
  } : {
    interests: "What can we help with?", interestsHint: "Choose one or more.",
    details: "Your details", name: "Name", email: "Email", phone: "Phone", subject: "Subject", message: "Message",
    namePlaceholder: "Your full name", emailPlaceholder: "name@company.com", phonePlaceholder: "Optional", subjectPlaceholder: "What is it about?",
    messagePlaceholder: "Tell us about your project, goals and timeline.",
    about: (topic: string) => `I would like to know more about: ${topic}.\n\n`,
    steps: ["Tell us what you have in mind", "We review it and get back to you", "We plan the next step together"], stepsTitle: "How it works",
    sending: "Sending…", sent: "Your message has been sent.", failed: "Your message could not be sent. Check the fields and try again later.",
  };
}

/**
 * Contact widget: an intro column with guidance beside a clean form. Picked
 * services travel as keys; /api/contact re-validates everything against the CMS
 * widget and adds the services to the email subject.
 */
export function ContactForm(props: ContactProps) {
  const id = useId();
  const [pending, setPending] = useState(false);
  const [result, setResult] = useState<{ ok: boolean; message: string } | null>(null);
  const t = copy(props.locale === "sq");
  const formRef = useRef<HTMLFormElement>(null);
  // Service pages link here with ?topic=…; start the message (and subject) with it so the request arrives in context.
  useEffect(() => {
    const topic = new URLSearchParams(window.location.search).get("topic")?.trim().slice(0, 200);
    const fields = formRef.current?.elements;
    if (!topic || !fields) return;
    const message = fields.namedItem("message");
    const subject = fields.namedItem("subject");
    if (message instanceof HTMLTextAreaElement && !message.value) message.value = copy(props.locale === "sq").about(topic);
    if (subject instanceof HTMLInputElement && !subject.value) subject.value = topic;
  }, [props.locale]);
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = event.currentTarget;
    setPending(true); setResult(null);
    try {
      const data = new FormData(form);
      // Every picked service (a checkbox group), not just the last one.
      const services = data.getAll("services").map(String);
      data.delete("services");
      const values = Object.fromEntries(data);
      const response = await fetch("/api/contact", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ ...values, services, locale: props.locale, path: props.path, widgetId: props.widgetId }) });
      if (!response.ok) throw new Error();
      // A conversion, described only by the chosen service keys; nothing the visitor typed is sent.
      track("generate_lead", { form_type: "contact", services: services.join(",") });
      setResult({ ok: true, message: props.successMessage || t.sent });
      form.reset();
    } catch { setResult({ ok: false, message: t.failed }); }
    finally { setPending(false); }
  }
  const field = (name: string, label: string, input: ReactNode, wide = false) =>
    <div className={`${styles.field}${wide ? ` ${styles.wide}` : ""}`}>
      <label className={styles.label} htmlFor={`${id}-${name}`}>{label}</label>
      {input}
    </div>;
  return <section className={styles.contact} aria-labelledby={`${id}-title`}>
    <header className={styles.intro}>
      {props.label && <p className={styles.eyebrow}>{props.label}</p>}
      {props.title && <h2 className={styles.title} id={`${id}-title`}>{props.title}</h2>}
      {props.subtitle && <p className={styles.subtitle}>{props.subtitle}</p>}
      <div className={styles.steps}>
        <p className={styles.stepsTitle}>{t.stepsTitle}</p>
        <ol>{t.steps.map((step) => <li key={step}>{step}</li>)}</ol>
      </div>
    </header>
    <form ref={formRef} className={styles.form} onSubmit={submit} aria-busy={pending}>
      <fieldset className={styles.group}>
        <legend className={styles.groupTitle}>{t.interests}<span>{t.interestsHint}</span></legend>
        <div className={styles.choices}>
          {contactServiceOptions(props.locale).map((service) => <label className={styles.choice} key={service.key}>
            <input type="checkbox" name="services" value={service.key} />
            <span>{service.label}</span>
          </label>)}
        </div>
      </fieldset>
      <fieldset className={styles.group}>
        <legend className={styles.groupTitle}>{t.details}</legend>
        <div className={styles.fields}>
          {field("name", t.name, <input id={`${id}-name`} name="name" autoComplete="name" required maxLength={100} placeholder={t.namePlaceholder} />)}
          {field("email", t.email, <input id={`${id}-email`} name="email" type="email" autoComplete="email" required maxLength={191} placeholder={t.emailPlaceholder} />)}
          {props.showPhone && field("phone", t.phone, <input id={`${id}-phone`} name="phone" type="tel" autoComplete="tel" maxLength={30} placeholder={t.phonePlaceholder} />)}
          {props.showSubject && field("subject", t.subject, <input id={`${id}-subject`} name="subject" required maxLength={255} placeholder={t.subjectPlaceholder} />)}
          {field("message", t.message, <textarea id={`${id}-message`} name="message" required minLength={10} maxLength={5000} rows={5} placeholder={t.messagePlaceholder} />, true)}
        </div>
      </fieldset>
      <div className={styles.actions}>
        <button className={`button ${styles.submit}`} type="submit" disabled={pending}>
          {pending ? t.sending : <>{props.submitLabel}<span aria-hidden="true">↗</span></>}
        </button>
        {result && <p className={styles.feedback} data-ok={result.ok} role={result.ok ? "status" : "alert"}><span aria-hidden="true">{result.ok ? "✓" : "!"}</span>{result.message}</p>}
      </div>
    </form>
  </section>;
}
