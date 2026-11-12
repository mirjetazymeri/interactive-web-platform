"use client";

import { useEffect, useRef, type ReactNode } from "react";
import { motionViewport } from "@/lib/cms/motion-viewport";

export function ProjectDetailMotion({ children }: { children: ReactNode }) {
  const ref = useRef<HTMLDivElement>(null);
  useEffect(() => {
    const root = ref.current!;
    const intro = root.querySelector<HTMLElement>("[data-detail-intro]")!;
    const opening = root.querySelector<HTMLElement>("[data-detail-opening]")!;
    const reveals = [...root.querySelectorAll<HTMLElement>("[data-detail-reveal]")];
    const reduced = matchMedia("(prefers-reduced-motion: reduce)");
    let frame = 0;
    const viewport = motionViewport(root, schedule);
    function render() {
      frame = 0;
      // Tall translations can be read fully before their header becomes sticky.
      intro.style.setProperty("--detail-intro-top", `${Math.min(90, viewport.height() - intro.offsetHeight - 32)}px`);
      opening.style.setProperty("--detail-cover-height", `${intro.offsetHeight + 32}px`);
      reveals.forEach(element => {
        const progress = Math.max(0, Math.min(1, (viewport.height() - element.getBoundingClientRect().top) / Math.min(180, viewport.height() * .25)));
        element.style.opacity = reduced.matches ? "1" : String(.25 + progress * .75);
        element.style.transform = reduced.matches ? "none" : `translateY(${(1 - progress) * 22}px)`;
      });
    }
    function schedule() { if (!frame) frame = requestAnimationFrame(render); }
    const observer = new ResizeObserver(schedule);
    observer.observe(intro);
    window.addEventListener("scroll", schedule, { passive: true });
    window.addEventListener("resize", schedule, { passive: true });
    reduced.addEventListener("change", schedule);
    schedule();
    return () => {
      cancelAnimationFrame(frame); observer.disconnect(); viewport.dispose();
      window.removeEventListener("scroll", schedule); window.removeEventListener("resize", schedule);
      reduced.removeEventListener("change", schedule);
      intro.style.removeProperty("--detail-intro-top");
      opening.style.removeProperty("--detail-cover-height");
      reveals.forEach(element => { element.style.removeProperty("opacity"); element.style.removeProperty("transform"); });
    };
  }, []);
  return <div ref={ref}>{children}</div>;
}
