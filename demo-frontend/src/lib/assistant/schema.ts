import { z } from "zod";

/**
 * Contracts for the Laravel Smart Assistant API (/api/v1/assistant/*).
 * Answers are plain text written by admins or taken from CMS fields; every
 * link is re-checked here before it can reach the page.
 */

export const MAX_MESSAGE_LENGTH = 300;
const ENTITY = /^(service|subservice|project):[A-Za-z0-9_:/-]{1,180}$/;
const LOCALE = /^[a-z]{2,3}(?:-[A-Za-z0-9]+)?$/;

const suggestionSchema = z.object({ id: z.number().int().positive(), question: z.string().min(1).max(300) });
export type AssistantSuggestion = z.infer<typeof suggestionSchema>;

export const bootstrapSchema = z.union([
  z.object({
    enabled: z.literal(true),
    preview: z.boolean().optional().default(false),
    show_on_mobile: z.boolean(),
    name: z.string().max(100),
    welcome_message: z.string().max(1000),
    tooltip: z.string().max(160),
    placeholder: z.string().max(160),
    notice: z.string().max(300),
    suggestions: z.array(suggestionSchema).max(12),
    max_length: z.number().int().min(1).max(1000),
  }),
  z.object({ enabled: z.literal(false) }),
]);
export type AssistantBootstrap = Extract<z.infer<typeof bootstrapSchema>, { enabled: true }>;

const LANGUAGE = /^[a-z]{2,3}$/;
/** Request types the engine carries from one question to the next ("what about mobile?"). */
const ACTS = ["pricing", "timeline", "contact", "similar", "projects", "information", "capability"] as const;

export const contextSchema = z.object({
  intent_id: z.number().int().positive().nullable(),
  entity: z.string().regex(ENTITY).nullable(),
  turn: z.number().int().min(0).max(100),
  // The conversation's language (it follows the visitor's questions, not the page) and the last request type.
  language: z.string().regex(LANGUAGE).nullish(),
  act: z.enum(ACTS).nullish(),
  // Services under discussion together ("a website and SEO") and services the visitor ruled out ("not marketing").
  entities: z.array(z.string().regex(ENTITY)).max(5).nullish(),
  excluded: z.array(z.string().regex(ENTITY)).max(5).nullish(),
});
export type AssistantContext = z.infer<typeof contextSchema>;

