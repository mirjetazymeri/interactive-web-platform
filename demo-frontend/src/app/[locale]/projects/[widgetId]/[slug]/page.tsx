import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { getConfig, getFooterCta, getMenus, getProject, getProjectCollection, getSettings, requireLocale } from "@/lib/cms/api";
import { SiteShell } from "@/components/cms/shell";
import { pageMetadata } from "@/lib/cms/seo";
import { projectJsonLd, projectSeo } from "@/lib/cms/page-seo";
import { ProjectDetail, ProjectIndexLink } from "@/components/cms/project-detail";
import { sameOnAllDevices } from "@/lib/cms/projects";
import { ProjectsHorizontal } from "@/components/cms/projects-horizontal";
import { JsonLd } from "@/components/cms/json-ld";
import { TrackView } from "@/components/tracking/track-view";
import { getAssistant } from "@/lib/assistant/server";

type Props = { params: Promise<{ locale: string; widgetId: string; slug: string }> };
export const dynamic = "force-dynamic";
async function details({ params }: Props) {
  const { locale, widgetId, slug } = await params;
  await requireLocale(locale);
  const [desktop, mobile] = await Promise.all([getProject(locale, widgetId, slug, "desktop"), getProject(locale, widgetId, slug, "mobile")]);
  if (!desktop || !mobile) notFound();
  return { locale, widgetId, slug, desktop, mobile };
}
async function seoInput(props: Props) {
  const { locale, widgetId, slug, desktop: project } = await details(props);
  const [settings, locales] = await Promise.all([getSettings(locale), requireLocale(locale)]);
  return projectSeo({ settings, locales, locale, backendOrigin: getConfig().origin, project, widgetId, slug });
}
export async function generateMetadata(props: Props): Promise<Metadata> {
  return pageMetadata(await seoInput(props));
}
export default async function ProjectRoute(props: Props) {
  const { locale, widgetId, slug, desktop, mobile } = await details(props);
  const [locales, settings, menus, footerCta, desktopCollection, mobileCollection, assistant, seo] = await Promise.all([
    requireLocale(locale), getSettings(locale), getMenus(locale), getFooterCta(locale),
    getProjectCollection(locale, widgetId, "desktop"), getProjectCollection(locale, widgetId, "mobile"), getAssistant(locale), seoInput(props),
  ]);
  const path = `projects/${widgetId}/${slug}`;
  const context = { locale, locales: locales.locales.map((l) => l.code), backendOrigin: getConfig().origin };
  function showcase(collection: typeof desktopCollection, project: typeof desktop) {
    if (!collection) return null;
    const projects = collection.config.projects.filter(item => !(String(item.widget_id ?? collection.id) === String(project.widget_id ?? widgetId) && String(item.id) === String(project.id)));
    return projects.length ? <ProjectsHorizontal projects={projects} locale={locale} widgetId={collection.id} media="secondary" /> : null;
  }
  // One detail (one <h1>) when both devices show the same project; device copies only where they differ.
  const desktopShowcase = showcase(desktopCollection, desktop);
  const mobileShowcase = showcase(mobileCollection, mobile);
  const sameShowcase = sameOnAllDevices(desktopCollection, mobileCollection);
  const following = sameShowcase ? desktopShowcase : (desktopShowcase || mobileShowcase) && <>
    <div className="desktop-only">{desktopShowcase ?? <ProjectIndexLink locale={locale} />}</div>
    <div className="mobile-only">{mobileShowcase ?? <ProjectIndexLink locale={locale} />}</div>
  </>;
  return <>
    <JsonLd graph={projectJsonLd(seo, desktop)} />
    <TrackView event="view_project" params={{ project_slug: slug, project_name: desktop.name, project_category: desktop.category ?? undefined }} />
    <SiteShell settings={settings} menus={menus} footerCta={footerCta} assistant={assistant} locales={locales} context={context} path={path}>
      {sameOnAllDevices(desktop, mobile)
        ? <ProjectDetail project={desktop} locale={locale} priority>{following}</ProjectDetail>
        : <>
          <div className="desktop-only"><ProjectDetail project={desktop} locale={locale} priority>{desktopShowcase}</ProjectDetail></div>
          <div className="mobile-only"><ProjectDetail project={mobile} locale={locale} priority>{mobileShowcase}</ProjectDetail></div>
        </>}
    </SiteShell>
  </>;
}
