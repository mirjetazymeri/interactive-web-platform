# Home project composition disintegration

The former image-only canvas sampled image pixels and capped drift at 20px. It could not break up lettering or spread fragments beyond the media frame. It has been replaced by `service-x-composition-dissolve.ts`, isolated to the existing Home variant.

## Rendering

A single aria-hidden, pointer-transparent canvas lives in the sticky stage, outside card clipping. At measurement time it caches each project's source image and actual lettering in transparent offscreen canvases. Canvas does not capture HTML automatically: a DOM Range measures the browser's wrapped grapheme runs, groups them into lines, and the renderer explicitly paints those lines with their computed font, weight, size, spacing, case and color. Text tiles sample transparent letter shapes rather than solid bounding boxes. Resting content always uses the original semantic DOM.

A deterministic diagonal wave fractures the composition. Squares move upward/right with varied distances, slight rotation and delayed fading; incoming fragments converge from below/left. Travel bends into available section space so fragments remain visible on narrow screens. Outgoing and incoming phases share the existing native-scroll progress, without time-based continuation. Large compositions briefly pull back during the transition to include their headline, image and lower copy, then return to the original reading scale. Reading intervals, content panning, pin duration and section release are unchanged.

Source measurements refresh through the existing resize, font-load and image-load lifecycle. If assets or rendering are unavailable, the existing semantic card transition remains visible. Reduced motion uses the established static layout without particles. Cleanup removes the canvas, releases snapshots and restores original opacity. No dependencies or routes changed.

## Changed files

- `src/components/cms/service-x-composition-dissolve.ts`: replaces the image-only renderer with measured text/image snapshots and scroll-driven fragment trajectories.
- `src/components/cms/service-x-deck-motion.ts`: connects the Home transition interval and cleanup to the new renderer; leaves existing responsive measurements and timing intact.
- `src/components/cms/service-x.module.css`: positions the decorative stage canvas and restores surface opacity for reduced motion.
- `tests/service-x-composition-dissolve.test.ts`: replaces image-only tests with source-lettering, displacement-before-fade, deterministic reversal, redraw avoidance, fallback and cleanup coverage.

## Verification — 2026-10-19

Actual Chromium browser viewport testing at **1920x1080, 1366x768, 768x1024 and 390x844**. Inspected fracture (~15%), dispersion (~34%), incoming reconstruction (~84%) and complete resting states, plus reverse scrolling. Both lettering and image fragments visibly detach; the next state restores sharp original content with the decorative canvas hidden. The section label and navigation remain outside the effect. No horizontal document overflow at any tested size.

Also checked resize through phone/tablet/desktop/laptop sizes while mid-transition, phone reload, Home -> Services -> Home client navigation, the second project transition, mobile menu interaction, final-project reading and release to the footer. Dedicated Services had no composition canvas; returning Home created one. On cold navigation the fallback remained readable until lazy images loaded, then fragmentation initialized. No browser warning/error logs were recorded.

Passed: 49 automated tests, typecheck, lint, production webpack build, and the isolated Service X route/content verification suite. Reduced motion and failure/cleanup paths have automated coverage; reduced motion was not visually emulated in this browser.

Screenshots: `C:/Users/demo/.codex/visualizations/2026/10/16/01a144b3-3c13-70f2-bd86-a278624e98ee/composition-pixels/`. Each requested viewport has fracture, dispersion, reconstructed and reverse captures; additional incoming-reconstruction captures are available for desktop, laptop and phone.

These were browser viewport tests, not physical-device or touch-hardware tests. Safari, other browser engines, physical mobile toolbar behavior and GPU/frame-rate profiling remain unverified. The measured text renderer is tailored to this component's existing plain-text content; it is not a general HTML/CSS screenshot engine.
