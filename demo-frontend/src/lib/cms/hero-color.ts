const reveals = new WeakMap<HTMLVideoElement, (active: boolean) => void>();

// The title shares this controller, so ambient accents and hover never compete.
export function revealHeroColor(video: HTMLVideoElement | null, active: boolean) {
  if (video) reveals.get(video)?.(active);
}

// Decoration only: this controller never plays, pauses, seeks or changes source.
export function heroColorAccents(video: HTMLVideoElement) {
  if (typeof video.animate !== "function" || typeof IntersectionObserver !== "function") return;
  const motion = window.matchMedia("(prefers-reduced-motion: reduce)");
  const capability = window.matchMedia("(hover: hover) and (pointer: fine)");
  let visible = false;
  let disposed = false;
  let revealed = false;
  let timer: ReturnType<typeof setTimeout> | undefined;
  let animation: Animation | undefined;
  const interactive = () => !disposed && visible && !document.hidden && !motion.matches;
  const eligible = () => interactive() && capability.matches && !revealed && !video.paused && !video.error;
  function stop() {
    clearTimeout(timer);
    timer = undefined;
    animation?.cancel();
    animation = undefined;
  }
  function schedule() {
    if (!eligible() || timer !== undefined || animation) return;
    timer = setTimeout(() => {
      timer = undefined;
      if (!eligible()) return;
      // 220ms into color, 200ms hold, 300ms return; no brightness/opacity pulse.
      const accent = video.animate([
        { filter: "grayscale(1)", offset: 0, easing: "ease-in-out" },
        { filter: "grayscale(0)", offset: 220 / 720 },
        { filter: "grayscale(0)", offset: 420 / 720, easing: "ease-in-out" },
        { filter: "grayscale(1)", offset: 1 },
      ], { duration: 720 });
      animation = accent;
      accent.finished.then(() => {
        if (animation !== accent) return;
        animation = undefined;
        schedule();
      }, () => { /* Cancellation is expected on suspension/cleanup. */ });
    }, 8000 + Math.random() * 6000);
  }
  function reveal(active: boolean) {
    active = active && interactive();
    if (active === revealed) return;
    revealed = active;
    // Sample before cancelling: interruption of either an accent or a hover
    // transition starts at the displayed color, including on rapid reversals.
    const current = getComputedStyle(video).filter;
    stop();
    const transition = video.animate([
      { filter: current }, { filter: active ? "grayscale(0)" : "grayscale(1)" },
    ], { duration: active ? 240 : 460, easing: "cubic-bezier(.2,.65,.3,1)", fill: "forwards" });
    animation = transition;
    transition.finished.then(() => {
      if (animation !== transition || revealed) return;
      stop();
      schedule();
    }, () => { /* A reversal continues from the sampled presentation value. */ });
  }
  reveals.set(video, reveal);
  function sync() {
    if (!interactive()) { revealed = false; stop(); }
    else if (!revealed) { if (eligible()) schedule(); else stop(); }
  }
  const observer = new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; sync(); });
  observer.observe(video);
  for (const event of ["playing", "pause", "error", "emptied"]) video.addEventListener(event, sync);
  document.addEventListener("visibilitychange", sync);
  motion.addEventListener("change", sync);
  capability.addEventListener("change", sync);
  return () => {
    disposed = true;
    reveals.delete(video);
    stop();
    observer.disconnect();
    for (const event of ["playing", "pause", "error", "emptied"]) video.removeEventListener(event, sync);
    document.removeEventListener("visibilitychange", sync);
    motion.removeEventListener("change", sync);
    capability.removeEventListener("change", sync);
  };
}
