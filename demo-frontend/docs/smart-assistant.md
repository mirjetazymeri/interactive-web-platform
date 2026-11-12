# Smart Assistant (frontend)

The floating orb and full-screen assistant. All answers, suggestions and links
come from the Laravel engine (`/api/v1/assistant/*`, see
`web-admin/docs/cms/smart-assistant.md`). No AI service is called.

## How it fits together

| Piece | File | Notes |
|---|---|---|
| Bootstrap fetch | `src/lib/assistant/server.ts` | `getAssistant(locale)` runs server-side with the page's other CMS reads; returns `null` while the assistant is hidden or the CMS fails, so nothing renders and no assistant code is sent |
| Mount | `src/components/cms/shell.tsx` | `SiteShell` renders the launcher when `assistant` is set |
| Proxy | `src/app/api/assistant/route.ts` | Same-origin JSON only, 4 KB cap, zod input; forwards to Laravel with the visitor's locale |
| Contract | `src/lib/assistant/schema.ts` | Validates bootstrap and replies; drops any link that is not a same-locale site path, `mailto:` or `tel:`. The context also carries the conversation's `language` and last request type (`act`) |
| Language | `assistant-reply.tsx` | An answer follows the visitor's language, not the page's (an Albanian question on `/en` gets an Albanian answer); the reply is marked with `lang` and its links still point to `/en/…` |
| Launcher | `assistant-launcher.tsx` | CSS orb + admin tooltip (hover and keyboard focus); preloads the panel on hover/focus |
| Panel | `assistant-panel.tsx`, `assistant-reply.tsx` | Dialog in a portal, page behind made `inert`, focus trap, Escape, focus return, session in `sessionStorage` |
| Orb | `assistant-orb.tsx`, `src/lib/assistant/orb.ts` | Own WebGL fragment shader (no dependency), theme colours, states idle/thinking/responding/error, ≤1.5× DPR, paused when hidden, still for reduced motion, CSS fallback |

Admin changes appear on the next page load (bootstrap) or next question
(answers); Laravel's index cache follows edits automatically.

## Previewing before going live

The assistant stays hidden from visitors until **Admin → AI Assistant →
General settings → Show the assistant on the website** is switched on. To try
it locally while it is hidden:

1. Put the same random value (32+ characters) in both
   - `web-admin/.env`: `ASSISTANT_PREVIEW_TOKEN=…`
   - `web-frontend/.env.local`: `ASSISTANT_PREVIEW_TOKEN=…`
2. Run `npm run dev`. The orb appears with a "Preview — hidden from visitors" badge.

The frontend sends the token only from a development server (`next dev`);
production builds never do. Laravel ignores it unless configured, and preview
questions are not recorded as unanswered. Leave it unset on production servers.

## Going live checklist

- Unpublish placeholder projects (they appear in project answers).
- Configure Laravel to trust the Next.js server as a proxy, so its per-visitor rate limit (20/min) uses the visitor's address that the route forwards in `X-Forwarded-For`; until then all visitors share one limit.
- Enable the assistant in the admin; no rebuild or deploy is needed.

## Environment variables (Next.js)

| Variable | Production | Purpose |
|---|---|---|
| `API_BASE_URL`, `STOREFRONT_CHANNEL` | required (HTTPS URL) | Laravel API used server-side |
| `SITE_URL` | recommended | canonical URLs and sitemap |
| `TRUSTED_CLIENT_IP_HEADER` | set to the proxy's client-IP header | forwards the visitor address for per-visitor limits (see web-admin docs) |
| `ASSISTANT_PREVIEW_TOKEN` | **leave unset** | development preview only; ignored by production builds anyway |
