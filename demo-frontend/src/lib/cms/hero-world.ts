import type { Widget } from "./schema";

export const heroWorldType = "hero-world";
/** Widget types that render as a page's leading hero (heading, navbar overlay, top placement). */
export const heroTypes: readonly string[] = ["hero", heroWorldType];
export const isHeroType = (type: string | undefined) => !!type && heroTypes.includes(type);

/** Icon keys the CMS may choose; anything else falls back to a plain dot. */
export const heroWorldIcons = ["dot", "code", "cloud", "data", "ai", "project", "design", "security", "support", "mobile", "analytics", "globe"] as const;
export type HeroWorldIcon = typeof heroWorldIcons[number];

const text = (value: unknown) => typeof value === "string" ? value.trim() : "";
const coordinate = (value: unknown, limit: number) => {
  const n = typeof value === "number" ? value : typeof value === "string" && value.trim() ? Number(value) : NaN;
  return Number.isFinite(n) && Math.abs(n) <= limit ? n : null;
};
export type HeroWorldService = { id: string; title: string; description: string; icon: HeroWorldIcon; lat: number; lng: number };

/** The CMS content of a Hero World widget. Malformed services are dropped, never fatal. */
export function heroWorldContent(config: Record<string, unknown>) {
  const str = (key: string) => text(config[key]);
  const raw = Array.isArray(config.services) ? config.services : [];
  const seen = new Set<string>();
  const parsed = raw.flatMap((item: unknown) => {
    if (!item || typeof item !== "object" || Array.isArray(item)) return [];
    const service = item as Record<string, unknown>;
    const id = typeof service.id === "number" ? String(service.id) : text(service.id);
    const title = text(service.title);
    if (!id || !title || seen.has(id)) return [];
    seen.add(id);
    const icon = heroWorldIcons.includes(service.icon as HeroWorldIcon) ? service.icon as HeroWorldIcon : "dot";
    return [{ id, title, description: text(service.description), icon, lat: coordinate(service.lat, 90), lng: coordinate(service.lng, 180) }];
  });
  let auto = 0;
  const automatic = parsed.filter((service) => service.lat === null || service.lng === null).length;
  const services: HeroWorldService[] = parsed.map((service) => {
    // A marker needs both coordinates; otherwise it is placed automatically.
    const [lat, lng] = service.lat !== null && service.lng !== null ? [service.lat, service.lng] : autoPlacement(auto++, automatic);
    return { ...service, lat, lng };
  });
  return {
    headline: str("headline"), subtitle: str("subtitle"), ctaText: str("cta_text"), ctaLink: config.cta_link,
    seoTitle: str("seo_title"), seoDescription: str("seo_description"), services,
  };
}

/** Latitudes cycled by automatic placement: neighbours sit on different rows so their labels do not collide. */
const AUTO_LATITUDES = [40, 8, -24, 26, -8, 48, -18, 16, 32];
/** Longitude step between automatically placed services while they fit in one hemisphere-wide cluster. */
export const autoStep = 40;

/**
 * Deterministic positions for services without coordinates. Services form a
 * cluster stepping westwards, 40° apart, so several face the viewer at once
 * and later ones turn into view after earlier ones; once the cluster would
 * wrap the globe, they spread evenly instead. Latitudes alternate between
 * rows so neighbouring labels stay apart.
 */
export function autoPlacement(index: number, count = index + 1): [number, number] {
  const step = Math.min(autoStep, 360 / Math.max(1, count));
  const lng = ((40 - index * step + 180) % 360 + 360) % 360 - 180;
  const lat = AUTO_LATITUDES[index % AUTO_LATITUDES.length] - Math.floor(index / AUTO_LATITUDES.length) * 5;
  return [lat, Math.round(lng * 100) / 100];
}

const wrap180 = (deg: number) => ((deg + 180) % 360 + 360) % 360 - 180;
/** Longitude facing the viewer at rotation phi (the inverse of globePoint's spin). */
export const facingLng = (phi: number) => wrap180(-90 - phi * 180 / Math.PI);
/** View-relative longitude: 0 faces the viewer, negative is the left side where points enter. */
export const viewOffset = (lng: number, phi: number) => wrap180(lng - facingLng(phi));

