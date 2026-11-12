/**
 * The site's single event layer. Components call track(); the tracking
 * provider decides whether and where events go (GTM dataLayer, GA4, Meta
 * Pixel). Only an allowlist of non-personal parameters is ever sent: no
 * names, email addresses, phone numbers or message text.
 */

export type TrackingEvent =
  | "generate_lead" | "contact_click" | "service_inquiry" | "view_project"
  | "email_click" | "phone_click" | "whatsapp_click";

export type EventParams = Record<string, string | number | boolean | undefined>;

const allowedParams = new Set(["form_type", "services", "link_area", "service", "destination", "project_slug", "project_name", "project_category", "page_locale"]);
/** An email address, or something shaped like a phone number (mostly digits, seven or more). */
const personal = (text: string) => {
  if (/\S@\S/.test(text)) return true;
  const digits = text.replace(/\D/g, "").length;
  return digits >= 7 && digits / text.replace(/\s/g, "").length >= 0.7;
};

/** Allowlisted, short, primitive parameters; anything that looks like an email address or phone number is dropped. */
export function sanitizeParams(params: EventParams = {}): Record<string, string | number | boolean> {
  return Object.fromEntries(Object.entries(params).flatMap(([key, value]): [string, string | number | boolean][] => {
    if (!allowedParams.has(key) || value === undefined || value === "") return [];
    if (typeof value === "string") {
      const text = value.replace(/\s+/g, " ").trim().slice(0, 100);
      return text && !personal(text) ? [[key, text]] : [];
    }
    return typeof value === "number" || typeof value === "boolean" ? [[key, value]] : [];
  }));
}

/** Meta Pixel equivalents: standard events where one fits, custom events otherwise. */
export function metaPixelEvent(name: TrackingEvent, params: Record<string, string | number | boolean>): { kind: "track" | "trackCustom"; name: string; params?: Record<string, unknown> } {
  switch (name) {
    case "generate_lead": return params.form_type === "meeting" ? { kind: "track", name: "Schedule" } : { kind: "track", name: "Lead", params: { content_category: params.form_type } };
    case "email_click": case "phone_click": case "whatsapp_click": return { kind: "track", name: "Contact" };
    case "view_project": return { kind: "track", name: "ViewContent", params: { content_name: params.project_name, content_category: params.project_category, content_type: "project" } };
    case "service_inquiry": return { kind: "trackCustom", name: "ServiceInquiry", params: { service: params.service } };
    case "contact_click": return { kind: "trackCustom", name: "ContactClick", params: { destination: params.destination } };
  }
}

/**
 * The event a link click stands for, from its href: email, phone and WhatsApp
 * links, the contact and meeting pages, and service inquiries (contact links
 * carrying ?topic=). Other links send nothing.
 */
export function linkEvent(href: string | null, { origin, locale, area }: { origin: string; locale: string; area: string }): { name: TrackingEvent; params: EventParams } | null {
  if (!href) return null;
  const value = href.trim();
  if (/^mailto:/i.test(value)) return { name: "email_click", params: { link_area: area } };
  if (/^tel:/i.test(value)) return { name: "phone_click", params: { link_area: area } };
  let url: URL;
  try { url = new URL(value, origin); } catch { return null; }
  if (url.protocol === "whatsapp:" || /^(?:wa\.me|(?:api|web|chat)\.whatsapp\.com)$/i.test(url.hostname)) return { name: "whatsapp_click", params: { link_area: area } };
  if (url.origin !== origin) return null;
  const path = url.pathname.replace(/\/+$/, "");
  const prefix = `/${encodeURIComponent(locale)}`;
  if (path === `${prefix}/contact`) {
    const topic = url.searchParams.get("topic")?.trim();
    return topic ? { name: "service_inquiry", params: { service: topic, link_area: area } } : { name: "contact_click", params: { destination: "contact", link_area: area } };
  }
  if (path === `${prefix}/book-a-meeting`) return { name: "contact_click", params: { destination: "meeting", link_area: area } };
  return null;
}

type Sink = (name: TrackingEvent, params: Record<string, string | number | boolean>) => void;
let sink: Sink | null = null;
let pending: { name: TrackingEvent; params: Record<string, string | number | boolean> }[] = [];

/**
 * Record a real user action. Before the visitor has decided about cookies,
 * events wait (at most 20, for this page view only) and are sent if they accept.
 */
export function track(name: TrackingEvent, params?: EventParams) {
  const clean = sanitizeParams(params);
  if (sink) sink(name, clean);
  else if (pending.length < 20) pending.push({ name, params: clean });
}

/** Called by the tracking provider: a sink once sending is allowed, or null to drop what is waiting. */
export function setTrackingSink(next: Sink | null, { flush }: { flush: boolean }) {
  sink = next;
  const waiting = pending;
  pending = [];
  if (next && flush) for (const event of waiting) next(event.name, event.params);
}
