"use client";

/* eslint-disable @next/next/no-img-element -- Same direct, lazy CMS media strategy as the existing widget registry. */
import { createContext, useContext, useState } from "react";
import styles from "./service-x.module.css";

export const ServiceXMediaVisibility = createContext(true);

/** `priority`: an above-the-fold image (the page's main visual) loads at once, at high priority, instead of lazily. */
export function ServiceXImage({ src, alt, className = styles.media, priority = false }: { src: string; alt: string; className?: string; priority?: boolean }) {
  const visible = useContext(ServiceXMediaVisibility);
  const [failed, setFailed] = useState(false);
  if (failed) return null;
  return <div className={className}>
    {visible && <img src={src} alt={alt} loading={priority ? "eager" : "lazy"} fetchPriority={priority ? "high" : undefined} decoding="async" width={800} height={600} onError={() => setFailed(true)} />}
  </div>;
}