/*
 * Service label presentation.
 *
 * Fixed geographic points on a turning sphere spend half of every turn
 * behind it, so they cannot keep the globe populated. Instead each visible
 * label rides an anchor: a real point on the sphere, turning with the globe.
 * Anchors enter on the left of the visible hemisphere and, near the right
 * limb, their service fades out while the next queued service fades in on
 * the left, so the globe holds a steady population: N − 1 labels for N
 * services (all of them for one or two), within what the stage can fit.
 */
export const enterOffset = -58;
export const exitOffset = 62;
/** Fade duration for incoming and outgoing labels (ms). */
export const labelFade = 450;
/** Delay between the initial labels' entrances (ms): short, so the globe is populated at once. */
export const initialStagger = 140;
/** Anchor latitudes; consecutive rows differ by ≥ 22° so neighbouring labels stay apart. */
const ROWS = [30, -12, 46, 8, -32, 20, -2, 38, -22];

/** Labels to keep visible for `count` services when the stage fits `capacity`. */
export function targetVisible(count: number, capacity: number) {
  return count <= 0 ? 0 : Math.min(capacity, count <= 2 ? count : count - 1);
}

export type PresentationEntry = { service: number; lat: number; lng: number; offset: number; shownAt: number; leftAt: number | null };
export type Presentation = { count: number; fixed: boolean; entries: PresentationEntry[]; queue: number[] };

/** The opening population: target labels spread evenly across the visible hemisphere, first service first. */
export function createPresentation(count: number, capacity: number, phi: number, now: number): Presentation {
  const k = targetVisible(count, capacity);
  // One or two services stay on screen: their anchors hold their place in view.
  const fixed = count <= 2;
  const offsets = fixed ? (k === 1 ? [-12] : [30, -34])
    : Array.from({ length: k }, (_, j) => exitOffset - (j + 0.5) * (exitOffset - enterOffset) / k);
  const entries = offsets.map((offset, j) => ({
    service: j, lat: ROWS[j % ROWS.length], offset, lng: wrap180(facingLng(phi) + offset), shownAt: now + j * initialStagger, leftAt: null,
  }));
  return { count, fixed, entries, queue: Array.from({ length: Math.max(0, count - k) }, (_, i) => k + i) };
}

/** Free row for a new anchor: farthest from the rows of anchors near the entry side. */
function entryRow(entries: readonly PresentationEntry[], phi: number) {
  const near = entries.filter((e) => e.leftAt === null && Math.abs(viewOffset(e.lng, phi) - enterOffset) < 75).map((e) => e.lat);
  let best = ROWS[0], score = -Infinity;
  for (const row of ROWS) {
    const distance = near.length ? Math.min(...near.map((lat) => Math.abs(lat - row))) : 90;
    if (distance > score + 1e-9) { best = row; score = distance; }
  }
  return best;
}

/**
 * Advances the presentation to `phi` at time `now`: retires the anchor that
 * passed the exit, brings in the next queued service at the entry side in
 * the same step (so the two crossfade), and adapts to a changed capacity.
 * Deterministic and allocation-light; called once per frame.
 */
export function stepPresentation(p: Presentation, count: number, capacity: number, phi: number, now: number): Presentation {
  if (count !== p.count) return createPresentation(count, capacity, phi, now);
  const k = targetVisible(count, capacity);
  p.entries = p.entries.filter((e) => e.leftAt === null || now - e.leftAt < labelFade);
  if (p.fixed) {
    for (const e of p.entries) e.lng = wrap180(facingLng(phi) + e.offset);
    return p;
  }
  const retire = (e: PresentationEntry) => { e.leftAt = now; p.queue.push(e.service); };
  for (const e of p.entries) if (e.leftAt === null && viewOffset(e.lng, phi) > exitOffset) retire(e);
  let active = p.entries.filter((e) => e.leftAt === null);
  // A smaller stage retires the right-most labels first.
  for (const e of [...active].sort((a, b) => viewOffset(b.lng, phi) - viewOffset(a.lng, phi)).slice(0, Math.max(0, active.length - k))) retire(e);
  active = p.entries.filter((e) => e.leftAt === null);
  const leaving = new Set(p.entries.filter((e) => e.leftAt !== null).map((e) => e.service));
  for (let n = active.length, tries = p.queue.length; n < k && tries > 0; tries--) {
    const service = p.queue.shift()!;
    // A service still fading out waits; one pin per service keeps labels stable.
    if (leaving.has(service) || active.some((e) => e.service === service)) { p.queue.push(service); continue; }
    p.entries.push({ service, lat: entryRow(p.entries, phi), offset: enterOffset, lng: wrap180(facingLng(phi) + enterOffset), shownAt: now, leftAt: null });
    n++;
  }
  return p;
}

