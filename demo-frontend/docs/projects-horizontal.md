# Horizontal Projects and branded ending

The existing full-bleed project panels, API item order, Services handoff, metadata, translations, and detail routes are preserved. Only `horizontal_scroll` (including normalized legacy `horizontal`) receives the closing scene. Empty widgets render nothing. A single project has its project panel followed by the closing panel.

## Timeline

`projects-horizontal-motion.ts` owns one timeline per widget: actual measured track overflow (now including the closing panel), a reading interval of max(320px, 55% of viewport height), and an exit interval of max(480px, 115% of viewport height). Horizontal motion stops when the closing panel is fully visible. Its text moves upward and fades before the logo becomes compact. The circle group then shrinks and recedes upward, entirely from scroll position; reversing reconstructs every frame.

The SVG clip uses three positive foreground circles, with a separate `useId` identifier for every widget. The initial large-circle radius exceeds the viewport diagonal, so the first clipped frame fully covers the stage. A transform on the clipPath keeps the three original proportions and spaces together. `projects-horizontal-ending.ts` contains the deterministic geometry and timing.

The existing pin overlaps the next content by at most one stage height. The overlap is capped to the remaining normal-flow content height, measured from the page shell; a short footer therefore ends at the stage boundary instead of leaving unused scroll space below it. Long following sections keep the full-stage reveal. A ResizeObserver tracks changes to that content. There is no cloned content, relocated next section, separate pin, second spacer, scroll lock, or wheel interception. Once the CTA fades, its article is inert and hidden from accessibility navigation, and the stage stops intercepting pointer input. Reverse scroll restores it. Observers, SVG transforms, styles and listeners are cleaned up on unmount.

Phones, tablets, coarse pointers and desktop all use the same native vertical-page-scroll timeline. The old 900px/fine-pointer gate has been removed. Reduced motion instead displays every panel and the CTA in a normal vertical document, without a pin, mask exit, arrows or horizontal gesture requirement. Keyboard arrows/Home/End work on the stage. Tabbing into the closing link brings it into view, and tabbing onwards reaches the real following content.

## Sources and theme

- Logo geometry comes from `public/brand/demo-original.png`, the unchanged 180 × 50 configured logo. The symbol occupies its first 67 × 50 pixels. Alpha-component bounds are large `(0,15)–(34,49)`, medium `(33,0)–(56,23)`, and small `(53,23)–(65,35)`. Circle centers/radii preserve these proportions. No original asset pixels or colors were edited.
- `projectLabels()` supplies English “Explore all projects”, Albanian “Shiko të gjitha projektet”, and Hungarian labels.
- The action uses existing `pageHref(locale, "projects")`, distinct from widget-owned detail URLs. Read-only live CMS checks confirmed the published `projects` page in English and Albanian. The action navigates only on activation.
- Closing background uses the exact API `--color-primary`, with existing `--surface-foreground` ink. Borders, directional cue, focus and hover derive from those tokens. As explicitly requested, the exit silhouette turns white after the CTA fades; this is a scoped brand-color exception to the earlier all-API-color requirement. Project panel colors are unchanged.
- Confirmed theme limitation: the contract supplies primary/secondary/accent colors but no dedicated light page-surface token. No field or static white was invented. The transition is transparent around the positive silhouette, revealing the actual following content and the established API-derived page background. Its appearance therefore follows the API theme rather than forcing the reference's light surface.

## Verification (2026-10-20)

Browser checks against the production build and isolated API at 1440 × 900, 1280 × 800, 1024 × 768 and 390 × 844:

