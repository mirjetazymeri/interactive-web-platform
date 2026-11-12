"use client";

import { useEffect, useRef, useState } from "react";
import styles from "./service-x.module.css";

/**
 * One service list. Desktop shows it as a sticky sidebar; small screens collapse
 * it into a sticky bar naming the service in view, which opens the same list.
 */
export function ServiceXNavigation({ items, label, caption }: { items: { id: string; name: string }[]; label: string; caption: string }) {
  const nav = useRef<HTMLElement>(null);
  const toggle = useRef<HTMLButtonElement>(null);
  const [active, setActive] = useState(items[0]?.id);
  const [open, setOpen] = useState(false);
  useEffect(() => {
    const element = nav.current;
    if (!element) return;
    const section = element.closest<HTMLElement>("[data-service-x]");
    const header = document.querySelector<HTMLElement>(".header-dock");
    const articles = items.map(({ id }) => document.getElementById(id)).filter((item): item is HTMLElement => !!item);
    let frame = 0;
    let offset = 0;
    let barHeight = 0;
    let currentId = items[0]?.id;
    function update() {
      frame = 0;
      // Only the mobile bar can stick over content; it then paints a backing behind the floating header.
      element!.toggleAttribute("data-stuck", barHeight > 0 && element!.getBoundingClientRect().top <= offset - barHeight - 7);
      const visible = articles.filter((article) => article.getClientRects().length);
      if (!visible.length) return;
      const current = visible.findLast((article) => article.getBoundingClientRect().top <= offset + 24) ?? visible[0];
      if (current.id !== currentId) { currentId = current.id; setActive(current.id); }
    }
    function schedule() { if (!frame) frame = requestAnimationFrame(update); }
    function measure() {
      const bounds = header?.getBoundingClientRect();
      const headerBottom = (bounds && bounds.bottom > 0 && bounds.top < window.innerHeight / 2 ? bounds.bottom : 72) + 12;
      // The toggle is display:none on desktop, where the sidebar never overlays the reading line.
      barHeight = toggle.current?.offsetHeight ?? 0;
      section?.style.setProperty("--service-header-offset", `${headerBottom}px`);
      section?.style.setProperty("--service-nav-height", `${barHeight}px`);
      offset = headerBottom + barHeight;
      schedule();
    }
    const resize = new ResizeObserver(measure);
    resize.observe(element);
    if (header) resize.observe(header);
    const observer = new IntersectionObserver(schedule);
    articles.forEach((article) => observer.observe(article));
    window.addEventListener("scroll", schedule, { passive: true });
    window.addEventListener("resize", measure);
    measure();
    // Native anchors work before hydration too; correct the initial deep-link
    // offset once measured, without changing history or moving keyboard focus.
    let hash = "";
    try { hash = decodeURIComponent(window.location.hash.slice(1)); } catch { /* malformed hash */ }
    const target = articles.find((article) => article.id === hash);
    if (target?.getClientRects().length) target.scrollIntoView({ behavior: "instant" });
    return () => { resize.disconnect(); observer.disconnect(); cancelAnimationFrame(frame); window.removeEventListener("scroll", schedule); window.removeEventListener("resize", measure); };
  }, [items]);
  useEffect(() => {
    if (!open) return;
    const close = (event: Event) => {
      if (event instanceof KeyboardEvent ? event.key === "Escape" : !nav.current?.contains(event.target as Node)) setOpen(false);
    };
    document.addEventListener("keydown", close);
    document.addEventListener("pointerdown", close);
    return () => { document.removeEventListener("keydown", close); document.removeEventListener("pointerdown", close); };
  }, [open]);
  const current = items.find(({ id }) => id === active) ?? items[0];
  const listId = `${items[0]?.id ?? "service"}-list`;
  return <nav ref={nav} className={styles.serviceIndex} aria-label={label} data-open={open || undefined}>
    <p className={styles.indexCaption}>{caption}<span>{items.length}</span></p>
    <button ref={toggle} type="button" className={styles.indexToggle} aria-expanded={open} aria-controls={listId} onClick={() => setOpen((value) => !value)}>
      <span className={styles.toggleCaption}>{caption}</span>
      <span className={styles.toggleCurrent}>{current?.name}</span>
      <span className={styles.toggleChevron} aria-hidden="true" />
    </button>
    <ul id={listId}>{items.map(({ id, name }) => <li key={id}>
      <a href={`#${encodeURIComponent(id)}`} aria-current={active === id ? "location" : undefined} onClick={() => setOpen(false)}>
        <span>{name}</span><span className={styles.indexArrow} aria-hidden="true">→</span>
      </a>
    </li>)}</ul>
  </nav>;
}
