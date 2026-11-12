# Hero media integration

For the current typography, overlay, playback and navbar treatment, see
[Hero/navbar refinement](hero-refinement.md). It supersedes the earlier visual
choices recorded below; the API contract remains unchanged.

## Ownership and confirmed contract

This Next.js 16.4 App Router project is the public storefront. The Hero editor,
session authentication, uploads, validation and save workflow live in the separate
Laravel repository at `../web-admin`. No duplicate editor, upload proxy or backend
changes were introduced.

The contract was checked against Laravel's `docs/cms/hero-media.md`,
`HeroMediaService`, `WidgetRenderer`, `StoreWidgetRequest`, `HeroVideo`, the Blade
Hero editor and its feature tests, plus a live public page response.

The existing frontend read is
`GET /api/v1/pages/{path}?include_widgets=1&device=desktop|mobile`, with `X-Locale`,
`X-Channel` and `Accept: application/json`. Hero placements expose:

```ts
config.media: {
  type: 'image' | 'video';
  url: string;
  mime_type: string | null;
  size: number | null; // bytes
} | null
```

`config.bg_image` remains an image-only legacy field. The shared normalizer accepts
it only when `media` is absent. Explicit `null`, invalid media and unsafe URLs never
restore an obsolete image. Metadata and signed URL query strings are preserved;
type is never inferred from an extension. Invalid media falls back to the Hero's
color and content instead of failing the whole page.

For reference, the **external Laravel editor** uses:

| Action | Existing contract |
|---|---|
| Create | `POST /admin/widgets`, multipart |
| Update | `POST /admin/widgets/{id}`, multipart with `_method=PUT` |
| Image replacement | `config[bg_image_file]`; Laravel `file|image|max:5120` (5 MiB) |
| Video replacement | `config[bg_video_file]`; MP4, WebM or MOV; default 100 MiB, configurable by `HERO_VIDEO_MAX_KB` |
| Preserve media | Omit both file fields and removal flags |
| Remove media | `config[remove_media]=1`; legacy `config[remove_bg_image]=1` is also accepted |

Create/update require `type=hero` and `status=active|inactive`, alongside the usual
title/settings fields. Both uploads together, or upload plus removal, are invalid.
The installed Laravel image rule accepts JPG/JPEG, PNG, GIF, BMP and WebP; SVG is
not enabled. Video validation checks extension against detected MIME and container
structure: MP4 (`video/mp4`, `application/mp4`, `video/x-m4v`), WebM (`video/webm`),
MOV (`video/quicktime`, `video/x-quicktime`). This does not certify browser codecs.

Writes require an authenticated, verified Admin session, channel permissions and
CSRF. Success redirects (302); ordinary form failures use session errors. JSON
validation uses 422 `{message, errors: {"config.bg_video_file": ["..."]}}`.
Upstream body limits may return 413. These are documented boundaries, not new
frontend write endpoints; upload/replacement/removal transactions were not submitted
or retested from this repository.

## Public behavior

Images retain the existing native image helper, lazy loading, empty decorative
alternative text and cover positioning. The cinematic design removes the central
panel and shade layer for both images and video. Video is a small client component;
headings, copy and links stay server-rendered. It uses the exact public URL and the
media server's HTTP Content-Type for decoding, with native muted, looping, inline
playback at 0.65×, reapplied when source metadata loads. Only video receives a
grayscale filter. There are no backend playback settings, posters or caption tracks in this
background-media contract. No thumbnail service or codec conversion is added.

No `src` or autoplay attribute is emitted during server rendering. After hydration,
visible video starts only when reduced motion is off. Reduced-motion visitors see
the stable configured background, without downloading
the video first. Hidden placements do not load their video. Shared desktop/mobile
placements retain the existing single-element merger. Playback pauses offscreen or
in background tabs; a user's Pause choice persists. Preference changes stop motion,
and pending play promises cannot undo a later pause or unmount.

The persistent play/pause button and its reserved space are removed. An
English/Albanian keyboard-only control appears on focus, preserving a way to pause
without placing permanent controls over the video. Loading, blocked autoplay and playback errors have readable
status messages. Failed video leaves the configured fallback, text and CTA usable.
Listeners, observers and the media source are released on unmount/replacement.

## Delivery and deployment

