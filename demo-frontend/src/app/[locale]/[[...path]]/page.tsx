import type { Metadata } from "next";
import { permanentRedirect } from "next/navigation";
import { getConfig, getFooterCta, getMenus, getSettings, requireLocale, requirePage } from "@/lib/cms/api";
import { pageMetadata } from "@/lib/cms/seo";
import { cmsPageJsonLd, cmsPageSeo } from "@/lib/cms/page-seo";
import { PageBody } from "@/components/cms/page-body";
import { SiteShell } from "@/components/cms/shell";
import { JsonLd } from "@/components/cms/json-ld";
import { pageWidgets } from "@/lib/cms/page-layout";
import { isHeroType } from "@/lib/cms/hero-world";
import { getAssistant } from "@/lib/assistant/server";

type Props = { params: Promise<{ locale: string; path?: string[] }> };
export const dynamic = "force-dynamic";

/** The CMS path of the request. /{locale}/home is the home page under a second address, so it moves to /{locale}. */
async function cmsPath({ params }: Props) {
  const { locale, path } = await params;
  const joined = path?.join("/") ?? "";
  if (joined === "home") permanentRedirect(`/${encodeURIComponent(locale)}`);
  return { locale, cmsPath: joined || "home" };
}

async function seoInput(locale: string, path: string) {
  const { desktop: page } = await requirePage(locale, path);
  const [settings, locales] = await Promise.all([getSettings(locale), requireLocale(locale)]);
  return { page, ...cmsPageSeo({ settings, locales, locale, backendOrigin: getConfig().origin, page }) };
}

export async function generateMetadata(props: Props): Promise<Metadata> {
  const { locale, cmsPath: path } = await cmsPath(props);
  return pageMetadata((await seoInput(locale, path)).seo);
}
export default async function CmsRoute(props: Props) {
  const { locale, cmsPath: path } = await cmsPath(props);
  const locales = await requireLocale(locale);
  const [pages, settings, menus, footerCta, assistant, seo] = await Promise.all([requirePage(locale, path), getSettings(locale), getMenus(locale), getFooterCta(locale), getAssistant(locale), seoInput(locale, path)]);
  const context = { locale, locales: locales.locales.map((l) => l.code), backendOrigin: getConfig().origin };
  const desktop = { ...pages.desktop, widgets: pageWidgets(pages.desktop) };
  const mobile = { ...pages.mobile, widgets: pageWidgets(pages.mobile) };
  return <>
    <JsonLd graph={cmsPageJsonLd(seo.seo, seo.page, seo.widgets)} />
    <SiteShell settings={settings} menus={menus} footerCta={footerCta} assistant={assistant} locales={locales} context={context} path={path} heroDesktop={isHeroType(desktop.widgets.hero?.[0]?.type)} heroMobile={isHeroType(mobile.widgets.hero?.[0]?.type)}><PageBody desktop={desktop} mobile={mobile} context={context} /></SiteShell>
  </>;
}
