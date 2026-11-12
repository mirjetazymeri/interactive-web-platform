"use client";

import dynamic from "next/dynamic";
import { useCallback, useId, useRef, useState } from "react";
import type { AssistantBootstrap } from "@/lib/assistant/schema";
import { assistantLabels } from "@/lib/assistant/labels";
import styles from "./assistant.module.css";

// The conversation and the WebGL orb load only when a visitor shows interest.
const loadPanel = () => import("./assistant-panel");
const AssistantPanel = dynamic(() => loadPanel().then((m) => m.AssistantPanel), { ssr: false });

/**
 * The floating orb in the bottom-right corner. Rendered only when the CMS
 * has the assistant enabled (or in a development preview); the tooltip text
 * is the admin's hover text and shows on hover and on keyboard focus.
 */
export function AssistantLauncher({ config, locale }: { config: AssistantBootstrap; locale: string }) {
  const [open, setOpen] = useState(false);
  const button = useRef<HTMLButtonElement>(null);
  const tooltipId = useId();
  const t = assistantLabels(locale);
  const prefetch = useCallback(() => { void loadPanel(); }, []);
  const close = useCallback(() => {
    setOpen(false);
    // Back to where the visitor was.
    requestAnimationFrame(() => button.current?.focus({ preventScroll: true }));
  }, []);

  return <>
    <div className={styles.launcher} data-open={open} data-mobile={config.show_on_mobile}>
      <span id={tooltipId} role="tooltip" className={styles.tooltip}>{config.tooltip}</span>
      <button ref={button} type="button" className={styles.orbButton} aria-label={`${t.open}: ${config.name}`} aria-describedby={tooltipId}
        aria-haspopup="dialog" aria-expanded={open} onPointerEnter={prefetch} onFocus={prefetch} onClick={() => setOpen(true)}>
        <span className={styles.miniOrb} aria-hidden="true" />
      </button>
    </div>
    {open && <AssistantPanel config={config} locale={locale} onClose={close} />}
  </>;
}
