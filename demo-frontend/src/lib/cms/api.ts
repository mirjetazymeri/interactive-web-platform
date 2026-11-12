import "server-only";
import { cache } from "react";
import { notFound } from "next/navigation";
import { apiConfig, CmsError, parseEnvelope, requestCms } from "./transport";
import { footerCtaSchema, localesSchema, menusSchema, pageSchema, settingsSchema, type Device } from "./schema";
import { validateTheme } from "./theme";
import { projectDetailSchema, projectsConfigSchema } from "./projects";
import { z } from "zod";

// The public widget resolver applies publication, locale and channel rules.
export const getProjectCollection = cache(async (locale: string, widgetId: string, device: Device = "desktop") => {
  try {
    const widget = parseEnvelope(await requestCms(getConfig(), `widgets/${encodeURIComponent(widgetId)}?device=${device}`, locale),
      z.object({ id: z.number(), type: z.literal("projects"), config: projectsConfigSchema }));
    return widget;
  } catch (error) {
    if (error instanceof CmsError && error.kind === "http" && error.status === 404) return null;
    throw error;
  }
});

export const getProject = cache(async (locale: string, widgetId: string, slug: string, device: Device = "desktop") => {
  if (!/^\d+$/.test(widgetId) || !slug || /[\/\\]/.test(slug) || slug === "." || slug === "..") return null;
  try {
    return parseEnvelope(await requestCms(getConfig(), `widgets/${encodeURIComponent(widgetId)}/projects/${encodeURIComponent(slug)}?device=${device}`, locale), projectDetailSchema);
  } catch (error) {
    if (error instanceof CmsError && error.kind === "http" && error.status === 404) return null;
    throw error;
  }
});

export const getConfig = () => apiConfig(process.env);
export const getLocales = cache(async () => parseEnvelope(await requestCms(getConfig(), "locales"), localesSchema));
export const requireLocale = cache(async (locale: string) => {
  const data = await getLocales();
  if (!data.locales.some((l) => l.code === locale)) notFound();
  return data;
});
export const getSettings = cache(async (locale: string) => {
  const settings = parseEnvelope(await requestCms(getConfig(), "settings", locale), settingsSchema);
  validateTheme(settings.theme);
  return settings;
});
export const getFooterCta = cache(async (locale: string) => {
  try {
    return parseEnvelope(await requestCms(getConfig(), "widgets/footer", locale), footerCtaSchema);
  } catch (error) {
    // A CMS without the footer endpoint simply has no call to action.
    if (error instanceof CmsError && error.kind === "http" && error.status === 404) return null;
    throw error;
  }
});
export const getMenus = cache(async (locale: string) => parseEnvelope(await requestCms(getConfig(), "menus", locale), menusSchema));
export const getPage = cache(async (locale: string, path: string, device: Device = "desktop") => {
  const endpoint = `pages/${path.split("/").map(encodeURIComponent).join("/")}?include_widgets=1&device=${device}`;
  try {
    const page = parseEnvelope(await requestCms(getConfig(), endpoint, locale), pageSchema);
    // The backend also accepts numeric IDs. Public URLs must use the CMS path.
    if (page.path !== path) return null;
    return page;
  } catch (error) {
    if (error instanceof CmsError && error.kind === "http" && error.status === 404) return null;
    throw error;
  }
});
export const requirePage = cache(async (locale: string, path: string) => {
  await requireLocale(locale);
  const [desktop, mobile] = await Promise.all([getPage(locale, path, "desktop"), getPage(locale, path, "mobile")]);
  if (!desktop || !mobile) notFound();
  return { desktop, mobile };
});
