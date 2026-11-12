# Hero refinement — 15 October 2026

This records the current implementation and supersedes the earlier video speed,
overlay and title interaction choices in `hero-media.md`. Navbar, layout, CTA,
subtitle, Laravel contracts, uploads and CMS data are unchanged by this refinement.

## Implementation

- CSS still caps the title at 170px (exactly 170px from 1024px); smaller screens
  retain the existing responsive size and wrapping. The heading is server-rendered
  through a client boundary, with server-segmented Unicode graphemes and one
  continuous accessible name. Cursive scripts retain their connected shaping and
  whole-title entrance rather than splitting joined glyphs.
- Outer character spans retain the sequential 650ms entrance. After its actual
  animation promises settle, inner spans respond to a fine, hovering pointer.
  A 150px radius produces up to 12px translation and 5 degrees rotation, with
  exponential interpolation and an exact zero snap after settling (~600ms on exit).
  Moving glyphs do not participate in hit testing; the stationary heading is the
  interaction boundary. No React updates occur on pointer movement.
- Character centres are cached from stationary outer spans. Resize, font loading,
  entry and scroll invalidate measurements. There is no permanent animation loop;
  frames stop once settled. Cleanup cancels frames and removes listeners/observers.
  Touch/coarse-pointer and reduced-motion visitors receive no displacement.
- Video retains one stable element and source-scoped controller. Playback rate is
  0.85, applied at source setup and `loadedmetadata`, with equality guards. Ordinary
  React renders, pointer movement, scroll and color effects never reload the source
  or reset `currentTime`. Existing muted/loop/inline/autoplay recovery remains.
- Exactly one `rgba(0,0,0,0.25)` overlay covers the whole video, including behind
  the header. It has no pointer events and never animates.
- A separate decoration controller waits a random 8–14 seconds, then animates only
  the video's grayscale: 220ms into original color, 200ms hold, 300ms back. It uses
  the Web Animations API with no dependencies or per-frame React state. Hidden,
  offscreen, paused/error and reduced-motion states cancel the accent and pending
  timer. Resuming starts a fresh interval; missed events are never replayed.
  Source changes/unmount remove every observer, timer, animation and listener.

## Title-driven color reveal

- The existing title pointer handlers now also request color from the decoration
  controller. Cached stationary word bounds bridge the gaps between letters;
  empty heading margins do not activate color. Letter physics, entrance/reset,
  typography and layout are unchanged, with no React state updates on hover.
- Hover eases grayscale to zero over 240ms and returns over 460ms. Reversals
  sample the currently displayed filter before replacing the animation, including
  when taking over an automatic accent. Held hover suspends accents; their fresh
  delay starts after the return finishes. No saturation or tint is introduced.
- The neutral 25% black overlay and text shadow remain for readability. The same
  video continues playing at 0.85×. Reduced-motion and non-hover devices retain
  the default grayscale treatment; preference changes and cleanup cancel effects.
- Verification: all 18 tests, TypeScript and ESLint pass. Controller tests cover
  slow movement through letter gaps, rapid reversal from intermediate color,
  accent takeover, empty margins, exit/reset, preferences and cleanup. The live
  browser showed grayscale zero with displaced letters, original cyan footage,
  unchanged title geometry, then grayscale one and empty transforms on exit.
  Playback remained active on one video/source at 0.85×; no browser errors appeared.

## Actual source and limits

The live API still serves
`https://web-admin.test/storage/widgets/heroes/45dd7485-d43c-4ae2-8f15-ee5ebcbc0ae3.mp4`.
The inspected source is H.264, 3840×2160, 500 frames over 20 seconds (constant 25fps),
55,141,379 bytes (~22 Mbit/s overall), with metadata before media payload.
At 0.85×, its unique-frame cadence is **21.25fps**. Native slow playback cannot add
source frames or remove an edit at the loop boundary; 4K decoding and delivery cost
also remain. No transcoding, interpolation, seeking or frame skipping was added.
For smoother slow motion, a higher-frame-rate original is needed; simply changing
an export's nominal FPS cannot recover missing motion information.

## Verification

- 18 tests passed, including source assignment once, metadata rate restoration,
  autoplay rejection, visibility, reduced motion, async cancellation, accent
  scheduling/cleanup and repeated pointer entry/exit with exact restoration.
- TypeScript, ESLint and Webpack production build passed. The existing Turbopack
  worker limitation is unchanged. Isolated production renderer checks passed for
  image/video/no-media, legacy contracts, Unicode/long titles, locales and errors.
  Local server checks required running outside the network-restricted sandbox.
- Live browser: actual HTTPS video, readyState 4, rate 0.85, progressing playback
  throughout letter interaction. Offscreen playback paused at 6.292s and resumed
  at 6.523s, preserving position. HTTPS byte-range delivery returned 206 with
  `video/mp4`, `Content-Range: bytes 0-15/55141379`, and 16 bytes.
- Observed a complete gradual grayscale 1 → 0 → 1 accent while playback remained
  at 0.85. Original cyan colors were visually inspected. Nearby letters displaced,
  distant letters stayed still, and rapid entry/exit left all inner transforms empty.
- Desktop computed title: 170px. Exactly one 25% overlay starts at y=0 and covers
  the 1100px Hero in a 1000px viewport. At 390px, title size is 64.8064px. No horizontal
  overflow, new console errors or hydration warnings appeared in inspected views.
- Touch capability, hidden-tab and reduced-motion changes were tested in controller
  simulations; physical touch hardware and OS-level preference switching were not
  tested. Frame-drop counters were unavailable through browser inspection, so zero
  dropped frames or universally smooth playback are not claimed.
