"use client";

import { useEffect, useRef } from "react";
import { enhanceServicesWheel } from "./impact-motion";
import { impactServicesFor, wheelLabels } from "./impact-services";
import { ServicesWheel } from "./services-wheel";
import styles from "./impact-widget.module.css";

/** Receives server-sanitized HTML; CMS JavaScript is never executed. */
export function AnimatedWidgetHtml({ html, className, locale }: { html: string; className: string; locale: string }) {
  const container = useRef<HTMLDivElement>(null);
  // Stable template identity, never a text match. Both server and client select
  // the same presentation; arbitrary HTML widgets retain their existing output.
  const isImpact = /\bid="ptx-home-impact"/.test(html);

  useEffect(() => {
    const host = container.current;
    if (host && isImpact) return enhanceServicesWheel(host);
  }, [html, isImpact]);

  // Neither hydration nor enhancement changes the CMS's HTML tree or link labels.
  if (!isImpact) return <div className={className} dangerouslySetInnerHTML={{ __html: html }} />;
  return <div ref={container} className={styles.presentation} data-impact-wheel>
    <div className={styles.stage} data-wheel-stage>
      <div className={styles.copy} data-wheel-copy>
        <div className={className} dangerouslySetInnerHTML={{ __html: html }} />
        {/* The copy's own index of the wheel: selecting here turns the wheel, and a
            line joins the active entry to the wheel's marker. */}
        <ol className={styles.index} data-wheel-index aria-label={wheelLabels(locale).expertise}>
          {impactServicesFor(locale).map((service, index) => <li key={service.number}>
            <button type="button" data-wheel-index-select={index}><span aria-hidden="true">{service.number}</span>{service.title}</button>
          </li>)}
        </ol>
      </div>
      <div className={styles.wheelRunway} data-wheel-runway><div className={styles.wheelWindow} data-wheel-window><ServicesWheel locale={locale} /></div></div>
      <svg className={styles.bridge} data-wheel-bridge aria-hidden="true" focusable="false"><path pathLength={1} /></svg>
    </div>
  </div>;
}
