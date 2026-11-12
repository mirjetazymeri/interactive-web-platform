import type { MetadataRoute } from "next";
import { z } from "zod";
import { pageHref } from "./links";

export const sitemapSchema = z.object({
  pages: z.array(z.object({ path: z.string().min(1), updated_at: z.string().nullish(), locales: z.array(z.string()).nullish() })),
  projects: z.array(z.object({
    widget_id: z.number(), slug: z.string().min(1), updated_at: z.string().nullish(),
    locales: z.array(z.string()).nullish(), image: z.string().nullish(),
  })),
});
export type SitemapData = z.infer<typeof sitemapSchema>;

/**
 * One entry per canonical URL: each page or project in every language that
 * has its own content (older CMS versions list no languages: then all), with
 * its language alternates and x-default. Modification times come from the
 * CMS and are omitted when unknown, never invented.
 */
export function buildSitemap(data: SitemapData, { origin, locales, defaultLocale }: { origin: string; locales: string[]; defaultLocale: string }): MetadataRoute.Sitemap {
  const url = (code: string, path: string) => new URL(pageHref(code, path), origin).href;
  const entry = (path: string, updated: string | null | undefined, available: string[] | null | undefined, priority: number, image?: string | null): MetadataRoute.Sitemap => {
    const codes = available?.length ? locales.filter((code) => available.includes(code)) : locales;
    const languages = codes.length > 1 ? {
      ...Object.fromEntries(codes.map((code) => [code, url(code, path)])),
      "x-default": url(codes.includes(defaultLocale) ? defaultLocale : codes[0], path),
    } : undefined;
    return codes.map((code) => ({
      url: url(code, path),
      ...(updated && !Number.isNaN(Date.parse(updated)) ? { lastModified: updated } : {}),
      changeFrequency: "weekly" as const,
      priority,
      ...(languages ? { alternates: { languages } } : {}),
      ...(image && /^https?:\/\//.test(image) ? { images: [image] } : {}),
    }));
  };
  return [
    ...data.pages.flatMap((page) => entry(page.path, page.updated_at, page.locales, page.path === "home" ? 1 : 0.7)),
    ...data.projects.flatMap((project) => entry(`projects/${project.widget_id}/${project.slug}`, project.updated_at, project.locales, 0.6, project.image)),
  ];
}

/** /robots.txt rules: production websites open to crawlers (except internal API routes); every other environment closed. */
export function robotsRules({ indexable, origin }: { indexable: boolean; origin: string | null }): MetadataRoute.Robots {
  return indexable && origin
    ? { rules: { userAgent: "*", allow: "/", disallow: "/api/" }, sitemap: `${origin}/sitemap.xml` }
    : { rules: { userAgent: "*", disallow: "/" } };
}
