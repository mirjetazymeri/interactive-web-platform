const clamp = (value: number) => Math.max(0, Math.min(1, value));

/** Pin only when the whole identity fits and the real list supplies reading room. */
export function chapterCanStick(width: number, height: number, offset: number, identityHeight: number, listHeight: number, reduced: boolean) {
  return !reduced && width >= 1100 && identityHeight > 0
    && identityHeight + offset + 40 < height && listHeight > identityHeight + 64;
}

/** The last 120px of a chapter's natural travel affect media only, never text. */
export function chapterHandoff(bottom: number, offset: number, identityHeight: number) {
  const progress = clamp((offset + identityHeight + 120 - bottom) / 120);
  return { y: -12 * progress, scale: 1 - .03 * progress };
}

export function enhanceServiceChapters(root: HTMLElement) {
  if (typeof ResizeObserver !== 'function') return;
  const section = root.closest<HTMLElement>('[data-service-x="stacked"]');
  if (!section) return;
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const chapters = Array.from(root.querySelectorAll<HTMLElement>('[data-service-chapter]'));
  const animations = new Set<Animation>();
  const panels = new Map<HTMLElement, { animation?: Animation; open: boolean; button: HTMLButtonElement }>();
  const disposers: (() => void)[] = [];
  let alive = true, frame = 0, offset = 0;
  let geometry: { chapter: HTMLElement; identity: HTMLElement; visual: HTMLElement | null; height: number; sticky: boolean }[] = [];

  function animate(element: HTMLElement, keyframes: Keyframe[], options: KeyframeAnimationOptions) {
    if (reduced.matches || typeof element.animate !== 'function') return;
    const animation = element.animate(keyframes, options);
    animations.add(animation);
    animation.finished.then(() => animations.delete(animation), () => animations.delete(animation));
    return animation;
  }

  function setPanel(panel: HTMLElement, open: boolean, immediate = false) {
    const state = panels.get(panel)!;
    // Read once per explicit toggle, not on scroll or on animation frames.
    const from = panel.hidden ? 0 : panel.getBoundingClientRect().height;
    state.animation?.cancel();
    state.animation = undefined;
    state.open = open;
    state.button.setAttribute('aria-expanded', String(open));
    panel.inert = !open;
    panel.hidden = false;
    const to = open ? panel.scrollHeight : 0;
    if (!immediate && !reduced.matches && from !== to) {
      // Only the outer disclosure clips while its height changes. Its text has
      // no opacity/transform animation, and the height returns to auto afterwards.
      panel.style.overflow = 'clip';
      const animation = animate(panel, [{ height: `${from}px` }, { height: `${to}px` }], { duration: 280, easing: 'cubic-bezier(.22,1,.36,1)' });
      if (animation) {
        state.animation = animation;
        animation.finished.then(() => {
          if (!alive || state.animation !== animation) return;
          state.animation = undefined;
          panel.hidden = !state.open;
          panel.style.removeProperty('overflow');
          measure();
        }, () => {});
        return;
      }
    }
    panel.hidden = !open;
    panel.style.removeProperty('overflow');
  }

  for (const chapter of chapters) {
    const buttons = Array.from(chapter.querySelectorAll<HTMLButtonElement>('[data-solution-trigger]'));
    const group = buttons.map(button => {
      const panel = document.getElementById(button.getAttribute('aria-controls')!);
      if (!panel || !chapter.contains(panel)) return null;
      panels.set(panel, { open: true, button });
      button.disabled = false;
      return panel;
    }).filter((panel): panel is HTMLElement => !!panel);
    group.forEach((panel, index) => setPanel(panel, index === 0, true));
    group.forEach(panel => {
      const button = panels.get(panel)!.button;
      const click = () => {
        const open = !panels.get(panel)!.open;
        const index = group.indexOf(panel);
        const collapsingAbove = open ? group.slice(0, index).reduce((height, sibling) => height + (panels.get(sibling)!.open ? sibling.getBoundingClientRect().height : 0), 0) : 0;
        const protectFocus = button.getBoundingClientRect().top - collapsingAbove < offset;
        // Closing is always explicit; scroll and resize never alter reading state.
        // If a long preceding panel would carry the clicked control under the
        // sticky navigation, settle that closure immediately and reveal the
        // control using its scroll margin. Never animate a focused row offscreen.
        for (const sibling of group) if (sibling === panel || (open && panels.get(sibling)!.open)) setPanel(sibling, sibling === panel && open, protectFocus && sibling !== panel);
        if (protectFocus && button.getBoundingClientRect().top < offset) button.scrollIntoView({ block: 'start', behavior: 'instant' });
        schedule();
      };
      button.addEventListener('click', click);
      disposers.push(() => button.removeEventListener('click', click));
    });
  }

  function update() {
    frame = 0;
    for (const { chapter, identity, visual, height, sticky } of geometry) {
      if (!visual) continue;
      const { y, scale } = reduced.matches ? { y: 0, scale: 1 }
        : chapterHandoff((sticky ? chapter : identity).getBoundingClientRect().bottom, offset, sticky ? height : 0);
      visual.style.transform = `translateY(${y}px) scale(${scale})`;
    }
  }
  function schedule() { if (alive && !frame) frame = requestAnimationFrame(update); }
  function measure() {
    if (!alive) return;
    const styles = getComputedStyle(section!);
    offset = (parseFloat(styles.getPropertyValue('--service-header-offset')) || 0)
      + (parseFloat(styles.getPropertyValue('--service-nav-height')) || 0) + 24;
    geometry = chapters.map(chapter => {
      const identity = chapter.querySelector<HTMLElement>('[data-chapter-identity]')!;
      const list = chapter.querySelector<HTMLElement>('[data-service-solutions]');
      const sticky = chapterCanStick(window.innerWidth, window.innerHeight, offset, identity.offsetHeight, list?.offsetHeight ?? 0, reduced.matches);
      chapter.dataset.chapterSticky = String(sticky);
      return { chapter, identity, visual: chapter.querySelector<HTMLElement>('[data-chapter-visual]'), height: identity.offsetHeight, sticky };
    });
    schedule();
  }

  const resize = new ResizeObserver(measure);
  for (const element of root.querySelectorAll<HTMLElement>('[data-chapter-identity], [data-service-solutions]')) resize.observe(element);
  const nav = section.querySelector('nav');
  const header = document.querySelector('.header-dock');
  if (nav) resize.observe(nav);
  if (header) resize.observe(header);
  window.addEventListener('scroll', schedule, { passive: true });
  window.addEventListener('resize', measure);
  root.addEventListener('load', measure, true);
  window.visualViewport?.addEventListener('resize', measure, { passive: true });
  document.fonts?.addEventListener('loadingdone', measure);
  const preference = () => {
    if (reduced.matches) {
      animations.forEach(animation => animation.cancel());
      panels.forEach((state, panel) => setPanel(panel, state.open, true));
    }
    measure();
  };
  reduced.addEventListener('change', preference);
  document.fonts?.ready.then(() => { if (alive) measure(); });

  // One-shot enhancement: the underlying SSR/CSS is visible even when JS, IO or
  // WAAPI is unavailable. Never attach opacity:0 to uninitialised content.
  const entrance = typeof IntersectionObserver === 'function' ? new IntersectionObserver(entries => {
    for (const entry of entries) {
      if (!entry.isIntersecting) continue;
      entrance?.unobserve(entry.target);
      const element = entry.target as HTMLElement;
      if (element.hasAttribute('data-chapter-entry')) animate(element, [{ opacity: .7, transform: 'translateY(10px)' }, { opacity: 1, transform: 'none' }], { duration: 380, easing: 'cubic-bezier(.22,1,.36,1)' });
      if (element.hasAttribute('data-solution-row')) animate(element, [{ opacity: .75, transform: 'translateY(12px)' }, { opacity: 1, transform: 'none' }], { duration: 380, easing: 'cubic-bezier(.22,1,.36,1)' });
    }
  }, { threshold: 0, rootMargin: '0px 0px -32px 0px' }) : null;
  root.querySelectorAll('[data-chapter-entry], [data-solution-row]').forEach(element => entrance?.observe(element));
  if (document.querySelector('[data-service-x="stacked"]') === section) {
    const title = document.querySelector<HTMLElement>('.services-page h1');
    for (const element of [title, nav]) if (element instanceof HTMLElement) animate(element, [{ opacity: .75, transform: 'translateY(8px)' }, { opacity: 1, transform: 'none' }], { duration: 320, easing: 'ease-out' });
  }
  measure();
  // Accordion enhancement can shorten preceding chapters after the navigation
  // has measured its initial anchor. Resolve deep links against the final layout.
  let hash = '';
  try { hash = decodeURIComponent(window.location?.hash.slice(1) ?? ''); } catch { /* malformed fragment */ }
  const linkedChapter = chapters.find(chapter => chapter.id === hash);
  if (linkedChapter) linkedChapter.scrollIntoView({ behavior: 'instant', block: 'start' });

  return () => {
    alive = false;
    cancelAnimationFrame(frame);
    resize.disconnect(); entrance?.disconnect();
    window.removeEventListener('scroll', schedule);
    window.removeEventListener('resize', measure);
    root.removeEventListener('load', measure, true);
    window.visualViewport?.removeEventListener('resize', measure);
    document.fonts?.removeEventListener('loadingdone', measure);
    reduced.removeEventListener('change', preference);
    disposers.forEach(dispose => dispose());
    animations.forEach(animation => animation.cancel());
    panels.forEach(({ button }, panel) => {
      panel.hidden = false; panel.inert = false; panel.style.removeProperty('overflow');
      button.disabled = true; button.setAttribute('aria-expanded', 'true');
    });
    geometry.forEach(({ chapter, visual }) => { delete chapter.dataset.chapterSticky; visual?.style.removeProperty('transform'); });
  };
}
