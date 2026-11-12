# Responsive animation correction — 2026-10-19

## Reproduced cause

Compared the live Home page at 1920 × 1080 and 1366 × 768 before editing. Reduced motion was false at both sizes. Both enhancers initialized; the failure was their subsequent content-fit eligibility checks, not a missing animation provider or a pointer requirement.

- `service-x-deck-motion.ts`: Home used `serviceDeckFits`, requiring `contentHeight + 32 <= viewportHeight - top`. At the laptop size, a roughly 643px card plus the heading and padding required about 718px, but only 664px remained below the header. The code removed `data-deck-pinned` and substituted the small document-flow reveal, losing the overlap and dissolve.
- `impact-motion.ts`: the two-column branch required `max(copy.scrollHeight, wheelHeight) + padding <= available`. The composition measured about 750px against 656px available. The code unpinned the wheel, and the unpinned render path only responded to its buttons, not page scrolling.
- During browser resize testing, a second defect emerged: the enhancers derived the old animation position from bounding boxes after breakpoint reflow had already moved the sections. Resizing the preceding wheel changed the deck's start position and lost its transition phase.

## Implemented changes

- `src/components/cms/service-x-deck-motion.ts`: Home always keeps its native-scroll sequence when motion is enabled and a presentation area is available. Each card gets its original reading interval plus the distance required to reveal any overflowing content. The live surface pans vertically during that reading interval, then the existing rise/dissolve replaces it. No rasterized text, additional particles, global wheel interception or dependencies. Keyboard focus reveals an offscreen link in the active card. Exceptions restore readable static markup. Resize preserves the last rendered card/overlap phase; observing document height catches changes to preceding content.
- `src/components/cms/service-x.module.css`: a measured presentation window contains each Home card; only its live surface pans. Reduced motion restores normal document flow. Other slider variants and dedicated Services chapters retain their existing behavior.
- `src/components/cms/animated-widget-html.tsx`: adds a window around the existing wheel inside its existing runway.
- `src/components/cms/impact-motion.ts`: if the whole composition cannot fit, pin the wheel independently while retaining the text layout. Adapt orbit size and use compact number labels on short screens. For unusually short viewports, scroll through the wheel's height during each service's reading interval, then rotate to the next service. Existing buttons and keyboard focus share that scroll position. Cache the rendered position across reflow.
- `src/components/cms/impact-widget.module.css`: independently sticky wheel window and desktop text column; short-window panning and visible focus outlines; static reduced-motion fallback.
- `tests/service-x-deck.test.ts`, `tests/impact-motion.test.ts`: regressions for long cards, short-screen pinning, resize with preceding layout movement, state/controls, cleanup and failure recovery.

The hero, dedicated Services implementation, routes, CMS content, integrations and animation technology were audited and preserved in this correction. Hero entrance and pointer response and dedicated Services disclosures were checked in the browser.

## Actual browser verification

Used the in-app Chromium browser against the live development site, with native scroll input outside the cards. These are browser viewport tests, not physical-device tests.

| Viewport | Wheel | Home Services |
| --- | --- | --- |
| 1920 × 1080 | Rotated through all four services; reversed | Rising overlap and pixel mask visible; all three reading states; release |
| 1536 × 864 | Same | Same |
| 1440 × 900 | Same | Same |
| 1366 × 768 | Same; independent wheel pin | Same; content pan before replacement |
| 1280 × 720 | Same; independent wheel pin | Same; content pan before replacement |
| 1024 × 768 | Same; stacked wheel pin | Rising overlap and pixel mask visible; all three reading states; release |
| 768 × 1024 | Same; stacked wheel pin | Same |
| 390 × 844 | Same; compact labels | Same; full-height content reading pan |
| 375 × 667 | Same; compact labels | Same; full-height content reading pan |
| 320 × 568 | Same; short-window reading pan | Same; full-height content reading pan |
| 844 × 390 landscape | Same; short-window reading pan | Same; full-height content reading pan |

At every size, inspected wheel and dissolve screenshots and live transforms/mask paths; scrolled forward and backward; checked final release and horizontal overflow. Separately sampled the middle of every card's reading interval: one card interactive, other cards inert, no dissolve residue. No document horizontal overflow was measured.

The exact same-scroll-position pixel output is covered by deterministic geometry tests. Browser reverse captures restored the fragmenting composition; native input sometimes rounded the target by one CSS pixel, so those slightly different positions are not asserted to have identical path strings.

Resized 1920 × 1080 → 1280 × 720 → 390 × 844 → 844 × 390 → 1920 × 1080 during overlap. The incoming card retained approximately 50% translation throughout (within native scroll rounding), ending at exactly 50%. The wheel retained the selected second service across the same cycle.

Reloaded at 375 × 667; both sequences initialized. Navigated Home → Services → Home using the desktop navigation and phone menu. Dedicated Services retained its disclosures and had no Home deck. Home reinitialized on return. Activated the landscape wheel's Next control with Enter and confirmed service 02 and -90° rotation. Observed the hero CSS entrance and letter deformation on the laptop viewport. Browser console inspection returned no warnings or errors, including no hydration warnings.

## Automated checks

- `npm test`: 47 passing tests (including reduced motion, touch/pen handlers, failure recovery and listener cleanup).
- `npm run typecheck` and `npm run lint`: pass.
- `npm run build -- --webpack`: pass.
- `npm run verify:service-x`: pass, including Home-only variant, other placements, translated content and edge cases.
- `npm run verify:isolated`: pass, including routes, hero variants, CMS rendering and error responses.

## Limits

Physical phones/tablets, Safari/iOS, real touch/trackpad hardware, and expanding/collapsing mobile browser toolbars were not tested. The browser tool exposes viewport sizing but no touch or reduced-motion emulation; those paths were exercised by the existing lifecycle tests and inspected in code, not visually verified under an OS reduced-motion setting. No mobile frame-rate profiling was performed. Very short screens intentionally reveal tall content through native-scroll reading intervals rather than shrinking text or disabling the effects.
