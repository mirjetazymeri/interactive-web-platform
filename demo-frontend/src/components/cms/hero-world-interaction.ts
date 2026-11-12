import { assignLinks, curveDistance, curvePath, curvePoint, damp, linkCurve, magnet, parallax, type Curve, type Point } from "@/lib/cms/hero-world";

/**
 * The connected-hero layer: a damped pointer glow, a few thin curved lines
 * from the copy to visible service markers (with a slow signal dot), marker
 * proximity, a magnetic CTA and a small globe parallax. It owns no animation
 * loop: the globe's frame loop calls `step` and `draw`, so every effect moves
 * in the same frame as the globe. All of it is decorative and pointer-inert.
 */
export type Interaction = ReturnType<typeof createInteraction>;

type Slot = { service: number | null; alpha: number; curve: Curve | null; phase: number; group: SVGGElement; path: SVGPathElement; signal: SVGCircleElement };
type Box = { left: number; top: number; width: number; height: number };

const LINK_REST = 0.14;
const NEAR_MARKER = 80;
const NEAR_LINE = 60;

export function createInteraction(container: HTMLElement, stage: HTMLElement, reduced: MediaQueryList) {
  const section = container.closest("section") ?? container;
  const ambient = container.querySelector<HTMLElement>("[data-world-ambient]");
  const cta = container.querySelector<HTMLElement>("[data-world-cta]");
  const copy = container.querySelector<HTMLElement>("[data-world-copy]");
  const slots: Slot[] = Array.from(container.querySelectorAll<SVGGElement>("[data-world-link]"), (group, i) => ({
    service: null, alpha: 0, curve: null, phase: i / 3, group,
    path: group.querySelector("path")!, signal: group.querySelector("circle")!,
  }));
  const finePointer = window.matchMedia("(hover: hover) and (pointer: fine)");
  const motion = () => finePointer.matches && !reduced.matches;

  // Geometry relative to the container, re-measured on resize only (no per-frame layout reads).
  let stageOffset: Point = { x: 0, y: 0 }, stageSize = 0, origins: Point[] = [], ctaBox: Box | null = null, sideBySide = false;
  function measure() {
    const base = container.getBoundingClientRect();
    const rel = (r: DOMRect): Box => ({ left: r.left - base.left, top: r.top - base.top, width: r.width, height: r.height });
    const s = rel(stage.getBoundingClientRect());
    stageOffset = { x: s.left, y: s.top };
    stageSize = s.width;
    const copyBox = copy ? rel(copy.getBoundingClientRect()) : null;
    sideBySide = !!copyBox && s.left > copyBox.left + copyBox.width - 1;
    const anchors = [copy?.querySelector(".hero-title-visual"), copy?.querySelector("p"), cta].filter((el): el is Element => !!el);
    origins = anchors.map((el) => { const r = rel(el.getBoundingClientRect()); return { x: r.left + r.width + 18, y: r.top + r.height / 2 }; });
    ctaBox = cta ? rel(cta.getBoundingClientRect()) : null;
  }

  // Pointer target in container coordinates; the frame loop eases toward it.
  let target: Point | null = null, cursor: Point | null = null, presence = 0, ctaBoost = 0;
  const tilt = { theta: 0, phi: 0 }, pull = { x: 0, y: 0 };
  const move = (event: PointerEvent) => {
    if (event.pointerType === "touch" || !motion()) return;
    const base = container.getBoundingClientRect();
    target = { x: event.clientX - base.left, y: event.clientY - base.top };
    if (!cursor) cursor = { ...target };
  };
  const leave = () => { target = null; };
  section.addEventListener("pointermove", move, { passive: true });
  section.addEventListener("pointerleave", leave);

  /** Advances eased pointer state; returns the parallax offsets for this frame. */
  function step(elapsed: number) {
    const active = !!target && motion();
    presence = damp(presence, active ? 1 : 0, elapsed, 350);
    if (target && cursor) cursor = { x: damp(cursor.x, target.x, elapsed, 140), y: damp(cursor.y, target.y, elapsed, 140) };
    if (!active && presence < 0.002) cursor = null;
    // Parallax relative to the globe's centre; it eases back to zero when the pointer leaves.
    const centre = { x: stageOffset.x + stageSize / 2, y: stageOffset.y + stageSize / 2 };
    const goal = active && target && stageSize ? parallax((target.x - centre.x) / container.clientWidth, (target.y - centre.y) / (stageSize / 2)) : { theta: 0, phi: 0 };
    tilt.theta = damp(tilt.theta, goal.theta, elapsed, 700);
    tilt.phi = damp(tilt.phi, goal.phi, elapsed, 700);
    const m = active && target && ctaBox ? magnet(target, ctaBox) : { x: 0, y: 0 };
    pull.x = damp(pull.x, m.x, elapsed, 110);
    pull.y = damp(pull.y, m.y, elapsed, 110);
    ctaBoost = damp(ctaBoost, m.x || m.y ? 1 : 0, elapsed, 250);
    if (cta) cta.style.translate = Math.abs(pull.x) + Math.abs(pull.y) > 0.05 ? `${pull.x.toFixed(2)}px ${pull.y.toFixed(2)}px` : "";
    if (ambient) {
      ambient.style.opacity = (presence * 0.9).toFixed(3);
      if (cursor) ambient.style.transform = `translate3d(${cursor.x.toFixed(1)}px,${cursor.y.toFixed(1)}px,0) translate(-50%,-50%)`;
    }
    return tilt;
  }

  /** Cursor in stage coordinates (eased), for marker proximity. */
  const stageCursor = () => cursor && presence > 0.05 ? { x: cursor.x - stageOffset.x, y: cursor.y - stageOffset.y } : null;
  function nearMarker(point: Point) {
    const c = stageCursor();
    return !!c && Math.hypot(c.x - point.x, c.y - point.y) < NEAR_MARKER;
  }
  /** How close the pointer is to the globe (0–1), for a slight dot brightening. */
  function globeProximity() {
    const c = stageCursor();
    if (!c || !stageSize) return 0;
    const d = Math.hypot(c.x - stageSize / 2, c.y - stageSize / 2) / (stageSize * 0.55);
    return presence * Math.max(0, 1 - d);
  }

  /** Redraws the connection lines toward `markers` (visible service markers, stage coordinates). */
  function draw(markers: ReadonlyMap<number, Point>, near: ReadonlySet<number>, elapsed: number) {
    if (!sideBySide || !origins.length) { for (const slot of slots) slot.group.style.opacity = "0"; return; }
    const visible = [...markers].map(([index, p]) => ({ index, x: p.x }));
    const released = new Set(slots.flatMap((slot, i) => slot.service !== null && !markers.has(slot.service) ? [i] : []));
    const assigned = assignLinks(slots.map((slot, i) => released.has(i) ? null : slot.service), visible, released);
    slots.forEach((slot, i) => {
      const origin = origins[i % origins.length];
      if (released.has(i)) {
        // The marker left: fade out where it was, then free the line.
        slot.alpha = damp(slot.alpha, 0, elapsed, 160);
        if (slot.alpha < 0.02) { slot.service = null; slot.curve = null; slot.alpha = 0; }
      } else {
        const service = assigned[i];
        if (service !== slot.service) { slot.service = service; slot.alpha = 0; }
        const p = service !== null ? markers.get(service) : undefined;
        slot.curve = p ? linkCurve(origin, { x: p.x + stageOffset.x, y: p.y + stageOffset.y }) : null;
        let goal = 0;
        if (slot.curve) {
          const lineNear = cursor && presence > 0.05 ? Math.max(0, 1 - curveDistance(slot.curve, cursor) / NEAR_LINE) : 0;
          goal = Math.min(0.85, LINK_REST + presence * 0.2 + lineNear * 0.35 + (service !== null && near.has(service) ? 0.3 : 0) + ctaBoost * 0.22);
        }
        slot.alpha = damp(slot.alpha, goal, elapsed, 160);
      }
      slot.group.style.opacity = slot.alpha.toFixed(3);
      if (!slot.curve || slot.alpha < 0.005) return;
      slot.path.setAttribute("d", curvePath(slot.curve));
      if (reduced.matches) { slot.signal.style.display = "none"; return; }
      // A faint signal travels slowly from the copy to the globe.
      slot.phase = (slot.phase + elapsed / 7000) % 1;
      const q = curvePoint(slot.curve, slot.phase);
      slot.signal.setAttribute("cx", q.x.toFixed(1));
      slot.signal.setAttribute("cy", q.y.toFixed(1));
      slot.signal.style.opacity = (Math.sin(slot.phase * Math.PI) * 0.9).toFixed(3);
    });
  }

  measure();
  const resize = new ResizeObserver(measure);
  resize.observe(container);
  // The title's letter entrance changes its painted box; measure again once it settles.
  const settle = window.setTimeout(measure, 1600);
  document.fonts?.addEventListener("loadingdone", measure);

  return {
    step, draw, nearMarker, globeProximity, measure,
    dispose() {
      section.removeEventListener("pointermove", move);
      section.removeEventListener("pointerleave", leave);
      resize.disconnect();
      clearTimeout(settle);
      document.fonts?.removeEventListener("loadingdone", measure);
      if (cta) cta.style.translate = "";
      if (ambient) ambient.style.opacity = "0";
    },
  };
}
