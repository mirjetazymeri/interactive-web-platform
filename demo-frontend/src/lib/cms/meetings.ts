import { z } from "zod";
import type { Config } from "./config";

/**
 * Meeting booking: the widget config the CMS sends, what the booking routes
 * accept, and the date/time helpers the booking flow uses. Shared by the
 * client component and the route handlers, so it stays free of server code.
 */

export const meetingFormats = ["online", "in_person"] as const;
export type MeetingFormat = typeof meetingFormats[number];

const text = z.string().trim();
const typeSchema = z.object({
  slug: z.string().min(1).max(100),
  name: text.min(1),
  description: text.nullish().transform((value) => value || null),
  default_format: z.enum(meetingFormats).nullish(),
  formats: z.array(z.object({ format: z.enum(meetingFormats), duration: z.number().int().positive() })).min(1),
});
export type MeetingTypeOption = z.infer<typeof typeSchema>;

/** Copy keys the CMS fills (with translated defaults when an editor leaves them empty). */
export const meetingCopyKeys = [
  "title", "subtitle", "online_title", "online_line", "online_note", "in_person_title", "in_person_line", "in_person_note",
  "consent_text", "submit_label", "success_title", "success_message",
] as const;
export type MeetingCopy = Record<typeof meetingCopyKeys[number], string>;

export type MeetingConfig = { label: string; copy: MeetingCopy; timezone: string; types: MeetingTypeOption[] };

/** Whether a string is an IANA timezone this runtime knows. */
export function isTimeZone(value: string) {
  try { new Intl.DateTimeFormat("en", { timeZone: value }); return value.length <= 64; } catch { return false; }
}

/**
 * The widget config as the booking flow needs it, or null when it can't be
 * used (no bookable types). Malformed types are dropped rather than failing
 * the whole widget; a type's default format is kept only if it is offered.
 */
export function normalizeMeetingConfig(config: Config): MeetingConfig | null {
  const types = (Array.isArray(config.types) ? config.types : [])
    .map((type) => typeSchema.safeParse(type))
    .flatMap((result) => result.success ? [result.data] : [])
    .map((type) => ({ ...type, default_format: type.formats.some((f) => f.format === type.default_format) ? type.default_format : type.formats[0].format }));
  const timezone = typeof config.timezone === "string" && isTimeZone(config.timezone) ? config.timezone : "UTC";
  if (!types.length) return null;
  const copy = Object.fromEntries(meetingCopyKeys.map((key) => [key, typeof config[key] === "string" ? (config[key] as string).trim() : ""])) as MeetingCopy;
  return { label: typeof config.section_label === "string" ? config.section_label : "", copy, timezone, types };
}

/** The formats a visitor chooses from; one format means the format step is skipped. */
export const formatsOf = (type: MeetingTypeOption) => type.formats.map((f) => f.format);
export const durationOf = (type: MeetingTypeOption, format: MeetingFormat) => type.formats.find((f) => f.format === format)?.duration ?? null;

/* ──────────────── Route input ─────────────────────────────────────── */

const target = { locale: z.string().min(2).max(30), path: z.string().min(1).max(1024), widgetId: z.coerce.number().int().positive() };
const isoInstant = z.string().max(40).refine((value) => !Number.isNaN(Date.parse(value)), "Invalid date.");

/** Longest range one availability request may cover (the CMS allows 31 days). */
export const maxRangeDays = 31;

export const availabilityQuerySchema = z.object({
  ...target,
  type: z.string().min(1).max(100),
  format: z.enum(meetingFormats),
  from: isoInstant,
  to: isoInstant,
}).refine((q) => Date.parse(q.to) > Date.parse(q.from) && Date.parse(q.to) - Date.parse(q.from) <= maxRangeDays * 86_400_000, "Invalid range.");
export type AvailabilityQuery = z.infer<typeof availabilityQuerySchema>;

export const bookingInputSchema = z.object({
  ...target,
  type: z.string().min(1).max(100),
  format: z.enum(meetingFormats),
  start: isoInstant,
  visitorTimezone: z.string().refine(isTimeZone, "Unknown timezone."),
  name: text.min(1).max(100),
  email: z.email().max(191),
  phone: text.max(30).optional(),
  company: text.max(150).optional(),
  projectDetails: text.min(10).max(5000),
  consent: z.literal(true),
  // Honeypot: hidden from people, so anything in it means a bot.
  website: z.string().max(0).optional(),
});
export type BookingInput = z.infer<typeof bookingInputSchema>;

