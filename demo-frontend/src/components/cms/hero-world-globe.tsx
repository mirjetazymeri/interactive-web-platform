"use client";

import { useEffect, useRef, useState } from "react";
import { globePoint, rgbChannels, shade } from "@/lib/cms/hero-globe";
import { advancePhi, createPresentation, initialStagger, labelLimit, layoutLabels, mapSamples, stepPresentation, type HeroWorldService, type LabelCandidate, type Presentation } from "@/lib/cms/hero-world";
import { HeroWorldIconSvg } from "./hero-world-icon";
import { createInteraction, type Interaction } from "./hero-world-interaction";
import styles from "./hero-world.module.css";

type Rgb = [number, number, number];
type Globe = { toggle(play?: boolean): void; destroy(): void };

const THETA = 0.28;

/** Resolves a theme custom property (any CSS color) to 0–1 RGB through a 1px canvas. */
function cssColor(element: HTMLElement, property: string): Rgb | null {
  const raw = getComputedStyle(element).getPropertyValue(property).trim();
  if (!raw) return null;
  const context = document.createElement("canvas").getContext("2d", { willReadFrequently: true });
  if (!context) return rgbChannels(raw);
  context.fillStyle = raw;
  context.fillRect(0, 0, 1, 1);
  const [r, g, b] = context.getImageData(0, 0, 1, 1).data;
  return [r / 255, g / 255, b / 255];
}
const luminance = ([r, g, b]: Rgb) => 0.2126 * r + 0.7152 * g + 0.0722 * b;
const scale = ([r, g, b]: Rgb, k: number): Rgb => [r * k, g * k, b * k];

/**
 * Globe paint, derived from the API theme tokens on every read: dots in the
 * accent hue, markers in the readable secondary action color, glow from the
 * accent. A dark canvas gets a dark sphere with bright dots, a light canvas
 * the inverse, so the globe never fights the configured background.
 */
function palette(element: HTMLElement) {
  const background = cssColor(element, "--surface-background");
  const accent = cssColor(element, "--color-accent");
  const marker = cssColor(element, "--secondary-action") ?? accent;
  if (!background || !accent || !marker) return null;
  return luminance(background) < 0.4
    ? { dark: 1, baseColor: scale(shade(accent, 0.15), 0.24), markerColor: marker, glowColor: scale(accent, 0.55), mapBrightness: 5, diffuse: 1.2, mapBaseBrightness: 0.03 }
    : { dark: 0, baseColor: shade(accent, 0.86), markerColor: marker, glowColor: shade(accent, 0.6), mapBrightness: 6, diffuse: 1.1, mapBaseBrightness: 0 };
}

// Europe and Africa face the viewer first, then the globe turns eastwards.
const START_PHI = -Math.PI / 2 - 0.35;

/**
 * Decorative WebGL globe with service labels. Rendering, rotation and label
 * placement run outside React (direct DOM writes per frame); React only
 * renders the content, so translated text updates never restart the globe.
 *
 * Labels follow the presentation in src/lib/cms/hero-world.ts: each rides a
 * real anchor on the turning sphere, and services take turns one out, one in,
 * so the globe always shows N − 1 labels (within what the stage fits). Every
 * pin stays mounted per service id; only data attributes change.
 */
