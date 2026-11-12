import { z } from "zod";
import { safeUrl } from "./links";

export function projectVariant(value: unknown) {
  if (value === "horizontal") return "horizontal_scroll";
  if (value === "vertical") return "vertical_scroll";
  return value;
}
const identifier = z.union([z.string(), z.number()]);
const imageSchema = z.object({ id: identifier.optional(), position: z.number().optional(), url: z.string(), alt: z.string().nullish().transform((v) => v ?? "") }).passthrough();
export const projectCardSchema = z.object({
  id: identifier, widget_id: identifier.optional(), position: z.number().optional(), name: z.string(), slug: z.string(), url: z.string().nullish(),
  detail_url: z.string().nullish(), website_url: z.string().nullish(),
  images: z.array(imageSchema).optional(),
  category: z.string().nullish(), client_name: z.string().nullish(),
  technologies: z.array(z.string()).nullish().transform((v) => v ?? []),
  main_image: imageSchema.nullish(), featured: z.boolean().optional(),
  video: z.object({ url: z.string(), mime: z.string().nullish() }).nullish(),
  mobile_video: z.object({ url: z.string(), mime: z.string().nullish() }).nullish(),
}).passthrough();
export const projectDetailSchema = projectCardSchema.extend({
  work_delivered: z.string().nullish(), goal: z.string().nullish(),
  project_type: z.string().nullish(), completion_year: z.union([z.string(), z.number()]).nullish(),
  description: z.string().nullish(), content: z.string().nullish(),
  meta_title: z.string().nullish(), meta_description: z.string().nullish(),
  meta: z.object({ title: z.string().nullish(), description: z.string().nullish(), robots: z.object({ index: z.boolean(), follow: z.boolean() }).nullish() }).nullish(),
  /** The widget that owns this slug when several widgets publish the same project. */
  canonical_widget_id: identifier.nullish(),
  locales: z.array(z.string()).nullish(),
  created_at: z.string().nullish(), updated_at: z.string().nullish(),
});
/**
 * Whether the desktop and mobile payloads show the same thing. Their API links
 * differ only by the ?device= they were requested with; anything else that
 * differs (images, videos, items) keeps the separate device versions.
 */
export function sameOnAllDevices(desktop: unknown, mobile: unknown) {
  const neutral = (value: unknown): unknown => typeof value === "string" ? value.replace(/([?&])device=(?:desktop|mobile)(&|$)/, (_, lead, tail) => tail ? lead : "")
    : Array.isArray(value) ? value.map(neutral)
    : value && typeof value === "object" ? Object.fromEntries(Object.entries(value).map(([key, v]) => [key, neutral(v)]))
    : value;
  return JSON.stringify(neutral(desktop)) === JSON.stringify(neutral(mobile));
}
/**
 * The image shown for a project in one context. The CMS keeps an ordered list:
 * the first image is the primary (Project Vertical cards, detail opening,
 * home showcase); the second is the secondary, shown by the related-projects
 * slider on detail pages. A project without a usable second image falls back
 * to its primary; none at all gives undefined, so no empty frame is drawn.
 */
export function projectImage(project: Pick<ProjectCard, "name" | "main_image" | "images">, role: "primary" | "secondary" = "primary") {
  const pick = (media: { url: string; alt: string } | null | undefined) => {
    const url = safeUrl(media?.url, true);
    return url ? { url, alt: media!.alt || project.name } : undefined;
  };
  const primary = pick(project.main_image ?? project.images?.[0]);
  if (role === "primary") return primary;
  // Stable sort: CMS order decides between images without a position.
  const ordered = (project.images ?? []).map((image, index) => ({ image, index }))
    .sort((a, b) => (a.image.position ?? a.index) - (b.image.position ?? b.index)).map(({ image }) => pick(image)).filter((image) => !!image);
  return ordered.slice(1).find((image) => image.url !== primary?.url) ?? primary;
}
export function projectHref(locale: string, widgetId: string | number, slug: string) {
  return `/${encodeURIComponent(locale)}/projects/${encodeURIComponent(widgetId)}/${encodeURIComponent(slug)}`;
}
export function websiteHref(value: unknown) {
  if (typeof value !== "string") return;
  try { const url = new URL(value); if (["http:", "https:"].includes(url.protocol) && !url.username && !url.password) return url.href; } catch { /* Invalid optional website. */ }
}
export const projectsConfigSchema = z.object({
  variant: z.preprocess(projectVariant, z.enum(["horizontal_scroll", "vertical_scroll"]).default("horizontal_scroll")),
  projects: z.array(projectCardSchema).default([]),
  section_label: z.string().nullish().transform((v) => v ?? ""),
}).passthrough();
export type ProjectCard = z.infer<typeof projectCardSchema>;
export function projectLabels(locale: string) {
  if (locale === "sq" || locale === "al") return { projects: "Projektet", explore: "Shiko të gjitha projektet", view: "Shiko projektin", category: "Kategoria", client: "Klienti", platform: "Platforma", previous: "Projekti i mëparshëm", next: "Projekti tjetër" };
  if (locale === "hu") return { projects: "Projektek", explore: "Összes projekt megtekintése", view: "Projekt megtekintése", category: "Kategória", client: "Ügyfél", platform: "Platform", previous: "Előző projekt", next: "Következő projekt" };
  return { projects: "Projects", explore: "Explore all projects", view: "View Project", category: "Category", client: "Client", platform: "Platform", previous: "Previous project", next: "Next project" };
}

