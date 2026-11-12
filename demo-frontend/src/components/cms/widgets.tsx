/* eslint-disable @next/next/no-img-element -- CMS URLs are resolved by Laravel; no image proxy allowlist is needed. */
import type { CSSProperties, ReactNode } from "react";
import { createHash } from "node:crypto";
import type { Widget } from "@/lib/cms/schema";
import { bool, items, num, str, type Config } from "@/lib/cms/config";
import { cmsHref, pageHref, safeUrl, type LinkContext } from "@/lib/cms/links";
import { CmsHtml } from "./html";
import { ExternalMedia } from "./media";
import { HeroVideo } from "./hero-video";
import { HeroTitle } from "./hero-title";
import { heroTitleParts } from "@/lib/cms/hero-title";
import { normalizeHeroMedia } from "@/lib/cms/hero-media";
import { heroGlobeLabels } from "@/lib/cms/hero-globe";
import { HeroGlobe } from "./hero-globe";
import { ContactForm } from "./contact-form";
import { MeetingBooking } from "./meeting-booking";
import { normalizeMeetingConfig } from "@/lib/cms/meetings";
import { cleanWidgetCss } from "@/lib/cms/widget-css";
import { ServiceX } from "./service-x";
import { Projects } from "./projects";
import { HeroWorld } from "./hero-world";
import { heroWorldType, isHeroType } from "@/lib/cms/hero-world";

