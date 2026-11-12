import type { Widget } from "@/lib/cms/schema";
import { str } from "@/lib/cms/config";
import { cmsHref, type LinkContext } from "@/lib/cms/links";
import { heroTitleParts } from "@/lib/cms/hero-title";
import { heroWorldContent } from "@/lib/cms/hero-world";
import { serviceXLabels } from "@/lib/cms/service-x";
import { HeroTitle } from "./hero-title";
import { HeroWorldGlobe } from "./hero-world-globe";
import styles from "./hero-world.module.css";

/**
 * Hero World: title, subtitle and CTA beside a rotating dotted globe with the
 * CMS service points. All copy is server-rendered (React escapes it), so the
 * heading, text, link and service list stay available without WebGL.
 */
export function HeroWorld({ widget, context, primaryHero, heroTitleFallback }: { widget: Widget; context: LinkContext; primaryHero?: boolean; heroTitleFallback?: string }) {
  const content = heroWorldContent(widget.config);
  const title = content.headline || widget.title || (primaryHero ? heroTitleFallback : "") || "";
  const href = cmsHref(content.ctaLink, context);
  const label = str(widget.config, "section_label") || serviceXLabels(context.locale).services;
  return <section className={`hero ${styles.hero}`} aria-label={title ? undefined : label}><div className={styles.container} data-world-container>
    {/* Decorative connection layer, driven by the globe's frame loop; never interactive. */}
    <div className={styles.ambient} data-world-ambient aria-hidden="true" />
    <svg className={styles.links} data-world-links aria-hidden="true" focusable="false">
      {[0, 1, 2].map((i) => <g key={i} data-world-link>
        <path className={styles.link} />
        <circle className={styles.signal} r="1.6" />
      </g>)}
    </svg>
    <div className={`hero-copy ${styles.copy}`} data-world-copy>
      {title && <HeroTitle title={title} parts={heroTitleParts(title)} primary={primaryHero} />}
      {content.subtitle && <p className={`hero-subtitle ${styles.subtitle}`}>{content.subtitle}</p>}
    </div>
    <HeroWorldGlobe services={content.services} label={label} />
    {/* A separate grid item: under the subtitle on desktop, under the globe on phones. */}
    {href && content.ctaText && <div className={styles.action}><a className={`button ${styles.cta}`} href={href} data-world-cta>{content.ctaText}<span aria-hidden>↗</span></a></div>}
  </div></section>;
}
