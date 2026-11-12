import type { z } from "zod";
import { projectDetailSchema, projectLabels, websiteHref } from "@/lib/cms/projects";
import { safeUrl, pageHref } from "@/lib/cms/links";
import { ServiceXImage } from "./service-x-image";
import { ProjectDetailMotion } from "./project-detail-motion";
import styles from "./project-detail.module.css";

// **word** marks an editor highlight; listed terms (e.g. technologies) are highlighted automatically.
function highlight(text: string, terms: string[]) {
  const escaped = [...new Set(terms.map(term => term.trim()).filter(term => term.length > 1))]
    .sort((a, b) => b.length - a.length).map(term => term.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"));
  const pattern = new RegExp(`\\*\\*(.+?)\\*\\*${escaped.length ? `|(?<![\\w.])(${escaped.join("|")})(?!\\w)` : ""}`, "gi");
  const nodes: React.ReactNode[] = [];
  let last = 0;
  for (const match of text.matchAll(pattern)) {
    nodes.push(text.slice(last, match.index));
    nodes.push(<strong className={styles.highlight} key={match.index}>{match[1] ?? match[2]}</strong>);
    last = match.index + match[0].length;
  }
  nodes.push(text.slice(last));
  return nodes;
}

// A short "Label:" opening a paragraph becomes an accent lead-in.
function paragraph(text: string, terms: string[]) {
  const lead = text.match(/^([^.:\n]{2,40}):\s+/);
  if (!lead) return highlight(text, terms);
  return [<span className={styles.lead} key="lead">{lead[1]}</span>, " ", ...highlight(text.slice(lead[0].length), terms)];
}

/** `priority`: the opening visual is above the fold (the page's largest paint), so it is not lazy-loaded. */
export function ProjectDetail({ project, locale, children, priority = false }: { project: z.infer<typeof projectDetailSchema>; locale: string; children: React.ReactNode; priority?: boolean }) {
  const labels = projectLabels(locale);
  const sq = locale === "sq";
  const website = websiteHref(project.website_url);
  const source = project.images?.length ? project.images : project.main_image ? [project.main_image] : [];
  const seen = new Set<string>();
  const images = [...source].sort((a, b) => (a.position ?? 0) - (b.position ?? 0)).filter(image => {
    const url = safeUrl(image.url, true);
    const identity = String(image.id ?? url);
    if (!url || seen.has(identity)) return false;
    seen.add(identity); return true;
  }).slice(0, 3);
  const mobileVideo = safeUrl(project.mobile_video?.url, true);
  const video = safeUrl(project.video?.url, true) ?? mobileVideo;
  const terms = [...project.technologies, project.client_name ?? ""];
  const metadata = [[labels.category, project.category || project.project_type], [labels.client, project.client_name], [labels.platform, project.technologies.join(", ")], [sq ? "Viti" : "Year", project.completion_year]].filter(([, value]) => value);
  const sections = [[null, project.description], [sq ? "Puna e realizuar" : "Work delivered", project.work_delivered], [sq ? "Qëllimi" : "Goal", project.goal]].filter(([, text]) => text?.trim());
  // Images without alternative text in the CMS are described by the project name.
  const visual = (image: typeof images[number], main = false, index = 0) => <figure className={main ? styles.mainVisual : styles.visual} data-detail-reveal={main ? undefined : ""}>
    <ServiceXImage src={safeUrl(image.url, true)!} alt={image.alt || (main || index === 0 ? project.name : `${project.name} — ${index + 1}`)} className={styles.image} priority={main && priority} />
  </figure>;
  return <ProjectDetailMotion>
    <article className={styles.detail}>
      <div className={styles.opening} data-detail-opening>
        <header className={styles.introduction} data-detail-intro>
          <div className={styles.introContent}>
            <h1>{project.name}</h1>
            {!!metadata.length && <dl className={styles.metadata}>{metadata.map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value}</dd></div>)}</dl>}
            {website && <a className={styles.website} href={website} target="_blank" rel="noopener noreferrer">{sq ? "Vizito faqen" : "Visit Website"}<span aria-hidden="true">↗</span></a>}
          </div>
        </header>
        {video ? <figure className={styles.mainVisual}>
          {/* Sources are matched once at load: phones get the portrait cut when one exists. */}
          <video className={mobileVideo ? `${styles.video} ${styles.portrait}` : styles.video} autoPlay muted loop playsInline preload="auto" aria-hidden="true">
            {mobileVideo && mobileVideo !== video && <source media="(max-width: 767px)" src={mobileVideo} />}
            <source src={video} />
          </video>
        </figure> : images[0] && visual(images[0], true)}
      </div>
      {!!sections.length && <div className={styles.story}>
        <span className={styles.mark} aria-hidden="true">↗</span>
        <div>{sections.map(([heading, text], index) => <section className={styles.textSection} key={index} aria-label={heading || (sq ? "Rreth projektit" : "About the project")}>
          {heading && <h2 data-detail-reveal>{heading}</h2>}
          {text!.split(/\r?\n\s*\r?\n/).filter(p => p.trim()).map((block, i) => <p data-detail-reveal key={i}>{paragraph(block.trim(), terms)}</p>)}
        </section>)}</div>
      </div>}
      {(video ? images : images.slice(1)).map((image, i) => <div key={image.id ?? image.url}>{visual(image, false, video ? i : i + 1)}</div>)}
    </article>
    {children || <ProjectIndexLink locale={locale} />}
  </ProjectDetailMotion>;
}

/** The way back to all projects, shown where no other projects follow the detail. */
export function ProjectIndexLink({ locale }: { locale: string }) {
  return <div className={styles.indexLink}><a href={pageHref(locale, "projects")}>{projectLabels(locale).explore} <span aria-hidden="true">↗</span></a></div>;
}