type Props = { widget: Widget; context: LinkContext; path: string; primaryHero?: boolean; heroTitleFallback?: string };
/** `priority`: the page's leading visual (likely its largest paint) loads at once instead of lazily. */
function Image({ src, alt = "", className, priority = false }: { src: unknown; alt?: string; className?: string; priority?: boolean }) {
  const url = safeUrl(src, true);
  return url ? <img src={url} alt={alt} className={className} loading={priority ? "eager" : "lazy"} fetchPriority={priority ? "high" : undefined} decoding="async" /> : null;
}
function Cta({ config, context }: { config: Config; context: LinkContext }) {
  const href = cmsHref(config.cta_link, context);
  return href && str(config, "cta_text") ? <a className="button" href={href}>{str(config, "cta_text")} <span aria-hidden>↗</span></a> : null;
}
function Heading({ widget }: { widget: Widget }) {
  const c = widget.config;
  const title = str(c, "heading") || (bool(c, "show_title") ? widget.title : "");
  return <header className="section-heading">
    {str(c, "section_label") && <p className="eyebrow">{str(c, "section_label")}</p>}
    {title && <h2>{title}</h2>}{str(c, "subheading") && <p>{str(c, "subheading")}</p>}
  </header>;
}
function Grid({ config, children }: { config: Config; children: ReactNode }) {
  return <div className="card-grid" style={{ "--columns": num(config, "columns", 3, 1, 4) } as CSSProperties}>{children}</div>;
}
function Hero({ widget, context, primaryHero, heroTitleFallback }: Props) {
  const c = widget.config;
  const title = str(c, "headline") || widget.title || (primaryHero ? heroTitleFallback : "") || "";
  const subtitle = str(c, "subtitle");
  const labels = heroGlobeLabels(c);
  // Globe labels select the globe hero; uploaded media stays stored for when they are cleared.
  if (labels.length) return <section className="hero hero-globe">
    <div className="hero-copy">{title && <HeroTitle title={title} parts={heroTitleParts(title)} primary={primaryHero} />}{subtitle && <p className="hero-subtitle">{subtitle}</p>}<Cta config={c} context={context} /></div>
    <HeroGlobe key={labels.join("\n")} labels={labels} />
  </section>;
  const media = normalizeHeroMedia(c);
  return <section className={`hero ${media ? "hero-has-media" : ""}`}>
    {media?.type === "image" && <Image src={media.url} alt="" className="hero-image" priority={primaryHero} />}
    {media?.type === "video" && <HeroVideo url={media.url} locale={context.locale} />}
    {media && <div className="hero-video-overlay" aria-hidden="true" />}
    <div className="hero-copy">{title && <HeroTitle title={title} parts={heroTitleParts(title)} primary={primaryHero} />}{subtitle && <p className="hero-subtitle">{subtitle}</p>}<Cta config={c} context={context} /></div>
  </section>;
}
function Carousel({ widget, context }: Props) {
  const slides = items(widget.config, "slides");
  const resolved = slides.length ? slides : items(widget.config, "images").map((i) => ({ image: i.url, alt: i.alt }));
  return <><Heading widget={widget} /><div className="carousel" tabIndex={0} role="region" aria-label={str(widget.config, "section_label", "Carousel")}>
    {resolved.map((slide, index) => <article className="slide" key={index}><Image src={slide.image} alt={str(slide, "alt")} /><div className="slide-copy"><h3>{str(slide, "headline")}</h3><p>{str(slide, "subtitle")}</p><Cta config={slide} context={context} /></div></article>)}
  </div></>;
}
function Gallery({ widget, context }: Props) {
  return <><Heading widget={widget} /><Grid config={widget.config}>{items(widget.config).map((item, i) => {
    const href = cmsHref(item.link, context) || (bool(widget.config, "lightbox") ? safeUrl(item.image, true) : undefined);
    const img = <Image src={item.image} alt={str(item, "alt")} />;
    return <figure className="gallery-item" key={i}>{href ? <a href={href}>{img}</a> : img}{str(item, "caption") && <figcaption>{str(item, "caption")}</figcaption>}</figure>;
  })}</Grid></>;
}
function Features({ widget }: Props) {
  return <><Heading widget={widget} /><Grid config={widget.config}>{items(widget.config).map((item, i) => <article className="card" key={i}><Image src={item[str(item, "media_type", "icon")]} alt="" className="card-media" /><h3>{str(item, "title")}</h3><p>{str(item, "description")}</p></article>)}</Grid></>;
}
function Faq({ widget, context }: Props) {
  return <><Heading widget={widget} /><div className="faq">{items(widget.config).map((item, i) => <details key={i} name={bool(widget.config, "allow_multiple_open") ? undefined : `faq-${widget.id}`}><summary>{str(item, "question")}</summary><Image src={item[str(item, "media_type", "icon")]} alt="" className="card-media" /><CmsHtml html={str(item, "answer")} context={context} /></details>)}</div></>;
}
function Testimonials({ widget }: Props) {
  return <><Heading widget={widget} /><Grid config={widget.config}>{items(widget.config).map((item, i) => <figure className="card" key={i}><Image src={item[str(item, "media_type", "image")]} alt="" className="card-media" />{bool(widget.config, "show_rating") && <p aria-label={`${num(item, "rating", 5, 0, 5)} / 5`}>{"★".repeat(Math.floor(num(item, "rating", 5, 0, 5)))}</p>}<blockquote>{str(item, "quote")}</blockquote><figcaption><strong>{str(item, "name")}</strong><br />{str(item, "role")}</figcaption></figure>)}</Grid></>;
}
function Pricing({ widget, context }: Props) {
  return <><Heading widget={widget} /><Grid config={widget.config}>{items(widget.config).map((item, i) => <article className={`card price-card ${bool(widget.config, "highlight_popular") && bool(item, "is_popular") ? "popular" : ""}`} key={i}><p className="eyebrow">{str(item, "badge")}</p><h3>{str(item, "name")}</h3><p>{str(item, "description")}</p><p className="price">{str(widget.config, "currency")}{str(item, "price")}<small>{str(item, "period")}</small></p><ul>{str(item, "features").split(/\r?\n/).filter(Boolean).map((f, j) => <li key={j}>{f}</li>)}</ul><Cta config={item} context={context} /></article>)}</Grid><p>{str(widget.config, "billing_note")}</p></>;
}
function Separator({ widget }: Props) {
  const c = widget.config;
  return <div className="separator" style={{ borderTopStyle: ["solid", "dashed", "dotted"].includes(str(c, "style")) ? str(c, "style") as CSSProperties["borderTopStyle"] : "solid", borderTopWidth: num(c, "thickness", 1, 1, 12), borderTopColor: "var(--divider)", marginTop: num(c, "spacing_top", 24, 0, 120), marginBottom: num(c, "spacing_bottom", 24, 0, 120) }}>{str(c, "label") && <span>{str(c, "label")}</span>}</div>;
}
function Html({ widget, context }: Props) {
  const html = str(widget.config, "html");
  const rawCss = str(widget.config, "css");
  // Content hash separates translated/device variants of the same widget ID.
  const scope = `widget-${widget.id}-${createHash("sha256").update(html).update(rawCss).digest("hex").slice(0, 16)}`;
  const css = cleanWidgetCss(rawCss, scope);
  return <div className={`widget widget-html${css ? " widget-html-custom" : ""}`} data-widget-id={widget.id}>
    <div className={css ? "widget-content" : "container widget-content"}>
      <Heading widget={widget} />
      <div data-cms-html={scope}>
        {css && <style>{css}</style>}
        <CmsHtml html={html} context={context} customWidget styled={!!css} />
      </div>
    </div>
  </div>;
}
function videoEmbed(value: string) {
  try {
    const u = new URL(value);
    if (["www.youtube.com", "youtube.com", "youtu.be", "www.youtube-nocookie.com"].includes(u.hostname)) {
      const id = u.hostname === "youtu.be" ? u.pathname.slice(1) : u.searchParams.get("v") || u.pathname.split("/").pop();
      if (id && /^[\w-]{11}$/.test(id)) return `https://www.youtube-nocookie.com/embed/${id}`;
    }
    if (["vimeo.com", "www.vimeo.com", "player.vimeo.com"].includes(u.hostname)) {
      const id = u.pathname.split("/").pop();
      if (id && /^\d+$/.test(id)) return `https://player.vimeo.com/video/${id}?dnt=1`;
    }
  } catch { /* Invalid or unsupported provider. */ }
}
function Media({ widget, context }: Props) {
  const c = widget.config;
  const src = widget.type === "video" ? videoEmbed(str(c, "video_url")) : safeUrl(c.map_embed_url, true);
  const title = widget.title || (widget.type === "video" ? "Video" : "Map");
  return <><Heading widget={widget} />{src ? <ExternalMedia key={src} src={src} title={title} locale={context.locale} privacyHref={pageHref(context.locale, "privacy-policy")} /> : <p>{context.locale === "sq" ? "Media nuk është e disponueshme." : "Media is unavailable."}</p>}</>;
}
function Contact({ widget, context, path }: Props) {
  const c = widget.config;
  return <ContactForm locale={context.locale} path={path} widgetId={widget.id} label={str(c, "section_label")} title={str(c, "title")} subtitle={str(c, "subtitle")} showPhone={bool(c, "show_phone")} showSubject={bool(c, "show_subject")} submitLabel={str(c, "submit_label", "Send message")} successMessage={str(c, "success_message")} />;
}
function Booking({ widget, context, path }: Props) {
  const config = normalizeMeetingConfig(widget.config);
  return config ? <MeetingBooking locale={context.locale} path={path} widgetId={widget.id} config={config} /> : null;
}
const registry: Record<string, (props: Props) => ReactNode> = {
  hero: Hero, carousel: Carousel, image_gallery: Gallery, features: Features, faq: Faq,
  testimonials: Testimonials, pricing: Pricing, separator: Separator, html: Html,
  video: Media, map: Media, contact_form: Contact, projects: Projects, meeting_booking: Booking,
  [heroWorldType]: HeroWorld,
};
export function WidgetRenderer(props: Props) {
  if (props.widget.type === "service_x") return <ServiceX widget={props.widget} locale={props.context.locale} homeTransition={props.path === "home"} />;


  const Component = registry[props.widget.type];
  if (!Component) {
    if (process.env.NODE_ENV !== "production") return <aside className="unsupported">Unsupported CMS widget: {props.widget.type} (#{props.widget.id})</aside>;
    console.error(`[CMS] Unsupported widget type: ${props.widget.type}`);
    return null;
  }
  if (["projects", "html"].includes(props.widget.type)) return <Component {...props} />;
  return <div className={`widget widget-${props.widget.type}`} data-widget-id={props.widget.id}><div className={isHeroType(props.widget.type) ? undefined : "container widget-content"}><Component {...props} /></div></div>;
}

