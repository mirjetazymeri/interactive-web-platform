import type { ProjectCard as Project } from "@/lib/cms/projects";
import { projectHref, projectLabels } from "@/lib/cms/projects";
import { safeUrl } from "@/lib/cms/links";
import { ServiceXImage } from "./service-x-image";
import { ProjectsVerticalOrbit } from "./projects-vertical-orbit";
import { orbitRepeats } from "./projects-vertical-motion";
import styles from "./projects-vertical.module.css";

export function ProjectsVertical({ projects, locale, widgetId }: { projects: Project[]; locale: string; widgetId: number }) {
  if (!projects.length) return null;
  const labels = projectLabels(locale);
  // The ring repeats the list so it always looks full; repeats are visual only.
  const slots = Array.from({ length: orbitRepeats(projects.length) }, (_, round) => projects.map((project, index) => ({ project, index, round }))).flat();
  return <ProjectsVerticalOrbit count={projects.length} label={labels.projects} previous={labels.previous} next={labels.next}>
    {slots.map(({ project, index, round }) => {
      const media = project.main_image ?? project.images?.[0];
      const image = safeUrl(media?.url, true);
      const subtitle = [project.category, project.client_name].filter(Boolean).join(" · ");
      const copy = round > 0;
      return <article key={`${widgetId}:${project.id}:${round}`} className={styles.card} data-orbit-card data-orbit-copy={copy || undefined}
        data-index={index} data-title={project.name} data-subtitle={subtitle || labels.view} data-project-id={project.id} aria-hidden={copy || undefined}>
        <div className={styles.frame}>
          {/* The gallery opens with the first project in front: its image is the largest paint, so it is not lazy-loaded. */}
          {image ? <ServiceXImage className={styles.image} src={image} alt={copy ? "" : media?.alt || project.name} priority={round === 0 && index === 0} /> : <div className={styles.image} />}
          <div className={styles.shade} aria-hidden="true" />
          <div className={styles.details}>
            {project.category && <p className={styles.kicker}>{project.category}</p>}
            <h3>{project.name}</h3>
            {project.client_name && <p className={styles.client}><span>{labels.client}</span> {project.client_name}</p>}
            {project.technologies.length > 0 && <ul className={styles.pills} aria-label={labels.platform}>{project.technologies.slice(0, 4).map((technology, i) => <li key={`${technology}:${i}`}>{technology}</li>)}</ul>}
            <a className={styles.action} href={projectHref(locale, widgetId, project.slug)} tabIndex={copy ? -1 : undefined}>{labels.view}<span className="sr-only"> — {project.name}</span><span aria-hidden="true">↗</span></a>
          </div>
        </div>
      </article>;
    })}
  </ProjectsVerticalOrbit>;
}
