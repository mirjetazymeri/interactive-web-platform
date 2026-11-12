import { motionViewport } from "../../lib/cms/motion-viewport";
import { impactServices } from "./impact-services";

const clamp = (value: number) => Math.max(0, Math.min(1, value));
const smooth = (value: number) => { const t = clamp(value); return t * t * (3 - 2 * t); };
/** Evenly spaced resting points, one per service, between 6% and 94% of the scroll. */
export const wheelStopsFor = (count: number) => Array.from({ length: count }, (_, i) => count > 1 ? 0.06 + 0.88 * i / (count - 1) : 0.06);
export const wheelStops: readonly number[] = wheelStopsFor(impactServices.length);
/** Where each node sits on the ring: position 0 at the left marker, the rest clockwise. */
export function wheelNodePosition(index: number, count: number) {
  const angle = (180 + index * 360 / count) * Math.PI / 180;
  const at = (value: number) => `${Math.round((50 + 50 * value) * 1000) / 1000}%`;
  return { left: at(Math.cos(angle)), top: at(Math.sin(angle)) };
}

export function wheelFrame(progress: number, stops: readonly number[] = wheelStops) {
  const p = clamp(progress);
  // Each service rests for the same share of its interval (0.045 of 0.293 for four).
  const dwell = stops.length > 1 ? (stops[1] - stops[0]) * 0.1534 : 0;
  let position = 0;
  for (let i = 0; i < stops.length - 1; i++) {
    const start = stops[i] + dwell;
    const end = stops[i + 1] - dwell;
    position += smooth((p - start) / (end - start));
  }
  const active = Math.round(position);
  const proximity = 1 - Math.min(1, Math.abs(position - active) * 2);
  return { progress: p, position, angle: -360 / stops.length * position, active,
    detailOpacity: smooth(proximity), detailY: (1 - proximity) * 10,
    hintOpacity: 1 - smooth(p / 0.09) };
}

/** Keep every cabin, including its focus outline, inside the actual visual box. */
export function wheelGeometry(width: number, nodeWidth: number, nodeHeight: number, preferredHeight: number) {
  const radius = Math.max(0, Math.min((width - nodeWidth - 32) / 2, (preferredHeight - nodeHeight - 32) / 2));
  return { diameter: radius * 2, height: radius * 2 + nodeHeight + 32 };
}

/** Service names compare without numbering, punctuation or case ("02 IT Cloud" = "IT & CLOUD"). */
export const serviceKey = (name: string) => name.toLowerCase().replace(/^\s*\d+[\s.\-–—]*/, '').replace(/[^\p{L}]/gu, '');

/** Shows a detail's "View the full service" link when the page's ServiceX section has that service. */
function linkServices(details: HTMLElement[], signal: AbortSignal) {
  if (typeof document.querySelectorAll !== 'function') return;
  const cards = Array.from(document.querySelectorAll<HTMLElement>('[data-service-card]'));
  for (const detail of details) {
    const link = detail.querySelector<HTMLAnchorElement>('[data-wheel-service-link]');
    const key = serviceKey(detail.dataset.serviceName ?? '');
    const card = cards.find((candidate) => serviceKey(candidate.getAttribute('aria-label') ?? '') === key);
    if (!link || !card || !key) continue;
    link.hidden = false;
    link.href = `#${card.id}`;
    link.addEventListener('click', (event) => {
      event.preventDefault();
      // The deck scrolls to the card when it is pinned; otherwise scroll to it directly.
      const handled = !card.dispatchEvent(new CustomEvent('service-deck:show', { bubbles: true, cancelable: true }));
      if (!handled) card.scrollIntoView({ behavior: 'instant', block: 'start' });
    }, { signal });
  }
}

