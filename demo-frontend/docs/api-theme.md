# API-driven theme

## Verified source and mapping

Verified read-only against the actual default-channel API on 2026-10-16:
`GET /api/v1/settings`, with `X-Channel`, `X-Locale` and `Accept-Language` from the existing transport.

| API field | Role |
| --- | --- |
| `data.theme.primary_color` | Page canvas and origin of surfaces, text, borders, shadows and overlays |
| `data.theme.secondary_color` | Secondary action background, contrast-adjusted when necessary |
| `data.theme.accent_color` | Primary actions, accent text, selected states, focus and decoration |

The observed response was `#050505`, `#000480`, `#ff9778`. Those values are **not application defaults**. They appear only in isolated test palettes. The captured settings fixture and the adjacent Laravel `SettingResource`, `SettingController`, and `config/settings.php` confirm the nested field mapping. The initial sandbox DNS failure was resolved by making the read-only request outside that sandbox.

## One pipeline

`getSettings(locale)` → response adapter → `validateTheme` → `themeVariables` → `[locale]/layout.tsx` → inline CSS properties on `<html>`.

No provider fetches or client-side color derivation are needed. The server serializes exactly the values inherited by client components. Three- and six-digit opaque hex strings are accepted and normalized; missing, non-string, named, alpha, CSS-expression and malformed values are rejected. All three fields must validate.

| Tokens | Use |
| --- | --- |
| `--color-primary`, `--color-secondary`, `--color-accent` | Validated API inputs (raw secondary is not used directly for text) |
| `--surface-background`, `--surface`, `--surface-elevated` | Canvas and two levels of surface |
| `--surface-foreground`, `--text-muted` | Readable text across all three surfaces |
| `--border`, `--divider` | Controls / meaningful boundaries, decorative separation |
| `--accent-foreground`, `--accent-text` | Contrast partner on accent fill, adjusted accent for text on surfaces |
| `--accent-hover`, `--accent-hover-foreground` | Paired action hover state |
| `--secondary-action`, `--secondary-foreground` | Visible secondary action and readable text |
| `--state-hover`, `--state-selected`, `--state-selected-foreground` | Navigation states; selected also has underline and weight |
| `--disabled-background`, `--disabled-foreground` | Disabled controls, accompanied by disabled semantics |
| `--focus-ring` | Focus outlines, offset from controls |
| `--overlay`, `--shadow`, `--accent-glow` | Media legibility, elevation and decorative transparency |
| `--feedback-background`, `--feedback-foreground`, `--feedback-border` | Form/unsupported-widget feedback, always with text and status/alert semantics |
| `--navbar-surface`, `--navbar-foreground`, `--navbar-active` | Compatibility aliases into the same palette |

Derivation is centralized in `src/lib/cms/theme.ts`. Surfaces mix primary toward its contrast endpoint, with a contrast guard at middle luminances. Muted text, accent text and focus are adjusted to at least 4.5:1 against every surface. Control borders and secondary fills achieve at least 3:1 against surfaces. Action/selected foregrounds achieve at least 4.5:1 against their fills. Decorative dividers and glows carry no essential information. Black/white are mathematical contrast endpoints, not a replacement fixed neutral or brand palette.

Hero overlays use the smallest tested opacity that protects foreground text even over the brightest or darkest possible media pixel. The original media file and existing motion behavior remain unchanged. No service image or logo pixels are altered.

## Migration policy

Global styles, header/menu/language disclosures, footer, shared buttons, forms, feedback, hero controls, ServiceX slider and stack, and the Impact widget now consume the same tokens. Tailwind's fixed default color palette is disabled. Separator-specific and Hero-specific saved color overrides are superseded by the global brand palette; layout, media and content settings remain supported.

CMS HTML remains sanitized. Its CSS remains parsed/scoped by `cleanWidgetCss`, with paint translated centrally by `theme-css.ts`: text to foreground, backgrounds to surface, explicit `.button`/`.btn`/`.cta` or `button` selectors to paired action colors, hover to paired hover colors, borders/focus/shadows to their semantic roles. Transparency is preserved. Borders retain width/style; gradients become a semantic surface; source shadows become a semantic elevation. Arbitrary source CSS variables and resource loads remain disallowed. This prevents saved `!important` colors, named colors, RGB/HSL or gradients from bypassing the API. Existing uploaded artwork remains untouched.

For custom widgets, use the documented action class hooks on filled actions. A future explicit CMS `surface/action` role would be preferable to adding new color fields. Embedded third-party video/map contents are external documents; the surrounding consent UI is themed, but their internal pixels are outside frontend styling control.

## Freshness, channel isolation and failures

The existing strategy is unchanged: transport uses `cache: "no-store"`; React `cache` deduplicates settings during one server render. Pages are dynamic. Reloading or navigating via the existing native links makes a new settings request, so API theme changes need no rebuild. There is no background polling; an already-open page keeps its server-rendered theme until navigation/reload.

Requests retain channel and locale headers. No module-global cache, local storage, or cross-tenant fallback was added. There is no persistent frontend last-known-good settings cache to reuse. Valid settings already resolved in the current React render are reused through the existing cache. Failed/incomplete settings on a later request follow the existing CMS error path and yield HTTP 500 with an explicit content/configuration-unavailable screen. No partial theme or fabricated brand palette is served. This deliberately favors correctness over silently showing an obsolete tenant theme.

Laravel caches settings by `api.settings.v1.channel_{channelId}`; normal settings saves clear this key in `SettingService`. Direct database edits that bypass that service require backend cache invalidation. No backend cache or live setting was changed here.

Before any valid locale/configuration can be resolved (entry redirect, global 404, root configuration failure), the minimal status UI uses native browser/system `Canvas`, `CanvasText`, `Highlight` and `HighlightText`, not a fallback brand. Valid localized pages inherit API colors for their own error boundary.

## Verification

`npm test` includes validation failures, all semantic text/control pairs across dark/light/identical inputs and 256 grayscale primaries, overlay extremes, CMS CSS paint mapping, and an audit rejecting fixed colors in TSX/CSS. `npm run verify:service-x` switches its mock settings response through dark, light and low-contrast palettes without rebuilding, checks HTTP 500 for a missing theme, and restores the mock dark theme.

Only the isolated mock server has `/__theme/dark`, `/__theme/light`, `/__theme/low` and `/__theme/invalid` QA controls (port 4012). Production has no test-theme endpoint. No live brand values were changed.

Remaining source color literals are black/white contrast endpoints in the theme module. Other legitimate literals are synthetic test data, historical documentation, unused starter artwork under `public`, and original brand/media assets. System colors on unconfigured error pages are deliberate. Native browser validation widgets and externally embedded documents use their own rendering. No component-specific fixed interface palette remains.
