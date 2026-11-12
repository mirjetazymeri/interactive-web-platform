/** The ring needs enough slots that a card wrapping around always does so out of sight (behind the ring). */
export const ORBIT_MIN_SLOTS = 16;
/** Auto-rotation speed, in slots per second. */
const AUTO_SPEED = .2;
/** Cards fade out between these angles (degrees from the front) and are hidden beyond. */
const FADE_FROM = 56, HIDE_AT = 80;

/** How many times the project list is repeated to fill the ring. */
export function orbitRepeats(count: number, minimum = ORBIT_MIN_SLOTS) {
  return count > 0 ? Math.max(1, Math.ceil(minimum / count)) : 0;
}

/** Signed angle (degrees) of a slot around the ring, in (-total/2, total/2] slots of `step`. */
export function orbitAngle(slot: number, offset: number, total: number, step: number) {
  let delta = ((slot - offset) % total + total) % total;
  if (delta > total / 2) delta -= total;
  return delta * step;
}

/** Shortest signed distance (in slots) from `offset` to `slot` around the ring. */
export function orbitDistance(slot: number, offset: number, total: number) {
  return orbitAngle(slot, offset, total, 1);
}

/** Radius that keeps `spacing` px between card centres at the given angular step. */
export function orbitRadius(spacing: number, step: number) {
  return spacing / (2 * Math.sin(step * Math.PI / 360));
}

/**
 * Projects a point of the ring (angle in degrees, `y` px below the card centres) onto the stage.
 * The ring's front touches the screen plane; the camera sits `camera` px above the card centres.
 * Cards are placed with this 2D projection instead of a shared 3D scene, so an enlarged card can never cut through its neighbours.
 */
export function orbitProject(angle: number, radius: number, perspective: number, camera: number, y = 0) {
  const theta = angle * Math.PI / 180;
  const depth = radius * (1 - Math.cos(theta));
  const scale = perspective / (perspective + depth);
  return { x: radius * Math.sin(theta) * scale, y: (y + camera) * scale - camera, scale, depth };
}

/** SVG path of the floor ring from one angle to another, in either direction. */
export function orbitFloorPath(radius: number, perspective: number, camera: number, y: number, from: number, to: number) {
  const points: string[] = [], step = from <= to ? 3 : -3;
  for (let angle = from; step > 0 ? angle <= to + 1e-9 : angle >= to - 1e-9; angle += step) {
    const p = orbitProject(angle, radius, perspective, camera, y);
    points.push(`${points.length ? "L" : "M"}${p.x.toFixed(1)} ${p.y.toFixed(1)}`);
  }
  return points.join("");
}

