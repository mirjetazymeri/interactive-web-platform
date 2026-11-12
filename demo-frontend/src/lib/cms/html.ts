import "server-only";
import sanitizeHtml from "sanitize-html";
import { cmsHref, safeUrl, type LinkContext } from "./links";

const widgetAttributes = ["class", "id", "lang", "dir", "role", "aria-label", "aria-labelledby", "aria-describedby", "aria-hidden"];
const presentation = (attrs: Record<string, string>, enabled: boolean) => enabled
  ? Object.fromEntries(Object.entries(attrs).filter(([name]) => widgetAttributes.includes(name)))
  : {};

export function cleanHtml(html: string, context: LinkContext, customWidget = false) {
  return sanitizeHtml(html, {
    allowedTags: ["p", "br", "strong", "em", "u", "s", "ul", "ol", "li", "a", "h2", "h3", "h4", "h5", "h6", "blockquote", "span", "div", "img", "figure", "figcaption", "table", "thead", "tbody", "tr", "th", "td", "code", "pre", "hr", ...(customWidget ? ["section", "article", "header", "footer"] : [])],
    allowedAttributes: { ...(customWidget ? { "*": widgetAttributes } : {}), a: ["href", "title", "target", "rel"], img: ["src", "alt", "loading"], th: ["scope"], td: ["colspan", "rowspan"] },
    allowedSchemes: ["http", "https", "mailto", "tel"], allowProtocolRelative: false,
    transformTags: {
      h1: "h2",
      a: (_, attrs) => ({ tagName: "a", attribs: { ...presentation(attrs, customWidget), href: cmsHref(attrs.href, context) ?? "", title: attrs.title ?? "", ...(attrs.target === "_blank" ? { target: "_blank", rel: "noopener noreferrer" } : {}) } }),
      img: (_, attrs) => ({ tagName: "img", attribs: { ...presentation(attrs, customWidget), src: safeUrl(attrs.src, true) ?? "", alt: attrs.alt ?? "", loading: "lazy" } }),
    },
    exclusiveFilter: (frame) => frame.tag === "img" && !frame.attribs.src,
  });
}

export function cleanWidgetHtml(html: string, context: LinkContext) {
  const cleaned = cleanHtml(html, context, true);
  // Existing Laravel responses strip the Impact template's outer <section>.
  // Restore only this known template; complete sections and other HTML stay intact.
  if (!/\bid="ptx-home-impact"/.test(cleaned)
    && /\bclass="[^"]*\bptx-inner\b[^"]*"/.test(cleaned)
    && /\bid="ptx-home-impact-title"/.test(cleaned)) {
    return `<section id="ptx-home-impact" lang="${context.locale.replace(/[^a-zA-Z0-9-]/g, "")}" aria-labelledby="ptx-home-impact-title">${cleaned}</section>`;
  }
  return cleaned;
}
