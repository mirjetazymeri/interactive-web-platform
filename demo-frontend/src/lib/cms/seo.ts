import type { Metadata } from "next";
import type { Settings } from "./schema";
import { pageHref, safeUrl } from "./links";

/**
 * Search and sharing metadata built from CMS settings and page content:
 * titles, descriptions, canonical and language alternates, Open Graph and
 * X cards, robots, site verification. Icons come from app/icon.tsx and
 * app/apple-icon.tsx; schema.org data from ./structured-data.
 */

type Env = Record<string, string | undefined>;

/**
 * Where this website runs, from SITE_ENV. Only "production" may be indexed or
 * load analytics; anything unset or unknown counts as development, so a new
 * deployment never starts indexing or tracking by accident.
 */
export type SiteEnvironment = "production" | "staging" | "development";
export function siteEnvironment(env: Env = process.env): SiteEnvironment {
  const value = env.SITE_ENV?.trim().toLowerCase();
  return value === "production" || value === "staging" ? value : "development";
}

/** Hosts that only exist on a developer machine or a private network. */
const localHost = /^(?:localhost|0\.0\.0\.0|127(?:\.\d{1,3}){3}|\[::1\]|10(?:\.\d{1,3}){3}|192\.168(?:\.\d{1,3}){2})$|\.(?:localhost|local|test|internal|invalid|example)$/i;
export const isLocalHost = (host: string) => localHost.test(host);

let warned = false;
/**
 * The public website origin: SITE_URL (or NEXT_PUBLIC_SITE_URL) on this
 * server, else Settings → SEO → Production website URL; null when neither is
 * set. Absolute URLs (canonical, alternates, sitemap, structured data) need
 * it; it is never guessed from the request. In production a local or test
 * host is refused, so no localhost URL reaches search engines.
 */
export function siteOrigin(backendOrigin: string, settings?: Pick<Settings, "seo"> | null, env: Env = process.env): string | null {
  const configured = env.SITE_URL?.trim() || env.NEXT_PUBLIC_SITE_URL?.trim();
  let origin: string | null = null;
  if (configured) {
    const url = new URL(configured);
    if (!/^https?:$/.test(url.protocol) || url.origin === backendOrigin) throw new Error("SITE_URL must be the frontend HTTP(S) origin.");
    origin = url.origin;
  } else {
    // A CMS value is advisory: an unusable one is ignored rather than breaking pages.
    const cms = safeUrl(settings?.seo.site_url, true);
    try { if (cms && /^https?:/.test(cms)) origin = new URL(cms).origin; } catch { /* Ignored. */ }
    if (origin === backendOrigin) origin = null;
  }
  if (origin && siteEnvironment(env) === "production" && isLocalHost(new URL(origin).hostname)) {
    if (!warned) { warned = true; console.error(`[SEO] Ignoring local site URL ${origin} in production; canonical URLs and indexing stay off.`); }
    return null;
  }
  return origin;
}

/** Search engines may index only the production website, with a known origin, when the CMS allows it. */
export function indexingAllowed(settings: Pick<Settings, "seo">, origin: string | null, env: Env = process.env) {
  return siteEnvironment(env) === "production" && !!origin && settings.seo.allow_indexing !== false;
}

/** Open Graph locale codes for the site's languages. */
const ogLocales: Record<string, string> = { en: "en_US", sq: "sq_AL", hu: "hu_HU", de: "de_DE", fr: "fr_FR", it: "it_IT" };
export const ogLocale = (locale: string) => ogLocales[locale] ?? locale;

export const siteName = (settings: Settings) => settings.seo.site_name?.trim() || settings.general.app_name?.trim() || "Demo";

/** A site default in the visitor's language: Settings → SEO language overrides, then the default value. */
export function seoDefault(settings: Settings, locale: string, key: "meta_title" | "meta_description" | "organization_description") {
  return settings.seo.localized?.[locale]?.[key]?.trim() || settings.seo[key]?.trim() || "";
}

