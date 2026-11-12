import { cleanHtml, cleanWidgetHtml } from "@/lib/cms/html";
import type { LinkContext } from "@/lib/cms/links";
import { AnimatedWidgetHtml } from "./animated-widget-html";
export function CmsHtml({ html, context, customWidget = false, styled = false }: { html: string; context: LinkContext; customWidget?: boolean; styled?: boolean }) {
  if (customWidget) return <AnimatedWidgetHtml html={cleanWidgetHtml(html, context)} className={styled ? "custom-html" : "prose"} locale={context.locale} />;
  return <div className="prose" dangerouslySetInnerHTML={{ __html: cleanHtml(html, context) }} />;
}