/** The CMS booking payload for a validated input. */
export function bookingPayload(input: BookingInput) {
  return {
    type: input.type, format: input.format, start: new Date(input.start).toISOString(),
    visitor_timezone: input.visitorTimezone, locale: input.locale,
    name: input.name, email: input.email, phone: input.phone || undefined, company: input.company || undefined,
    project_details: input.projectDetails, consent: true,
  };
}

/** Whether the widget offers this type in this format. */
export function offers(config: MeetingConfig, type: string, format: MeetingFormat) {
  return config.types.some((option) => option.slug === type && formatsOf(option).includes(format));
}

/* ──────────────── CMS responses ───────────────────────────────────── */

export const slotsResponseSchema = z.object({
  timezone: z.string(),
  slots: z.array(z.object({ start: isoInstant, end: isoInstant })),
});
export type Slot = z.infer<typeof slotsResponseSchema>["slots"][number];

export const bookingResponseSchema = z.object({
  reference: z.string().min(1),
  status: z.enum(["confirmed", "pending_approval"]),
  format: z.enum(meetingFormats),
  start: isoInstant,
  end: isoInstant,
  // Online: never a URL here, only whether it is ready. In person: the office.
  location: z.discriminatedUnion("kind", [
    z.object({ kind: z.literal("online"), link_status: z.enum(["ready", "pending"]) }),
    z.object({ kind: z.literal("in_person"), name: z.string().nullish(), address: z.string().nullish(), maps_url: z.string().nullish(), instructions: z.array(z.string()).default([]) }),
  ]),
});
export type BookingResult = z.infer<typeof bookingResponseSchema>;

/** Stable codes the booking flow reacts to. */
export type BookingErrorCode = "slot_taken" | "calendar_unavailable" | "invalid" | "rate_limited" | "failed";

/** Map a CMS HTTP status to the route's status and error code. */
export function bookingError(status: number | undefined): { status: number; code: BookingErrorCode } {
  if (status === 409) return { status: 409, code: "slot_taken" };
  if (status === 503) return { status: 503, code: "calendar_unavailable" };
  if (status === 422) return { status: 422, code: "invalid" };
  if (status === 429) return { status: 429, code: "rate_limited" };
  return { status: 502, code: "failed" };
}

/* ──────────────── Dates and times ─────────────────────────────────── */

/** "YYYY-MM-DD" of an instant in a timezone. */
export function dateKey(instant: string | Date, timeZone: string) {
  return new Intl.DateTimeFormat("en-CA", { timeZone, year: "numeric", month: "2-digit", day: "2-digit" }).format(new Date(instant));
}

export type SlotDay = { date: string; slots: { start: string; end: string; time: string }[] };

/** Slots grouped by the day they fall on in $timeZone, in time order, with display times. */
export function groupSlots(slots: Slot[], timeZone: string, locale: string): SlotDay[] {
  const time = new Intl.DateTimeFormat(locale, { timeZone, hour: "2-digit", minute: "2-digit" });
  const days = new Map<string, SlotDay>();
  for (const slot of [...slots].sort((a, b) => Date.parse(a.start) - Date.parse(b.start))) {
    const date = dateKey(slot.start, timeZone);
    const day = days.get(date) ?? { date, slots: [] };
    day.slots.push({ ...slot, time: time.format(new Date(slot.start)) });
    days.set(date, day);
  }
  return [...days.values()];
}

/** A day label like "Mon 2 Nov" for a "YYYY-MM-DD" key (calendar date, so no timezone shift). */
export function dayLabel(date: string, locale: string, style: "short" | "long" = "short") {
  return new Intl.DateTimeFormat(locale, style === "short"
    ? { weekday: "short", day: "numeric", month: "short", timeZone: "UTC" }
    : { weekday: "long", day: "numeric", month: "long", year: "numeric", timeZone: "UTC" }).format(new Date(`${date}T12:00:00Z`));
}

/** "09:00–09:30" for an instant range in a timezone. */
export function timeRange(start: string, end: string, timeZone: string, locale: string) {
  const time = new Intl.DateTimeFormat(locale, { timeZone, hour: "2-digit", minute: "2-digit" });
  return `${time.format(new Date(start))}–${time.format(new Date(end))}`;
}

/** The availability window to request: [from, from + days), starting no earlier than now. */
export function rangeFrom(start: Date, days = 14) {
  return { from: start.toISOString(), to: new Date(start.getTime() + days * 86_400_000).toISOString() };
}