/**
 * "Page | Site" from the CMS title template. A title that already names the
 * site stays as it is; an empty title becomes the site name.
 */
export function pageTitle(title: string, settings: Settings) {
  const name = siteName(settings);
  const clean = title.replace(/\s+/g, " ").trim();
  if (!clean || clean.toLowerCase().includes(name.toLowerCase())) return clean || name;
  const template = settings.seo.title_template?.includes("{title}") ? settings.seo.title_template : "{title} {separator} {site}";
  return template.replace("{title}", clean).replace("{separator}", settings.seo.title_separator?.trim() || "|").replace("{site}", name).replace(/\s+/g, " ").trim();
}

/** Plain text from CMS HTML, with entities and whitespace normalized. */
export function plainText(text: string | null | undefined) {
  return (text ?? "").replace(/<(script|style)[\s\S]*?<\/\1>/gi, " ").replace(/<[^>]*>/g, " ")
    .replace(/&nbsp;/g, " ").replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&quot;/g, "\"").replace(/&#0?39;/g, "'")
    .replace(/\*\*(.+?)\*\*/g, "$1").replace(/\s+/g, " ").replace(/\s+([.,;:!?])/g, "$1").trim();
}

/** Collapse whitespace and keep descriptions to the length search results show (about 160 characters). */
export function metaDescription(text: string | null | undefined) {
  const clean = plainText(text);
  if (clean.length <= 160) return clean || undefined;
  const cut = clean.slice(0, 157);
  return `${cut.slice(0, Math.max(cut.lastIndexOf(" "), 120)).trimEnd()}…`;
}

export type PageSeo = {
  settings: Settings;
  backendOrigin: string;
  locale: string;
  /** Active locales, default first. */
  locales: string[];
  defaultLocale: string;
  /** CMS path ("home", "services/web", "projects/18/shop"). */
  path: string;
  title: string;
  description?: string | null;
  image?: string | null;
  type?: "website" | "article";
  /** Languages with their own content; others serve fallback content and point their canonical to one that has it. */
  available?: string[] | null;
  /** Editor override: a site path ("/services", localized) or an absolute URL. */
  canonical?: string | null;
  /** A path of this site (e.g. the owning widget of a shared project) that is canonical instead of `path`. */
  canonicalPath?: string | null;
  ogTitle?: string | null;
  ogDescription?: string | null;
  robots?: { index: boolean; follow: boolean } | null;
};

