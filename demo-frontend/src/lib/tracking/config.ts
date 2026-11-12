import type { Settings } from "@/lib/cms/schema";
import { siteEnvironment } from "@/lib/cms/seo";

type Env = Record<string, string | undefined>;

/** Public ID formats (the CMS validates the same); anything else disables that provider. */
export const trackingIdPatterns = {
  gtm: /^GTM-[A-Z0-9]{4,10}$/,
  ga4: /^G-[A-Z0-9]{4,15}$/,
  metaPixel: /^\d{10,20}$/,
} as const;

export type TrackingConfig = {
  /** live: providers load (production only). debug: no provider loads; events and consent are logged for testing. */
  mode: "live" | "debug";
  locale: string;
  requireConsent: boolean;
  /** Show the consent banner when the visitor has not decided yet. */
  banner: boolean;
  showReject: boolean;
  version: string;
  expiryDays: number;
  privacyHref?: string;
  cookieHref?: string;
  copy: { title?: string; text?: string };
  gtm: string | null;
  ga4: string | null;
  metaPixel: string | null;
  /** Consent categories that have something behind them. */
  categories: { analytics: boolean; marketing: boolean };
};

const id = (enabled: boolean | undefined, value: string | null | undefined, pattern: RegExp) => {
  const clean = value?.trim() ?? "";
  return enabled && pattern.test(clean) ? clean : null;
};

/**
 * What the website may load, from Settings → SEO & Tracking. Nothing at all
 * (null) unless tracking is switched on, at least one provider is enabled
 * with a valid ID, and the website runs with SITE_ENV=production. Outside
 * production, TRACKING_DEBUG=true shows the consent flow and logs events
 * without loading any provider.
 */
export function trackingConfig(settings: Pick<Settings, "tracking" | "gdpr">, locale: string, links: { privacyHref?: string; cookieHref?: string } = {}, env: Env = process.env): TrackingConfig | null {
  const tracking = settings.tracking;
  const gdpr = settings.gdpr;
  if (!tracking || !gdpr?.analytics_tracking_enabled) return null;
  const production = siteEnvironment(env) === "production";
  const debug = !production && /^(1|true|yes)$/i.test(env.TRACKING_DEBUG ?? "");
  if (!production && !debug) return null;

  const gtm = id(tracking.gtm_enabled, tracking.gtm_id?.toUpperCase(), trackingIdPatterns.gtm);
  // A container that manages GA4 and the Pixel would count every event twice if they also loaded directly.
  const managed = !!gtm && tracking.gtm_manages_tags !== false;
  const ga4 = managed ? null : id(tracking.ga4_enabled, tracking.ga4_measurement_id?.toUpperCase(), trackingIdPatterns.ga4);
  const metaPixel = managed ? null : id(tracking.meta_pixel_enabled, tracking.meta_pixel_id, trackingIdPatterns.metaPixel);
  if (!gtm && !ga4 && !metaPixel) return null;

  const requireConsent = gdpr.enabled === false ? false : gdpr.analytics_require_consent !== false;
  const days = Number(gdpr.consent_expiry_days ?? 180);
  const copy = tracking.consent_copy?.[locale] ?? {};
  return {
    mode: production ? "live" : "debug",
    locale,
    requireConsent,
    banner: requireConsent && gdpr.cookie_banner_enabled !== false,
    showReject: gdpr.show_reject_button !== false,
    version: /^[A-Za-z0-9._-]{1,20}$/.test(gdpr.consent_version ?? "") ? gdpr.consent_version! : "v1",
    expiryDays: Number.isFinite(days) ? Math.min(395, Math.max(1, Math.round(days))) : 180,
    ...links,
    copy: { title: copy.title?.trim() || undefined, text: copy.text?.trim() || undefined },
    gtm, ga4, metaPixel,
    // A GTM container may hold advertising tags too, so it offers both choices.
    categories: { analytics: !!gtm || !!ga4, marketing: !!gtm || !!metaPixel },
  };
}
