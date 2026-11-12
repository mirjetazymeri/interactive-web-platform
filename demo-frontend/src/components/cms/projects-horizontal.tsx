import type { ProjectCard as Project } from "@/lib/cms/projects";
import { projectHref, projectImage, projectLabels } from "@/lib/cms/projects";
import { pageHref } from "@/lib/cms/links";
import { ServiceXImage } from "./service-x-image";
import { ProjectsHorizontalScroller } from "./projects-horizontal-scroller";
import styles from "./projects-horizontal.module.css";

/** `media`: "secondary" for the related-projects slider on detail pages, which shows each project's second image, complete. */
export function ProjectsHorizontal({ projects, locale, widgetId, media = "primary" }: { projects: Project[]; locale: string; widgetId: number; media?: "primary" | "secondary" }) {
  if (!projects.length) return null;
  const labels = projectLabels(locale);
  return <ProjectsHorizontalScroller label={labels.projects} previous={labels.previous} next={labels.next}>
    {projects.map((project, index) => {
      const image = projectImage(project, media);
      return <article className={styles.panel} key={`${widgetId}:${project.id}`} data-project-id={project.id} data-project-media={media === "secondary" ? media : undefined}>
        {image && <ServiceXImage className={styles.image} src={image.url} alt={image.alt} />}
        <div className={styles.information} data-project-reading>
          <div className={styles.titleRow}><h3>{project.name}</h3><span className={styles.index} aria-hidden="true">{String(index + 1).padStart(2, "0")} / {String(projects.length).padStart(2, "0")}</span></div>
          <div className={styles.details}>
            <dl>{project.category && <div><dt>{labels.category}</dt><dd>{project.category}</dd></div>}{project.client_name && <div><dt>{labels.client}</dt><dd>{project.client_name}</dd></div>}</dl>
            {project.technologies.length > 0 && <ul className={styles.pills} aria-label={labels.platform}>{project.technologies.map((technology, i) => <li key={`${technology}:${i}`}>{technology}</li>)}</ul>}
            <a className={styles.action} href={projectHref(locale, widgetId, project.slug)}>{labels.view}<span className="sr-only"> — {project.name}</span><span aria-hidden="true">↗</span></a>
          </div>
        </div>
      </article>;
    })}
    <article className={`${styles.panel} ${styles.closing}`} data-project-closing>
      <div className={styles.closingContent} data-project-closing-content data-project-reading>
        <a className={styles.explore} href={pageHref(locale, "projects")}><span>{labels.explore}</span><span className={styles.exploreArrow} aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 19 19 5M5 5h14v14" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" /></svg></span></a>
      </div>
    </article>
  </ProjectsHorizontalScroller>;
}





