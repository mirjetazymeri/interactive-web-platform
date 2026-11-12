import type { MetadataRoute } from "next";
import { getConfig, getLocales, getSettings } from "@/lib/cms/api";
import { indexingAllowed, siteOrigin } from "@/lib/cms/seo";
import { robotsRules } from "@/lib/cms/sitemap";

// Follows the "Allow Search Engines" setting and SITE_ENV without a rebuild.
export const dynamic = "force-dynamic";

/** /robots.txt: open on the production website when the CMS allows indexing; closed everywhere else. */
export default async function robots(): Promise<MetadataRoute.Robots> {
  const config = getConfig();
  const locales = await getLocales();
  const settings = await getSettings(locales.default);
  const origin = siteOrigin(config.origin, settings);
  return robotsRules({ indexable: indexingAllowed(settings, origin), origin });
}
