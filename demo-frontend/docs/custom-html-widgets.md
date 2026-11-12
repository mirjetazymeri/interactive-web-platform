# Custom HTML widget rendering

Custom widget HTML continues through `cleanWidgetHtml`, and CMS styles through `cleanWidgetCss`. The existing sanitizer, link localization, API mapping and section restoration are unchanged. CMS JavaScript is never executed. Every CMS CSS selector remains scoped to its content-derived widget identifier.

## Impact services wheel

`AnimatedWidgetHtml` recognizes the stable `ptx-home-impact` template ID, not the headline text. The API-rendered HTML stays intact inside its presentation wrapper, with a sibling services wheel. Other HTML widgets retain their original output. `impact-services.ts` retains the supplied service categories and offerings, without invented links or endpoints.

The native controller uses one progress model with four reading plateaus and three reversible quarter turns. Orbiting cabins counter-rotate to keep labels upright. When pinned, native page scrolling supplies progress; controls scroll to the corresponding service stop. When unpinned, those same controls animate that model without changing the page's scroll position. There is no automatic rotation, cancelled scrolling, body lock or additional animation library.

## Responsive fix

The previous wheel was hidden unless both the 1200px width and 900px height conditions were met. Its orbit layout also used a fixed 660px height, a large diameter, an 82% center offset and clipping around all content. The section used different gutters from the footer.

The section and footer now share `--site-content-max` (1200px) and `--site-content-gutter` (32px, 24px below 768px). The footer's resulting dimensions are unchanged. Every grid child can shrink, and the section has a centered, bounded layout. Only the decorative rings and glow are clipped inside their own layer; nodes, details, controls and focus outlines are not clipped.

Two columns are used at 1100px and above; below that, the wheel follows the API content in normal flow. This breakpoint provides room for two readable columns. Wheel visibility does not depend on pinning. The actual wheel width and measured node dimensions determine its orbital diameter and visual height, with clearance for focus outlines. Wider columns show category labels; narrow columns show numbered nodes with the full selected category and offerings below. Labels are not scaled with a parent transform.

Details and controls remain in normal flow. All service articles participate in the detail grid's natural sizing, reserving enough height for the longest description without imposing a text height limit. Previous/next targets are 46px square. Long API copy removes pinning without removing the wheel.

Pin eligibility is measured from the actual column layout, copy height, complete wheel height, vertical padding and visible fixed-header clearance. The finite native sticky runway remains 2.4 viewport heights on qualifying layouts. Removing a pin clears its height/spacer immediately and keeps the wheel in reading context. Resizing back restores the selected service where practical. Resize observers cover the copy, wheel, details and header; font readiness also refreshes geometry. No-JS, reduced-motion and initialization-failure paths preserve the complete static service list. Cleanup removes listeners, observers, frames, dimensions and state.

## Verification of the responsive fix

Live-page checks selected all four services at each size below, checked the wheel and node bounds, compared the section's horizontal bounds with `.footer-inner`, checked detail/control separation and 44px minimum targets, and reached the footer. No horizontal document overflow was found.

| CSS viewport | Layout | Pinned | Four services / bounds / footer |
| --- | --- | --- | --- |
| 1920 × 1080 | Two columns, labeled wheel | Yes | Passed |
| 1440 × 900 | Two columns, labeled wheel | Yes | Passed |
| 1366 × 768 | Two columns, compact orbit | No | Passed |
| 1280 × 720 | Two columns, compact orbit | No | Passed |
| 1024 × 768 | Stacked, labeled wheel | No | Passed |
| 768 × 1024 | Stacked, labeled wheel | No | Passed |
| 390 × 844 | Stacked, numbered wheel | No | Passed |
| 360 × 800 | Stacked, numbered wheel | No | Passed |
| 844 × 390 | Short landscape, stacked | No | Passed |
| 720 × 450 | Narrow/short viewport | No | Passed |

Unpinned layouts had zero leftover pin spacer. A 1440×900 → 360×800 → 1920×1080 round trip retained service 03 and removed/restored the spacer correctly. Reverse scrolling on the large layout returned from service 03 to 02 while the stage stayed pinned. API section markup remained unchanged. Browser console diagnostics were also checked.

Actual browser 200% zoom could not be applied through the preview's keyboard shortcuts: both attempted zoom shortcuts left `innerWidth`, `innerHeight` and device pixel ratio unchanged. The 720×450 test checks an equivalent CSS viewport reduction from 1440×900, but is **not** claimed as a browser-zoom test.

All 28 unit tests, lint, TypeScript, the Webpack production build and isolated production HTTP checks passed. Unit tests cover continuous reverse progress, the exact services, all-angle cabin bounds including taller labels, content measurement, unpinned controls, selection retention, preference changes, runtime failure and cleanup. Reduced motion and dynamic long-content changes were tested with mocked browser APIs, not changed OS settings or a modified live CMS response. Production HTTP checks include both locales and unrelated widgets.
