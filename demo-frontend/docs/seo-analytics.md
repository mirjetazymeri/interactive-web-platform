# SEO, structured data and analytics

How the website builds search metadata, what the CMS controls, and what to do
when the production domain is ready.

## Where things are decided

| Concern | Source of truth | Code |
| --- | --- | --- |
| Site defaults (title, template, description, share image, organization, verification, indexing switch) | Admin → SEO & Tracking → SEO Settings (`seo.*` settings) | `src/lib/cms/seo.ts` |
| Per-page SEO (title, description, canonical override, OG title/description/image, index/follow, schema type) | Page editor → SEO tab and language tabs | `src/lib/cms/page-seo.ts` |
| Per-project SEO (SEO title/description, index switch) | Widget editor → project item → Search & sharing | `src/lib/cms/page-seo.ts` |
| Languages a page really exists in | Laravel `SeoService::pageLocales()` (default language + page/widget translations) | `canonicalUrls()` |
| Sitemap entries | `GET /api/v1/sitemap` | `src/app/sitemap.ts`, `src/lib/cms/sitemap.ts` |
| robots.txt | `SITE_ENV` + indexing switch + site origin | `src/app/robots.ts` |
| JSON-LD | CMS content only | `src/lib/cms/structured-data.ts` |
| Analytics providers and consent | Admin → SEO & Tracking → Analytics & Tracking | `src/lib/tracking/*`, `src/components/tracking/*` |

## Environment separation

* `SITE_ENV` defaults to `development`. Only `SITE_ENV=production` can produce
  `index` robots directives, open robots.txt, or load GTM / GA4 / Meta Pixel.
* The site origin comes from `SITE_URL` (or `NEXT_PUBLIC_SITE_URL`), then the CMS
  "Production website URL". In production a local or test host (`localhost`,
  private IPs, `*.test`, `*.local`, …) is refused, so no localhost canonical can
  ship; pages then have no canonical and stay `noindex`.
* `TRACKING_DEBUG=true` (non-production only) shows the consent banner and logs
  events to the console without loading any provider.
* The Laravel host sends `X-Robots-Tag: noindex, nofollow`, its robots.txt
  disallows everything except `/storage/` (media used in link previews), and its
  `/sitemap.xml` redirects to the website's sitemap once the production URL is set.

## Canonical and hreflang rules

* Canonical = the page's own URL without query string (`?topic=` etc.), in the
  current language. `/{locale}/home` permanently redirects to `/{locale}`.
* A language without its own content (no page or widget translation) renders
  fallback content; its canonical points to the default-language URL and it is
  not listed in hreflang or the sitemap.
* hreflang + `x-default` (the default language) are emitted only on canonical
  pages that exist in two or more languages.
* An editor canonical override that is a site path (`/services`) is localized;
  an absolute `https://` URL is used as is. Overridden pages leave the sitemap.
* A project slug published by several widgets has one owner (indexable item
  first, then the lowest widget id); the other URLs canonicalize to it.

## Structured data

One `@graph` per page: `Organization` and `WebSite` (from settings), the page
(`WebPage`, `ContactPage`, `CollectionPage`, `FAQPage`, `AboutPage`, `ItemPage`),
`BreadcrumbList` (matching the visible breadcrumb; projects: Home › Projects ›
Project), `Service` nodes for ServiceX services (with their solutions as an
`OfferCatalog`), `CreativeWork` for projects, and `FAQPage` questions only when a
visible FAQ widget exists. Nothing is invented: no ratings, reviews, prices or
awards. Validate with <https://validator.schema.org/> and Google's Rich Results Test.

## Analytics events

Events go through `track()` (`src/lib/tracking/events.ts`), which keeps only an
allowlist of non-personal parameters and drops anything shaped like an email
address or phone number.

| Event | When | Meta Pixel |
| --- | --- | --- |
| page view | every page load (GA4 config / Pixel `PageView` / GTM "All Pages") | `PageView` |
| `generate_lead` (`form_type: contact`, `services`) | contact form sent successfully | `Lead` |
| `generate_lead` (`form_type: meeting`) | meeting booked successfully | `Schedule` |
| `contact_click` (`destination`, `link_area`) | link to the contact or meeting page | `ContactClick` (custom) |
| `service_inquiry` (`service`) | ServiceX "Discuss / Ask about" links (`/contact?topic=`) | `ServiceInquiry` (custom) |
| `view_project` (`project_slug`, `project_name`, `project_category`) | project page shown | `ViewContent` |
| `email_click` / `phone_click` / `whatsapp_click` | `mailto:` / `tel:` / WhatsApp links | `Contact` |

With GTM active and "GTM manages GA4 and Meta Pixel" on (default), only the
container loads and events are pushed to `dataLayer`; configure GA4/Pixel tags
inside GTM with Consent Mode checks. Otherwise GA4 (`gtag.js`) and the Pixel load
directly.

Consent: scripts load only after the visitor accepts the relevant category
(analytics → GTM/GA4, marketing → Pixel; GTM also receives Consent Mode v2
signals). The choice is stored in the first-party `px_consent` cookie with the
policy version and expires after the configured days; "Cookie settings" in the
footer reopens it, and withdrawing clears `_ga*`, `_gid`, `_gcl_*`, `_fbp`, `_fbc`
and reloads the page.

## When the production domain is ready

1. Website server: set `SITE_URL=https://<domain>` and `SITE_ENV=production`, then
   rebuild/restart (`npm run build && npm start`).
2. Admin → SEO & Tracking → SEO Settings: enter the same production URL, review
   the default title/description per language, upload a 1200×630 share image,
   fill the organization description, keep "Allow search engines" on.
3. Replace the seeded placeholder page descriptions (e.g. "Default seeded
   homepage with reusable widgets.") in each page's SEO tab.
4. Check `https://<domain>/robots.txt` (Allow + Sitemap line) and
   `https://<domain>/sitemap.xml` (absolute production URLs only).
5. Google Search Console: add the domain property, paste the verification token
   in SEO Settings (or verify by DNS), then submit `https://<domain>/sitemap.xml`.
   Same for Bing Webmaster Tools.
6. Analytics & Tracking: enter the GTM container ID and/or GA4 measurement ID and
   Meta Pixel ID, enable each provider, then switch on "Enable analytics &
   tracking". Verify with GTM Preview / GA4 DebugView / Meta Pixel Helper after
   accepting the cookie banner.
7. Test link previews with the Facebook Sharing Debugger and LinkedIn Post
   Inspector; test structured data with the Rich Results Test.
