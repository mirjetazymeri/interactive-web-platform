import { motionViewport } from "../../lib/cms/motion-viewport";

const clamp = (value: number) => Math.max(0, Math.min(1, value));

/** Each card gets a reading interval, including the final card before release. */
export function serviceDeckFrame(distance: number, count: number, reading: number | number[], transition: number) {
  const intervals = Array.from({ length: count }, (_, i) => typeof reading === "number" ? reading : reading[i]);
  const starts = intervals.map((_, i) => intervals.slice(0, i).reduce((a, b) => a + b, 0) + i * transition);
  const entrances = starts.map((start, index) => index === 0 ? 1 : clamp((distance - start + transition) / transition));
  const active = Math.max(0, entrances.findLastIndex(value => value >= 1));
  const moving = entrances.some(value => value > 0 && value < 1);
  return { entrances, starts, active, moving, travel: intervals.reduce((a, b) => a + b, 0) + Math.max(0, count - 1) * transition };
}

/** Passive page-scroll observation only: never capture wheel, touch, or keyboard input.
 * The next service rises from below the stage and covers the current one. */
export function enhanceServiceDeck(root: HTMLElement) {
  if (typeof ResizeObserver !== "function") return;
  const stage = root.querySelector<HTMLElement>("[data-deck-stage]")!;
  const track = root.querySelector<HTMLElement>("[data-deck-track]")!;
  const heading = root.querySelector<HTMLElement>("[data-deck-heading]");
  const cards = Array.from(root.querySelectorAll<HTMLElement>("[data-service-card]"));
  const surfaces = cards.map(card => card.querySelector<HTMLElement>("[data-deck-surface]")!);
  const header = document.querySelector<HTMLElement>(".header-dock");
  const reduced = window.matchMedia("(prefers-reduced-motion: reduce)");
  let frame = 0, dirty = true, disposed = false, pinned = false;
  let top = 104, reading = 0, transition = 0, travel = 0, stageHeight = 0;
  let active = 0, previousState = "";
  let intervals: number[] = [], overflows: number[] = [];
  let lastDistance = Number.NEGATIVE_INFINITY;
  const viewport = motionViewport(root, invalidate);

  function restoreCards() {
    cards.forEach(card => {
      card.inert = false;
      card.removeAttribute("aria-hidden");
      card.removeAttribute("data-active");
      card.style.removeProperty("transform");
      card.style.removeProperty("visibility");
      card.style.removeProperty("pointer-events");
      card.style.removeProperty("z-index");
    });
    surfaces.forEach(surface => surface.style.removeProperty("--covered"));
    surfaces.forEach(surface => surface.style.removeProperty("--deck-entry"));
    surfaces.forEach(surface => surface.style.removeProperty("--deck-pan"));
    delete root.dataset.deckFlow;
    previousState = "";
  }
  function measure() {
    dirty = false;
    const oldBounds = root.getBoundingClientRect();
    const oldTop = top;
    // Breakpoint reflow can move this section before ResizeObserver runs. Use
    // the last painted position, not a bounding box from the new layout.
    const inside = pinned && lastDistance >= 0 && lastDistance <= travel;
    const oldDistance = lastDistance;
    const oldState = pinned ? serviceDeckFrame(oldDistance, cards.length, intervals, transition) : null;
    const oldReadingProgress = oldState ? clamp((oldDistance - oldState.starts[oldState.active]) / intervals[oldState.active]) : 0;
    const oldHeight = pinned ? stageHeight + travel : oldBounds.height;
    const wasPinned = pinned;
    const viewportHeight = viewport.height();
    const headerBounds = header?.getBoundingClientRect();
    top = Math.max(88, headerBounds && headerBounds.top >= 0 && headerBounds.top < window.innerHeight / 2 ? headerBounds.bottom + 16 : 88);
    // Measure surfaces individually: the static track is the sum of all cards.
    const headingHeight = heading ? heading.offsetHeight + 24 : 0;
    const contentHeight = Math.max(...surfaces.map(surface => surface.offsetHeight), 0) + headingHeight;
    // Oversized cards are read by panning their live content before being
    // covered. Content height must never turn the sequence off on a laptop/phone.
    pinned = cards.length > 1 && !reduced.matches && contentHeight > 0 && viewportHeight > top;
    if (pinned) {
      delete root.dataset.deckFlow;
      stageHeight = viewportHeight - top;
      reading = Math.max(280, viewportHeight * .65);
      transition = Math.max(360, viewportHeight * .95);
      const windowHeight = Math.min(contentHeight - headingHeight, Math.max(1, stageHeight - headingHeight - 32));
      overflows = surfaces.map(surface => Math.max(0, surface.offsetHeight - windowHeight));
      intervals = overflows.map(overflow => reading + overflow);
      travel = serviceDeckFrame(0, cards.length, intervals, transition).travel;
      root.style.setProperty("--deck-window", `${windowHeight}px`);
      root.style.setProperty("--deck-top", `${top}px`);
      root.style.setProperty("--deck-height", `${stageHeight}px`);
      root.style.setProperty("--deck-travel", `${travel}px`);
      root.dataset.deckPinned = "true";
    } else {
      delete root.dataset.deckPinned;
      for (const property of ["--deck-top", "--deck-height", "--deck-travel", "--deck-window"]) root.style.removeProperty(property);
      restoreCards();
    }
    // Correct layout changes only, never ordinary scrolling. Disabling motion or
    // rotating midway through the pin must not strand the reader at the footer.
    if (inside && wasPinned) {
      const start = root.getBoundingClientRect().top + window.scrollY;
      const nextState = serviceDeckFrame(0, cards.length, intervals, transition);
      const nextDistance = oldState ? nextState.starts[oldState.active] + (oldState.moving
        ? intervals[oldState.active] + oldState.entrances[oldState.active + 1] * transition
        : oldReadingProgress * intervals[oldState.active]) : 0;
      const target = pinned ? start - top + nextDistance
        : cards[active].getBoundingClientRect().top + window.scrollY - top;
      if (Math.abs(target - window.scrollY) > 1) window.scrollTo({ top: target, behavior: "instant" });
    } else if (wasPinned !== pinned && oldBounds.bottom < oldTop) {
      window.scrollTo({ top: window.scrollY + root.getBoundingClientRect().height - oldHeight, behavior: "instant" });
    }
  }
  function renderMeasured() {
    frame = 0;
    if (dirty) measure();
    if (!pinned) {
      // Tall phone/landscape cards stay in document flow. Reveal their surface
      // over the first part of entry without hiding content or adding a runway.
      if (!reduced.matches) {
        root.dataset.deckFlow = "true";
        const viewHeight = viewport.height();
        const available = viewHeight - top;
        const positions = cards.map(card => card.getBoundingClientRect().top);
        cards.forEach((card, index) => surfaces[index].style.setProperty("--deck-entry",
          String(clamp((viewHeight - positions[index]) / Math.max(120, available * .45)))));
      }
      return;
    }
    const distance = top - root.getBoundingClientRect().top;
    lastDistance = distance;
    const state = serviceDeckFrame(distance, cards.length, intervals, transition);
    active = state.active;
    cards.forEach((card, index) => {
      const entrance = state.entrances[index];
      card.style.transform = `translate3d(0,${(1 - entrance) * 100}%,0)`;
      // Later services paint above earlier ones; only the covering pair is drawn.
      card.style.visibility = entrance > 0 && index >= active ? "visible" : "hidden";
      card.style.zIndex = String(index + 1);
      card.style.pointerEvents = index === active && !state.moving ? "auto" : "none";
      surfaces[index].style.setProperty("--covered", String(state.entrances[index + 1] ?? 0));
      const pan = Math.max(0, Math.min(overflows[index], distance - state.starts[index] - reading / 2));
      surfaces[index].style.setProperty("--deck-pan", `${-pan}px`);
    });
    const key = `${active}:${state.moving}`;
    if (key !== previousState) {
      if (cards.some((card, index) => (index !== active || state.moving) && card.contains(document.activeElement))) stage.focus({ preventScroll: true });
      cards.forEach((card, index) => {
        card.inert = index !== active || state.moving;
        card.setAttribute("aria-hidden", String(index !== active));
        card.dataset.active = String(index === active);
      });
      previousState = key;
    }
  }
  function render() {
    try { renderMeasured(); } catch { dispose(); }
  }
  function schedule() { if (!disposed && !frame) frame = requestAnimationFrame(render); }
  function invalidate() { dirty = true; schedule(); }
  function revealFocus(event: FocusEvent) {
    if (!pinned || !(event.target instanceof HTMLElement)) return;
    const index = cards.findIndex(card => card.contains(event.target as Node));
    if (index !== active || cards[index].inert || !overflows[index]) return;
    const bounds = event.target.getBoundingClientRect();
    const windowBounds = cards[index].getBoundingClientRect();
    if (bounds.top >= windowBounds.top && bounds.bottom <= windowBounds.bottom) return;
    const localTop = bounds.top - surfaces[index].getBoundingClientRect().top;
    const pan = Math.max(0, Math.min(overflows[index], localTop - 16));
    const state = serviceDeckFrame(0, cards.length, intervals, transition);
    window.scrollTo({ top: root.getBoundingClientRect().top + window.scrollY - top + state.starts[index] + reading / 2 + pan, behavior: "instant" });
  }
  // Other sections may ask for a card (`service-deck:show` dispatched on it);
  // while pinned, scroll to where that card has fully risen.
  function show(event: Event) {
    const index = cards.findIndex(card => card === event.target);
    if (index < 0) return;
    event.preventDefault(); // tells the sender the deck handled it
    // An instant jump: a smooth glide would cross other pinned sections and be interrupted by them.
    const behavior: ScrollBehavior = "instant";
    if (!pinned) { cards[index].scrollIntoView({ behavior, block: "start" }); return; }
    const state = serviceDeckFrame(0, cards.length, intervals, transition);
    window.scrollTo({ top: root.getBoundingClientRect().top + window.scrollY - top + state.starts[index] + 1, behavior });
  }
  root.addEventListener("service-deck:show", show);
  const observer = new ResizeObserver(invalidate);
  surfaces.forEach(surface => observer.observe(surface));
  observer.observe(track);
  if (heading) observer.observe(heading);
  if (header) observer.observe(header);
  // A preceding pinned section can change height without resizing our cards.
  if (document.body) observer.observe(document.body);
  window.addEventListener("scroll", schedule, { passive: true });
  window.addEventListener("resize", invalidate, { passive: true });
  root.addEventListener("focusin", revealFocus);
  reduced.addEventListener("change", invalidate);
  document.fonts?.ready.then(() => { if (!disposed) invalidate(); });
  schedule();
  function dispose() {
    disposed = true; cancelAnimationFrame(frame); observer.disconnect(); viewport.dispose();
    window.removeEventListener("scroll", schedule);
    window.removeEventListener("resize", invalidate);
    root.removeEventListener("focusin", revealFocus);
    root.removeEventListener("service-deck:show", show);
    reduced.removeEventListener("change", invalidate);
    delete root.dataset.deckPinned;
    for (const property of ["--deck-top", "--deck-height", "--deck-travel", "--deck-window"]) root.style.removeProperty(property);
    restoreCards();
  }
  return dispose;
}
