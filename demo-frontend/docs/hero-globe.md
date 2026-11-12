# Globe hero

## Content contract

The hero widget gains an optional `config.rotating_labels`: an ordered list of short strings, edited in web-admin as **Globe labels** (one per line, up to 12, 60 characters each; blank lines and duplicates are dropped). Each locale's translation section has its own Globe labels field. A translated list replaces the base list as a whole; an empty translation field uses the base list.

When the list is non-empty the frontend renders the globe hero. The stored background image or video is left untouched and not rendered, so clearing the labels restores the media hero without re-uploading anything. No label text exists in the frontend.

## Experience

- Background `--color-accent`, text `--accent-foreground`; title, subtitle and CTA come from the existing hero fields. The CTA uses the dark surface tokens so it stays visible on the accent.
- A dotted WebGL globe ([cobe](https://github.com/shuding/cobe) 0.6.4, ~6 KB) turns once a minute. Its sphere, glow and markers are derived at runtime from the theme tokens; there are no fixed colors.
- Up to three labels are pinned to marker dots. Every 4 seconds the oldest fades out and the next label in the list appears at the most front-facing free marker that is at least one chip height away from the other chips. Chips fade toward the horizon, hide behind the globe and open leftwards on the right half.
- Pin positions use `globePoint`, which mirrors cobe's shader projection (radius 0.8, rotation by theta/phi), and are written to the DOM per frame without React state updates.
- Rendering pauses when the hero is off screen or the tab is hidden. With reduced motion the globe stays still and labels do not rotate.
- Without WebGL, or if theme colors cannot be resolved, a flat accent sphere is shown instead of the canvas. All labels are always present in the server HTML as a visually hidden list; the animated pins are `aria-hidden`.
- Below 900px the copy stacks above the globe.

## Files

- `src/lib/cms/hero-globe.ts`: label parsing, marker list, projection, marker choice, rotation step, color helpers.
- `src/components/cms/hero-globe.tsx`: canvas, pins and timers.
- `src/components/cms/widgets.tsx`: selects the globe hero when labels exist.
- `src/app/globals.css`: `.hero-globe*` styles.
- Tests: `tests/hero-globe.test.ts`; `scripts/verify-isolated.ts` page `hero/globe`.
- Backend (web-admin): `HeroWidgetTypeService` (normalization, translations), `Widget::localizedConfig` (list replacement), `StoreWidgetRequest` (validation), hero form, translation form and create-form fan-out, feature test in `HeroMediaTest`.
