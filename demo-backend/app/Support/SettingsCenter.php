<?php

namespace App\Support;

/**
 * Presentation of System Configs → All Configs (the Settings Center).
 *
 * Purely a navigation and grouping layer over config/settings.php: it never adds keys or
 * changes storage. Tabs map to a top-level schema group and are edited in the single
 * settings form; "page" entries open their existing dedicated screens (SEO, Tracking),
 * which keep their own controllers, validation and save logic.
 */
class SettingsCenter
{
    public static function categories(): array
    {
        return [
            'General' => [
                'general' => ['label' => 'General', 'icon' => 'ri-settings-3-line', 'desc' => 'Application name and brand assets.'],
            ],
            'Store' => [
                'store' => ['label' => 'Store', 'icon' => 'ri-store-2-line', 'desc' => 'Company details, contact information, tax and banking.'],
            ],
            'Appearance' => [
                'theme' => ['label' => 'Theme', 'icon' => 'ri-palette-line', 'desc' => 'Brand colours and typography of the public site.'],
                'layout' => ['label' => 'Layout', 'icon' => 'ri-layout-line', 'desc' => 'Page dimensions, spacing, radii and breakpoints.'],
                'header' => ['label' => 'Header', 'icon' => 'ri-layout-top-2-line', 'desc' => 'Top bar, main header and navigation menu.'],
                'footer' => ['label' => 'Footer', 'icon' => 'ri-layout-bottom-line', 'desc' => 'Footer contact details, columns and links.'],
            ],
            'Communication & Engagement' => [
                'social' => ['label' => 'Social', 'icon' => 'ri-share-line', 'desc' => 'Social media profile links.'],
                'popup' => ['label' => 'Popup', 'icon' => 'ri-window-line', 'desc' => 'Promotional popup content and behaviour.'],
                'reviews' => ['label' => 'Reviews', 'icon' => 'ri-star-line', 'desc' => 'Customer review rules and moderation.'],
                'notifications' => ['label' => 'Notifications', 'icon' => 'ri-notification-3-line', 'desc' => 'Mail delivery (SMTP) and notification rules.'],
            ],
            'System Configuration' => [
                'system' => ['label' => 'General System', 'icon' => 'ri-terminal-box-line', 'desc' => 'Caching and debugging.'],
                'seo' => ['label' => 'SEO Settings', 'icon' => 'ri-search-eye-line', 'desc' => 'Metadata, social sharing, indexing and verification.', 'route' => 'admin.seo.settings.edit', 'active' => 'admin.seo.settings.*'],
                'tracking' => ['label' => 'Tracking & Analytics', 'icon' => 'ri-line-chart-line', 'desc' => 'Analytics providers, consent and cookie banner.', 'route' => 'admin.seo.tracking.edit', 'active' => 'admin.seo.tracking.*'],
                'gdpr' => ['label' => 'Privacy & GDPR', 'icon' => 'ri-shield-check-line', 'desc' => 'Consent texts, legal links and analytics retention.'],
                'currency' => ['label' => 'Currency', 'icon' => 'ri-money-dollar-circle-line', 'desc' => 'Currency code, symbol and number formatting.'],
            ],
        ];
    }

    /**
     * Section titles for flat schema groups. Fields of a tab that are not listed here fall
     * into a generic section, so a new schema key can never disappear from the form.
     * Nested schema groups (e.g. header.top_bar) become sections automatically.
     */
    public static function sections(): array
    {
        return [
            'general' => [
                'application' => ['title' => 'Application', 'desc' => 'How the application is named across the admin and the public site.', 'fields' => ['app_name']],
                'brand' => ['title' => 'Brand assets', 'desc' => 'Shown in the admin sidebar, browser tabs and the public site.', 'fields' => ['logo', 'favicon']],
            ],
            'store' => [
                'company' => ['title' => 'Company details', 'fields' => ['company_name', 'vat_number', 'registration_number']],
                'contact' => ['title' => 'Contact information', 'fields' => ['email', 'phone', 'address', 'city', 'postcode', 'country']],
                'banking' => ['title' => 'Banking', 'fields' => ['bank_name', 'bank_iban', 'bank_swift']],
                'invoicing' => ['title' => 'Invoicing', 'fields' => ['invoice_prefix', 'invoice_footer_note']],
            ],
            'theme' => [
                'colors' => ['title' => 'Brand colours', 'fields' => ['primary_color', 'secondary_color', 'accent_color']],
            ],
            'layout' => [
                'container' => ['title' => 'Container & grid', 'fields' => ['max_width', 'container_padding', 'gutter_width', 'spacing_unit']],
                'chrome' => ['title' => 'Header & footer', 'fields' => ['header_height', 'header_shadow', 'footer_height']],
                'type' => ['title' => 'Type scale', 'fields' => ['base_font_size', 'line_height_tight', 'line_height_normal', 'line_height_relaxed']],
                'radius' => ['title' => 'Corner radius', 'fields' => ['border_radius_sm', 'border_radius_md', 'border_radius_lg', 'border_radius_xl']],
                'components' => ['title' => 'Cards & buttons', 'fields' => ['card_padding', 'card_shadow', 'card_border_radius', 'button_border_radius', 'button_padding_x', 'button_padding_y', 'shadow_elevation']],
                'breakpoints' => ['title' => 'Breakpoints', 'fields' => ['breakpoint_sm', 'breakpoint_md', 'breakpoint_lg', 'breakpoint_xl', 'breakpoint_2xl']],
            ],
            'footer' => [
                'contact' => ['title' => 'Contact details', 'fields' => ['contact_email', 'contact_phone', 'address', 'opening_hours', 'maps_url']],
                'content' => ['title' => 'Layout & links', 'fields' => ['columns', 'show_socials', 'sections']],
            ],
            'popup' => [
                'behaviour' => ['title' => 'Behaviour', 'fields' => ['enabled', 'delay', 'show_once', 'allow_manual_close', 'auto_close', 'auto_close_delay']],
                'content' => ['title' => 'Content', 'fields' => ['title', 'description', 'cta_text', 'cta_link', 'image']],
                'style' => ['title' => 'Style', 'fields' => ['background_color', 'text_color']],
            ],
            'gdpr' => [
                'consent' => ['title' => 'Consent', 'desc' => 'Cookie banner visibility, consent version and policy links are managed under Tracking & Analytics.', 'fields' => ['enabled', 'cookie_banner_title', 'cookie_banner_text', 'marketing_default_checked', 'newsletter_consent_text']],
                'legal' => ['title' => 'Legal links', 'fields' => ['terms_and_conditions_url']],
                'analytics' => ['title' => 'Analytics data', 'fields' => ['analytics_anonymize_ip', 'analytics_retention_days']],
            ],
        ];
    }

