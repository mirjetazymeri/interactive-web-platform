"use client";

import { useEffect, useId, useRef, type CSSProperties, type ReactNode } from "react";
import { enhanceProjectsHorizontal } from "./projects-horizontal-motion";
import styles from "./projects-horizontal.module.css";

export function ProjectsHorizontalScroller({ children, label, previous, next }: {
  children: ReactNode; label: string; previous: string; next: string;
}) {
  const ref = useRef<HTMLDivElement>(null);
  const id = useId();
  useEffect(() => enhanceProjectsHorizontal(ref.current!), [children]);

  const clipId = `projects-logo-${id.replace(/[^a-zA-Z0-9_-]/g, "")}`;
  return <div ref={ref} className={styles.layout} style={{ "--project-exit-clip": `url(#${clipId})` } as CSSProperties}>
    <svg className={styles.definitions} aria-hidden="true" focusable="false"><defs><clipPath id={clipId} clipPathUnits="userSpaceOnUse" data-project-logo>
      {/* Geometry traced from the original 67 × 50 symbol, before its wordmark. */}
      <circle cx="17.5" cy="32.5" r="17.5" /><circle cx="45" cy="12" r="12" /><circle cx="59.5" cy="29.5" r="6.5" />
    </clipPath></defs></svg>
    <div className={styles.stage} data-project-stage id={id} tabIndex={0} role="region" aria-label={label}>
      <div className={styles.track} data-project-track>{children}</div>
    </div>
    <div className={styles.controls}>
      <button type="button" aria-controls={id} aria-label={previous}>←</button>
      <button type="button" aria-controls={id} aria-label={next}>→</button>
    </div>
  </div>;
}




