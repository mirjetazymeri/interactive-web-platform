# ServiceX redesign

## Contract and architecture

Next.js 16.4 App Router / React 19.3. Pages are server-rendered from the existing localized, channel-specific CMS endpoint. The local Next.js guides for server/client boundaries, CSS, and non-Cache-Components caching were read before implementation. No dependencies or second theme provider were introduced.

The registered widget type stays `service_x`. Presentation always comes from `placement.placement_config?.display_mode ?? "slider"`, preserved through the existing Zod adapter (including Laravel's empty arrays). Page pathname never selects ServiceX presentation. Returned service and subservice order, IDs, translations, active flags, `image` and `image_alt` are preserved. There are no per-service requests.

Actual API verification on 2026-10-16 found:

- Services: page **2**, widget **15**, placement **18**, `stacked`.
- Home: page **1**, widget **14**, placement **17**, `slider`.
- These are different widget records, not shared content. Consolidating them is an optional CMS editorial action, not part of this frontend implementation.
- `/en/contact` and `/sq/contact` are existing contact routes. The form has no supported service-context prefill contract, so actions link directly without invented query parameters.

## Experience

Services has one localized page-level H1. Breadcrumb/title spacing is compact, and existing page body content remains the introduction when supplied. Currently Services `content` is null, so no introduction is invented. Other pages' headings keep their CMS titles. Any explicitly placed Services hero uses a compact supporting presentation instead of a new full-screen hero.

**Update 2026-10-28 — findable catalogue.** The bento layout was too heavy to scan with eight services. Desktop (≥1000px) now pairs a sticky sidebar index of every service (scroll-spy `aria-current`) with one reading column per service: a moderate H2 name, the value proposition, the introduction, every solution as a ruled two-column list (H3, full description and its own "Ask about this solution" link) and a "Discuss this service" button. The oversized lead tile and the chips were removed. Below 1000px the same single list collapses into one sticky bar that names the service in view; tapping it opens the list from anywhere on the page (closes on selection, Escape or outside tap). While stuck, the bar paints an opaque backing up to the viewport top so copy never shows through the floating header. Contact links carry `?topic=<Service> — <Solution>` (or just the service); the contact form reads it once on load and starts an empty message (and subject, when shown) with it. The topic is display text only, capped at 200 characters, and never trusted server-side. Service images, when supplied, render as a modest 21:9 frame.

**Update 2026-10-26 — bento layout, no numbering.** The Services page shows no generated indices: a leading CMS index (`01 Development`) is removed from the displayed name, and the sticky anchor strip is a floating pill segmented control. Each service is a bento composition: a lead tile with the H2 name and value proposition over the CMS image (a surface-tinted scrim keeps text readable on any upload; without an image it uses an accent glow), an intro tile with the introduction, "What we can help with" (H3) chips that jump to each solution, and the filled "Discuss" button. Below, every solution is a visible tile (H3 title, full description, contact link) with a hover/`:target` glow; a lone solution becomes one wide row. Below 1000px the bento stacks. The accordion was removed, so no description is hidden behind a click; `enhanceServiceChapters` finds no triggers and only runs one-shot entrances. All paint uses API theme tokens via `color-mix`. The earlier description follows for reference.

Each stacked service is an H2 using `name`, followed by a supporting paragraph from `title`, introduction from `subtitle`, original media in a compact contain frame, and a service contact action. A narrow desktop identity column sits alongside a wider, full-width ruled list of prominent H3 capabilities. This gives subservices more visual weight than the supporting image. Tablet and mobile use natural vertical flow. Images reserve space but do not dominate the page. Missing/failed images disappear without hiding text. A final contact action serves undecided visitors.

The sticky native anchor strip is inside its placement, beneath the measured site header. It releases at the end of that placement, so multiple placements do not compete. On mobile it scrolls horizontally. IDs are placement-namespaced and service IDs are encoded for DOM anchors. IntersectionObserver plus passive, animation-frame-throttled scroll measurement tracks the last section crossing the reading line, including tall sections and gaps. Active navigation has weight, background and an underline. Passive scrolling never changes history. Header/nav ResizeObservers keep offsets accurate; direct links are corrected once after measurement. No scroll hijacking is introduced; reduced motion disables smooth scrolling.

Subservices now use editorial rows rather than rounded cards. Each row has a sequential number, a large title, a decorative SVG trace and an explicit plus/minus indicator. Its H3 contains a real disclosure button with `aria-expanded` and `aria-controls`. The separate detail region contains the entire API description and a contextual contact link. Each service initially opens its first row after enhancement; selecting another closes its sibling, and the open row can also be closed. Scrolling never changes that reading state. No inferred benefits, FAQs, deliverables, service-specific content maps or invented claims are generated.

All descriptions and links are server-rendered and visible before enhancement. Disclosure buttons are disabled until their handlers are ready; their titles remain visible. If JavaScript fails or is unavailable, visitors can read every description and use the contact links. Enhancement collapses the remaining panels and enables controls. Hidden panels are inert during closing, then use `hidden`; open panels return to intrinsic height after animation. Rapid reversals cancel the old animation safely. If closing a long preceding panel would carry the selected control under the sticky navigation, that closure settles immediately and the control is brought below the reading line without moving focus. Initial deep links are resolved again after enhancement changes preceding content heights.

### Motion direction and safeguards

The motion vocabulary uses native CSS and the Web Animations API, consistent with the existing project; no animation library was added. On fine pointers, only the hovered row receives a 260ms lateral surface reveal, a 5px title shift, accent emphasis and a single 320ms SVG trace. Keyboard focus receives the same emphasis with a visible outline. Touch operation uses the same disclosure buttons and never depends on hover. Intro/heading reveals last 320–380ms; a list enters once with a capped 45ms stagger. IntersectionObserver observes headings and lists separately, so long mobile introductions do not consume an offscreen list's reveal. Base CSS never hides content in anticipation of animation.

On viewports at least 1100px wide and 800px tall, an identity may stick beneath the measured header/navigation if its complete content fits with 40px spare and the real list is at least 64px taller. It occupies a separate column and cannot cover subservice controls. There are no artificial runways, wheel interception, fullscreen pins or forced scenes. During the last 120px of a qualifying chapter's natural travel, only its image wrapper recedes by at most 12px and scales to 0.97. Text remains fully opaque. The transform reverses with ordinary backward scrolling. Short chapters (including the current live content), oversized identities, smaller viewports and reduced-motion mode use normal flow.

Scroll work is animation-frame-throttled DOM measurement with no per-frame React updates. Active navigation only updates React when the active ID changes. ResizeObserver, image load, font readiness and viewport changes recalculate eligibility; disclosure changes do not require fixed height assumptions. Observers, listeners, animations and inline presentation are cleaned up. Reduced motion cancels in-flight animation, restores intrinsic disclosure heights, removes pinning and decorative transforms, and retains the current expanded state and all controls. The existing Hero video pause control is local to that video, not a global motion preference.

Every effect uses the existing semantic theme tokens: canvas/foreground, muted text, divider, hover surface, accent text and focus ring. SVG uses `currentColor`; uploaded image pixels remain unchanged. No new palette or API request was introduced.

Only decorative generated indices are suppressed when a name already starts with the matching index and a clear separator. CMS names are not stripped or mutated. `01 Development` remains that exact heading; it no longer becomes `01 — 01 Development`. Names such as `3D development`, `24/7 support` and `ISO 27001` remain intact. Stacked presentation does not add widget-level label/index chrome above the service categories.

Home's slider placement is now a full-width sequence driven by native vertical document scrolling. The initial HTML contains every service in normal flow. A passive scroll observer enhances it only when the complete tallest card, section label and comfortable padding fit below the header. The dedicated runway provides 0.65 viewport heights (at least 420px) of reading time per service and 0.95 viewport heights (at least 600px) per transition. The final card receives its full reading interval before the sticky stage releases.

**Update 2026-10-26 — stacked cover, no pixel dissolve.** Every slider placement now uses one motion: the next service rises from +100% below the presentation area and covers the current one, in unchanged CMS order, with no horizontal travel. The covered service stays in place beneath it, dimming to 40% opacity and scaling to 0.96 in step with the scroll. The SVG mask, canvas composition dissolve and their modules (`service-x-dissolve.ts`, `service-x-composition-dissolve.ts`) were removed. `home-vertical` now only selects Home's page paint. Each card leads with the CMS service name as its heading (a leading index such as `01` is shown separately), the CMS `title` is supporting copy beside the image, and subservices are numbered cards. The dedicated Services page retains its stacked chapters and disclosures. Motion maps directly to scroll distance with no easing loop, timer or input interception. Scrolling over whitespace advances the same sequence; upward scrolling reverses it. Card frames and bottom navigation remain removed. Desktop surfaces use approximately 92% of the viewport (capped at 1800px).

Only the fully revealed active card accepts input; all card controls are temporarily inert during overlap to prevent click-through. Focus moves to the stage if its card becomes hidden. Short screens, oversized translations/content, reduced motion and no JavaScript use a readable vertical sequence with every card accessible. Resize and motion changes remove the runway and preserve the reader's position near the current service. Mobile uses this static sequence whenever the complete card cannot fit, preserving normal vertical scrolling without clipping. Subcategories remain unnumbered and noninteractive because the CMS supplies no destinations. The separate stacked Services page keeps its disclosures, navigation and contact links.

The About Us/Impact composition now uses a wider two-column grid with a 64–104px gap and top-aligned columns. The left composition has separate heading, introductory copy, CTA and supporting-copy intervals, including a 48–72px gap after the CTA. Supporting copy is constrained to 46ch with 1.85 line-height. Wheel geometry, reading intervals and controls are preserved; additional visual/detail/footer spacing keeps labels and controls separate. Below 1100px, the text and wheel stack with comfortable separation.

Verification: 43 tests, lint, TypeScript, the Webpack production build, isolated ServiceX scenarios and the broader isolated CMS suite pass. Browser checks at 1440×1000 and 1440×900 confirmed full-width pinning, progress from outer-margin scrolling, left-to-right entry, reverse travel, final reading time and release. Tablet 834×1112 fits the sequence; mobile 390×844 switches to natural flow with no inert cards or horizontal overflow. About Us columns share their upper edge; mobile wheel nodes stay within the column and its controls remain functional. Reduced-motion lifecycle, short-screen and oversized-content fallback are covered by automated tests; no physical device or OS preference-toggle verification is claimed.

### Home vertical transition verification (2026-10-19)

All 46 tests, lint, TypeScript, the Webpack production build and isolated ServiceX scenarios pass. The isolated suite verifies that the Home variant is absent on Services and all other placement scenarios in English and Albanian. Desktop browser checks at 1440×1000 confirmed zero horizontal translation, a rising horizontal pixel band at beginning/midpoint/end, an unchanged mask while idle, and exactly identical pixels after scrolling forward and back to the same position. The final service receives its reading interval and then releases normally. Tablet 834×1112 uses the compact mask; mobile 390×844 restores all cards in natural flow with no masks, inert content or horizontal overflow. The live Services page retains its stacked layout, navigation and working disclosures, with no deck mounted. Reduced-motion restoration, focus protection and oversized-content fallback are covered by automated lifecycle tests; no physical touch device or OS preference-toggle check is claimed.

## CMS correction checklist — actual response

No CMS data was changed and no sentence-based hiding rule was added. The following fields need editorial review before the page can fully explain the services:

| Record / field | Observed issue | Suggested correction |
| --- | --- | --- |
| Page 2 `content` | null | Add a short localized introduction, if desired |
| Widget 15 `config.services[0].title` | `Title` | Write an approved Development value proposition |
| Widget 15 `config.services[0].subtitle` and `[1].subtitle` | Admin instructions | Replace with visitor-oriented service introductions |
| Widget 15 `services[0].subservices[0].description` | Admin instructions | Explain Web & App Development in customer terms |
| Widget 15 `services[1].subservices[0].description` and `[1].description` | Admin instructions | Explain Business ICT / Cloud Services accurately |
| Widget 15 `services[1].subservices[0].title` | `Busines ICT` | Review spelling: `Business ICT` |
| Widget 15 `section_index` | `Services for service pagee` | Replace with a genuine editorial index or clear it; stacked view has no use for this label |
| Widget 15 service `name` fields | `01 Development`, `02 IT Cloud` | Optional: store category names alone and let presentation supply indices |
| Service images `image_alt` | `dev` / empty | Use useful alternatives when informative; keep empty for decorative imagery |
| Widget 14 Home | `SuCustom…`, `SuEnd-to-end…`, `Infrastructure that worksd`; IT descriptions repeat Development copy | Correct typographical and category-content errors independently, or consciously share one widget |
| Contact page 3 `content`; widget 6 `config.subtitle` | Seed/admin demonstration instructions | Replace with approved contact guidance |

The exact “Manage this content once…” source exists in backend `resources/views/admin/widgets/partials/service_x.blade.php:4` as editor help. The live API also contains that same text in the public fields listed above. The frontend mapping is correct: `subtitle` and `description` are rendered as supplied. The current public placeholder copy therefore remains visible until edited in the CMS, as required.

Check the base content, translations, and channel-specific overrides. Widget 15 service IDs are `svc-e45feb59-d0e9-4e32-8cb3-76de4d5a7543` (Development) and `svc-415657a0-4a43-45b4-b892-3c7d7862eb5c` (IT Cloud). Do not change these identities to repair copy.

### Editorial draft — for review, not published

Suggested page introduction: “Explore our services, review the areas we can help with, and contact us to discuss your needs.”

Suggested Development headline: “Websites and applications for your business.”

Service descriptions still need an editor or service owner to confirm scope. A useful authoring pattern is: one sentence describing the customer situation, followed by the actual assistance offered, then a benefit only when supported. This is guidance for the existing description field, not new structured facts added by the frontend.

Optional backend enhancements: translated `when_this_helps`, `assistance`, and `benefit` fields; a validated contact-service context; explicit semantic color-role choices for custom widgets. None are required or assumed by this implementation.

## Changed components

- `page-body.tsx`: compact Services introduction and heading ownership.
- `service-x.tsx`, `service-x.module.css`: stacked editorial rows, compact identity/media, responsive chapter composition and contact actions.
- `service-x-navigation.tsx`: small active-anchor client behavior.
- `service-x-experience.tsx`, `service-x-motion.ts`: focused client enhancement of server-rendered content, accordion lifecycle, one-shot entrances and safe chapter motion. Replaces the earlier standalone disclosure component.
- `service-x.ts` library: labels, safe anchors, index detection and description splitting.
- `service-x-slider.tsx`, `service-x-deck-motion.ts`, `service-x-dissolve.ts`: semantic cards, native scroll pinning, Home-only vertical overlap and deterministic vector dissolution. No bottom navigation.
- Theme, shared styles, widgets, Impact and form changes are detailed in `api-theme.md`.

## Verification and repeatable previews

Run `npm run lint`, `npm run typecheck`, `npm test`, `npm run build`, `npm run verify:isolated`, and `npm run verify:service-x`.

For an isolated production preview, set `$env:VERIFY_KEEP_SERVER='1'` and run `npm run verify:service-x`. It starts the mock API on 4012 and frontend on 3012; Ctrl+C stops both. No fixture is imported by production code. Preview `/en/services`, `/en`, and `/en/service-x/{empty,single,missing,long,multiple,multiple-slider,swapped}`; also test `/sq/services`.

The mock suite checks both locales, mode isolation, ordering, one H1, H2 service headings, SSR disclosure semantics and visible fallback content, distinct placement IDs, contact destinations, empty/single/missing/long/multiple content, theme changes without rebuild and invalid-theme failure. Unit tests include 260 palettes, contrast for semantic pairs and media overlays, numbering and a fixed-color source audit. Chapter tests cover viewport/content eligibility, bounded reversible handoffs, accordion reversal, state preservation on scrolling, reduced-motion preference changes, focus protection and cleanup.

Browser QA covers real CMS Services at 1440×900, 834×1112 and 390×844, plus isolated long/multiple/alternate-palette cases. Physical touch hardware and an OS reduced-motion preference toggle are not available in this browser tool; swipe logic and reduced-motion code paths are covered by tests and stylesheet inspection. No contact form was submitted or email sent. Supplied screenshot attachments were not available in this chat, so verification uses the actual local website and the written requirements.

### Initial implementation checks (2026-10-16)

- Lint, standalone TypeScript, all 37 unit tests, and the production build passed. The build required running outside the restricted Windows sandbox; no project configuration workaround was added.
- Both isolated HTTP suites passed, including all ServiceX scenarios in English/Albanian, API theme freshness, malformed/missing theme handling and existing Hero/CMS regressions.
- Live smoke checks passed for Home, Services, Contact, Privacy and Terms in English/Albanian, plus expected 404s.
- Browser checks confirmed responsive layouts, single H1, preserved CMS numbering without duplication, no horizontal overflow, measured anchor offsets, deep links, disclosure expansion/collapse via keyboard, visible focus, contact navigation, multiple unique placement IDs and working live Home slider selection. Browser logs contained no hydration errors in the checked pages.
- Light-theme forms used dark text on the API canvas and readable white text on purple mock buttons. Identical gray API inputs yielded adjusted readable text/link/focus tokens. Live theme settings were never modified.
- Actual Services still displays the CMS placeholders listed above. Its final desktop/mobile screenshots capture the implementation with that unchanged content.

Additional observed CMS cleanup: Home Hero widget 1 has `cta_link: "demo"`, which resolves to `/en/demo`; change it to an approved contact destination in the CMS. This is independent of the new ServiceX actions, which use the verified contact route.

### Editorial motion redesign checks (2026-10-16)

- The expanded regression suite has 40 passing tests, including the new chapter/disclosure lifecycle tests. Lint, standalone TypeScript validation and the final production build pass. Both isolated HTTP suites pass, covering existing CMS/Hero behavior, ServiceX modes, locales, empty/missing/long/multiple content, alternate API palettes and explicit configuration failure.
- Browser inspection of the real CMS at 1440×1000 and 390×844 confirmed the prominent row hierarchy, one-open-per-service behavior, correct anchors and no horizontal overflow. Real CMS chapters are short, so they intentionally use normal flow.
- Isolated multi-row content activates desktop pinning; the media transform reaches its bounded `translateY(-12px) scale(0.97)` while identity text retains opacity 1. Long identity copy disables pinning. The long-description browser case exposed a focused-control occlusion during sibling closure; the controller now settles that closure and restores the control below navigation.
- The corrected long-description case places the focused button at 187.7px below a navigation bottom of 159.6px. Ordinary forward/backward keyboard scrolling produced an intermediate media scale of 0.988 and returned it to 1, with expansion state and the URL fragment unchanged. A qualifying identity stayed at 184px beneath navigation; text was never faded during handoff.
- Tablet (834×1112), light API palette and identical-gray palette checks showed no horizontal overflow and usable row contrast. Multiple placements produced 32 distinct IDs; refreshing a direct link into the second placement landed its heading at 183.75px below navigation. The first placement's navigation had already left the viewport.
- Live Home selection changed to the second ServiceX slide with no stacked controllers mounted. A subservice action navigated successfully to the existing Contact form; no form was submitted. All new paint continues to pass the fixed-color source audit and centralized contrast tests.
- The browser tool supports clicks, keyboard navigation and scrolling, but has no dedicated hover or reduced-motion emulation command. Pointer/focus styling is inspected through interaction; reduced-motion lifecycle and fast animation reversal are exercised by tests. Physical touch hardware and frame-by-frame hover timing are not claimed as verified.
