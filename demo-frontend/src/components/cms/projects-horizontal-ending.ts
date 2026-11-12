const clamp = (value: number) => Math.max(0, Math.min(1, value));

/** One timeline: measured horizontal travel, a reading interval, then the logo exit. */
export function projectEndingTiming(height: number) {
  return { hold: Math.max(320, height * .55), exit: Math.max(480, height * 1.15) };
}

/** Limit overlap to real following content so a short footer cannot leave an overflow tail. */
export function projectEndingOverlap(height: number, followingHeight: number) {
  return Math.max(0, Math.min(height, followingHeight));
}

export function projectEndingFrame(distance: number, travel: number, rawWidth: number, rawHeight: number, timingHeight = rawHeight) {
  // A hidden or collapsing stage can measure 0 (or NaN); log(0) would turn the
  // scale into NaN and the clip path's transform invalid. Real stages are larger.
  const size = (value: number) => Number.isFinite(value) ? Math.max(1, value) : 1;
  const width = size(rawWidth), height = size(rawHeight);
  const { hold, exit } = projectEndingTiming(timingHeight);
  const progress = clamp((distance - travel - hold) / exit);
  // Start inside the large logo circle so the first frame covers every corner.
  const cover = Math.hypot(width / 2, height / 2) / 17.5 * 1.02;
  const recognizable = Math.min(width * .56 / 67, height * .5 / 50);
  const zoom = clamp((progress - .15) / .57);
  const retreat = clamp((progress - .72) / .28);
  const scale = Math.exp(Math.log(cover) * (1 - zoom) + Math.log(recognizable) * zoom) * (1 - retreat);
  const x = width / 2 - 16 * recognizable * zoom;
  const y = height / 2 + 7.5 * recognizable * zoom - height * 1.05 * retreat;
  return {
    progress, scale, x, y,
    textOpacity: 1 - clamp(progress / .22),
    textY: -height * .16 * clamp(progress / .25),
    transform: `translate(${x} ${y}) scale(${scale}) translate(-17.5 -32.5)`,
  };
}


