import { z } from "zod";
import { bool, str } from "./config";

/**
 * Services a visitor can pick on the contact form. The form sends the stable
 * keys; the server maps them to the English names used in the email subject,
 * so the subject is the same in every language and never contains free text
 * from the picker.
 */
export const contactServices = [
  { key: "development", subject: "Web & App Development", en: "Web & app development", sq: "Zhvillim web dhe aplikacione" },
  { key: "it-cloud", subject: "IT & Cloud", en: "IT & cloud", sq: "IT dhe Cloud" },
  { key: "communication", subject: "Communication", en: "Communication", sq: "Komunikim" },
  { key: "digital-growth", subject: "Digital Growth", en: "Digital growth", sq: "Rritje digjitale" },
  { key: "other", subject: "Other", en: "Something else", sq: "Diçka tjetër" },
] as const;

export type ContactServiceKey = typeof contactServices[number]["key"];
export const contactServiceKeys = contactServices.map((service) => service.key) as [ContactServiceKey, ...ContactServiceKey[]];

/** The picker's options in the visitor's language. */
export function contactServiceOptions(locale: string) {
  return contactServices.map((service) => ({ key: service.key, label: locale === "sq" ? service.sq : service.en }));
}

/** The email subject limit enforced by the CMS contact endpoint. */
export const subjectLimit = 255;

/**
 * The email subject: the base subject followed by the picked services in the
 * list's order, e.g. "Website Contact Inquiry — Web & App Development, IT & Cloud".
 * Unknown or repeated keys are ignored; the result never exceeds the CMS limit.
 */
export function contactSubject(base: string, picked: readonly string[] = []) {
  const chosen = contactServices.filter((service) => picked.includes(service.key)).map((service) => service.subject);
  const head = base.trim();
  const subject = !chosen.length ? head : head ? `${head} — ${chosen.join(", ")}` : chosen.join(", ");
  return subject.length <= subjectLimit ? subject : `${subject.slice(0, subjectLimit - 1).trimEnd()}…`;
}

/** What the contact route accepts from the form (re-validated server-side). */
export const contactInputSchema = z.object({
  locale: z.string().max(30), path: z.string().min(1).max(1024), widgetId: z.number().int().positive(),
  name: z.string().trim().min(1).max(100), email: z.email().max(191),
  message: z.string().trim().min(10).max(5000), phone: z.string().trim().max(30).optional(), subject: z.string().trim().max(255).optional(),
  // Only known service keys; anything else is rejected rather than reaching the email subject.
  services: z.array(z.enum(contactServiceKeys)).max(contactServiceKeys.length).optional(),
});
export type ContactInput = z.infer<typeof contactInputSchema>;

/**
 * The CMS contact payload for a validated input and its widget's config, or
 * null when no subject is available. The widget decides between the visitor's
 * subject and its fixed one, and whether a phone number is passed on.
 */
export function contactPayload(input: ContactInput, config: Record<string, unknown>) {
  const base = bool(config, "show_subject") ? input.subject : str(config, "fixed_subject");
  const subject = base ? contactSubject(base, input.services) : "";
  if (!subject) return null;
  return { name: input.name, email: input.email, message: input.message, subject, ...(bool(config, "show_phone") ? { phone: input.phone } : {}) };
}
