"use client";

import { useEffect, useId, useRef, type ReactNode } from "react";
import { enhanceProjectsVertical } from "./projects-vertical-motion";
import styles from "./projects-vertical.module.css";

export function ProjectsVerticalOrbit({ children, count, label, previous, next }: {
  children: ReactNode; count: number; label: string; previous: string; next: string;
}) {
  const ref = useRef<HTMLDivElement>(null);
  const id = useId();
  // useId can contain characters that break SVG url(#…) references.
  const svgId = `orbit-${id.replace(/[^a-zA-Z0-9_-]/g, "")}`;
  useEffect(() => enhanceProjectsVertical(ref.current!), [children]);

  return <div ref={ref} className={styles.layout}>
    <div className={styles.stage} data-orbit-stage id={id} tabIndex={0} role="region" aria-roledescription="carousel" aria-label={label}>
      <div className={styles.room} aria-hidden="true" />
      <div className={styles.scene}>
        <svg className={`${styles.floor} ${styles.floorBack}`} aria-hidden="true" focusable="false">
          <path className={styles.ringLine} data-orbit-floor="back" />
        </svg>
        {children}
        {/* Layered like neon: a wide halo, the coloured tube, a bright core, and the lit edge of the platform below. */}
        <svg className={`${styles.floor} ${styles.floorFront}`} aria-hidden="true" focusable="false">
          {/* The front arc fades out towards its ends, where it curves away behind the side cards. */}
          <defs>
            <linearGradient id={`${svgId}-fade`} gradientUnits="userSpaceOnUse" data-orbit-fade>
              <stop offset="0" stopOpacity="0" /><stop offset=".16" /><stop offset=".84" /><stop offset="1" stopOpacity="0" />
            </linearGradient>
            {/* An alpha mask reads only the stops' opacity, so the fade needs no colour of its own. */}
            <mask id={`${svgId}-mask`} maskUnits="userSpaceOnUse" x="-50000" y="-50000" width="100000" height="100000" style={{ maskType: "alpha" }}><rect x="-50000" y="-50000" width="100000" height="100000" fill={`url(#${svgId}-fade)`} /></mask>
          </defs>
          <g mask={`url(#${svgId}-mask)`}>
            <g className={styles.ringRim} data-orbit-rim>{Array.from({ length: 10 }, (_, i) => <path key={i} style={{ opacity: Math.round(85 * (1 - i / 10) ** 1.6) / 100 }} />)}</g>
            <path className={styles.ringHalo} data-orbit-floor="front" /><path className={styles.ringLine} data-orbit-floor="front" /><path className={styles.ringCore} data-orbit-floor="front" />
          </g>
        </svg>
      </div>
    </div>
    <div className={styles.hud}>
      <div className={styles.caption} data-orbit-caption aria-hidden="true">
        <p className={styles.title} data-orbit-title />
        <p className={styles.subtitle} data-orbit-subtitle />
      </div>
      <div className={styles.meter} aria-hidden="true">
        <span data-orbit-current>01</span>
        <ol>{Array.from({ length: count }, (_, i) => <li key={i} data-orbit-tick />)}</ol>
        <span>{String(count).padStart(2, "0")}</span>
      </div>
      <div className={styles.controls}>
        <button type="button" data-orbit-step aria-controls={id} aria-label={previous}>←</button>
        <button type="button" data-orbit-step aria-controls={id} aria-label={next}>→</button>
      </div>
    </div>
  </div>;
}