export function HeroWorldGlobe({ services, label }: { services: HeroWorldService[]; label: string }) {
  const stage = useRef<HTMLDivElement>(null);
  const canvas = useRef<HTMLCanvasElement>(null);
  const servicesRef = useRef(services);
  const highlightRef = useRef(-1);
  const sync = useRef<{ measure: boolean; place(): void }>({ measure: true, place() {} });
  const [selected, setSelected] = useState<string | null>(null);
  const [hovered, setHovered] = useState<string | null>(null);
  const highlightId = hovered ?? selected;

  // New text needs new chip measurements.
  useEffect(() => { servicesRef.current = services; sync.current.measure = true; sync.current.place(); }, [services]);
  useEffect(() => {
    highlightRef.current = services.findIndex((s) => s.id === highlightId);
    sync.current.place();
  }, [highlightId, services]);

  useEffect(() => {
    const box = stage.current, element = canvas.current, hooks = sync.current;
    if (!box || !element) return;
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)");
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    let size = box.clientWidth, phi = START_PHI, last = performance.now();
    let onScreen = false, disposed = false, recolor = false, globe: Globe | null = null, colors = palette(box);
    let shown = new Map<number, { side: "start" | "end"; dy: number }>();
    let pins: HTMLElement[] = [], widths: number[] = [], heights: number[] = [];
    let presentation: Presentation | null = null, settle = 0;
    // Pointer parallax offsets this frame (eased by the interaction layer; zero at rest).
    let view = { theta: 0, phi: 0 }, frameElapsed = 16;
    const container = box.closest<HTMLElement>("[data-world-container]");
    const interaction: Interaction | null = container ? createInteraction(container, box, reduced) : null;

    function place() {
      if (!size) return;
      const current = servicesRef.current, now = performance.now(), capacity = labelLimit(size);
      if (hooks.measure || pins.length !== current.length) {
        pins = Array.from(box!.querySelectorAll<HTMLElement>("[data-world-pin]"));
        const chips = pins.map((pin) => pin.querySelector<HTMLElement>("[data-world-chip]"));
        widths = chips.map((chip) => chip?.offsetWidth ?? 0);
        heights = chips.map((chip) => chip?.offsetHeight ?? 0);
        hooks.measure = false;
      }
      // The opening population appears as soon as the globe is on screen.
      if (!presentation && !onScreen) return;
      presentation = presentation
        ? stepPresentation(presentation, current.length, capacity, phi, now)
        : createPresentation(current.length, capacity, phi, now);
      if (!globe && !settle) {
        // Without a render loop, place once more after the staggered entrance.
        settle = window.setTimeout(() => { settle = 0; place(); }, presentation.entries.length * initialStagger + 30);
      }
      const byService = new Map(presentation.entries.map((entry) => [entry.service, entry]));
      const candidates: LabelCandidate[] = [];
      const points = current.map((_, index) => {
        const entry = byService.get(index);
        if (!entry) return null;
        const point = globePoint(entry.lat, entry.lng, phi + view.phi, THETA + view.theta);
        if (entry.leftAt === null && now >= entry.shownAt) candidates.push({ index, x: point.x * size, y: point.y * size, depth: point.depth, width: widths[index] ?? 0, height: heights[index] ?? 0 });
        return point;
      });
      const placed = layoutLabels(candidates, { width: size, height: size }, capacity, shown, highlightRef.current);
      shown = new Map(placed.map((label) => [label.index, { side: label.side, dy: label.dy }]));
      const markers = new Map<number, { x: number; y: number }>(), near = new Set<number>();
      pins.forEach((pin, index) => {
        const point = points[index], entry = byService.get(index);
        // "on": entered and current; "off": queued, still staggered, or fading out.
        const active = point && entry && entry.leftAt === null && now >= entry.shownAt ? "on" : "off";
        if (pin.dataset.active !== active) pin.dataset.active = active;
        if (!point) return;
        pin.style.transform = `translate3d(${(point.x * size).toFixed(1)}px,${(point.y * size).toFixed(1)}px,0)`;
        pin.style.setProperty("--pin-depth", Math.max(0, Math.min(1, (point.depth - 0.04) / 0.26)).toFixed(3));
        const side = shown.get(index)?.side;
        // An outgoing label keeps its chip while the pin fades out.
        const state = side ? "shown" : entry && entry.leftAt !== null && pin.dataset.label === "shown" ? "shown" : "hidden";
        if (pin.dataset.label !== state) pin.dataset.label = state;
        if (side && pin.dataset.side !== side) pin.dataset.side = side;
        const dy = `${shown.get(index)?.dy ?? 0}px`;
        if (side && pin.style.getPropertyValue("--label-dy") !== dy) pin.style.setProperty("--label-dy", dy);
        const highlighted = index === highlightRef.current ? "true" : "false";
        if (pin.dataset.highlighted !== highlighted) pin.dataset.highlighted = highlighted;
        // Visible markers feed the connection lines and react to a nearby pointer.
        if (active === "on" && state === "shown") {
          const marker = { x: point.x * size, y: point.y * size };
          if (interaction?.nearMarker(marker)) near.add(index);
          // Lines join markers whose label opens away from the copy, so a line never crosses its own label.
          if (side === "end") markers.set(index, marker);
        }
        const isNear = near.has(index) ? "true" : "false";
        if (pin.dataset.near !== isNear) pin.dataset.near = isNear;
      });
      interaction?.draw(markers, near, frameElapsed);
    }
    // Outside the render loop (static, paused, fallback) changes are placed directly.
    hooks.place = () => { if (!globe || reduced.matches || !onScreen) place(); };

    const fallback = () => { box.dataset.globeFallback = "true"; globe?.destroy(); globe = null; place(); };
    const resize = new ResizeObserver(() => { size = box.clientWidth; hooks.measure = true; hooks.place(); });
    resize.observe(box);
    const toggle = () => { last = performance.now(); globe?.toggle(onScreen && document.visibilityState === "visible"); };
    const visibility = new IntersectionObserver(([entry]) => { onScreen = entry.isIntersecting; toggle(); hooks.place(); }, { rootMargin: "80px" });
    visibility.observe(box);
    document.addEventListener("visibilitychange", toggle);
    // Theme tokens live on <html>; repaint when they change, without a rebuild.
    const theme = new MutationObserver(() => { colors = palette(box) ?? colors; recolor = true; });
    theme.observe(document.documentElement, { attributes: true, attributeFilter: ["style", "class", "data-theme"] });
    const fonts = () => { hooks.measure = true; hooks.place(); interaction?.measure(); };
    document.fonts?.addEventListener("loadingdone", fonts);
    const lost = (event: Event) => { event.preventDefault(); fallback(); };
    element.addEventListener("webglcontextlost", lost);

    if (!colors) fallback();
    else import("cobe").then(({ default: createGlobe }) => {
      if (disposed || !colors) return;
      // Service markers are DOM pins on their anchors, so the sphere itself draws none.
      globe = createGlobe(element, {
        devicePixelRatio: dpr, width: size * dpr, height: size * dpr, phi, theta: THETA,
        mapSamples: mapSamples(size), ...colors, markers: [],
        onRender(state) {
          const now = performance.now();
          frameElapsed = Math.min(64, now - last);
          if (!reduced.matches) phi = advancePhi(phi, now - last);
          last = now;
          // One loop for everything: pointer easing, parallax, labels and lines.
          if (interaction) view = interaction.step(frameElapsed);
          place();
          state.phi = phi + view.phi;
          state.theta = THETA + view.theta;
          if (colors) state.mapBrightness = colors.mapBrightness * (1 + 0.15 * (interaction?.globeProximity() ?? 0));
          state.width = size * dpr;
          state.height = size * dpr;
          if (recolor && colors) { Object.assign(state, colors); recolor = false; }
        },
      }) as Globe;
      box.dataset.globeReady = "true";
      toggle();
    }).catch(fallback);

    return () => {
      disposed = true;
      resize.disconnect(); visibility.disconnect(); theme.disconnect();
      document.removeEventListener("visibilitychange", toggle);
      document.fonts?.removeEventListener("loadingdone", fonts);
      element.removeEventListener("webglcontextlost", lost);
      clearTimeout(settle);
      hooks.place = () => {};
      interaction?.dispose();
      globe?.destroy();
    };
  }, []);

  return <div className={styles.globe}>
    <div ref={stage} className={styles.stage}>
      <canvas ref={canvas} className={styles.canvas} aria-hidden="true" />
      <div className={styles.pins} aria-hidden="true">
        {services.map((service) => <div key={service.id} className={styles.pin} data-world-pin data-active="off" data-label="hidden" data-side="end">
          <span className={styles.marker} />
          <span className={styles.chip} data-world-chip onPointerEnter={(e) => { if (e.pointerType === "mouse") setHovered(service.id); }} onPointerLeave={() => setHovered(null)} onClick={() => setSelected((id) => id === service.id ? null : service.id)}>
            <span className={styles.chipIcon}><HeroWorldIconSvg icon={service.icon} /></span>
            <span className={styles.chipText}>
              <span className={styles.chipTitle}>{service.title}</span>
              {service.description && <span className={styles.chipDescription}>{service.description}</span>}
            </span>
          </span>
        </div>)}
      </div>
    </div>
    {/* The animated pins are decorative; this list carries the same content for assistive technology. */}
    {services.length > 0 && <ul className="sr-only" aria-label={label}>
      {services.map((service) => <li key={service.id}>{service.title}{service.description && <>: {service.description}</>}</li>)}
    </ul>}
  </div>;
}
