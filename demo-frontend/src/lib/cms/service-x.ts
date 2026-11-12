import { z } from "zod";
import type { Widget } from "./schema";

// The API resolves translations, visibility and ordering. Never sort or filter here.
const subserviceSchema = z.object({
  id: z.string(), title: z.string(), description: z.string().default(""),
  position: z.number().optional(), active: z.boolean().optional(),
});
const serviceSchema = z.object({
  id: z.string(), name: z.string(), title: z.string(), subtitle: z.string().default(""),
  image: z.string().nullable().default(null), image_alt: z.string().default(""),
  position: z.number().optional(), active: z.boolean().optional(),
  subservices: z.array(subserviceSchema).default([]),
});
export const serviceXConfigSchema = z.object({
  section_label: z.string().default(""), section_index: z.string().default(""),
  services: z.array(serviceSchema).default([]),
});
export type Service = z.infer<typeof serviceSchema>;
export const serviceNumber = (index: number) => String(index + 1).padStart(2, "0");
export const serviceAnchor = (widget: Pick<Widget, "id" | "placement_id" | "slot" | "position">) => `service-x-${widget.placement_id ?? `${widget.id}-${encodeURIComponent(widget.slot)}-${widget.position}`}`;
// Preserve editorial names verbatim; omit only our redundant decorative index.
export const hasServiceNumber = (name: string, index: number) => new RegExp(`^0?${index + 1}(?:\\s+|[.\\-–—]\\s*)`).test(name.trim());
/** Display split only: a CMS name like `01 Development` shows its index beside a prominent name. */
export function serviceNameParts(name: string, index: number) {
  const trimmed = name.trim();
  if (!hasServiceNumber(trimmed, index)) return { number: serviceNumber(index), name: trimmed };
  const match = trimmed.match(/^(0?\d+)(?:\s+|[.\-–—]\s*)(.+)$/);
  return match ? { number: match[1].padStart(2, "0"), name: match[2] } : { number: serviceNumber(index), name: trimmed };
}
export function descriptionParts(description: string) {
  if (description.length <= 420) return { preview: description, remainder: "" };
  const prefix = description.slice(0, 320);
  const sentence = [...prefix.matchAll(/[.!?](?:\s|$)/g)].at(-1);
  const boundary = sentence && sentence.index >= 160 ? sentence.index + 1 : prefix.lastIndexOf(" ");
  // Unbroken strings stay intact. Never split words or repeat the preview.
  if (boundary < 160) return { preview: description, remainder: "" };
  return { preview: description.slice(0, boundary), remainder: description.slice(boundary).trimStart() };
}
export const serviceXMode = (widget: Pick<Widget, "placement_config">) => widget.placement_config?.display_mode ?? "slider";
export function selectedServiceIndex(services: readonly { id: string }[], id: string | undefined) {
  return Math.max(0, services.findIndex((service) => service.id === id));
}
export function serviceStep(index: number, direction: number, count: number) {
  return count ? (index + direction + count) % count : 0;
}
export function swipeStep(dx: number, dy: number) {
  return Math.abs(dx) >= 50 && Math.abs(dx) > Math.abs(dy) * 1.5 ? (dx < 0 ? 1 : -1) : 0;
}

// Match the frontend's locale-based UI-copy convention; CMS text is already localized.
export function serviceXLabels(locale: string) {
  if (locale === "sq") return { previous: "Shërbimi i mëparshëm", next: "Shërbimi tjetër", navigation: "Zgjidhni shërbimin", services: "Shërbimet", discuss: "Diskutoni këtë shërbim", ask: "Pyesni për këtë zgjidhje", more: "Lexoni më shumë", less: "Më pak detaje", help: "Si mund t’ju ndihmojmë", unsure: "Nuk jeni të sigurt nga t’ia filloni?", contact: "Na kontaktoni" };
  if (locale === "hu") return { previous: "Előző szolgáltatás", next: "Következő szolgáltatás", navigation: "Szolgáltatás kiválasztása", services: "Szolgáltatások", discuss: "Beszéljünk a szolgáltatásról", ask: "Érdeklődöm a megoldásról", more: "További részletek", less: "Kevesebb részlet", help: "Amiben segíthetünk", unsure: "Nem tudja, hol kezdje?", contact: "Kapcsolatfelvétel" };
  return { previous: "Previous service", next: "Next service", navigation: "Choose a service", services: "Services", discuss: "Discuss this service", ask: "Ask about this solution", more: "Read more", less: "Show less", help: "What we can help with", unsure: "Not sure where to start?", contact: "Get in touch" };
}