Media comes from Laravel's public storage disk, without API authorization headers.
Use a browser-accessible HTTPS origin with trusted certificates and correct video
MIME mappings, `Range` support and `206 Content-Range` responses. Preserve signed
queries if a CDN supplies them. There is no new Next.js upload body/timeout setting.
Laravel/PHP/proxy upload limits still need to accommodate the configured video limit
plus multipart overhead; see the backend contract for deployment values.

Accepted MOV and some MP4/WebM encodings may not play in every browser. Prepare
browser-compatible files; failures are handled without claiming codec support.

## Verification — 15 October 2026

### Cinematic design refresh

The leading Hero now starts behind the header at the page top, with a 110svh desktop
minimum and 105svh mobile minimum that grows for content. Safe-area padding and
header clearance keep text usable. Header transparency is scoped by the presence
of a leading Hero for each device; scrolling restores the existing surface and
mobile disclosures remain unchanged. Other pages retain their original header.

Backend titles keep their capitalization and use the existing Arial/Helvetica
font stack. Short titles fit the available width; long titles wrap. Server-side
grapheme segmentation preserves combining marks and emoji; cursive scripts keep
their shaping with a whole-title reveal. A 750ms entrance plus up to 600ms stagger
runs once. CSS leaves text visible without JavaScript and disables all title
animation for reduced motion. A shared leading Hero owns the single semantic h1;
its continuous accessible text is separate from decorative animated letters.
Subtitle and monochrome CTA use the existing data and disappear cleanly when empty.

Refresh checks: all **16** unit tests, lint, TypeScript, the Webpack production build
and expanded isolated checks passed. Live browser inspection verified grayscale,
0.65× playback, no central panel/shade, no persistent pause UI, top position 0,
1100px Hero height at a 1000px desktop viewport and approximately 93% title width.
1440px, 768px, 390px and 320px widths had no horizontal overflow. Long/Unicode
fixtures, mobile menu/Escape, CTA navigation, sticky scrolled styling and the
unaffected About header were checked. No browser hydration/console errors were
reported. Reduced-motion playback is covered by simulated preference tests and
title behavior by the CSS media rule; OS-level preference switching was not tested.
The live CMS currently spells the heading `demo`; this was not changed.

### Original media integration checks

- `npm test`: all 15 tests passed, including normalization, explicit removal,
  reduced motion, visibility, manual pause, blocked playback, unsupported playback,
  loading, cancellation races and cleanup. Playback-policy tests use simulated DOM
  events/preferences; they do not claim an OS-level reduced-motion browser test.
- `npm run lint` and `npm run typecheck`: passed.
- `npm run build -- --webpack`: passed. Default `npm run build` failed in this
  environment because Turbopack's CSS worker exited before connecting, including
  outside the sandbox. No validation was disabled or build configuration changed.
- `npm run verify:isolated`: passed production HTML checks for legacy/new images,
  video, no media, failed media, single shared placement, CTA preservation and safe
  initial hydration, plus existing routing/theme/error checks.
- Browser checks: isolated sample video decoded and played; Enter paused and Space
  resumed; 1440px desktop and 390px mobile had no horizontal overflow. The unavailable
  video displayed its message while copy/CTA remained visible. No browser console
  warnings/errors were reported in the inspected pages.
- `npm run verify:live` against the production frontend on port 3001: passed both
  locales, public pages and expected 404s. The real saved 55,141,379-byte MP4 played
  in the browser. A system-trusted HTTPS range request returned 206,
  `video/mp4`, `bytes 0-15/55141379` and exactly 16 bytes. Node alone did not trust the
  local self-signed certificate; TLS checks were not disabled.
- No live CMS content, uploads, administrator settings or backend files were changed.
  Other codecs/browsers and production CDN configuration were not tested.

For repeatable isolated browser previews after building:

```powershell
$env:VERIFY_KEEP_SERVER='1'
$env:VERIFY_VIDEO_FILE='C:\path\to\a\browser-compatible.mp4'
npm run verify:isolated
# Open http://127.0.0.1:3002/en/hero/video (also image, legacy, none, broken).
# Ctrl+C stops both temporary servers. No backend writes are performed.
```

Without `VERIFY_VIDEO_FILE`, video delivery intentionally returns 404; production
markup tests still run. Sample files are read only and never added to the frontend.
