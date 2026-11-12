import { projectSequence, projectSequenceFrame, projectSequenceRemap } from "./projects-horizontal-sequence";
import { projectEndingFrame, projectEndingOverlap, projectEndingTiming } from "./projects-horizontal-ending";
import { motionViewport } from "../../lib/cms/motion-viewport";

/** Observe native page scrolling; each widget owns its geometry and lifecycle. */
export function enhanceProjectsHorizontal(root: HTMLElement) {
  if (typeof ResizeObserver !== "function") return;
  const stage = root.querySelector<HTMLElement>("[data-project-stage]")!;
  const track = root.querySelector<HTMLElement>("[data-project-track]")!;
  const closing = root.querySelector<HTMLElement>("[data-project-closing]");
  const closingContent = root.querySelector<HTMLElement>("[data-project-closing-content]");
  const logo = root.querySelector<SVGClipPathElement>("[data-project-logo]");
  const ending = !!(closing && closingContent && logo);
  const flowBoundary = typeof root.closest === "function" ? root.closest<HTMLElement>(".site-shell") ?? document.body : undefined;
  const panels = Array.from(track.children) as HTMLElement[];
  const buttons = Array.from(root.querySelectorAll<HTMLButtonElement>("button"));
  const contents = panels.map(panel => panel.querySelector<HTMLElement>("[data-project-reading]"));
  let sequence = projectSequence(0, [], []);
  let pans: { start: number; end: number }[] = [];
  let lastDistance: number | null = null;
  const reduced = matchMedia("(prefers-reduced-motion: reduce)");
  let frame = 0, dirty = true, alive = true, pinned = false, travel = 0, distance = 0, stageHeight = 0, visibleHeight = 0, total = 0;
  const viewport = motionViewport(root, invalidate);
  function render() {
    frame = 0;
    if (dirty) {
      dirty = false;
      const wasPinned = pinned;
      const oldTravel = travel;
      const oldTotal = total;
      const oldSequence = sequence;
      const oldHeight = stageHeight;
      stageHeight = viewport.height();
      const oldTop = root.getBoundingClientRect().top;
      const oldDistance = lastDistance ?? -oldTop;
      const inside = wasPinned && oldDistance >= 0 && oldDistance <= oldTotal;
      root.toggleAttribute("data-project-static", reduced.matches);
      travel = Math.max(0, track.scrollWidth - stage.clientWidth);
      pinned = !reduced.matches && travel > 1;
      root.toggleAttribute("data-pinned", pinned);
      root.toggleAttribute("data-project-ending", ending && pinned);
      root.style.setProperty("--project-height", `${stageHeight}px`);
      // dvh fills expanding browser chrome; svh keeps timeline distances stable.
      visibleHeight = stage.clientHeight || stageHeight;
      const headerBox = document.querySelector?.<HTMLElement>(".header-dock")?.getBoundingClientRect();
      const readingTop = Math.min(stageHeight * .4, Math.max(24, headerBox && headerBox.top >= 0 ? headerBox.bottom + 16 : 88));
      pans = contents.map((content, index) => {
        const height = content?.offsetHeight ?? 0;
        panels[index].style.setProperty("--project-information-height", `${height}px`);
        if (content === closingContent && height > stageHeight - readingTop - 20) return { start: Math.max(0, (height - stageHeight) / 2 + readingTop), end: Math.min(0, (stageHeight - height) / 2 - 20) };
        return { start: Math.max(0, height - stageHeight + readingTop), end: 0 };
      });
      sequence = projectSequence(travel, panels.map(panel => panel.offsetLeft), pans.map(pan => pan.start - pan.end));
      const timing = projectEndingTiming(stageHeight);
      total = sequence.length + (ending ? timing.hold + timing.exit : 0);
      root.style.setProperty("--project-travel", `${pinned ? total : 0}px`);
      if (pinned && ending) {
        // Measure normal flow with a full-stage overlap, then cap it to the
        // remaining content. The shell box excludes descendant overflow.
        root.style.setProperty("--project-overlap", `${visibleHeight}px`);
        if (flowBoundary) {
          const followingHeight = flowBoundary.getBoundingClientRect().bottom - root.getBoundingClientRect().bottom + visibleHeight;
          root.style.setProperty("--project-overlap", `${projectEndingOverlap(visibleHeight, followingHeight)}px`);
        }
      } else root.style.removeProperty("--project-overlap");
      if (pinned) stage.scrollLeft = 0;
      const changed = wasPinned !== pinned || oldTravel !== travel || oldHeight !== stageHeight || oldSequence.length !== sequence.length || oldSequence.readings.some((length, index) => length !== sequence.readings[index]);
      if (inside && changed) {
        const start = root.getBoundingClientRect().top + window.scrollY;
        if (!pinned) {
          const oldX = projectSequenceFrame(oldDistance, oldSequence).x;
          const index = Math.max(0, oldSequence.positions.findLastIndex(position => position <= oldX + 1));
          const target = panels[index]?.getBoundingClientRect().top;
          window.scrollTo({ top: target === undefined ? start : target + window.scrollY, behavior: "instant" });
        } else {
          const mapped = oldDistance <= oldSequence.length ? projectSequenceRemap(oldDistance, oldSequence, sequence)
            : sequence.length + Math.max(0, Math.min(1, (oldDistance - oldSequence.length) / Math.max(1, oldTotal - oldSequence.length))) * (total - sequence.length);
          window.scrollTo({ top: start + mapped, behavior: "instant" });
        }
      }
    }
    const rawDistance = pinned ? -root.getBoundingClientRect().top : 0;
    lastDistance = pinned ? rawDistance : null;
    const state = projectSequenceFrame(rawDistance, sequence);
    distance = pinned ? state.x : stage.scrollLeft;
    track.style.transform = pinned ? `translate3d(${-distance}px,0,0)` : "none";
    contents.forEach((content, index) => {
      if (!content) return;
      const pan = pans[index];
      content.toggleAttribute("data-project-reading-overflow", pinned && pan.start > pan.end);
      content.style.setProperty("--project-content-pan", `${pinned ? pan.start + (pan.end - pan.start) * state.progress[index] : 0}px`);
    });
    if (ending) {
      const exit = projectEndingFrame(rawDistance, sequence.length, stage.clientWidth, visibleHeight, stageHeight);
      root.toggleAttribute("data-project-exiting", pinned && exit.progress > 0);
      logo!.setAttribute("transform", exit.transform);
      root.style.setProperty("--project-logo-color-progress", `${Math.max(0, Math.min(1, (exit.progress - .22) / .22)) * 100}%`);
      closingContent!.style.opacity = String(pinned ? exit.textOpacity : 1);
      closingContent!.style.transform = pinned ? `translate3d(0,${exit.textY}px,0)` : "none";
      closing!.inert = pinned && exit.textOpacity === 0;
      if (closing!.inert) closing!.setAttribute("aria-hidden", "true"); else closing!.removeAttribute("aria-hidden");
      stage.style.pointerEvents = pinned && exit.textOpacity === 0 ? "none" : "";
    }
    buttons[0].disabled = distance <= 1;
    buttons[1].disabled = distance >= travel - 1;
    buttons.forEach(button => button.hidden = reduced.matches || travel <= 1);
  }
  function schedule() { if (alive && !frame) frame = requestAnimationFrame(render); }
  function invalidate() { dirty = true; schedule(); }
  function moveTo(left: number, immediate = false, completeReading = false) {
    left = Math.max(0, Math.min(travel, left));
    const behavior = immediate || reduced.matches ? "instant" : "smooth";
    const index = sequence.positions.findIndex(position => Math.abs(position - left) <= 1);
    const pageDistance = index >= 0 ? sequence.starts[index] + (completeReading ? sequence.readings[index] : 0) : left + sequence.readings.reduce((sum, length, i) => sum + (sequence.positions[i] < left ? length : 0), 0);
    if (pinned) window.scrollTo({ top: root.getBoundingClientRect().top + window.scrollY + pageDistance, behavior });
    else stage.scrollTo({ left, behavior });
  }
  function click(event: Event) {
    const target = (event.target as HTMLElement).closest("button");
    if (!target) return;
    const positions = panels.map(panel => panel.offsetLeft);
    moveTo(target === buttons[1] ? positions.find(left => left > distance + 2) ?? travel : positions.findLast(left => left < distance - 2) ?? 0);
  }
  function focus(event: FocusEvent) {
    const panel = (event.target as HTMLElement).closest<HTMLElement>("[data-project-id], [data-project-closing]");
    if (panel && pinned) { stage.scrollLeft = 0; moveTo(panel.offsetLeft, true, true); }
  }
  function key(event: KeyboardEvent) {
    if (reduced.matches || event.target !== stage || event.altKey || event.ctrlKey || event.metaKey) return;
    const left = event.key === "ArrowRight" ? distance + stage.clientWidth : event.key === "ArrowLeft" ? distance - stage.clientWidth : event.key === "Home" ? 0 : event.key === "End" ? travel : null;
    if (left !== null) { event.preventDefault(); moveTo(left); }
  }
  const observer = new ResizeObserver(invalidate);
  observer.observe(stage); observer.observe(track);
  if (flowBoundary) observer.observe(flowBoundary);
  panels.forEach(panel => observer.observe(panel));
  contents.forEach(content => { if (content) observer.observe(content); });
  window.addEventListener("scroll", schedule, { passive: true });
  window.addEventListener("resize", invalidate);
  stage.addEventListener("scroll", schedule, { passive: true });
  stage.addEventListener("focusin", focus);
  stage.addEventListener("keydown", key);
  root.addEventListener("click", click);
  reduced.addEventListener("change", invalidate);
  document.fonts.ready.then(() => { if (alive) invalidate(); });
  render();
  return () => {
    alive = false; cancelAnimationFrame(frame); observer.disconnect(); viewport.dispose();
    window.removeEventListener("scroll", schedule); window.removeEventListener("resize", invalidate);
    stage.removeEventListener("scroll", schedule); stage.removeEventListener("focusin", focus); stage.removeEventListener("keydown", key);
    root.removeEventListener("click", click); reduced.removeEventListener("change", invalidate);
    panels.forEach(panel => panel.style.removeProperty("--project-information-height"));
    root.removeAttribute("data-project-static");
    contents.forEach(content => { content?.style.removeProperty("--project-content-pan"); content?.removeAttribute("data-project-reading-overflow"); });
    root.removeAttribute("data-pinned"); root.removeAttribute("data-project-ending"); root.removeAttribute("data-project-exiting");
    track.style.removeProperty("transform"); stage.style.removeProperty("pointer-events");
    logo?.removeAttribute("transform");
    root.style.removeProperty("--project-logo-color-progress");
    if (closing) { closing.inert = false; closing.removeAttribute("aria-hidden"); }
    closingContent?.style.removeProperty("transform"); closingContent?.style.removeProperty("opacity");
    root.style.removeProperty("--project-height"); root.style.removeProperty("--project-travel"); root.style.removeProperty("--project-overlap");
  };

}











