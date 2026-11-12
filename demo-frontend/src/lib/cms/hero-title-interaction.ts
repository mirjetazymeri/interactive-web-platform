import { revealHeroColor } from "./hero-color";

// Separate inner transforms compose with the outer CSS entrance animation.
// All pointer/animation state stays outside React; only moving letters get writes.
export function heroTitleInteraction(heading: HTMLHeadingElement) {
  const capability = window.matchMedia("(hover: hover) and (pointer: fine)");
  const motion = window.matchMedia("(prefers-reduced-motion: reduce)");
  const video = heading.closest(".hero")?.querySelector<HTMLVideoElement>(".hero-video") ?? null;
  const words = Array.from(heading.querySelectorAll<HTMLElement>(".hero-title-word"));
  let wordBounds: DOMRect[] = [];
  const letters = Array.from(heading.querySelectorAll<HTMLElement>(".hero-letter-motion"), (element) => ({
    element, cx: 0, cy: 0, x: 0, y: 0, rotation: 0,
  }));
  let disposed = false;
  let ready = false;
  let inside = false;
  let touching = false;
  let dirty = true;
  let frame = 0;
  let last = 0;
  let px = 0;
  let py = 0;
  let bounds = { left: 0, top: 0 };
  const enabled = () => ready && (capability.matches || touching) && !motion.matches && !document.hidden;
  function measure() {
    bounds = heading.getBoundingClientRect();
    // Word rectangles bridge character gaps without including the heading's
    // full-width empty margins. They never move with the deformed glyphs.
    wordBounds = words.flatMap((word) => Array.from(word.getClientRects()));
    // Measure the stationary outer spans, never the displaced characters.
    for (const letter of letters) {
      const rect = letter.element.parentElement!.getBoundingClientRect();
      letter.cx = rect.left - bounds.left + rect.width / 2;
      letter.cy = rect.top - bounds.top + rect.height / 2;
    }
    dirty = false;
  }
  function queue() { if (!frame && !disposed && ready) frame = requestAnimationFrame(update); }
  function update(time: number) {
    frame = 0;
    if (dirty) measure();
    const elapsed = last ? Math.min(time - last, 64) : 16;
    last = time;
    const active = inside && enabled();
    revealHeroColor(video, active && wordBounds.some((rect) =>
      px >= rect.left - 6 && px <= rect.right + 6 && py >= rect.top && py <= rect.bottom));
    const alpha = 1 - Math.exp(-elapsed / (active ? 75 : 90));
    let moving = false;
    for (const letter of letters) {
      const dx = letter.cx - (px - bounds.left);
      const dy = letter.cy - (py - bounds.top);
      const distance = Math.hypot(dx, dy);
      const influence = active ? Math.max(0, 1 - distance / 150) ** 2 : 0;
      const targetX = influence * 12 * dx / Math.max(distance, 1);
      const targetY = influence * 12 * dy / Math.max(distance, 1);
      const targetRotation = influence * 5 * dx / Math.max(distance, 1);
      letter.x += (targetX - letter.x) * alpha;
      letter.y += (targetY - letter.y) * alpha;
      letter.rotation += (targetRotation - letter.rotation) * alpha;
      const unsettled = Math.abs(targetX - letter.x) + Math.abs(targetY - letter.y) + Math.abs(targetRotation - letter.rotation) > 0.025;
      moving ||= unsettled;
      if (!unsettled) { letter.x = targetX; letter.y = targetY; letter.rotation = targetRotation; }
      letter.element.style.transform = letter.x || letter.y || letter.rotation
        ? `translate(${letter.x}px, ${letter.y}px) rotate(${letter.rotation}deg)` : "";
    }
    if (moving) queue(); else last = 0;
  }
  function move(event: PointerEvent) {
    if (!enabled() || event.pointerType === "touch") return;
    inside = true;
    px = event.clientX;
    py = event.clientY;
    queue();
  }
  function enter(event: PointerEvent) { dirty = true; move(event); }
  function press(event: PointerEvent) {
    if (event.pointerType !== "touch" && event.pointerType !== "pen") return;
    touching = true;
    if (!enabled()) { touching = false; return; }
    dirty = true; inside = true; px = event.clientX; py = event.clientY; queue();
  }
  function leave() { touching = false; inside = false; revealHeroColor(video, false); queue(); }
  function release() { if (touching) leave(); }
  function reset() {
    touching = false; inside = false;
    revealHeroColor(video, false);
    cancelAnimationFrame(frame);
    frame = last = 0;
    for (const letter of letters) {
      letter.x = letter.y = letter.rotation = 0;
      letter.element.style.transform = "";
    }
  }
  function preference() { dirty = true; reset(); }
  function invalidate() { dirty = true; if (inside) queue(); }
  // Resolve the actual CSS animation promises, including delayed entrances.
  // Without animation API support the heading simply stays readable and static.
  const entrance = heading.getAnimations?.({ subtree: true });
  if (entrance) void Promise.allSettled(entrance.map((animation) => animation.finished)).then(() => {
    if (disposed) return;
    ready = true;
    dirty = true;
  });
  const resize = new ResizeObserver(invalidate);
  resize.observe(heading);
  document.fonts?.addEventListener("loadingdone", invalidate);
  void document.fonts?.ready.then(() => { if (!disposed) invalidate(); });
  heading.addEventListener("pointerenter", enter);
  heading.addEventListener("pointerdown", press, { passive: true });
  window.addEventListener("pointerup", release, { passive: true });
  heading.addEventListener("pointermove", move);
  heading.addEventListener("pointerleave", leave);
  heading.addEventListener("pointercancel", leave);
  window.addEventListener("scroll", invalidate, { passive: true, capture: true });
  window.addEventListener("resize", invalidate, { passive: true });
  window.addEventListener("blur", reset);
  document.addEventListener("visibilitychange", preference);
  capability.addEventListener("change", preference);
  motion.addEventListener("change", preference);
  return () => {
    disposed = true;
    reset();
    resize.disconnect();
    document.fonts?.removeEventListener("loadingdone", invalidate);
    heading.removeEventListener("pointerenter", enter);
    heading.removeEventListener("pointerdown", press);
    window.removeEventListener("pointerup", release);
    heading.removeEventListener("pointermove", move);
    heading.removeEventListener("pointerleave", leave);
    heading.removeEventListener("pointercancel", leave);
    window.removeEventListener("scroll", invalidate, true);
    window.removeEventListener("resize", invalidate);
    window.removeEventListener("blur", reset);
    document.removeEventListener("visibilitychange", preference);
    capability.removeEventListener("change", preference);
    motion.removeEventListener("change", preference);
  };
}