- Last project partially exits as the closing scene enters; CTA is fully readable and clickable during its hold.
- Early background reveal has curved boundaries; the compact frame visibly contains three separate logo circles, then shrinks and moves upward before clean release into the unchanged vertical widget.
- Reversing restores the circle surface, CTA, and horizontal project sequence. No scrolling triggers navigation.
- CTA activation reaches `/en/projects` and `/sq/projects`. Mobile Albanian copy wraps readably, with a visible theme-driven focus outline and no document overflow.
- Single-project and multi-project widgets have independent measured timelines and unique clip IDs. The other instance remains at its initial transform while the first exits.
- Live homepage regression: reproduced roughly 345px of unused space below the footer at a 720px viewport; adaptive overlap now aligns the footer and page-shell bottom with the viewport bottom. The production short-footer fixture also ends within one pixel of the viewport boundary. Multiple widgets retain independent full or capped overlaps.
- Resizing during the exit preserves a usable stage and coherent logo geometry. Mobile retains the pin, horizontal track and logo exit; only layout and type scale change. Services-to-Projects boundary coordinates still match.
- Alternate Albanian API palette changes page, closing foreground/ink, borders and focus together; no source palette was added.

Automated tests additionally exercise zero/single/multiple items, reduced-motion unpinning and restored static CTA, timeline clamping and exact reversal, initial full coverage, viewport geometry, resize preservation, image/font refresh, and cleanup. The connected browser has no reduced-motion media emulation, so that preference is verified through the lifecycle tests rather than claimed as a browser check. No physical iOS/Android device or Safari session is connected. Browser checks use the in-app Chromium viewport and normal vertical page-scroll input; they are not physical touch or mobile Safari certification.

All 58 tests, ESLint, TypeScript, and `npm run build -- --webpack` pass. The isolated integration script covers canonical and legacy variants, English/Albanian, index/detail destinations, placement and item updates, and existing Services/Vertical renderers. It never changes CMS data. Run `node --import tsx scripts/verify-projects.ts --browser` for its temporary preview at port 3019 (mock API 4019).



## Responsive scroll refinement

The cause of the mobile mismatch was `matchMedia("(min-width: 900px) and (pointer: fine)")`, which explicitly selected the native horizontal/arrow fallback on small or touch screens. Pinning now depends on actual overflow and reduced-motion preference only. The pinned stage allows native vertical touch panning and pinch zoom, and clips the transformed track; no touch cancellation or document lock was added.

The CTA uses a minmax grid text column plus a separate arrow column, fluid phone typography, safe-area padding, intentional word wrapping and a 44px minimum action height. At 320px the complete Albanian label wraps into two readable lines without internal or document overflow; the localized destination is unchanged.

`projects-horizontal-sequence.ts` inserts measured reading intervals only for text taller than the safe stage area. During those intervals the panel stays in place while its information moves from top to bottom; horizontal movement then resumes. This also supports enlarged text. Focus navigation maps to the reading position needed to reveal each action. Overflowing project information receives the existing contrast-safe overlay. No nested vertical scroller is created.

The stage fills `100dvh`; timeline distances and reading measurements use the existing stable `100svh` viewport probe. Toolbar-only geometry changes can update the mask and bounded footer overlap without remapping the timeline. Width/orientation changes preserve horizontal or reading progress, including the closing phase. The same observer/listener lifecycle remains scoped to each widget.

Responsive browser checkpoints include 320×568, 390×844, 430×932, 768×1024, 1440×900, 844×390 and a stress case at 568×220: vertical input advances the track, upward input reverses it, the phone shows three white foreground circles, English/Albanian CTAs fit, and the short-landscape stress case pans overflowing text before moving the track. The stress case verifies the content-height behavior used for enlarged text; OS text scaling was not emulated. Automated tests additionally cover coarse/small viewport pinning, reduced-motion vertical flow, arbitrary panel readings and widths, resize remapping, stable toolbar timing, footer overlap and cleanup.


## Mobile visual correction

Portrait phone images now use contain sizing inside a dedicated area above the measured information block. This shows the complete supplied preview instead of stretching a landscape mockup across a full-height portrait cover crop. The reduced-motion document keeps an explicit image aspect-ratio area. Desktop full-bleed cropping remains unchanged.

The phone CTA uses smaller, light-weight fluid type with wrapping when needed, plus a standalone 24px SVG directional arrow without a circle. The visible link remains one action with the existing localized destination. Live API browser checks at 390×844 (English) and 320×568 (Albanian) confirmed the full image preview, readable label, visible arrow and no document/link overflow. All 58 tests, lint, TypeScript and the Webpack production build pass.
