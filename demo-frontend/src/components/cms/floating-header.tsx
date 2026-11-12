"use client";

import { useEffect, useId, useRef, useState, type ReactNode } from "react";

/**
 * Edge-aligned site header: brand, links and actions on the shared content
 * edges, transparent at the top of the page and a compact glass bar once
 * scrolled. When the links do not fit it switches to a "Menu" button that
 * opens a full-screen sheet (focus kept inside, Escape and Close shut it).
 */
export function FloatingHeader({ logo, navigation, actions, locale }: { logo: ReactNode; navigation: ReactNode; actions: ReactNode; locale: string }) {
  const [open, setOpen] = useState(false);
  const [compact, setCompact] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const root = useRef<HTMLElement>(null);
  const toggle = useRef<HTMLButtonElement>(null);
  const measure = useRef<HTMLDivElement>(null);
  const brand = useRef<HTMLDivElement>(null);
  const id = useId();
  const sq = locale === "sq";

  // Compact when the inline links and actions would not fit the bar.
  useEffect(() => {
    const resize = () => {
      const bar = root.current?.clientWidth ?? window.innerWidth;
      const needed = (measure.current?.scrollWidth ?? 0) + (brand.current?.offsetWidth ?? 160) * 2 + 96;
      const next = window.innerWidth < 768 || needed > bar;
      setCompact(next);
      if (!next) setOpen(false);
    };
    const observer = new ResizeObserver(resize);
    for (const node of [root.current, measure.current, brand.current]) if (node) observer.observe(node);
    window.addEventListener("resize", resize);
    resize();
    return () => { observer.disconnect(); window.removeEventListener("resize", resize); };
  }, []);

  // The glass bar appears once the page has scrolled a little.
  useEffect(() => {
    let frame = 0;
    const update = () => { frame = 0; setScrolled(window.scrollY > 24); };
    const onScroll = () => { if (!frame) frame = requestAnimationFrame(update); };
    update();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => { window.removeEventListener("scroll", onScroll); cancelAnimationFrame(frame); };
  }, []);

  // Close submenus on outside clicks.
  useEffect(() => {
    const close = (event: PointerEvent) => {
      if (root.current && !root.current.contains(event.target as Node)) {
        setOpen(false);
        root.current.querySelectorAll("details[open]").forEach((node) => node.removeAttribute("open"));
      }
    };
    document.addEventListener("pointerdown", close);
    return () => document.removeEventListener("pointerdown", close);
  }, []);

  // While the sheet is open the page behind it does not scroll.
  useEffect(() => {
    if (!open) return;
    const previous = document.documentElement.style.overflow;
    document.documentElement.style.overflow = "hidden";
    return () => { document.documentElement.style.overflow = previous; };
  }, [open]);

  return <header ref={root} className="header-dock" data-compact={compact} data-open={open} data-scrolled={scrolled} onKeyDown={(event) => {
    if (event.key === "Tab" && open && root.current) {
      // Keep keyboard focus inside the open sheet.
      const focusable = Array.from(root.current.querySelectorAll<HTMLElement>(".site-header a[href], .site-header button, .site-header summary")).filter((el) => el.offsetParent !== null);
      const first = focusable[0], last = focusable.at(-1);
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
      return;
    }
    if (event.key !== "Escape") return;
    const details = (event.target as HTMLElement).closest("details[open]");
    if (details) { details.removeAttribute("open"); details.querySelector("summary")?.focus(); }
    else if (open) { setOpen(false); toggle.current?.focus(); }
  }}>
    <div className="site-header">
      <div ref={brand} className="header-brand">{logo}</div>
      <div className="nav-panel" id={id} onClick={(event) => { if ((event.target as HTMLElement).closest("a")) setOpen(false); }}>
        {navigation}
        <div className="header-actions">{actions}</div>
      </div>
      <button ref={toggle} type="button" className="menu-toggle" aria-expanded={open} aria-controls={id} aria-label={sq ? (open ? "Mbyll menynë" : "Hap menynë") : (open ? "Close menu" : "Open menu")} onClick={() => setOpen(!open)}>
        <span>{open ? (sq ? "Mbyll" : "Close") : (sq ? "Meny" : "Menu")}</span>
        <span className="menu-icon" aria-hidden="true"><i /><i /></span>
      </button>
    </div>
    <span className="header-rule" aria-hidden="true" />
    <div ref={measure} className="nav-measure" aria-hidden="true" inert>{navigation}<div className="header-actions">{actions}</div></div>
  </header>;
}
