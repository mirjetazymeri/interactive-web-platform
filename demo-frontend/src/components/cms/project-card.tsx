import type { ProjectCard as Project } from "@/lib/cms/projects";
import { projectHref, projectLabels } from "@/lib/cms/projects";
import { safeUrl } from "@/lib/cms/links";
import { ServiceXImage } from "./service-x-image";
import styles from "./projects.module.css";

export function ProjectCard({ project, locale, widgetId }: { project: Project; locale: string; widgetId: number }) {
  const labels = projectLabels(locale);
  const image = safeUrl(project.main_image?.url, true);
  // This URL resolves to Laravel's widget-owned public detail route, not a CMS page path.
  const href = projectHref(locale, widgetId, project.slug);
  const metadata = [[labels.category, project.category], [labels.client, project.client_name], [labels.platform, project.technologies.join(", ")]].filter(([, value]) => !!value);
  return <article className={styles.card} data-project-id={project.id}>
    {image && <ServiceXImage key={image} className={styles.image} src={image} alt={project.main_image?.alt || project.name} />}
    <div className={styles.body}><h3>{project.name}</h3>
      {metadata.length > 0 && <dl className={styles.metadata}>{metadata.map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value}</dd></div>)}</dl>}
      {href && <a className="button" href={href}>{labels.view}<span className="sr-only"> — {project.name}</span><span aria-hidden>↗</span></a>}
    </div>
  </article>;
}
