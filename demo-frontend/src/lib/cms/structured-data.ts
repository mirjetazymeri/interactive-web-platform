import type { CmsPage, Settings, Widget } from "./schema";
import type { z } from "zod";
import type { projectDetailSchema } from "./projects";
import { items, str } from "./config";
import { pageHref, safeUrl } from "./links";
import { contactDetails, metaDescription, plainText, seoDefault, siteName } from "./seo";
import { serviceNameParts, serviceXConfigSchema } from "./service-x";

/**
 * schema.org JSON-LD built only from CMS content: the organization, the
 * website, the page with its breadcrumb trail, and what the page shows
 * (services, FAQ answers, a project). One @graph per page, so nothing is
 * declared twice. Without a site origin there are no absolute URLs, so only
 * nodes that make sense without them are emitted.
 */

type Node = Record<string, unknown>;
type Context = { settings: Settings; origin: string | null; locale: string; locales: string[]; defaultLocale: string };

/** Drop empty values so optional CMS fields never produce empty properties. */
function compact<T extends Node>(node: T): T {
  return Object.fromEntries(Object.entries(node).filter(([, value]) =>
    value !== undefined && value !== null && value !== "" && !(Array.isArray(value) && value.length === 0))) as T;
}
const absolute = (origin: string | null, locale: string, path: string) => origin ? new URL(pageHref(locale, path), origin).href : undefined;
const ref = (id: string | undefined) => id ? { "@id": id } : undefined;
const ids = (origin: string | null) => ({ organization: origin ? `${origin}/#organization` : undefined, website: origin ? `${origin}/#website` : undefined });

/** Serialized for a <script type="application/ld+json">: "<", ">" and "&" are escaped so CMS text can never close the element. */
export function jsonLd(graph: Node[]) {
  return JSON.stringify({ "@context": "https://schema.org", "@graph": graph })
    .replace(/</g, "\\u003c").replace(/>/g, "\\u003e").replace(/&/g, "\\u0026").replace(/\u2028/g, "\\u2028").replace(/\u2029/g, "\\u2029");
}

export function organizationNode({ settings, origin, locale, locales, defaultLocale }: Context): Node {
  const contact = contactDetails(settings);
  const logo = safeUrl(settings.general.logo, true);
  const sameAs = [...new Set(Object.values(settings.social).map((value) => safeUrl(value)).filter((value): value is string => !!value && /^https?:/.test(value)))];
  const contactPoint = contact.email || contact.phone ? [compact({
    "@type": "ContactPoint", contactType: "customer service", email: contact.email, telephone: contact.phone, availableLanguage: locales,
  })] : [];
  return compact({
    "@type": "Organization",
    "@id": ids(origin).organization,
    name: settings.seo.organization_name?.trim() || siteName(settings),
    url: absolute(origin, defaultLocale, "home"),
    logo: logo && /^https?:/.test(logo) ? { "@type": "ImageObject", url: logo } : undefined,
    description: plainText(seoDefault(settings, locale, "organization_description")),
    email: contact.email,
    telephone: contact.phone,
    address: contact.address.length ? { "@type": "PostalAddress", streetAddress: contact.address.join(", ") } : undefined,
    contactPoint,
    sameAs,
  });
}

export function websiteNode({ settings, origin, locales, defaultLocale }: Context): Node | null {
  if (!origin) return null;
  return compact({
    "@type": "WebSite", "@id": ids(origin).website, url: absolute(origin, defaultLocale, "home"), name: siteName(settings),
    inLanguage: locales, publisher: ref(ids(origin).organization),
  });
}

export type Crumb = { name: string; path: string };
/** BreadcrumbList for a trail ending at the current page; needs absolute URLs. */
export function breadcrumbNode(context: Context, pageUrl: string | undefined, trail: Crumb[]): Node | null {
  if (!context.origin || !pageUrl || trail.length < 2) return null;
  return {
    "@type": "BreadcrumbList", "@id": `${pageUrl}#breadcrumb`,
    itemListElement: trail.map((crumb, index) => compact({
      "@type": "ListItem", position: index + 1, name: crumb.name,
      // The last item is the page itself.
      item: index < trail.length - 1 ? absolute(context.origin, context.locale, crumb.path) : pageUrl,
    })),
  };
}

/** Each ServiceX service on the page, offered by the organization, with its solutions. */
export function serviceNodes(context: Context, pageUrl: string | undefined, widgets: Widget[]): Node[] {
  const seen = new Set<string>();
  return widgets.filter((widget) => widget.type === "service_x").flatMap((widget) => {
    const parsed = serviceXConfigSchema.safeParse(widget.config);
    // The index belongs to the service's own widget (it detects "01 Development"-style names).
    return parsed.success ? parsed.data.services.map((service, index) => ({ service, index })) : [];
  }).flatMap(({ service, index }) => {
    if (seen.has(service.id)) return [];
    seen.add(service.id);
    const name = serviceNameParts(service.name, index).name;
    const image = safeUrl(service.image, true);
    return [compact({
      "@type": "Service",
      "@id": pageUrl ? `${pageUrl}#service-${encodeURIComponent(service.id)}` : undefined,
      name,
      serviceType: name,
      description: metaDescription([service.title, service.subtitle].filter(Boolean).join(". ")),
      image: image && /^https?:/.test(image) ? image : undefined,
      provider: ref(ids(context.origin).organization),
      hasOfferCatalog: service.subservices.length ? {
        "@type": "OfferCatalog", name,
        itemListElement: service.subservices.filter((s) => s.title).map((s) => compact({ "@type": "Service", name: s.title, description: metaDescription(s.description) })),
      } : undefined,
    })];
  });
}