/** The canonical URL of a page and the languages to advertise as alternates (none when the page is not canonical itself). */
export function canonicalUrls({ origin, locale, locales, defaultLocale, path, available, canonical, canonicalPath }: Pick<PageSeo, "locale" | "locales" | "defaultLocale" | "path" | "available" | "canonical" | "canonicalPath"> & { origin: string | null }) {
  const languages = (available?.length ? locales.filter((code) => available.includes(code)) : locales);
  const url = (code: string, p = path) => origin ? new URL(pageHref(code, p), origin).href : undefined;
  const translated = languages.includes(locale);
  const fallback = languages.includes(defaultLocale) ? defaultLocale : languages[0] ?? defaultLocale;
  const override = canonical?.trim();
  // `language`: the language of the canonical URL (structured data follows it).
  const language = translated ? locale : fallback;
  if (override && !override.startsWith("/")) {
    const absolute = safeUrl(override, true);
    return { canonical: absolute && /^https?:/.test(absolute) ? absolute : url(language), self: false, languages: [], language };
  }
  if (override) {
    const segments = override.split(/[?#]/)[0].split("/").filter(Boolean);
    const target = locales.includes(segments[0]) ? segments.slice(1) : segments;
    const targetPath = target.join("/") || "home";
    return { canonical: url(locale, targetPath), self: targetPath === path, languages: targetPath === path && translated ? languages : [], language: locale };
  }
  if (canonicalPath && canonicalPath !== path) return { canonical: url(language, canonicalPath), self: false, languages: [], language };
  return translated
    ? { canonical: url(locale), self: true, languages, language }
    : { canonical: url(fallback), self: false, languages: [], language };
}

export function pageMetadata(page: PageSeo): Metadata {
  const { settings, backendOrigin, locale, defaultLocale, path, title, description, image, type = "website", ogTitle, ogDescription, robots } = page;
  const origin = siteOrigin(backendOrigin, settings);
  const name = siteName(settings);
  const fullTitle = pageTitle(title || seoDefault(settings, locale, "meta_title"), settings);
  const text = metaDescription(description) ?? metaDescription(seoDefault(settings, locale, "meta_description"));
  const shareImage = safeUrl(image, true) ?? safeUrl(settings.seo.og_image, true) ?? safeUrl(settings.general.logo, true);
  const urls = canonicalUrls({ ...page, origin });
  const alternates = origin && urls.languages.length > 1 ? {
    ...Object.fromEntries(urls.languages.map((code) => [code, new URL(pageHref(code, path), origin!).href])),
    "x-default": new URL(pageHref(urls.languages.includes(defaultLocale) ? defaultLocale : urls.languages[0], path), origin!).href,
  } : undefined;
  const index = indexingAllowed(settings, origin) && robots?.index !== false;
  const follow = indexingAllowed(settings, origin) && robots?.follow !== false;
  const handle = settings.seo.twitter_handle?.trim().replace(/^@?/, "@");
  const shareTitle = ogTitle?.trim() || fullTitle;
  const shareText = metaDescription(ogDescription) ?? text;
  const verification = {
    ...(settings.seo.google_site_verification?.trim() ? { google: settings.seo.google_site_verification.trim() } : {}),
    other: {
      ...(settings.seo.bing_site_verification?.trim() ? { "msvalidate.01": settings.seo.bing_site_verification.trim() } : {}),
      ...(settings.seo.facebook_domain_verification?.trim() ? { "facebook-domain-verification": settings.seo.facebook_domain_verification.trim() } : {}),
    },
  };

  return {
    title: fullTitle,
    description: text,
    ...(origin ? { metadataBase: new URL(origin) } : {}),
    ...(urls.canonical ? { alternates: { canonical: urls.canonical, ...(alternates ? { languages: alternates } : {}) } } : {}),
    openGraph: {
      type, siteName: name, title: shareTitle, description: shareText, url: urls.canonical, locale: ogLocale(locale),
      ...(urls.languages.length > 1 ? { alternateLocale: urls.languages.filter((code) => code !== locale).map(ogLocale) } : {}),
      ...(shareImage ? { images: [{ url: shareImage, alt: shareTitle }] } : {}),
    },
    twitter: {
      card: shareImage ? "summary_large_image" : "summary", title: shareTitle, description: shareText,
      ...(handle && handle.length > 1 ? { site: handle } : {}),
      ...(shareImage ? { images: [shareImage] } : {}),
    },
    // Staging, local and switched-off sites send noindex; pages can opt out individually.
    robots: index
      ? { index: true, follow, googleBot: { index: true, follow, "max-image-preview": "large", "max-snippet": -1, "max-video-preview": -1 } }
      : { index: false, follow },
    ...(verification.google || Object.keys(verification.other).length ? {
      verification: { ...(verification.google ? { google: verification.google } : {}), ...(Object.keys(verification.other).length ? { other: verification.other } : {}) },
    } : {}),
  };
}

/** Footer and schema.org contact details: footer settings first, then the store's. */
export function contactDetails(settings: Settings) {
  const address = (settings.footer.address ?? "").split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
  return {
    email: settings.footer.contact_email?.trim() || settings.store.email?.trim() || "",
    phone: settings.footer.contact_phone?.trim() || settings.store.phone?.trim() || "",
    address,
    mapsUrl: safeUrl(settings.footer.maps_url) ?? (address.length ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address.join(", "))}` : undefined),
    hours: settings.footer.opening_hours?.trim() || "",
  };
}