export function enhanceProjectsVertical(root: HTMLElement) {
  const stage = root.querySelector<HTMLElement>("[data-orbit-stage]")!;
  const cards = [...root.querySelectorAll<HTMLElement>("[data-orbit-card]")];
  const floorFront = root.querySelectorAll<SVGPathElement>("[data-orbit-floor=front]");
  const floorBack = root.querySelectorAll<SVGPathElement>("[data-orbit-floor=back]");
  const rimArcs = [...root.querySelectorAll<SVGPathElement>("[data-orbit-rim] path")];
  const fade = root.querySelector<SVGLinearGradientElement>("[data-orbit-fade]");
  const caption = root.querySelector<HTMLElement>("[data-orbit-caption]");
  const title = root.querySelector<HTMLElement>("[data-orbit-title]");
  const subtitle = root.querySelector<HTMLElement>("[data-orbit-subtitle]");
  const current = root.querySelector<HTMLElement>("[data-orbit-current]");
  const ticks = [...root.querySelectorAll<HTMLElement>("[data-orbit-tick]")];
  const [previous, next] = root.querySelectorAll<HTMLButtonElement>("[data-orbit-step]");
  const total = cards.length;
  if (!total) return;

  const reduced = matchMedia("(prefers-reduced-motion: reduce)");
  const emphasis = cards.map(() => 0);
  let step = 15, radius = 1000, perspective = 1600, camera = 80, slotPx = 300, cardHeight = 400;
  let offset = 0, velocity = 0, target: number | undefined, visible = true, resumeAt = 0;
  let active: HTMLElement | undefined, shown: HTMLElement | undefined, leaveTimer = 0;
  let drag: { x: number; offset: number; time: number; moved: boolean; last: number; vx: number } | undefined;
  let suppressClick = false, frame = 0, previousTime = performance.now();

  const measure = () => {
    const style = getComputedStyle(root);
    step = parseFloat(style.getPropertyValue("--orbit-step")) || 15;
    const width = cards[0].offsetWidth || 260, height = cards[0].offsetHeight || width * 1.25;
    cardHeight = height;
    slotPx = width * 1.1;
    radius = orbitRadius(slotPx, step);
    perspective = radius * 1.35;
    camera = height * .3;
    // Cards stand on the platform: its top edge is just under them and its lit face drops below.
    const floorY = height / 2 + 5, rim = height * .13;
    // The mirror floor starts below the platform face, so reflections begin under the band.
    cards.forEach((card) => card.style.setProperty("-webkit-box-reflect", `below ${Math.round(2 * (5 + rim))}px linear-gradient(transparent 35%,rgb(0 0 0 / .45))`));
    const path = (y: number, from: number, to: number) => orbitFloorPath(radius, perspective, camera, y, from, to);
    // The front arc is drawn over the reflections; its ends curve up behind the side cards, so they belong to the back layer.
    floorFront.forEach((p) => p.setAttribute("d", path(floorY, -80, 80)));
    floorBack.forEach((p) => p.setAttribute("d", `${path(floorY, 80, 180)}${path(floorY, -180, -80).replace("M", "L")}`));
    // The platform's lit edge: parallel arcs below the ring line, fading downwards, each in true perspective.
    // Perspective pulls far points inwards, so the arc's widest point is not always at its ends.
    const reach = Math.max(...Array.from({ length: 81 }, (_, a) => orbitProject(a, radius, perspective, camera, floorY).x));
    fade?.setAttribute("x1", String(-reach)); fade?.setAttribute("x2", String(reach));
    rimArcs.forEach((p, i) => { p.setAttribute("d", path(floorY + rim * (i + .5) / rimArcs.length, -80, 80)); p.style.strokeWidth = `${rim / rimArcs.length + 1.5}px`; });
  };

  const front = () => cards[Math.round(offset) % total];

  const updateCaption = () => {
    const card = active ?? front();
    if (card === shown) return;
    const first = !shown;
    shown = card;
    if (title) title.textContent = card.dataset.title ?? "";
    if (subtitle) subtitle.textContent = card.dataset.subtitle ?? "";
    const index = Number(card.dataset.index);
    if (current) current.textContent = String(index + 1).padStart(2, "0");
    ticks.forEach((tick, i) => tick.toggleAttribute("data-current", i === index));
    cards.forEach((c) => c.toggleAttribute("data-front", c === card));
    if (!first && caption && !reduced.matches) caption.animate([{ opacity: 0, transform: "translateY(10px)" }, { opacity: 1, transform: "none" }], { duration: 520, easing: "cubic-bezier(.22,1,.36,1)" });
  };

  const render = (dt: number) => {
    cards.forEach((card, i) => {
      const goal = card === active ? 1 : 0;
      emphasis[i] += (goal - emphasis[i]) * (reduced.matches ? 1 : Math.min(1, dt * 9));
      const e = emphasis[i];
      const angle = orbitAngle(i, offset, total, step);
      const hidden = Math.abs(angle) > HIDE_AT && e < .01;
      card.toggleAttribute("data-hidden", hidden);
      if (hidden) return;
      const p = orbitProject(angle, radius, perspective, camera);
      // The opened card turns to face the viewer and comes forward over its neighbours.
      const scale = p.scale * (1 + .14 * e);
      const turn = angle * .6 * (1 - e);
      card.style.transform = `translate(-50%,-50%) translate(${p.x.toFixed(2)}px,${(p.y - cardHeight * .1 * e).toFixed(2)}px) scale(${scale.toFixed(4)}) perspective(1200px) rotateY(${turn.toFixed(2)}deg)`;
      card.style.zIndex = String(Math.round(2000 - p.depth + 4000 * e));
      card.style.opacity = String(Math.max(e, Math.min(1, (HIDE_AT - Math.abs(angle)) / (HIDE_AT - FADE_FROM))));
      card.style.setProperty("--orbit-shade", Math.max(0, Math.min(.7, (1 - p.scale) * 1.6)) * (1 - e) + "");
    });
    updateCaption();
  };

  const bringToFront = (card: HTMLElement) => {
    target = offset + orbitDistance(cards.indexOf(card), offset, total);
  };

  const tick = (time: number) => {
    const dt = Math.min(.05, (time - previousTime) / 1000);
    previousTime = time;
    if (!drag) {
      if (target !== undefined) {
        const delta = target - offset;
        offset += delta * (reduced.matches ? 1 : Math.min(1, dt * 6));
        velocity = 0;
        if (Math.abs(delta) < .001) { offset = target; target = undefined; }
      } else {
        const paused = active || reduced.matches || time < resumeAt;
        velocity += ((paused ? 0 : AUTO_SPEED) - velocity) * Math.min(1, dt * (paused ? 8 : 1.2));
        offset += velocity * dt;
      }
    }
    // Keep the offset in one turn of the ring, moving a pending target with it.
    const wrapped = (offset % total + total) % total;
    if (target !== undefined) target += wrapped - offset;
    offset = wrapped;
    render(dt);
    frame = visible ? requestAnimationFrame(tick) : 0;
  };
  const start = () => { if (!frame && visible) { previousTime = performance.now(); frame = requestAnimationFrame(tick); } };

  const setActive = (card: HTMLElement | undefined) => {
    clearTimeout(leaveTimer);
    if (active === card) return;
    active?.removeAttribute("data-active");
    active = card;
    card?.setAttribute("data-active", "");
    root.toggleAttribute("data-orbit-engaged", !!card);
  };

  const listeners: [EventTarget, string, EventListener, AddEventListenerOptions?][] = [];
  const on = <E extends Event>(el: EventTarget, type: string, fn: (event: E) => void, options?: AddEventListenerOptions) => {
    el.addEventListener(type, fn as EventListener, options);
    listeners.push([el, type, fn as EventListener, options]);
  };

  cards.forEach((card) => {
    on<PointerEvent>(card, "pointerenter", (event) => { if (event.pointerType === "mouse" && !drag?.moved) setActive(card); });
    // A short grace period stops the opened card flickering while the pointer crosses a gap between cards.
    on<PointerEvent>(card, "pointerleave", (event) => {
      if (event.pointerType !== "mouse" || active !== card) return;
      clearTimeout(leaveTimer);
      leaveTimer = window.setTimeout(() => { if (active === card) { setActive(undefined); resumeAt = performance.now() + 700; } }, 160);
    });
    on<FocusEvent>(card, "focusin", () => { setActive(card); bringToFront(card); });
    on<FocusEvent>(card, "focusout", (event) => { if (!card.contains(event.relatedTarget as Node)) setActive(undefined); });
    // Touch has no hover: the first tap opens a card's details, the second follows its link.
    on<MouseEvent>(card, "click", (event) => {
      if (suppressClick) { event.preventDefault(); event.stopPropagation(); return; }
      if (active !== card) { event.preventDefault(); setActive(card); bringToFront(card); resumeAt = performance.now() + 4000; }
    }, { capture: true });
  });

  on<PointerEvent>(stage, "pointerdown", (event) => {
    if (event.button !== 0) return;
    suppressClick = false;
    drag = { x: event.clientX, offset, time: event.timeStamp, moved: false, last: event.clientX, vx: 0 };
  });
  on<PointerEvent>(stage, "pointermove", (event) => {
    if (!drag) return;
    const dx = event.clientX - drag.x;
    if (!drag.moved && Math.abs(dx) > 6) { drag.moved = true; target = undefined; stage.setPointerCapture(event.pointerId); setActive(undefined); root.setAttribute("data-orbit-dragging", ""); }
    if (!drag.moved) return;
    const dt = Math.max(1, event.timeStamp - drag.time);
    drag.vx = drag.vx * .6 + (event.clientX - drag.last) / dt * 400;
    drag.last = event.clientX; drag.time = event.timeStamp;
    offset = drag.offset - dx / slotPx;
  });
  const release = () => {
    if (!drag) return;
    if (drag.moved) { suppressClick = true; velocity = -drag.vx / slotPx; resumeAt = performance.now() + 1800; setTimeout(() => { suppressClick = false; }, 0); }
    drag = undefined;
    root.removeAttribute("data-orbit-dragging");
  };
  on(stage, "pointerup", release);
  on(stage, "pointercancel", release);
  on<DragEvent>(stage, "dragstart", (event) => event.preventDefault());

  const stepBy = (direction: number) => {
    setActive(undefined);
    target = Math.round(target ?? offset) + direction;
    resumeAt = performance.now() + 4000;
  };
  if (previous) on(previous, "click", () => stepBy(-1));
  if (next) on(next, "click", () => stepBy(1));
  on<KeyboardEvent>(stage, "keydown", (event) => {
    if (event.key === "ArrowLeft") { event.preventDefault(); stepBy(-1); }
    if (event.key === "ArrowRight") { event.preventDefault(); stepBy(1); }
  });

  const resize = new ResizeObserver(() => { measure(); render(1); });
  resize.observe(root);
  const intersection = new IntersectionObserver(([entry]) => { visible = entry.isIntersecting && !document.hidden; start(); });
  intersection.observe(root);
  on(document, "visibilitychange", () => { visible = !document.hidden; start(); });

  measure();
  render(1);
  root.setAttribute("data-orbit-ready", "");
  start();

  return () => {
    cancelAnimationFrame(frame);
    clearTimeout(leaveTimer);
    resize.disconnect();
    intersection.disconnect();
    listeners.forEach(([el, type, fn, options]) => el.removeEventListener(type, fn, options));
    root.removeAttribute("data-orbit-ready");
  };
}