/** Question/answer pairs of visible FAQ widgets. */
export function faqQuestions(widgets: Widget[]): Node[] {
  return widgets.filter((widget) => widget.type === "faq").flatMap((widget) => items(widget.config))
    .map((item) => ({ question: plainText(str(item, "question")), answer: plainText(str(item, "answer")) }))
    .filter((qa) => qa.question && qa.answer)
    .map((qa) => ({ "@type": "Question", name: qa.question, acceptedAnswer: { "@type": "Answer", text: qa.answer } }));
}

const contactTypes = new Set(["contact_form", "meeting_booking"]);
/** The editor's choice, or what the content shows. FAQPage needs visible FAQ content. */
export function pageType(page: CmsPage, widgets: Widget[]): string {
  const chosen = page.meta.schema_type;
  const hasFaq = faqQuestions(widgets).length > 0;
  if (chosen && chosen !== "FAQPage") return chosen;
  if (chosen === "FAQPage" && hasFaq) return "FAQPage";
  if (widgets.some((widget) => contactTypes.has(widget.type))) return "ContactPage";
  if (page.path !== "home" && widgets.some((widget) => widget.type === "projects")) return "CollectionPage";
  if (hasFaq) return "FAQPage";
  return "WebPage";
}

type PageGraph = Context & {
  page: CmsPage; widgets: Widget[]; url: string | undefined; title: string; description?: string; image?: string;
};
/** Graph for a CMS page: Organization, WebSite, the page, its breadcrumb and the services / FAQ it shows. */
export function pageGraph({ page, widgets, url, title, description, image, ...context }: PageGraph): Node[] {
  const home = page.path === "home";
  const homeName = context.locale === "sq" ? "Kreu" : "Home";
  const trail: Crumb[] = home ? [] : [{ name: homeName, path: "home" }, ...page.breadcrumbs.filter((b) => b.path !== "home").map((b) => ({ name: b.title, path: b.path })), { name: page.title, path: page.path }];
  const breadcrumb = breadcrumbNode(context, url, trail);
  const services = serviceNodes(context, url, widgets);
  const type = pageType(page, widgets);
  const questions = type === "FAQPage" ? faqQuestions(widgets) : [];
  const servicesList = page.path === "services" && services.length && url ? {
    "@type": "ItemList", "@id": `${url}#services`,
    itemListElement: services.map((service, index) => ({ "@type": "ListItem", position: index + 1, item: ref(service["@id"] as string | undefined) ?? service })),
  } : null;
  const webPage = compact({
    "@type": type,
    "@id": url ? `${url}#webpage` : undefined,
    url, name: title, description, inLanguage: context.locale,
    isPartOf: ref(ids(context.origin).website),
    about: home ? ref(ids(context.origin).organization) : undefined,
    breadcrumb: breadcrumb ? ref(breadcrumb["@id"] as string) : undefined,
    primaryImageOfPage: image && /^https?:/.test(image) ? { "@type": "ImageObject", url: image } : undefined,
    datePublished: page.published_at ?? undefined,
    dateModified: page.updated_at ?? undefined,
    mainEntity: questions.length ? questions : servicesList ? ref(servicesList["@id"]) : undefined,
  });
  return [organizationNode(context), websiteNode(context), webPage, breadcrumb, servicesList, ...services].filter((node): node is Node => !!node);
}

type Project = z.infer<typeof projectDetailSchema>;
/** Graph for a project page: the page and a CreativeWork created by the organization. */
export function projectGraph({ project, url, title, description, projectsTitle, ...context }: Context & { project: Project; url: string | undefined; title: string; description?: string; projectsTitle: string }): Node[] {
  const images = (project.images?.length ? project.images : project.main_image ? [project.main_image] : [])
    .map((image) => safeUrl(image.url, true)).filter((image): image is string => !!image && /^https?:/.test(image));
  // The last crumb is the page itself (its canonical URL), so its path is not used.
  const breadcrumb = breadcrumbNode(context, url, [{ name: context.locale === "sq" ? "Kreu" : "Home", path: "home" }, { name: projectsTitle, path: "projects" }, { name: project.name, path: "" }]);
  const year = project.completion_year ? String(project.completion_year).match(/^\d{4}$/)?.[0] : undefined;
  const work = compact({
    "@type": "CreativeWork",
    "@id": url ? `${url}#project` : undefined,
    name: project.name,
    headline: project.name,
    description: description,
    url,
    image: images,
    inLanguage: context.locale,
    creator: ref(ids(context.origin).organization),
    keywords: project.technologies.length ? project.technologies.join(", ") : undefined,
    genre: project.category || project.project_type || undefined,
    dateCreated: year,
    mainEntityOfPage: url ? { "@id": `${url}#webpage` } : undefined,
  });
  const webPage = compact({
    "@type": "ItemPage", "@id": url ? `${url}#webpage` : undefined, url, name: title, description, inLanguage: context.locale,
    isPartOf: ref(ids(context.origin).website),
    breadcrumb: breadcrumb ? ref(breadcrumb["@id"] as string) : undefined,
    primaryImageOfPage: images[0] ? { "@type": "ImageObject", url: images[0] } : undefined,
    dateModified: project.updated_at ?? undefined,
    mainEntity: work["@id"] ? ref(work["@id"] as string) : undefined,
  });
  return [organizationNode(context), websiteNode(context), webPage, breadcrumb, work].filter((node): node is Node => !!node);
}