/** A site path in the visitor's language ("/en/projects", "/sq/services#service-x-18"), never another origin. */
export function isLocalPath(href: string, locale: string) {
  const match = /^\/([a-z]{2,3}(?:-[A-Za-z0-9]+)?)((?:\/[A-Za-z0-9_-]+)*)(#[A-Za-z][A-Za-z0-9_-]{0,99})?$/.exec(href);
  return !!match && match[1] === locale;
}

function safeWeb(value: unknown) {
  if (typeof value !== "string") return null;
  try {
    const url = new URL(value);
    return ["http:", "https:"].includes(url.protocol) && !url.username && !url.password ? url.href : null;
  } catch { return null; }
}

export type AssistantAction =
  | { type: "page"; label: string; href: string }
  | { type: "email" | "phone"; label: string; href: string };
export type AssistantCard =
  | { type: "service"; title: string; subtitle: string | null; text: string; href: string | null }
  | { type: "project"; title: string; subtitle: string | null; text: string; image: string | null; href: string | null }
  | { type: "contact"; email: string | null; phone: string | null; address: string[]; hours: string | null; mapsUrl: string | null };

const text = (max: number) => z.string().max(max);
const optionalText = (max: number) => z.string().max(max).nullish().transform((v) => v || null);

/** Keeps only actions with a safe destination; anything else is dropped rather than failing the answer. */
export function cleanActions(raw: unknown, locale: string): AssistantAction[] {
  if (!Array.isArray(raw)) return [];
  const out: AssistantAction[] = [];
  for (const item of raw.slice(0, 8)) {
    const parsed = z.object({ type: z.enum(["page", "email", "phone"]), label: text(120).min(1), href: text(300) }).safeParse(item);
    if (!parsed.success) continue;
    const { type, label, href } = parsed.data;
    const ok = type === "page" ? isLocalPath(href, locale)
      : type === "email" ? /^mailto:[^\s<>"@]+@[^\s<>"@]+\.[^\s<>"@]+$/.test(href)
      : /^tel:\+?\d{5,20}$/.test(href);
    // One button per destination: the first (most specific) wins, so labels never repeat a link.
    if (ok && !out.some((action) => action.href === href)) out.push({ type, label, href });
  }
  return out;
}

export function cleanCards(raw: unknown, locale: string): AssistantCard[] {
  if (!Array.isArray(raw)) return [];
  const href = (value: unknown) => typeof value === "string" && isLocalPath(value, locale) ? value : null;
  const out: AssistantCard[] = [];
  for (const item of raw.slice(0, 8)) {
    const type = (item as { type?: unknown } | null)?.type;
    if (type === "service") {
      const p = z.object({ title: text(200).min(1), subtitle: optionalText(200), text: text(1000).catch("") }).safeParse(item);
      if (p.success) out.push({ type, ...p.data, href: href((item as { href?: unknown }).href) });
    } else if (type === "project") {
      const p = z.object({ title: text(200).min(1), subtitle: optionalText(200), text: text(1000).catch("") }).safeParse(item);
      if (p.success) out.push({ type, ...p.data, image: safeWeb((item as { image?: unknown }).image), href: href((item as { href?: unknown }).href) });
    } else if (type === "contact") {
      const p = z.object({
        email: optionalText(200), phone: optionalText(40), hours: optionalText(200),
        address: z.array(text(200)).max(6).catch([]).optional().transform((v) => v ?? []),
      }).safeParse(item);
      if (p.success) out.push({ type, ...p.data, email: p.data.email && /^[^\s<>"@]+@[^\s<>"@]+$/.test(p.data.email) ? p.data.email : null,
        phone: p.data.phone && /^\+?[0-9 ().-]{5,30}$/.test(p.data.phone) ? p.data.phone : null, mapsUrl: safeWeb((item as { maps_url?: unknown; mapsUrl?: unknown }).maps_url ?? (item as { mapsUrl?: unknown }).mapsUrl) });
    }
  }
  // The same card twice is shown once.
  return out.filter((card, index) => out.findIndex((other) => cardKey(other) === cardKey(card)) === index);
}

/** A card's identity: what it shows and where it links. Several services on one page stay distinct cards. */
export function cardKey(card: AssistantCard): string {
  return card.type === "contact" ? "contact" : `${card.type}:${card.title}:${card.href ?? ""}`;
}

/** An action's identity: its destination (actions are unique by destination after cleanActions). */
export function actionKey(action: AssistantAction): string {
  return `${action.type}:${action.href}`;
}

export const replyStatusSchema = z.enum(["matched", "ambiguous", "fallback"]);

/** The Laravel reply, validated and with links and cards made safe for this locale. */
export function replySchema(locale: string) {
  return z.object({
    status: replyStatusSchema,
    answer: z.string().max(4000),
    intent: z.object({ id: z.number().int(), question: z.string() }).nullable(),
    actions: z.unknown().transform((raw) => cleanActions(raw, locale)),
    cards: z.unknown().transform((raw) => cleanCards(raw, locale)),
    suggestions: z.array(suggestionSchema).max(12).catch([]).transform((list) => list.filter((item, index) => list.findIndex((other) => other.id === item.id) === index)),
    context: contextSchema,
    // How many cards the full list has (list answers show a few first).
    total: z.number().int().min(0).max(1000).optional(),
    // The answer's language, which may differ from the page's when the visitor asked in another one.
    language: z.string().regex(LANGUAGE).optional().catch(undefined),
  });
}
export type AssistantReply = z.infer<ReturnType<typeof replySchema>>;

/** What the browser sends to the Next.js route. */
export const messageInputSchema = z.object({
  locale: z.string().regex(LOCALE),
  message: z.string().trim().max(MAX_MESSAGE_LENGTH).optional(),
  intentId: z.number().int().positive().optional(),
  context: contextSchema.optional(),
}).strict().refine((v) => !!v.message || v.intentId !== undefined, "A message or a suggestion is required.");
export type AssistantMessageInput = z.infer<typeof messageInputSchema>;

/** Error codes the route returns; the panel turns them into friendly text. */
export type AssistantErrorCode = "invalid" | "unavailable" | "busy" | "failed";
export function errorFor(status: number | undefined): { status: number; code: AssistantErrorCode } {
  if (status === 404) return { status: 503, code: "unavailable" };
  if (status === 422) return { status: 422, code: "invalid" };
  if (status === 429) return { status: 429, code: "busy" };
  return { status: 502, code: "failed" };
}
