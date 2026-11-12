export type PlaybackState = "idle" | "loading" | "playing" | "paused" | "blocked" | "error";

// Kept outside React so asynchronous playback and preference changes can be
// exercised with the same controller used by the public player.
export function heroPlayback(video: HTMLVideoElement, url: string, report: (state: PlaybackState) => void) {
  const motion = window.matchMedia("(prefers-reduced-motion: reduce)");
  // Source-scoped setup; metadata can restore a browser's default rate.
  // Never seek or reinitialize playback to manufacture slower frames.
  const applyRate = () => {
    if (video.defaultPlaybackRate !== 0.85) video.defaultPlaybackRate = 0.85;
    if (video.playbackRate !== 0.85) video.playbackRate = 0.85;
  };
  applyRate();
  video.muted = true;
  let visible = false;
  let stopped = false;
  let failed = false;
  let disposed = false;
  let attempt = 0;
  let pending = false;
  let wanted = false;
  let state: PlaybackState = "idle";
  const emit = (next: PlaybackState) => { if (next === state) return; state = next; if (!disposed) report(next); };
  report("idle");
  function pause() {
    const wasPending = pending;
    attempt++;
    pending = false;
    wanted = false;
    if (wasPending || !video.paused) video.pause();
    if (state === "playing" || state === "loading") emit("paused");
  }
  async function play() {
    if (disposed || failed || pending || !video.paused) return;
    const current = ++attempt;
    pending = true;
    wanted = true;
    emit("loading");
    // No source is requested for hidden placements or reduced-motion visitors
    // until playback is actually wanted. Preserve the entire (possibly signed) URL.
    if (!video.hasAttribute("src")) video.src = url;
    try {
      await video.play();
    } catch (error) {
      if (disposed || current !== attempt || failed) return;
      if (error instanceof DOMException && error.name === "NotAllowedError") {
        stopped = true;
        emit("blocked");
      } else if (!(error instanceof DOMException && error.name === "AbortError")) {
        failed = true;
        emit("error");
      } else emit("paused");
    } finally {
      if (current === attempt) pending = false;
    }
  }
  function sync() {
    if (!visible || document.hidden || motion.matches || stopped) pause();
    else void play();
  }
  const playing = () => {
    if (disposed || !wanted || document.hidden || !visible || stopped) { pause(); return; }
    emit("playing");
  };
  const paused = () => { if (state === "playing" || state === "loading") emit("paused"); };
  const waiting = () => { if (!failed && !video.paused) emit("loading"); };
  const error = () => { failed = true; attempt++; pending = false; emit("error"); };
  video.addEventListener("playing", playing);
  video.addEventListener("pause", paused);
  video.addEventListener("waiting", waiting);
  video.addEventListener("error", error);
  video.addEventListener("loadedmetadata", applyRate);
  motion.addEventListener("change", sync);
  document.addEventListener("visibilitychange", sync);
  // IntersectionObserver also notices display:none on the existing responsive
  // placement wrappers. The page merger already renders shared placements once.
  const observer = new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; sync(); });
  observer.observe(video);
  return {
    toggle() {
      if (pending || !video.paused) { stopped = true; pause(); }
      else { stopped = false; void play(); }
    },
    dispose() {
      if (disposed) return;
      disposed = true;
      observer.disconnect();
      motion.removeEventListener("change", sync);
      document.removeEventListener("visibilitychange", sync);
      video.removeEventListener("playing", playing);
      video.removeEventListener("pause", paused);
      video.removeEventListener("waiting", waiting);
      video.removeEventListener("error", error);
      video.removeEventListener("loadedmetadata", applyRate);
      pause();
      video.removeAttribute("src");
      video.load();
    },
  };
}
