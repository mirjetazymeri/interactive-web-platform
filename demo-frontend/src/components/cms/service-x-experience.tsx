"use client";

import { useEffect, useRef, type ReactNode } from "react";
import { enhanceServiceChapters } from "./service-x-motion";
import styles from "./service-x.module.css";

/** Content stays server-rendered; this boundary only enhances its interactions. */
export function ServiceXExperience({ children }: { children: ReactNode }) {
  const root = useRef<HTMLDivElement>(null);
  useEffect(() => {
    if (root.current) return enhanceServiceChapters(root.current);
  }, [children]);
  return <div ref={root} className={styles.stack}>{children}</div>;
}