/** Seconds per full revolution. Phi advances by time, not frames, so speed is device independent. */
export const revolutionSeconds = 105;
export const rotationSpeed = Math.PI * 2 / revolutionSeconds;
/**
 * One animation step. Elapsed time is clamped so a long frame (tab switch,
 * jank) never jumps the globe; phi wraps at 2π, which is invisible to the
 * trigonometric projection, so a revolution never resets visibly.
 */
export function advancePhi(phi: number, elapsedMs: number) {
  const next = phi + Math.min(Math.max(elapsedMs, 0), 64) / 1000 * rotationSpeed;
  return next >= Math.PI * 2 ? next - Math.PI * 2 : next;
}

export type LabelCandidate = { index: number; x: number; y: number; depth: number; width: number; height: number };
export type PlacedLabel = { index: number; side: "start" | "end"; left: number; top: number; dy: number };
type Rect = { left: number; top: number; right: number; bottom: number };

/** Front-facing threshold below which a point counts as behind the globe. */
export const visibleDepth = 0.12;
const DOT_GAP = 12;
const PADDING = 6;
/** Vertical nudges (in label heights) tried before a label is hidden; the marker never moves. */
const NUDGES = [0, -0.6, 0.6, -1.2, 1.2];

/**
 * Chooses where labels go this frame. Front-most points win, labels already
 * on screen get a bonus and first try their previous side and nudge (no
 * flicker), earlier CMS order breaks ties, and a highlighted service always
 * goes first. A label opens toward the globe's centre, flips or shifts up or
 * down a little to avoid the stage edge and other labels, and is skipped only
 * when nothing fits.
 */
export function layoutLabels(candidates: readonly LabelCandidate[], stage: { width: number; height: number }, limit: number,
  shown: ReadonlySet<number> | ReadonlyMap<number, Pick<PlacedLabel, "side" | "dy">> = new Set(), highlighted = -1): PlacedLabel[] {
  const ranked = candidates.filter((c) => c.depth > visibleDepth)
    .map((c) => ({ c, score: c.depth + (shown.has(c.index) ? 0.18 : 0) + (c.index === highlighted ? 2 : 0) - c.index * 1e-4 }))
    .sort((a, b) => b.score - a.score);
  const placed: (PlacedLabel & { rect: Rect })[] = [];
  for (const { c } of ranked) {
    if (placed.length >= limit) break;
    const sides: ("start" | "end")[] = c.x > stage.width * 0.55 ? ["start", "end"] : ["end", "start"];
    const options = NUDGES.flatMap((nudge) => sides.map((side) => ({ side, dy: Math.round(nudge * c.height) })));
    const previous = shown instanceof Map ? shown.get(c.index) : undefined;
    if (previous) options.unshift(previous);
    for (const { side, dy } of options) {
      const left = side === "end" ? c.x + DOT_GAP : c.x - DOT_GAP - c.width;
      const top = c.y - c.height / 2 + dy;
      const rect = { left, top, right: left + c.width, bottom: top + c.height };
      if (rect.left < 0 || rect.right > stage.width || rect.top < 0 || rect.bottom > stage.height) continue;
      const overlaps = placed.some(({ rect: o }) => rect.left < o.right + PADDING && rect.right + PADDING > o.left && rect.top < o.bottom + PADDING && rect.bottom + PADDING > o.top);
      if (overlaps) continue;
      placed.push({ index: c.index, side, left, top, dy, rect });
      break;
    }
  }
  return placed.map(({ index, side, left, top, dy }) => ({ index, side, left, top, dy }));
}

/** Labels allowed at once for a stage width: fewer on phones keeps the globe legible. */
export function labelLimit(stageWidth: number) {
  return stageWidth < 360 ? 3 : stageWidth < 480 ? 4 : stageWidth < 640 ? 5 : stageWidth < 860 ? 6 : 7;
}

/** Dot samples for the globe surface, reduced on small stages for mobile GPUs. */
export function mapSamples(stageWidth: number) {
  return stageWidth < 480 ? 9000 : stageWidth < 720 ? 14000 : 20000;
}

