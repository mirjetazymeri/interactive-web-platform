import type { Config } from "./config";

/** CMS `rotating_labels`: an ordered list of short strings; anything else is ignored. */
export function heroGlobeLabels(config: Config): string[] {
  const value = config.rotating_labels;
  if (!Array.isArray(value)) return [];
  return value.filter((label): label is string => typeof label === "string").map((label) => label.trim()).filter(Boolean);
}

/** Decorative anchor points spread around the world; the CMS supplies only the label text. */
export const globeMarkers: readonly [number, number][] = [
  [42.66, 21.17], [51.51, -0.13], [40.71, -74.01], [25.2, 55.27], [1.35, 103.82], [37.77, -122.42],
  [35.68, 139.69], [-33.87, 151.21], [-23.55, -46.63], [48.86, 2.35], [19.43, -99.13], [-1.29, 36.82],
  [55.76, 37.62], [28.61, 77.21], [-33.92, 18.42], [43.65, -79.38],
];

/** Sphere radius in cobe's normalized clip space (its fragment shader renders `dot(b,b) <= .64`). */
const RADIUS = 0.8;

/**
 * Projects a latitude/longitude onto the rendered globe, mirroring cobe's
 * shader: marker vector from lat/lng, then rotation by theta (tilt) and phi
 * (spin). Returns the position as fractions of the canvas and a depth where
 * values above 0 face the viewer.
 */
export function globePoint(lat: number, lng: number, phi: number, theta: number) {
  const la = lat * Math.PI / 180, lo = lng * Math.PI / 180 - Math.PI, r = Math.cos(la);
  const sx = -r * Math.cos(lo), sy = Math.sin(la), sz = r * Math.sin(lo);
  const cp = Math.cos(phi), sp = Math.sin(phi), ct = Math.cos(theta), st = Math.sin(theta);
  const x = sx * cp + sz * sp;
  const y = sx * sp * st + sy * ct - sz * cp * st;
  const z = -sx * sp * ct + sy * st + sz * cp * ct;
  return { x: (1 + RADIUS * x) / 2, y: (1 - RADIUS * y) / 2, depth: z };
}

/**
 * Chooses the marker for the next label: the most front-facing point that no
 * visible label occupies, so a new label always appears where it can be read.
 */
export function frontMarker(phi: number, theta: number, taken: ReadonlySet<number>, rowGap = minRowGap) {
  // Chips are one line tall: keep a new one clear of the others' rows.
  const rows = [...taken].map((index) => globePoint(...globeMarkers[index], phi, theta).y);
  let best = -1, bestDepth = -Infinity, clear = false;
  globeMarkers.forEach(([lat, lng], index) => {
    if (taken.has(index)) return;
    const { y, depth } = globePoint(lat, lng, phi, theta);
    const separated = rows.every((row) => Math.abs(row - y) >= rowGap);
    // A clear, front-facing marker beats any crowded one.
    if ((separated && !clear) || (separated === clear && depth > bestDepth)) { best = index; bestDepth = depth; clear = separated; }
  });
  return best;
}

/** Default vertical distance between pinned chips, as a fraction of the globe stage; the component widens it to one chip height on small stages. */
export const minRowGap = 0.1;

/** Parses the `rgb()`/`rgba()` string a canvas or computed style yields into 0–1 channels. */
export function rgbChannels(value: string): [number, number, number] | null {
  const match = value.match(/rgba?\(\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)/i);
  if (match) return [Number(match[1]) / 255, Number(match[2]) / 255, Number(match[3]) / 255];
  const hex = value.trim().match(/^#([\da-f]{3}|[\da-f]{6})$/i)?.[1];
  if (!hex) return null;
  const full = hex.length === 3 ? [...hex].map((c) => c + c).join("") : hex;
  return [0, 2, 4].map((i) => parseInt(full.slice(i, i + 2), 16) / 255) as [number, number, number];
}

/** Moves a channel toward white (amount > 0) or black (amount < 0). */
export function shade([r, g, b]: [number, number, number], amount: number): [number, number, number] {
  const mix = (c: number) => amount >= 0 ? c + (1 - c) * amount : c * (1 + amount);
  return [mix(r), mix(g), mix(b)];
}

export type GlobePin = { id: number; text: string; marker: number; leaving?: boolean };

/**
 * One rotation step: retires the oldest label when the visible limit is
 * reached (it fades out, marked `leaving`), and pins label `step` of the
 * list to the most front-facing free marker. Label ids equal their step.
 */
export function advancePins(current: readonly GlobePin[], labels: readonly string[], step: number, phi: number, theta: number, rowGap = minRowGap): GlobePin[] {
  const limit = Math.min(visibleLabels, labels.length);
  const active = current.filter((pin) => !pin.leaving);
  const retiring = active.slice(0, Math.max(0, active.length - limit + 1));
  const kept = active.slice(retiring.length);
  const marker = frontMarker(phi, theta, new Set(kept.map((pin) => pin.marker)), rowGap);
  return [...retiring.map((pin) => ({ ...pin, leaving: true })), ...kept, { id: step, text: labels[step % labels.length], marker }];
}

/** Rotation interval for globe labels, in milliseconds. */
export const labelInterval = 4000;
/** At most this many labels are pinned at once. */
export const visibleLabels = 3;
