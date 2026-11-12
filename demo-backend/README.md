# Admin Web — Documentation

Laravel 12 admin panel and storefront API for a multi-channel, multi-locale commerce platform.

---

## Documentation Index

| Topic | File |
|---|---|
| **Build the website frontend** — render API pages, widgets, and menus in Blade | [docs/storefront/building-frontend.md](docs/storefront/building-frontend.md) |
| **Next.js storefront** — same API, App Router pages and widgets | [docs/storefront/nextjs.md](docs/storefront/nextjs.md) |
| **Project overview** — stack, folder layout, key concepts | [docs/overview.md](docs/overview.md) |
| **Authentication** — Fortify, 2FA, email verify, rate limits | [docs/auth/authentication.md](docs/auth/authentication.md) |
| **Google OAuth** — Socialite setup, flow, error handling | [docs/auth/google-oauth.md](docs/auth/google-oauth.md) |
| **API auth** — Sanctum tokens, login/register, revocation | [docs/auth/api-auth.md](docs/auth/api-auth.md) |
| **API overview** — all endpoints, middleware, response format | [docs/api/overview.md](docs/api/overview.md) |
| **Postman collection** — importable JSON for all API endpoints | [docs/postman_collection.json](docs/postman_collection.json) |
| **Postman AI test suite** — examples, error cases, and Newman checks for the storefront API | [docs/postman/storefront-ai-testing.postman_collection.json](docs/postman/storefront-ai-testing.postman_collection.json) |
| **GDPR & privacy API** — consent, event tracking, data rights | [docs/api/gdpr.md](docs/api/gdpr.md) |
| **Pages CMS** — model, tree structure, admin routes, API | [docs/cms/pages.md](docs/cms/pages.md) |
| **Projects widget frontend integration** — both variants, translations and detail API | [docs/projects-widget-frontend-integration.md](docs/projects-widget-frontend-integration.md) |
| **Widgets** — types, config, admin panel, API endpoints | [docs/cms/widgets.md](docs/cms/widgets.md) |
| **Hero media** — image/video uploads, Next.js contract, lifecycle and deployment | [docs/cms/hero-media.md](docs/cms/hero-media.md) |
| **Roles & permissions** — Spatie roles, access levels, middleware | [docs/admin/roles-permissions.md](docs/admin/roles-permissions.md) |
| **Channels & locales** — multi-channel setup, locale resolution | [docs/admin/channels-locales.md](docs/admin/channels-locales.md) |
| **Settings** — all setting keys, types, public/private, defaults | [docs/admin/settings.md](docs/admin/settings.md) |
| **Encryption keys** — APP_KEY_2FA, rotation, recovery | [docs/security/encryption-keys.md](docs/security/encryption-keys.md) |
| **Environment & deployment** — .env keys, setup steps, checklist | [docs/deployment/environment.md](docs/deployment/environment.md) |

---

## Quick Start

```bash
# 1. Install dependencies
composer install

# 2. Copy and configure environment
cp .env.example .env
php artisan key:generate
php artisan key:generate-2fa

# 3. Run migrations
php artisan migrate

# 3b. Seed default admin, channels, locales, widgets, and website pages
php artisan db:seed

# 4. Link storage
php artisan storage:link

# 5. Build frontend assets
npm install && npm run build

# 6. Start local server (Laragon) or:
php artisan serve
```

See [docs/deployment/environment.md](docs/deployment/environment.md) for all required `.env` keys and the full deployment checklist.

---

## Common Artisan Commands

```bash
php artisan key:generate              # Rotate APP_KEY
php artisan key:generate-2fa          # Generate APP_KEY_2FA (2FA encryption)
php artisan key:generate-2fa --rotate # Rotate 2FA key, archive old one
php artisan migrate                   # Run pending migrations
php artisan db:seed                   # Seed admin account, demo widgets, and default website pages
php artisan config:clear              # Clear config cache after .env changes
php artisan optimize                  # Cache config + routes + views
php artisan queue:work --queue=default --sleep=1 --tries=3  # Start queue worker
```

## Demo Seeders

The default `DatabaseSeeder` now creates a basic CMS-ready website structure:

- A default channel and active locales
- One seeded widget for each supported widget type
- Default website pages: `home`, `about`, `contact`, `privacy-policy`, and `terms-and-conditions`
- Page/widget attachments so the seeded pages render meaningful demo content

You can also run widget seeders individually when working on one type:

```bash
php artisan db:seed --class=HeroWidgetSeeder
php artisan db:seed --class=CarouselWidgetSeeder
php artisan db:seed --class=VideoWidgetSeeder
php artisan db:seed --class=HtmlWidgetSeeder
php artisan db:seed --class=SeparatorWidgetSeeder
php artisan db:seed --class=ContactFormWidgetSeeder
php artisan db:seed --class=ImageGalleryWidgetSeeder
php artisan db:seed --class=PageSeeder
```

---

## GDPR Quick Reference

See [docs/api/gdpr.md](docs/api/gdpr.md) for the full guide. Summary:

```
POST   /api/v1/storefront/consent      Store/update consent from cookie banner
GET    /api/v1/storefront/consent      Get current consent state
DELETE /api/v1/storefront/consent      Withdraw consent
POST   /api/v1/storefront/events       Send analytics event (throttle: 120/min)
GET    /api/v1/me/privacy/export       Export user tracking data (auth required)
DELETE /api/v1/me/privacy/tracking     Delete user tracking data (auth required)
```

Key GDPR settings (Admin → Settings → GDPR):

| Setting key | Description |
|---|---|
| `gdpr.enabled` | Master GDPR switch |
| `gdpr.cookie_banner_enabled` | Show consent banner |
| `gdpr.analytics_tracking_enabled` | Enable event tracking |
| `gdpr.analytics_require_consent` | Block tracking without consent |
| `gdpr.analytics_retention_days` | Days before telemetry is purged |
| `gdpr.consent_version` | Current policy version |

Purge old telemetry:
```bash
php artisan gdpr:purge-telemetry --dry-run   # Preview
php artisan gdpr:purge-telemetry             # Execute
php artisan gdpr:purge-telemetry --days=90   # Override retention period
```