/**
 * Hero World SEO, used only when that widget is the page's leading hero.
 * Precedence: explicit page SEO, then Hero World SEO, then the existing
 * defaults (the caller keeps those).
 */
export function heroWorldSeo(leading: Widget | undefined) {
  if (leading?.type !== heroWorldType) return { title: "", description: "" };
  const content = heroWorldContent(leading.config);
  return { title: content.seoTitle, description: content.seoDescription };
}

/*
 * Pointer interaction maths (pure; the DOM work lives in hero-world-interaction.ts).
 */

/** Frame-rate independent exponential approach of `current` to `target` with time constant `tau` (ms). */
export function damp(current: number, target: number, elapsedMs: number, tau: number) {
  return target + (current - target) * Math.exp(-Math.max(0, elapsedMs) / Math.max(1, tau));
}

export type Point = { x: number; y: number };
export type Curve = [Point, Point, Point, Point];

/** A soft horizontal S-curve from the copy (origin) to a globe marker (target). */
export function linkCurve(origin: Point, target: Point): Curve {
  const reach = Math.max(40, Math.abs(target.x - origin.x) * 0.5);
  return [origin, { x: origin.x + reach, y: origin.y }, { x: target.x - reach, y: target.y }, target];
}

export function curvePoint([a, b, c, d]: Curve, t: number): Point {
  const u = 1 - t;
  const w = [u * u * u, 3 * u * u * t, 3 * u * t * t, t * t * t];
  return { x: w[0] * a.x + w[1] * b.x + w[2] * c.x + w[3] * d.x, y: w[0] * a.y + w[1] * b.y + w[2] * c.y + w[3] * d.y };
}

/** Approximate distance from a point to a curve (sampled; enough for a proximity highlight). */
export function curveDistance(curve: Curve, p: Point, samples = 16) {
  let best = Infinity;
  for (let i = 0; i <= samples; i++) {
    const q = curvePoint(curve, i / samples);
    best = Math.min(best, Math.hypot(q.x - p.x, q.y - p.y));
  }
  return best;
}

export const curvePath = ([a, b, c, d]: Curve) =>
  `M${a.x.toFixed(1)} ${a.y.toFixed(1)}C${b.x.toFixed(1)} ${b.y.toFixed(1)} ${c.x.toFixed(1)} ${c.y.toFixed(1)} ${d.x.toFixed(1)} ${d.y.toFixed(1)}`;

/**
 * Which services the connection lines point at. A line keeps its service
 * while that label is visible; a free line takes the visible service closest
 * to the copy (left-most) that no other line uses. `released` lines are still
 * fading out and are not reassigned yet.
 */
export function assignLinks(current: readonly (number | null)[], visible: readonly { index: number; x: number }[], released: ReadonlySet<number> = new Set()) {
  const available = new Set(visible.map((v) => v.index));
  const next = current.map((index) => index !== null && available.has(index) ? index : null);
  const used = new Set(next.filter((index): index is number => index !== null));
  const free = [...visible].sort((a, b) => a.x - b.x).filter((v) => !used.has(v.index));
  return next.map((index, slot) => {
    if (index !== null || released.has(slot)) return index;
    const pick = free.shift();
    return pick ? pick.index : null;
  });
}

/** Magnetic offset for a control: up to `max` px toward the cursor while it is within `reach` px of the box. */
export function magnet(cursor: Point | null, box: { left: number; top: number; width: number; height: number }, max = 5, reach = 28): Point {
  if (!cursor) return { x: 0, y: 0 };
  const cx = box.left + box.width / 2, cy = box.top + box.height / 2;
  const dx = cursor.x - cx, dy = cursor.y - cy;
  const inside = Math.abs(dx) <= box.width / 2 + reach && Math.abs(dy) <= box.height / 2 + reach;
  if (!inside) return { x: 0, y: 0 };
  const clamp = (v: number) => Math.max(-max, Math.min(max, v));
  return { x: clamp(dx * 0.12), y: clamp(dy * 0.2) };
}

/** Pointer parallax for the globe: at most ±3° of extra tilt/turn from a normalized pointer (−1…1). */
export const parallaxLimit = 3 * Math.PI / 180;
export function parallax(nx: number, ny: number) {
  const clamp = (v: number) => Math.max(-1, Math.min(1, v));
  return { theta: clamp(ny) * parallaxLimit, phi: clamp(nx) * parallaxLimit };
}
