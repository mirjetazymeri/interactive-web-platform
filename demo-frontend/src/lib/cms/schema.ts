import { z } from "zod";
import { normalizeHeroMedia } from "./hero-media";
import { projectVariant } from "./projects";

const text = z.string().nullable();
const flag = z.union([z.boolean(), z.literal(0), z.literal(1), z.literal("0"), z.literal("1")]).transform((v) => v === true || v === 1 || v === "1");
export const localesSchema = z.object({
  locales: z.array(z.object({ code: z.string().regex(/^[a-z]{2,3}(?:-[A-Za-z0-9]+)*$/), name: z.string(), flag: text, is_default: z.boolean() })).min(1),
  default: z.string(), current: z.string(),
}).refine((v) => v.locales.some((l) => l.code === v.default), "Default locale must be active");

export type MenuItem = { id: number; label: string; path: string; url: string; target: "_self" | "_blank"; children: MenuItem[]; section_label?: string | null; section_key?: string | null };
const menuItemSchema: z.ZodType<MenuItem> = z.lazy(() => z.object({
  id: z.number(), label: z.string(), path: z.string(), url: z.string(), target: z.enum(["_self", "_blank"]),
  children: z.array(menuItemSchema), section_label: text.optional(), section_key: text.optional(),
}));
export const menusSchema = z.object({ header: z.array(menuItemSchema), footer: z.array(menuItemSchema) });
export const settingsSchema = z.object({
  theme: z.object({ primary_color: z.unknown().optional(), secondary_color: z.unknown().optional(), accent_color: z.unknown().optional(), typography: z.unknown().optional() }).nullish().catch(undefined),
  general: z.object({ app_name: text, logo: text, favicon: text }),
  store: z.object({ email: text, phone: text }),
  seo: z.object({
    meta_title: text, meta_description: text,
    // Newer fields are optional so an older CMS still validates.
    og_image: text.optional(), twitter_handle: text.optional(), allow_indexing: flag.optional(),
    google_site_verification: text.optional(), bing_site_verification: text.optional(),
    facebook_domain_verification: text.optional(),
    site_name: text.optional(), title_separator: text.optional(), title_template: text.optional(), site_url: text.optional(),
    organization_name: text.optional(), organization_description: text.optional(),
    /** Per-language overrides of the defaults above, e.g. { sq: { meta_title } }. */
    localized: z.record(z.string(), z.object({ meta_title: z.string().optional(), meta_description: z.string().optional(), organization_description: z.string().optional() }).partial()).nullish().catch(null),
  }),
  /** Analytics providers (public IDs only); unusable values disable a provider, never the page. */
  tracking: z.object({
    gtm_enabled: flag.optional(), gtm_id: text.optional(), gtm_manages_tags: flag.optional(),
    ga4_enabled: flag.optional(), ga4_measurement_id: text.optional(),
    meta_pixel_enabled: flag.optional(), meta_pixel_id: text.optional(),
    consent_copy: z.record(z.string(), z.object({ title: z.string().optional(), text: z.string().optional() })).nullish().catch(null),
  }).nullish().catch(null),
  gdpr: z.object({
    enabled: flag.optional(), cookie_banner_enabled: flag.optional(), show_reject_button: flag.optional(),
    analytics_tracking_enabled: flag.optional(), analytics_require_consent: flag.optional(),
    consent_version: text.optional(), consent_expiry_days: z.union([z.string(), z.number()]).nullish(),
    privacy_policy_url: text.optional(), cookie_policy_url: text.optional(),
  }).nullish().catch(null),
  header: z.object({
    top_bar: z.object({ enabled: flag, text, link: text }),
    main: z.object({ sticky: flag }), menu: z.object({ enabled: flag }),
  }),
  footer: z.object({
    show_socials: flag,
    contact_email: text.optional(), contact_phone: text.optional(), address: text.optional(), maps_url: text.optional(), opening_hours: text.optional(),
  }),
  social: z.record(z.string(), text),
});
/** The footer call to action widget (one per site), or null when none is active. */
export const footerCtaSchema = z.object({
  id: z.number(),
  config: z.object({
    eyebrow: z.string(), heading: z.string(), text: z.string(), button_label: z.string(), button_link: z.string(), show_email: flag,
  }),
}).nullable();
export const widgetSchema = z.object({
  placement_id: z.number().optional(),
  placement_config: z.union([
    z.object({ display_mode: z.enum(["slider", "stacked"]).optional() }).passthrough(),
    z.array(z.never()).transform((): { display_mode?: "slider" | "stacked" } => ({})),
  ]).nullish(),
  id: z.number(), type: z.string(), widget_key: z.string(), title: text,
  slot: z.string(), position: z.number(), config: z.record(z.string(), z.unknown()),
}).transform((widget) => widget.type === "hero"
  ? { ...widget, config: { ...widget.config, media: normalizeHeroMedia(widget.config) } }
  : widget.type === "projects" ? { ...widget, config: { ...widget.config, variant: projectVariant(widget.config.variant ?? "horizontal_scroll") } } : widget);
const referenceSchema = z.object({ id: z.number(), slug: z.string(), title: z.string(), path: z.string() });
// Laravel serializes an empty grouped collection as [], not {}.
const slotsSchema = z.union([z.record(z.string(), z.array(widgetSchema)), z.array(z.never()).transform((): Record<string, z.infer<typeof widgetSchema>[]> => ({}))]);
export const pageSchema = z.object({
  id: z.number(), slug: z.string(), path: z.string(), title: z.string(), content: text,
  layout: text, status: z.literal("published"), parent_id: z.number().nullable(),
  meta: z.object({
    title: text, description: text,
    // Per-page search and sharing overrides (newer CMS versions).
    canonical: text.optional(), og_title: text.optional(), og_description: text.optional(), og_image: text.optional(),
    robots: z.object({ index: z.boolean(), follow: z.boolean() }).optional(),
    schema_type: text.optional(),
  }), breadcrumbs: z.array(referenceSchema),
  children: z.array(referenceSchema), widgets: slotsSchema,
  /** Languages with their own content; absent on older CMS versions (then every language counts). */
  locales: z.array(z.string()).optional(),
  updated_at: text.optional(), published_at: text.optional(),
});
export type Locales = z.infer<typeof localesSchema>;
export type Settings = z.infer<typeof settingsSchema>;
export type Menus = z.infer<typeof menusSchema>;
export type Widget = z.infer<typeof widgetSchema>;
export type FooterCta = z.infer<typeof footerCtaSchema>;
export type CmsPage = z.infer<typeof pageSchema>;
export type Device = "desktop" | "mobile";
