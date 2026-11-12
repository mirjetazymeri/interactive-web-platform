import type { z } from "zod";
import type { CmsPage, Locales, Settings, Widget } from "./schema";
import { str } from "./config";
import { heroWorldSeo, isHeroType } from "./hero-world";
import { pageWidgets } from "./page-layout";
import { projectLabels, type projectDetailSchema } from "./projects";
import { canonicalUrls, plainText, seoDefault, siteOrigin, type PageSeo } from "./seo";
import { pageGraph, projectGraph } from "./structured-data";

/**
 * The search inputs of a CMS page or project in one place, so metadata,
 * structured data and tests apply the same fallbacks:
 * editor SEO fields → content of the page itself → site defaults.
 */

type Base = { settings: Settings; locales: Locales; locale: string; backendOrigin: string };

/** Text a page actually shows, for a default description: its body, else its leading hero's subtitle. */
export function contentDescription(page: CmsPage, widgets: Widget[]) {
  const body = plainText(page.content);
  if (body) return body;
  const hero = widgets.find((widget) => isHeroType(widget.type));
  return hero ? plainText(str(hero.config, "subtitle")) : "";
}

export function cmsPageSeo({ settings, locales, locale, backendOrigin, page }: Base & { page: CmsPage }) {
  const slots = pageWidgets(page);
  const widgets = Object.values(slots).flat();
  const hero = heroWorldSeo(slots.hero?.[0]);
  const home = page.path === "home";
  const seo: PageSeo = {
    settings, backendOrigin, locale, path: page.path,
    locales: locales.locales.map((l) => l.code), defaultLocale: locales.default,
    // Explicit page SEO, then the leading Hero World's SEO, then the site default (home) or page title.
    title: page.meta.title || hero.title || (home ? seoDefault(settings, locale, "meta_title") : page.title) || page.title || "",
    description: page.meta.description || hero.description || contentDescription(page, widgets) || null,
    image: page.meta.og_image, available: page.locales, canonical: page.meta.canonical,
    ogTitle: page.meta.og_title, ogDescription: page.meta.og_description, robots: page.meta.robots,
  };
  return { seo, widgets };
}

type Project = z.infer<typeof projectDetailSchema>;
export function projectSeo({ settings, locales, locale, backendOrigin, project, widgetId, slug }: Base & { project: Project; widgetId: string; slug: string }): PageSeo {
  const owner = project.canonical_widget_id != null ? String(project.canonical_widget_id) : widgetId;
  return {
    settings, backendOrigin, locale, path: `projects/${widgetId}/${slug}`, type: "article",
    locales: locales.locales.map((l) => l.code), defaultLocale: locales.default,
    title: project.meta?.title || project.meta_title || project.name,
    description: project.meta?.description || project.meta_description || project.description || project.goal,
    image: project.main_image?.url,
    available: project.locales,
    // The same project published by several widgets has one canonical address.
    canonicalPath: owner !== widgetId ? `projects/${owner}/${slug}` : null,
    robots: project.meta?.robots,
  };
}

const context = (seo: PageSeo) => ({
  settings: seo.settings, origin: siteOrigin(seo.backendOrigin, seo.settings), locale: seo.locale, locales: seo.locales, defaultLocale: seo.defaultLocale,
});

// Structured data describes the canonical page, so its URLs and language follow the canonical URL.
export function cmsPageJsonLd(seo: PageSeo, page: CmsPage, widgets: Widget[]) {
  const ctx = context(seo);
  const { canonical, language } = canonicalUrls({ ...seo, origin: ctx.origin });
  return pageGraph({ ...ctx, locale: language, page, widgets, url: canonical, title: seo.title || page.title, description: plainText(seo.description) || undefined, image: seo.image ?? undefined });
}

export function projectJsonLd(seo: PageSeo, project: Project) {
  const ctx = context(seo);
  const { canonical, language } = canonicalUrls({ ...seo, origin: ctx.origin });
  const description = plainText(seo.description).slice(0, 500) || undefined;
  return projectGraph({ ...ctx, locale: language, project, url: canonical, title: seo.title, description, projectsTitle: projectLabels(language).projects });
}
