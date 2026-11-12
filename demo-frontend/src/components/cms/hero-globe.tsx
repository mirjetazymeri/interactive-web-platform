"use client";

import { useEffect, useRef, useState } from "react";
import createGlobe from "cobe";
import { advancePins, globeMarkers, globePoint, labelInterval, minRowGap, rgbChannels, shade, visibleLabels, type GlobePin } from "@/lib/cms/hero-globe";

const THETA = 0.3;
// Europe starts just east of centre and drifts across as the globe turns.
const START_PHI = -Math.PI / 2 - 0.7;
const SPEED = 0.12; // radians per second: one calm turn in under a minute
const CHIP_ROW = 46; // px: a chip plus breathing room

/** Resolves any CSS color (including theme custom properties) to 0–1 RGB through the canvas. */
function themeColor(element: HTMLElement, property: string): [number, number, number] | null {
  const raw = getComputedStyle(element).getPropertyValue(property).trim();
  if (!raw) return null;
  const context = document.createElement("canvas").getContext("2d");
  if (!context) return rgbChannels(raw);
  context.fillStyle = raw;
  context.fillRect(0, 0, 1, 1);
  const [r, g, b] = context.getImageData(0, 0, 1, 1).data;
  return [r / 255, g / 255, b / 255];
}

function initialPins(labels: readonly string[]) {
  let pins: GlobePin[] = [];
  for (let step = 0; step < Math.min(visibleLabels, labels.length); step++) pins = advancePins(pins, labels, step, START_PHI, THETA);
  return pins;
}

/**
 * Decorative globe; the labels are also listed for assistive technology in
 * the server HTML. The parent keys this component by its labels, so a new
 * list starts a fresh rotation.
 */
export function HeroGlobe({ labels }: { labels: string[] }) {
  const stage = useRef<HTMLDivElement>(null);
  const canvas = useRef<HTMLCanvasElement>(null);
  const phiRef = useRef(START_PHI);
  const [pins, setPins] = useState(() => initialPins(labels));

  // Globe rendering and per-frame pin placement (direct DOM writes, no React state per frame).
  useEffect(() => {
    const box = stage.current, element = canvas.current;
    if (!box || !element) return;
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)");
    const accent = themeColor(box, "--color-accent");
    const ink = themeColor(box, "--accent-foreground");
    const fallback = () => { box.dataset.globeFallback = "true"; };
    if (!accent || !ink) return fallback();
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    let size = box.clientWidth, last = performance.now(), onScreen = true;
    let globe: ReturnType<typeof createGlobe>;
    try {
      globe = createGlobe(element, {
        devicePixelRatio: dpr, width: size * dpr, height: size * dpr,
        phi: phiRef.current, theta: THETA, dark: 0, diffuse: 1.15,
        mapSamples: 16000, mapBrightness: 5.5, mapBaseBrightness: 0,
        baseColor: shade(accent, 0.22), markerColor: ink, glowColor: shade(accent, 0.45),
        markers: globeMarkers.map((location) => ({ location, size: 0.03 })),
        onRender(state) {
          const now = performance.now();
          if (!reduced.matches) phiRef.current += Math.min(now - last, 64) / 1000 * SPEED;
          last = now;
          state.phi = phiRef.current;
          state.width = size * dpr;
          state.height = size * dpr;
          for (const pin of box.querySelectorAll<HTMLElement>("[data-globe-pin]")) {
            const [lat, lng] = globeMarkers[Number(pin.dataset.globePin)];
            const point = globePoint(lat, lng, phiRef.current, THETA);
            pin.style.transform = `translate3d(${(point.x * size).toFixed(1)}px,${(point.y * size).toFixed(1)}px,0)`;
            // Fade toward the horizon; hidden behind the globe.
            pin.style.setProperty("--pin-visibility", String(Math.max(0, Math.min(1, (point.depth - 0.08) / 0.22))));
            // Chips on the right half open leftwards so they never leave the stage.
            const side = point.x > 0.58 ? "start" : "end";
            if (pin.dataset.side !== side) pin.dataset.side = side;
          }
        },
      });
    } catch {
      return fallback();
    }
    const resize = new ResizeObserver(() => { size = box.clientWidth; });
    resize.observe(box);
    const toggle = () => globe.toggle(onScreen && document.visibilityState === "visible");
    const visibility = new IntersectionObserver(([entry]) => { onScreen = entry.isIntersecting; last = performance.now(); toggle(); });
    visibility.observe(box);
    document.addEventListener("visibilitychange", toggle);
    return () => {
      resize.disconnect(); visibility.disconnect();
      document.removeEventListener("visibilitychange", toggle);
      globe.destroy();
    };
  }, []);

  // Label rotation: every interval the oldest label fades out and the next appears up front.
  useEffect(() => {
    if (labels.length <= 1) return;
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)");
    const timers = new Set<number>();
    let step = Math.min(visibleLabels, labels.length);
    const interval = window.setInterval(() => {
      if (reduced.matches || document.visibilityState !== "visible") return;
      const current = step++;
      const gap = Math.max(minRowGap * .6, CHIP_ROW / Math.max(1, stage.current?.clientWidth ?? 0));
      setPins((pins) => advancePins(pins, labels, current, phiRef.current, THETA, gap));
      const timer = window.setTimeout(() => { timers.delete(timer); setPins((pins) => pins.filter((pin) => !pin.leaving)); }, 450);
      timers.add(timer);
    }, labelInterval);
    return () => { clearInterval(interval); timers.forEach(clearTimeout); };
  }, [labels]);

  return <div ref={stage} className="hero-globe-stage">
    <canvas ref={canvas} className="hero-globe-canvas" aria-hidden="true" />
    <div className="hero-globe-pins" aria-hidden="true">
      {pins.map((pin) => <div key={pin.id} className="hero-globe-pin" data-globe-pin={pin.marker} data-leaving={pin.leaving || undefined}>
        <span className="hero-globe-dot" />
        <span className="hero-globe-chip">
          <span className="hero-globe-chip-icon"><svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round"><path d="M5.5 5 2.5 8l3 3M10.5 5l3 3-3 3" /></svg></span>
          <span>{pin.text}</span>
        </span>
      </div>)}
    </div>
    <ul className="sr-only">{labels.map((text) => <li key={text}>{text}</li>)}</ul>
  </div>;
}
