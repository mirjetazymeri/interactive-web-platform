# Hero World widget (`hero-world`)

A homepage hero with a slowly rotating dotted 3D globe and CMS-managed service points. It is a regular widget type: administrators create it under **Widgets → Hero World**, attach it to a page in the page builder, and reorder, toggle or detach it there like any other widget.

## Content contract (web-admin → API)

`config` (base content; every text field is translatable):

| Field | Notes |
| --- | --- |
| `headline` | Required, ≤255. Rendered as the page H1 when the widget is the leading hero, otherwise H2. |
| `subtitle` | Optional, ≤1000. |
| `cta_text`, `cta_link` | Optional; a link is required when a label is set. Links are internal paths/slugs, `#section`, http(s), mailto or tel (validated in `HeroWorldRules::link`, then again by `cmsHref`). Not translatable. |
| `seo_title`, `seo_description` | Optional, ≤70 / ≤320. |
| `services[]` | ≤60 items: `id` (stable), `title` (required, ≤80), `description` (≤300), `icon` (one of `HeroWorldWidgetTypeService::ICONS`), `lat`/`lng` (both or neither), `position`, `active`. |

Translations are stored in `widget_translations.config` like other widgets: widget-level text fields plus `services[<id>][title|description]`. Layers apply base → fallback locale → requested locale, matched by service id, so reordering or deleting services never mixes translations. Translations cannot change icons, coordinates, order or status.

The public API (`WidgetRenderer::resolve`) returns only active services with a title, sorted by `position`, with coordinates normalized to numbers or `null`. Text is returned verbatim; the frontend renders it as escaped React text.

## Frontend

- `src/lib/cms/hero-world.ts`: content parsing, automatic placement for services without coordinates, time-based rotation (`advancePhi`, one revolution per 105 s), label collision layout, SEO helper, `isHeroType`.
- `src/components/cms/hero-world.tsx`: server-rendered copy, CTA and service list.
- `src/components/cms/hero-world-globe.tsx`: cobe WebGL globe (loaded with a dynamic import), per-frame pin placement through direct DOM writes, theme-derived paint, pause off screen or in hidden tabs, reduced-motion support, WebGL fallback, cleanup on unmount.
- `src/components/cms/hero-world.module.css`: layout and pins; theme tokens only.

Behavior:

- Background `--surface-background` (API `primary_color`); dots and glow from `--color-accent`; markers from `--secondary-action`; chips on `--surface-elevated`. A dark canvas gets a dark sphere with bright dots, a light canvas the inverse. Theme changes on `<html>` repaint the globe without rebuilding it.
- The globe is never empty: it keeps N − 1 labels for N services (all of them for one or two), within what the stage can fit without overlap (3 on small phones up to 7 on large desktops). Fixed geographic points would spend half of each turn behind the sphere, so labels use a presentation layer (`createPresentation` / `stepPresentation`): each label rides a real anchor point on the sphere that turns with the globe. Anchors enter on the left of the visible hemisphere; near the right limb a service fades out while the next queued service fades in on the left, one out and one in, in CMS order. The opening population appears at once (140 ms stagger). Configured latitude/longitude no longer pins a label to a place; the presentation chooses anchor rows so labels spread over upper, middle and lower regions on both sides.
- Labels open toward the centre, flip or shift slightly up or down to avoid each other and the stage edge, and keep their previous placement between frames. Phones use compact, truncating labels; labels are always on the globe, never in a list.
- Connected layer (`src/components/cms/hero-world-interaction.ts`, maths in `hero-world.ts`), desktop two-column layout only:
  - Up to three thin curved lines run from the right end of the title, subtitle and CTA to visible service markers whose label opens away from the copy. A line keeps its service while it is visible, fades out when it leaves and then takes the free visible service closest to the copy. At rest the lines sit at 14% opacity with a slow signal dot; they brighten with pointer presence, proximity, a nearby marker or a hovered CTA.
  - A fine pointer adds a large, low-opacity accent glow that follows with damping, a ±3° globe parallax that eases back when the pointer leaves, marker proximity (brighter marker, quicker ring, crisper label), slightly brighter globe dots near the pointer and a magnetic CTA (≤ 5 px) with an arrow nudge and accent glow on hover.
  - Everything runs inside the globe's frame loop (one loop, paused off screen), reads layout only on resize, is `aria-hidden` and pointer-inert. Touch devices, the stacked layout and reduced motion get no pointer effects; reduced motion also drops the signal dots and the glow.
- Hover (mouse) or tap shows a service's description. A visually hidden list carries all service text for screen readers.
- Layout: one centred container (`--hero-gutter`, max 1600px) holds both columns, so the title's left margin equals the globe glow's right margin; the globe fills its column, capped by the viewport height. Below 1024px: title, subtitle, globe, then a compact centred CTA.
- Text updates (language switches) only re-render content; the globe and its rotation are not restarted.

## SEO and page integration

`hero-world` counts as a hero type (`isHeroType`): on the homepage the first hero widget moves to the top, gets the navbar overlay and owns the single H1. Metadata precedence: page SEO → leading Hero World SEO → page title/global defaults. Canonical and the existing metadata pipeline are unchanged.

## Tests

- web-admin: `tests/Feature/HeroWorldTest.php`.
- web-frontend: `tests/hero-world.test.ts`; `scripts/verify-isolated.ts` pages `hero/world`, `hero/world-seo`, `hero/world-many`, `hero/world-empty`.