    /** Flat lookup of every entry: key => meta (with the category name). */
    public static function entries(): array
    {
        $entries = [];
        foreach (self::categories() as $category => $items) {
            foreach ($items as $key => $meta) {
                $entries[$key] = $meta + ['category' => $category];
            }
        }

        return $entries;
    }

    /**
     * Groups the editable (form) fields of the schema into tabs → sections → fields.
     * Fields that belong to a dedicated page ('panel') and the typography group (its own
     * panel with live preview) are left out, exactly as before.
     *
     * @param array<string, array> $flat dot-key => field definition
     */
    public static function tabs(array $flat): array
    {
        $sectionMap = self::sections();
        $tabs = [];

        foreach ($flat as $key => $field) {
            if (str_starts_with($key, 'theme.typography.') || !empty($field['panel'])) {
                continue;
            }

            $parts = explode('.', $key);
            $tab = $parts[0];
            $name = $parts[1] ?? $key;

            if (count($parts) > 2) {
                // Nested schema group, e.g. header.top_bar.enabled → section "top_bar".
                $section = $name;
                $title = ucfirst(str_replace('_', ' ', $name));
                $desc = null;
            } else {
                $section = 'other';
                $title = 'Settings';
                $desc = null;
                foreach ($sectionMap[$tab] ?? [] as $sectionKey => $meta) {
                    if (in_array($name, $meta['fields'], true)) {
                        $section = $sectionKey;
                        $title = $meta['title'];
                        $desc = $meta['desc'] ?? null;
                        break;
                    }
                }
            }

            $tabs[$tab][$section] ??= ['title' => $title, 'desc' => $desc, 'fields' => []];
            $tabs[$tab][$section]['fields'][$key] = $field;
        }

        // Keep the section order declared in sections(), then any remaining ones.
        foreach ($tabs as $tab => $sections) {
            $order = array_keys($sectionMap[$tab] ?? []);
            uksort($sections, function ($a, $b) use ($order) {
                $ia = array_search($a, $order, true);
                $ib = array_search($b, $order, true);

                return ($ia === false ? PHP_INT_MAX : $ia) <=> ($ib === false ? PHP_INT_MAX : $ib);
            });
            $tabs[$tab] = $sections;
        }

        return $tabs;
    }

    /** config('settings') as dot-key => field definition. */
    public static function flatten(array $schema, string $prefix = ''): array
    {
        $result = [];
        foreach ($schema as $key => $value) {
            $full = $prefix === '' ? $key : "{$prefix}.{$key}";
            if (isset($value['type'])) {
                $result[$full] = $value;
            } elseif (is_array($value)) {
                $result += self::flatten($value, $full);
            }
        }

        return $result;
    }

    /**
     * Navigation for the Settings Center: categories with their entries, limited to tabs
     * that have form fields and pages whose route exists. Schema groups without a
     * category are listed under "Other" so nothing becomes unreachable.
     */
    public static function navigation(array $tabs): array
    {
        $nav = [];
        $known = [];
        foreach (self::categories() as $category => $items) {
            foreach ($items as $key => $meta) {
                $known[] = $key;
                if (isset($meta['route'])) {
                    if (!\Illuminate\Support\Facades\Route::has($meta['route'])) {
                        continue;
                    }
                    $meta['url'] = route($meta['route']);
                } elseif (!isset($tabs[$key])) {
                    continue;
                }
                $nav[$category][$key] = $meta;
            }
        }

        foreach (array_diff(array_keys($tabs), $known) as $key) {
            $nav['Other'][$key] = ['label' => ucfirst(str_replace('_', ' ', $key)), 'icon' => 'ri-settings-line', 'desc' => ''];
        }

        return $nav;
    }
}
