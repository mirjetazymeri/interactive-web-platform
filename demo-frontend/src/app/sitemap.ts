import type { MetadataRoute } from "next";
import { getConfig, getLocales, getSettings } from "@/lib/cms/api";
import { parseEnvelope, requestCms } from "@/lib/cms/transport";
import { siteOrigin } from "@/lib/cms/seo";
import { buildSitemap, sitemapSchema } from "@/lib/cms/sitemap";

// New and edited CMS pages appear without a rebuild.
export const dynamic = "force-dynamic";

/**
 * /sitemap.xml: every indexable published CMS page and project page in each
 * language with its own content, with language alternates. Needs the site
 * origin (SITE_URL or Settings → SEO) for absolute URLs; robots.txt only
 * advertises it on the production website.
 */
export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const config = getConfig();
  const locales = await getLocales();
  const settings = await getSettings(locales.default);
  const origin = siteOrigin(config.origin, settings);
  if (!origin) return [];
  const data = parseEnvelope(await requestCms(config, "sitemap"), sitemapSchema);
  return buildSitemap(data, { origin, locales: locales.locales.map((l) => l.code), defaultLocale: locales.default });
}