/** One progress model; native scroll when pinned, control-driven progress otherwise. */
export function enhanceServicesWheel(host: HTMLElement) {
  const stage = host.querySelector<HTMLElement>('[data-wheel-stage]')!;
  const copy = host.querySelector<HTMLElement>('[data-wheel-copy]')!;
  const wheel = host.querySelector<HTMLElement>('[data-wheel]')!;
  const visual = host.querySelector<HTMLElement>('[data-wheel-visual]')!;
  if (!stage || !copy || !wheel || !visual || !window.ResizeObserver) return;
  const nodes = Array.from(wheel.querySelectorAll<HTMLButtonElement>('[data-wheel-select]'));
  const details = Array.from(wheel.querySelectorAll<HTMLElement>('[data-wheel-detail]'));
  const previous = wheel.querySelector<HTMLButtonElement>('[data-wheel-previous]')!;
  const next = wheel.querySelector<HTMLButtonElement>('[data-wheel-next]')!;
  const count = wheel.querySelector<HTMLElement>('[data-wheel-count]')!;
  const hint = wheel.querySelector<HTMLElement>('[data-wheel-hint]')!;
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const indexButtons = Array.from(host.querySelectorAll<HTMLButtonElement>('[data-wheel-index-select]'));
  const index = host.querySelector<HTMLElement>('[data-wheel-index]');
  const bridge = host.querySelector<SVGSVGElement>('[data-wheel-bridge]');
  const bridgePath = bridge?.querySelector('path') ?? null;
  const marker = wheel.querySelector<HTMLElement>('[data-wheel-marker]');
  const abort = new AbortController();
  const header = document.querySelector<HTMLElement>('.header-dock');
  const runway = host.querySelector<HTMLElement>('[data-wheel-runway]');
  const wheelWindow = host.querySelector<HTMLElement>('[data-wheel-window]');
  let pinRoot = host;
  let observer: ResizeObserver | undefined;
  let frame = 0;
  let disposed = false;
  let needsMeasure = true;
  let pinned = false;
  let travel = 0;
  let top = 104;
  let active = -1;
  const last = wheelStops.length - 1;
  let progress = wheelStops[0];
  let requestedService: number | null = null;
  let lastTime = 0;
  let overflow = 0, reading = 0, rotation = 0;
  let lastDistance = Number.NEGATIVE_INFINITY;
  let bridged = false;
  const viewport = motionViewport(host, invalidate);

  function unpin() {
    host.removeAttribute('data-wheel-pinned');
    host.style.removeProperty('--wheel-travel');
    host.style.removeProperty('--wheel-stage-height');
    host.style.removeProperty('--wheel-window');
    host.style.removeProperty('--wheel-copy-top');
    wheel.style.removeProperty('--wheel-pan');
    pinned = false; travel = 0;
  }
  function reset() {
    unpin(); host.removeAttribute('data-wheel-layout');
    host.style.removeProperty('--wheel-top');
    for (const name of ['angle','diameter','visual-height','node-width']) wheel.style.removeProperty(`--wheel-${name}`);
    details.forEach((detail) => {
      detail.removeAttribute('aria-hidden'); detail.inert = false;
      detail.style.removeProperty('opacity'); detail.style.removeProperty('transform');
    });
    nodes.forEach((node) => { node.removeAttribute('aria-current'); node.removeAttribute('data-active'); });
    indexButtons.forEach((button) => { button.removeAttribute('aria-current'); button.removeAttribute('data-active'); });
    if (bridge) bridge.dataset.visible = 'false';
    previous.removeAttribute('aria-disabled'); next.removeAttribute('aria-disabled');
    hint.style.removeProperty('opacity');
    active = -1; requestedService = null;
  }
  function stop() {
    disposed = true; cancelAnimationFrame(frame); abort.abort(); observer?.disconnect();
    viewport.dispose();
    reduced.removeEventListener('change', invalidate);
    reset();
  }
  function schedule() { if (!disposed && !frame) frame = requestAnimationFrame(render); }
  function invalidate() { needsMeasure = true; schedule(); }
  function startPosition() { return pinRoot.getBoundingClientRect().top + window.scrollY - top; }
  function selectionDistance(index: number) {
    return overflow ? index * (reading + rotation) + reading - (reading - overflow) / 4 : wheelStops[index] * travel;
  }

  function measure() {
    needsMeasure = false;
    const wasPinned = pinned;
    const hadLayout = host.dataset.wheelLayout !== undefined;
    const viewportHeight = viewport.height();
    const oldBounds = host.getBoundingClientRect();
    const oldWheel = wheel.getBoundingClientRect();
    const inside = wasPinned && lastDistance >= 0 && lastDistance <= travel;
    const followingVisible = oldBounds.bottom <= top;
    const wheelVisible = oldWheel.top < window.innerHeight && oldWheel.bottom > top;
    if (reduced.matches) {
      reset();
      if (inside) window.scrollTo({ top: Math.max(0, startPosition()), behavior: 'instant' });
      return;
    }
    // The header's own `top` can be 0 while its sticky ancestor and margin put
    // it 32px down the page. Measure its visible bottom, not just computed top.
    const headerBounds = header?.getBoundingClientRect();
    const headerBottom = headerBounds && headerBounds.top >= 0 && headerBounds.top < window.innerHeight / 2 ? headerBounds.bottom : 64;
    top = Math.max(88, headerBottom + 24);
    host.style.setProperty('--wheel-top', `${top}px`);
    const width = wheel.clientWidth;
    const wideLabels = width >= 480 && viewportHeight - top >= 560;
    host.dataset.wheelLayout = wideLabels ? 'wide' : 'compact';
    // Layout columns, not device labels, decide whether pinning is a candidate.
    const stageStyle = getComputedStyle(stage);
    const twoColumns = stageStyle.gridTemplateColumns.split(' ').filter(Boolean).length === 2;
    bridged = twoColumns && !!index && !!marker && !!bridgePath && getComputedStyle(index).display !== 'none';
    if (!bridged && bridge) bridge.dataset.visible = 'false';
    const padding = Number.parseFloat(stageStyle.paddingTop) + Number.parseFloat(stageStyle.paddingBottom);
    const available = viewportHeight - top;
    const nodeWidth = Math.max(...nodes.map(node => node.offsetWidth));
    const nodeHeight = Math.max(...nodes.map(node => node.offsetHeight));
    const naturalOtherHeight = wheel.offsetHeight - visual.offsetHeight;
    const budget = available - (twoColumns ? padding : 16) - naturalOtherHeight;
    const preferred = Math.min(500, width, Math.max(wideLabels ? 280 : 180, budget));
    const geometry = wheelGeometry(width, nodeWidth, nodeHeight, preferred);
    wheel.style.setProperty('--wheel-diameter', `${geometry.diameter}px`);
    wheel.style.setProperty('--wheel-visual-height', `${geometry.height}px`);
    wheel.style.setProperty('--wheel-node-width', `${nodeWidth}px`);
    // No fixed-height text: the grid reserves the tallest article's natural height.
    const wheelHeight = naturalOtherHeight + geometry.height;
    const fullStage = twoColumns && Math.max(copy.scrollHeight, wheelHeight) + padding <= available + 0.5;
    // Long copy must not disable wheel scrolling. Pin the independent right
    // column (or stacked wheel) and let the text retain its natural document flow.
    const fits = fullStage || !!runway;
    pinRoot = !fullStage && runway ? runway : host;
    overflow = !fullStage && wheelWindow ? Math.max(0, wheelHeight - available) : 0;
    if (!Number.isFinite(geometry.height)) throw new Error('Invalid wheel geometry');
    if (fits) {
      reading = Math.max(140, viewportHeight * .22) + overflow;
      rotation = Math.max(150, viewportHeight * .3);
      travel = overflow ? (last + 1) * reading + last * rotation : Math.round(viewportHeight * last * (fullStage ? .6 : .45));
      host.style.setProperty('--wheel-stage-height', `${fullStage ? available : Math.min(wheelHeight, available)}px`);
      host.style.setProperty('--wheel-window', `${Math.min(wheelHeight, available)}px`);
      host.style.setProperty('--wheel-copy-top', `${top + Math.min(0, available - copy.scrollHeight)}px`);
      host.style.setProperty('--wheel-travel', `${travel}px`);
      host.dataset.wheelPinned = fullStage ? 'true' : 'compact'; pinned = true;
      if ((inside || (hadLayout && !wasPinned && wheelVisible)) && Math.abs(window.scrollY - (startPosition() + progress * travel)) > 1) window.scrollTo({ top: startPosition() + progress * travel, behavior: 'instant' });
    } else {
      unpin();
      if (inside) {
        // Removing the runway must not strand the reader at the footer.
        const anchor = Math.max(top, Math.min(oldWheel.top, window.innerHeight / 3));
        window.scrollTo({ top: Math.max(0, window.scrollY + wheel.getBoundingClientRect().top - anchor), behavior: 'instant' });
      }
    }
    if (followingVisible && wasPinned !== pinned) {
      window.scrollTo({ top: Math.max(0, window.scrollY + host.getBoundingClientRect().bottom - oldBounds.bottom), behavior: 'instant' });
    }
  }

  function render(time: number) {
    frame = 0;
    try {
      const delta = Math.min(40, time - lastTime || 16); lastTime = time;
      if (needsMeasure) measure();
      if (reduced.matches) return;
      if (pinned) {
        lastDistance = window.scrollY - startPosition();
        progress = clamp(lastDistance / travel);
      }
      else if (requestedService !== null) {
        const target = wheelStops[requestedService];
        progress += (target - progress) * (1 - Math.exp(-delta / 65));
        if (Math.abs(target - progress) < 0.0001) progress = target;
        else schedule();
      }
      let wheelProgress = progress;
      let pan = 0;
      if (pinned && overflow) {
        const distance = progress * travel;
        const index = Math.min(last, Math.floor(distance / (reading + rotation)));
        const local = distance - index * (reading + rotation);
        const turn = clamp((local - reading) / rotation);
        wheelProgress = wheelStops[index] + (index < last ? (wheelStops[index + 1] - wheelStops[index]) * turn : 0);
        pan = Math.min(overflow, Math.max(0, local - (reading - overflow) / 2)) * (1 - turn);
      }
      wheel.style.setProperty('--wheel-pan', `${-pan}px`);
      const state = wheelFrame(wheelProgress);
      wheel.style.setProperty('--wheel-angle', `${state.angle}deg`);
      hint.style.opacity = String(state.hintOpacity);
      if (active !== state.active) {
        active = state.active;
        count.textContent = String(active + 1).padStart(2, '0');
        nodes.forEach((node, index) => {
          node.dataset.active = String(index === active);
          if (index === active) node.setAttribute('aria-current', 'step'); else node.removeAttribute('aria-current');
        });
        indexButtons.forEach((button, index) => {
          button.dataset.active = String(index === active);
          if (index === active) button.setAttribute('aria-current', 'step'); else button.removeAttribute('aria-current');
        });
        // The line draws itself in toward the newly active service.
        if (bridged && bridgePath && !reduced.matches) bridgePath.animate?.([{ strokeDashoffset: 1 }, { strokeDashoffset: 0 }], { duration: 650, easing: 'cubic-bezier(.22,1,.36,1)' });
        details.forEach((detail, index) => { detail.setAttribute('aria-hidden', String(index !== active)); detail.inert = index !== active; });
        previous.setAttribute('aria-disabled', String(active === 0)); next.setAttribute('aria-disabled', String(active === last));
      }
      details.forEach((detail, index) => {
        detail.style.opacity = index === active ? String(state.detailOpacity) : '0';
        detail.style.transform = `translateY(${index === active ? state.detailY : 10}px)`;
      });
      drawBridge();
      if (requestedService !== null && (pinned
        ? Math.abs(progress * travel - selectionDistance(requestedService)) < 2
        : Math.abs(progress - wheelStops[requestedService]) < 0.0001)) requestedService = null;
    } catch { stop(); }
  }
  /** A soft S-curve from the active index entry to the wheel's marker, hidden while the marker is out of view. */
  function drawBridge() {
    if (!bridged || !bridge || !bridgePath || !marker) return;
    const item = indexButtons[active];
    if (!item) return;
    const base = stage.getBoundingClientRect();
    const from = item.getBoundingClientRect(), to = marker.getBoundingClientRect();
    const view = (wheelWindow ?? wheel).getBoundingClientRect();
    const x1 = from.right - base.left + 16, y1 = from.top + from.height / 2 - base.top;
    const x2 = to.left - base.left - 6, y2 = to.top + to.height / 2 - base.top;
    const reach = Math.max(40, (x2 - x1) * 0.5);
    bridgePath.setAttribute('d', `M${x1.toFixed(1)} ${y1.toFixed(1)}C${(x1 + reach).toFixed(1)} ${y1.toFixed(1)} ${(x2 - reach).toFixed(1)} ${y2.toFixed(1)} ${x2.toFixed(1)} ${y2.toFixed(1)}`);
    const visible = x2 > x1 + 24 && to.top >= view.top - 1 && to.bottom <= view.bottom + 1;
    bridge.dataset.visible = String(visible);
  }
  function select(index: number) {
    if (reduced.matches || index < 0 || index > last) return;
    requestedService = index;
    if (pinned) window.scrollTo({ top: startPosition() + selectionDistance(index), behavior: 'smooth' });
    else schedule();
  }
  const options = { passive: true, signal: abort.signal };
  try {
    window.addEventListener('scroll', schedule, options);
    window.addEventListener('resize', invalidate, options);
    window.addEventListener('wheel', () => { if (pinned) requestedService = null; }, options);
    window.addEventListener('touchstart', () => { if (pinned) requestedService = null; }, options);
    index?.addEventListener('click', (event) => {
      const target = event.target instanceof Element ? event.target.closest<HTMLElement>('[data-wheel-index-select]') : null;
      if (target) select(Number(target.dataset.wheelIndexSelect));
    }, { signal: abort.signal });
    wheel.addEventListener('click', (event) => {
      const target = event.target instanceof Element ? event.target.closest<HTMLElement>('button') : null;
      if (!target) return;
      if (target === previous) select((requestedService ?? active) - 1);
      else if (target === next) select((requestedService ?? active) + 1);
      else if (target.hasAttribute('data-wheel-select')) select(Number(target.dataset.wheelSelect));
    }, { signal: abort.signal });
    wheel.addEventListener('focusin', (event) => {
      if (!pinned || !overflow || !wheelWindow || !(event.target instanceof HTMLElement)) return;
      const bounds = event.target.getBoundingClientRect();
      const visible = wheelWindow.getBoundingClientRect();
      if (bounds.top >= visible.top && bounds.bottom <= visible.bottom) return;
      const pan = Math.max(0, Math.min(overflow, bounds.top - wheel.getBoundingClientRect().top - 8));
      window.scrollTo({ top: startPosition() + active * (reading + rotation) + (reading - overflow) / 2 + pan, behavior: 'instant' });
    }, { signal: abort.signal });
    reduced.addEventListener('change', invalidate);
    linkServices(details, abort.signal);
    observer = new ResizeObserver(invalidate);
    observer.observe(copy); observer.observe(wheel);
    details.forEach(detail => observer!.observe(detail));
    if (header) observer.observe(header);
    document.fonts?.ready.then(() => { if (!disposed) invalidate(); });
    schedule();
  } catch { stop(); }
  return stop;
}
