# Projects integration

Projects use the existing page composition endpoint, slots and placement ordering. The single registry entry is `projects`; only `config.variant` selects the horizontal or vertical component, including legacy aliases. Services placement settings do not affect Projects. Parent widget IDs are passed into cards; links are generated as `/{locale}/projects/{widgetId}/{slug}` rather than following backend detail URLs.

The detail route uses the configured server transport and `widgets/{widgetId}/projects/{slug}?device=...`, retaining channel, locale and no-store conventions. Desktop/mobile requests follow the existing page strategy. HTTP 404 uses Next.js not-found; transport/contract failures reach the existing error boundary. Detail text is escaped, paragraphs retained, and up to three images are ordered by position. Website links accept only absolute HTTP(S) URLs.

Schemas retain project/widget identity, positions, featured state, nested images, navigation URLs and additional API fields. Cards retain returned project order. A valid empty widget renders nothing; malformed configuration logs a diagnostic and shows a development-only message; unknown widget types retain the registry diagnostic. Separate device responses no longer overwrite different configurations of the same placement.

## Verification on 2026-10-19

- TypeScript, ESLint and 52 unit tests pass.
- Production webpack build passes (`npm run build -- --webpack`). Default Turbopack build was stopped after it made no progress beyond the compilation banner.
- `node --import tsx scripts/verify-projects.ts` passes against a production server and isolated fixture API: multiple widget IDs on one page, canonical/legacy variants, English/Albanian, empty/invalid/single/optional content, Services, correct parent detail links, ordered galleries, escaped paragraphs, website and unavailable details. Updating the fixture API adds an item and changes placement order without rebuilding.
- Read-only live API checks in English/Albanian and desktop/mobile return widget 17 as `horizontal_scroll` with zero projects. This differs from the older snapshot in projects-horizontal.md. No live content was modified.
- No new browser or physical touch verification was performed for this change. Populated/detail verification is fixture HTTP integration verification, not live browser verification.

## CMS setup

Save and publish project items within each Projects widget, set its horizontal/vertical variant, and attach/reorder through page composition. Separate simultaneous layouts require separate widget instances. Empty widgets need no frontend fix. Real populated details/media remain to be verified when CMS items exist.

## Detail scroll template (2026-10-20)

The reusable `ProjectDetail` template renders introduction → foreground main image → description/work delivered/goal → second and third images → `ProjectsHorizontal`. The introduction uses native sticky positioning within the opening section; the later opaque theme surface scrolls above it. Its minimum covering height follows the measured introduction, including long translations. No artificial pin spacer is added. Paragraph and secondary-media entrances use the existing native scroll/requestAnimationFrame approach, reverse with scroll, and dispose observers/listeners on unmount. Reduced motion removes the sticky opening and restores readable normal flow.

The current backend has no detail-page showcase assignment contract. The frontend uses the supported public `GET widgets/{id}?device=...` resolver with the same locale/channel transport; that resolver returns published projects in CMS order. It filters the current widget/item identity and renders the existing horizontal component directly, without altering saved variant configuration. An empty collection retains the localized Projects index link. Detail copy is escaped and paragraph breaks remain intact. Images are ordered, deduplicated by supplied identity, capped at three, contained without cropping, and not repeated as a separate main-image fallback when the collection is populated.

Verification: TypeScript, ESLint, 58 unit tests, production webpack build, and extended isolated production HTTP integration checks passed. Integration covers English/Albanian, context forwarding, ordered detail fields/media, current-item exclusion, optional metadata, one-image/empty-showcase fallback and 404s. Browser verification used published widget 18 (TreForma) in English and Albanian plus fixtures for long titles and missing fields. Checked visible image overlap and reverse scroll, real desktop/phone assets, description, mobile viewport without horizontal overflow, vertical-driven horizontal track with `pan-y pinch-zoom`, closing scene/three-circle exit, footer release, and localized index navigation. Landscape long-title layout was inspected. Physical touchscreen and OS reduced-motion preference toggling were not tested; reduced-motion behavior is implemented through CSS and media-query lifecycle handling. Production preview: http://127.0.0.1:3020/en/projects/18/treforma.
