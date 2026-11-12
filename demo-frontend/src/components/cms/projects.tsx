import type { Widget } from "@/lib/cms/schema";
import type { LinkContext } from "@/lib/cms/links";
import { bool } from "@/lib/cms/config";
import { projectsConfigSchema, projectLabels } from "@/lib/cms/projects";
import { ProjectsHorizontal } from "./projects-horizontal";
import { ProjectsVertical } from "./projects-vertical";

export function Projects({ widget, context }: { widget: Widget; context: LinkContext }) {
  const parsed = projectsConfigSchema.safeParse(widget.config);
  if (!parsed.success) {
    console.error(`[CMS] Invalid Projects content for widget ${widget.id}`);
    return process.env.NODE_ENV !== "production" ? <aside className="unsupported">Invalid Projects configuration (#{widget.id})</aside> : null;
  }
  const { projects, variant, section_label } = parsed.data;
  if (!projects.length) return null;
  const labels = projectLabels(context.locale);
  const Component = variant === "vertical_scroll" ? ProjectsVertical : ProjectsHorizontal;
  return <div className="widget widget-projects widget-projects-immersive" data-widget-id={widget.id}><div className="container widget-content">
    <section data-projects={variant} aria-label={widget.title || section_label || labels.projects}>
    {(section_label || (bool(widget.config, "show_title") && widget.title)) && <header className={variant === "horizontal_scroll" ? "sr-only" : "section-heading"}>
      {section_label && <p className="eyebrow">{section_label}</p>}
      {bool(widget.config, "show_title") && widget.title && <h2>{widget.title}</h2>}
    </header>}
    <Component projects={projects} locale={context.locale} widgetId={widget.id} />
  </section></div></div>;
}


