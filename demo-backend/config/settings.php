<?php

// Curated public fonts (the web frontend self-hosts exactly these) and their shared weights.
$fonts = ['IBM Plex Mono', 'Poppins', 'Inter', 'Manrope', 'DM Sans', 'Space Grotesk', 'IBM Plex Sans', 'Roboto', 'Montserrat'];
$weights = ['300', '400', '500', '600', '700'];

return [

    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    */
    'general' => [
        'app_name' => [
            'label' => 'App Name',
            'type' => 'text',
            'required' => true,
            'public' => true,
        ],
        'logo' => [
            'label' => 'Logo',
            'type' => 'file',
            'public' => true,
        ],
        'favicon' => [
            'label' => 'Favicon',
            'type' => 'file',
            'public' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Store Information
    |--------------------------------------------------------------------------
    | Used in invoices, emails, footer, and legal pages.
    */
    'store' => [
        'company_name' => [
            'label'    => 'Company / Legal Name',
            'type'     => 'text',
            'required' => true,
            'public'   => false,
        ],
        'email' => [
            'label'   => 'Contact Email',
            'type'    => 'email',
            'public'  => true,
        ],
        'phone' => [
            'label'  => 'Phone Number',
            'type'   => 'text',
            'public' => true,
        ],
        'address' => [
            'label'  => 'Street Address',
            'type'   => 'text',
            'public' => false,
        ],
        'city' => [
            'label'  => 'City',
            'type'   => 'text',
            'public' => false,
        ],
        'postcode' => [
            'label'  => 'Postcode / ZIP',
            'type'   => 'text',
            'public' => false,
        ],
        'country' => [
            'label'   => 'Country (ISO 2-letter)',
            'type'    => 'text',
            'public'  => false,
            'default' => 'AL',
        ],
        'vat_number' => [
            'label'  => 'VAT / Tax ID',
            'type'   => 'text',
            'public' => false,
        ],
        'registration_number' => [
            'label'  => 'Business Registration Number',
            'type'   => 'text',
            'public' => false,
        ],
        'bank_name' => [
            'label'  => 'Bank Name',
            'type'   => 'text',
            'public' => false,
        ],
        'bank_iban' => [
            'label'  => 'IBAN',
            'type'   => 'text',
            'public' => false,
        ],
        'bank_swift' => [
            'label'  => 'SWIFT / BIC',
            'type'   => 'text',
            'public' => false,
        ],
        'invoice_prefix' => [
            'label'   => 'Invoice Number Prefix',
            'type'    => 'text',
            'default' => 'INV-',
            'public'  => false,
        ],
        'invoice_footer_note' => [
            'label'   => 'Invoice Footer Note',
            'type'    => 'textarea',
            'default' => 'Thank you for your order!',
            'public'  => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Settings
    |--------------------------------------------------------------------------
    */
    'theme' => [
        'primary_color' => [
            'label' => 'Primary Color',
            'type' => 'color',
            'public' => true,
        ],
        'secondary_color' => [
            'label' => 'Secondary Color',
            'type' => 'color',
            'public' => true,
        ],
        'accent_color' => [
            'label' => 'Accent Color',
            'type' => 'color',
            'public' => true,
        ],

        /*
        | Typography — public site fonts (served as theme.typography by the settings API).
        | The font list is curated: the frontend self-hosts exactly these families.
        | Rendered by admin.settings.partials.typography (labels in lang/{locale}/typography.php).
        */
        'typography' => [
            'display_font' => [
                'label' => 'Display & UI font',
                'type' => 'select',
                'options' => $fonts,
                'default' => 'IBM Plex Mono',
                'public' => true,
            ],
            'body_font' => [
                'label' => 'Body & reading font',
                'type' => 'select',
                'options' => $fonts,
                'default' => 'Poppins',
                'public' => true,
            ],
            'display_weight' => [
                'label' => 'Heading weight',
                'type' => 'select',
                'options' => $weights,
                'default' => '600',
                'public' => true,
            ],
            'ui_weight' => [
                'label' => 'UI weight',
                'type' => 'select',
                'options' => $weights,
                'default' => '500',
                'public' => true,
            ],
            'body_weight' => [
                'label' => 'Body weight',
                'type' => 'select',
                'options' => $weights,
                'default' => '400',
                'public' => true,
            ],
            'body_size' => [
                'label' => 'Base body size (px)',
                'type' => 'number',
                'min' => 14,
                'max' => 20,
                'step' => 1,
                'default' => 16,
                'public' => true,
            ],
            'body_line_height' => [
                'label' => 'Body line height',
                'type' => 'number',
                'min' => 1.2,
                'max' => 2,
                'step' => 0.05,
                'default' => 1.6,
                'public' => true,
            ],
            'heading_letter_spacing' => [
                'label' => 'Heading letter spacing (em)',
                'type' => 'number',
                'min' => -0.05,
                'max' => 0.1,
                'step' => 0.005,
                'default' => -0.02,
                'public' => true,
            ],
            'heading_scale' => [
                'label' => 'Heading scale',
                'type' => 'number',
                'min' => 0.8,
                'max' => 1.25,
                'step' => 0.05,
                'default' => 1,
                'public' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout Settings — Modern Design System
    |--------------------------------------------------------------------------
    | Container, spacing, typography, shadows, and responsive breakpoints.
    */
    'layout' => [

        /*
        | Container & Grid
        */
        'max_width' => [
            'label' => 'Container Max Width (px)',
            'type' => 'number',
            'default' => 1200,
            'public' => true,
        ],
        'container_padding' => [
            'label' => 'Container Padding (px)',
            'type' => 'number',
            'default' => 24,
            'public' => true,
        ],
        'gutter_width' => [
            'label' => 'Grid Gutter Width (px)',
            'type' => 'number',
            'default' => 24,
            'public' => true,
        ],

        /*
        | Header & Footer
        */
        'header_height' => [
            'label' => 'Header Height (px)',
            'type' => 'number',
            'default' => 80,
            'public' => true,
        ],
        'header_shadow' => [
            'label' => 'Header Shadow',
            'type' => 'select',
            'options' => ['none', 'light', 'medium', 'heavy'],
            'default' => 'light',
            'public' => true,
        ],
        'footer_height' => [
            'label' => 'Footer Height (px)',
            'type' => 'number',
            'default' => 100,
            'public' => true,
        ],

        /*
        | Spacing Scale (modern 8px baseline)
        */
        'spacing_unit' => [
            'label' => 'Spacing Unit (px)',
            'type' => 'number',
            'default' => 8,
            'public' => true,
            'description' => 'Base unit for margin/padding multiples (xs: 4px, sm: 8px, md: 16px, lg: 24px, xl: 32px)',
        ],

        /*
        | Corners & Borders
        */
        'border_radius_sm' => [
            'label' => 'Small Border Radius (px)',
            'type' => 'number',
            'default' => 4,
            'public' => true,
        ],
        'border_radius_md' => [
            'label' => 'Medium Border Radius (px)',
            'type' => 'number',
            'default' => 8,
            'public' => true,
        ],
        'border_radius_lg' => [
            'label' => 'Large Border Radius (px)',
            'type' => 'number',
            'default' => 12,
            'public' => true,
        ],
        'border_radius_xl' => [
            'label' => 'Extra Large Border Radius (px)',
            'type' => 'number',
            'default' => 16,
            'public' => true,
        ],

        /*
        | Shadow System (depth levels)
        */
        'shadow_elevation' => [
            'label' => 'Enable Shadow Elevation System',
            'type' => 'boolean',
            'default' => true,
            'public' => true,
        ],

        /*
        | Typography
        */
        'base_font_size' => [
            'label' => 'Base Font Size (px)',
            'type' => 'number',
            'default' => 16,
            'public' => true,
        ],
        'line_height_tight' => [
            'label' => 'Line Height (Tight)',
            'type' => 'number',
            'default' => 1.2,
            'public' => true,
        ],
        'line_height_normal' => [
            'label' => 'Line Height (Normal)',
            'type' => 'number',
            'default' => 1.5,
            'public' => true,
        ],
        'line_height_relaxed' => [
            'label' => 'Line Height (Relaxed)',
            'type' => 'number',
            'default' => 1.8,
            'public' => true,
        ],

        /*
        | Cards
        */
        'card_padding' => [
            'label' => 'Card Padding (px)',
            'type' => 'number',
            'default' => 24,
            'public' => true,
        ],
        'card_shadow' => [
            'label' => 'Card Shadow',
            'type' => 'select',
            'options' => ['none', 'light', 'medium', 'heavy'],
            'default' => 'medium',
            'public' => true,
        ],
        'card_border_radius' => [
            'label' => 'Card Border Radius (px)',
            'type' => 'number',
            'default' => 12,
            'public' => true,
        ],

        /*
        | Buttons
        */
        'button_border_radius' => [
            'label' => 'Button Border Radius (px)',
            'type' => 'number',
            'default' => 6,
            'public' => true,
        ],
        'button_padding_x' => [
            'label' => 'Button Horizontal Padding (px)',
            'type' => 'number',
            'default' => 20,
            'public' => true,
        ],
        'button_padding_y' => [
            'label' => 'Button Vertical Padding (px)',
            'type' => 'number',
            'default' => 12,
            'public' => true,
        ],

        /*
        | Responsive Breakpoints
        */
        'breakpoint_sm' => [
            'label' => 'Small Breakpoint (px)',
            'type' => 'number',
            'default' => 576,
            'public' => true,
        ],
        'breakpoint_md' => [
            'label' => 'Medium Breakpoint (px)',
            'type' => 'number',
            'default' => 768,
            'public' => true,
        ],
        'breakpoint_lg' => [
            'label' => 'Large Breakpoint (px)',
            'type' => 'number',
            'default' => 992,
            'public' => true,
        ],
        'breakpoint_xl' => [
            'label' => 'Extra Large Breakpoint (px)',
            'type' => 'number',
            'default' => 1200,
            'public' => true,
        ],
        'breakpoint_2xl' => [
            'label' => 'XXL Breakpoint (px)',
            'type' => 'number',
            'default' => 1400,
            'public' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Header Settings (FULL)
    |--------------------------------------------------------------------------
    */
    'header' => [

        /*
        | Top Notification Bar
        */
        'top_bar' => [
            'enabled' => [
                'label' => 'Enable Top Bar',
                'type' => 'boolean',
                'default' => true,
                'public' => true,
            ],
            'text' => [
                'label' => 'Announcement Text',
                'type' => 'text',
                'public' => true,
            ],
            'link' => [
                'label' => 'Announcement Link',
                'type' => 'text',
                'public' => true,
            ],
            'background_color' => [
                'label' => 'Background Color',
                'type' => 'color',
                'default' => '#000000',
                'public' => true,
            ],
            'text_color' => [
                'label' => 'Text Color',
                'type' => 'color',
                'default' => '#ffffff',
                'public' => true,
            ],
            'closable' => [
                'label' => 'Closable',
                'type' => 'boolean',
                'default' => true,
                'public' => true,
            ],
        ],

        /*
        | Main Header
        */
        'main' => [
            'sticky' => [
                'label' => 'Sticky Header',
                'type' => 'boolean',
                'default' => true,
                'public' => true,
            ],
            'transparent_on_home' => [
                'label' => 'Transparent on Homepage',
                'type' => 'boolean',
                'default' => false,
                'public' => true,
            ],
        ],

        /*
        | Navigation / Menu Bar
        */
        'menu' => [
            'enabled' => [
                'label' => 'Enable Menu',
                'type' => 'boolean',
                'default' => true,
                'public' => true,
            ],
            'menu_type' => [
                'label' => 'Menu Type',
                'type' => 'select',
                'options' => ['simple', 'mega'],
                'default' => 'mega',
                'public' => true,
            ],
            'sticky' => [
                'label' => 'Sticky Menu',
                'type' => 'boolean',
                'default' => false,
                'public' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Footer Settings
    |--------------------------------------------------------------------------
    */
    'footer' => [
        'columns' => [
            'label' => 'Footer Columns',
            'type' => 'number',
            'default' => 4,
            'public' => true,
        ],
        'sections' => [
            'label' => 'Footer Sections (one per line)',
            'type' => 'textarea',
            'default' => "Company\nCustomer Care\nLegal\nSocial",
            'public' => true,
            'description' => 'Defines available footer sections for page links. Use one section name per line.',
        ],
        'show_socials' => [
            'label' => 'Show Social Links',
            'type' => 'boolean',
            'default' => true,
            'public' => true,
        ],
        'contact_email' => [
            'label' => 'Contact Email',
            'type' => 'text',
            'public' => true,
            'description' => 'Shown in the footer and in search results (business contact). Leave empty to use the Store email.',
        ],
        'contact_phone' => [
            'label' => 'Contact Phone',
            'type' => 'text',
            'public' => true,
            'description' => 'International format, e.g. +355 69 123 4567. Leave empty to use the Store phone.',
        ],
        'address' => [
            'label' => 'Address / Location',
            'type' => 'textarea',
            'public' => true,
            'description' => 'Street, postcode and city, one part per line. Shown in the footer and used for search engines.',
        ],
        'maps_url' => [
            'label' => 'Google Maps Link',
            'type' => 'text',
            'public' => true,
            'description' => 'Optional link for the address. Without it, a Google Maps search for the address is used.',
        ],
        'opening_hours' => [
            'label' => 'Opening Hours',
            'type' => 'text',
            'public' => true,
            'description' => 'Optional, e.g. Mon–Fri 09:00–17:00.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Settings
    |--------------------------------------------------------------------------
    */
    /*
    | Managed on Admin → SEO & Tracking → SEO Settings ('panel' => 'seo'); the
    | generic settings page neither renders nor saves these keys.
    */
    'seo' => [
        'meta_title' => [
            'label' => 'Meta Title',
            'type' => 'text',
            'public' => true,
            'panel' => 'seo',
            'description' => 'Default page title (home page and pages without their own). Other pages show "Page title | Site name". Aim for 50–60 characters.',
        ],
        'meta_description' => [
            'label' => 'Meta Description',
            'type' => 'textarea',
            'public' => true,
            'panel' => 'seo',
            'description' => 'Default description for search results and link previews when a page has none of its own. Aim for 140–160 characters.',
        ],
        'site_name' => [
            'label' => 'Site Name',
            'type' => 'text',
            'public' => true,
            'panel' => 'seo',
            'description' => 'Brand name used in titles and link previews. Empty uses the App Name.',
        ],
        'title_separator' => [
            'label' => 'Title Separator',
            'type' => 'select',
            'options' => ['|', '—', '–', '-', '·', '/'],
            'default' => '|',
            'public' => true,
            'panel' => 'seo',
        ],
        'title_template' => [
            'label' => 'Title Template',
            'type' => 'text',
            'public' => true,
            'panel' => 'seo',
            'description' => 'Optional. Placeholders: {title}, {separator}, {site}. Empty uses "{title} {separator} {site}".',
        ],
        'site_url' => [
            'label' => 'Production Website URL',
            'type' => 'url',
            'public' => true,
            'panel' => 'seo',
            'description' => 'The public website origin, e.g. https://www.example.com. The SITE_URL server variable of the website takes precedence.',
        ],
        'organization_name' => [
            'label' => 'Organization Name',
            'type' => 'text',
            'public' => true,
            'panel' => 'seo',
            'description' => 'Name used in structured data. Empty uses the Site Name.',
        ],
        'organization_description' => [
            'label' => 'Organization Description',
            'type' => 'textarea',
            'public' => true,
            'panel' => 'seo',
        ],
        // Per-language overrides of meta_title, meta_description and organization_description: {"sq": {...}}.
        'localized' => [
            'label' => 'Localized SEO Defaults',
            'type' => 'json',
            'public' => true,
            'panel' => 'seo',
        ],
        'og_image' => [
            'label' => 'Social Share Image',
            'type' => 'file',
            'public' => true,
            'panel' => 'seo',
            'description' => 'Image shown when a page is shared on social media or chat apps. Best at 1200 × 630 px. Falls back to the logo.',
        ],
        'twitter_handle' => [
            'label' => 'X (Twitter) Handle',
            'type' => 'text',
            'public' => true,
            'panel' => 'seo',
            'description' => 'Optional, e.g. @demo.',
        ],
        'allow_indexing' => [
            'label' => 'Allow Search Engines',
            'type' => 'boolean',
            'default' => true,
            'public' => true,
            'panel' => 'seo',
            'description' => 'Turn off on staging or test sites so search engines do not index them. The website also needs SITE_ENV=production.',
        ],
        'google_site_verification' => [
            'label' => 'Google Search Console Verification',
            'type' => 'text',
            'public' => true,
            'panel' => 'seo',
            'description' => 'Only the content value of the google-site-verification meta tag.',
        ],
        'bing_site_verification' => [
            'label' => 'Bing Webmaster Verification',
            'type' => 'text',
            'public' => true,
            'panel' => 'seo',
            'description' => 'Only the content value of the msvalidate.01 meta tag.',
        ],
        'facebook_domain_verification' => [
            'label' => 'Meta (Facebook) Domain Verification',
            'type' => 'text',
            'public' => true,
            'panel' => 'seo',
            'description' => 'Only the content value of the facebook-domain-verification meta tag.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Tracking
    |--------------------------------------------------------------------------
    | Managed on Admin → SEO & Tracking → Analytics & Tracking. Only public IDs
    | are stored. Nothing loads unless gdpr.analytics_tracking_enabled, the
    | provider switch and a valid ID are all set, and the website runs with
    | SITE_ENV=production.
    */
    'tracking' => [
        'gtm_enabled' => [
            'label' => 'Google Tag Manager',
            'type' => 'boolean',
            'default' => false,
            'public' => true,
            'panel' => 'tracking',
        ],
        'gtm_id' => [
            'label' => 'GTM Container ID',
            'type' => 'text',
            'public' => true,
            'panel' => 'tracking',
        ],
        'gtm_manages_tags' => [
            'label' => 'GTM Manages GA4 and Meta Pixel',
            'type' => 'boolean',
            'default' => true,
            'public' => true,
            'panel' => 'tracking',
        ],
        'ga4_enabled' => [
            'label' => 'Google Analytics 4',
            'type' => 'boolean',
            'default' => false,
            'public' => true,
            'panel' => 'tracking',
        ],
        'ga4_measurement_id' => [
            'label' => 'GA4 Measurement ID',
            'type' => 'text',
            'public' => true,
            'panel' => 'tracking',
        ],
        'meta_pixel_enabled' => [
            'label' => 'Meta Pixel',
            'type' => 'boolean',
            'default' => false,
            'public' => true,
            'panel' => 'tracking',
        ],
        'meta_pixel_id' => [
            'label' => 'Meta Pixel ID',
            'type' => 'text',
            'public' => true,
            'panel' => 'tracking',
        ],
        // Cookie banner copy per language: {"en": {"title": "...", "text": "..."}}; empty uses the website's built-in text.
        'consent_copy' => [
            'label' => 'Cookie Banner Text',
            'type' => 'json',
            'public' => true,
            'panel' => 'tracking',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | GDPR Settings
    |--------------------------------------------------------------------------
    | Public storefront settings for consent, cookie banner text, and legal URLs.
    */
    'gdpr' => [
        'enabled' => [
            'label' => 'Enable GDPR Features',
            'type' => 'boolean',
            'default' => true,
            'public' => true,
        ],
        'cookie_banner_enabled' => [
            'label' => 'Enable Cookie Banner',
            'type' => 'boolean',
            'default' => true,
            'public' => true,
            'panel' => 'tracking',
        ],
        'cookie_banner_title' => [
            'label' => 'Cookie Banner Title',
            'type' => 'text',
            'default' => 'We value your privacy',
            'public' => true,
        ],
        'cookie_banner_text' => [
            'label' => 'Cookie Banner Text',
            'type' => 'textarea',
            'default' => 'We use cookies to improve your shopping experience, analyze traffic, and personalize content.',
            'public' => true,
        ],
        'privacy_policy_url' => [
            'label' => 'Privacy Policy URL',
            'type' => 'text',
            'default' => '/privacy-policy',
            'public' => true,
            'panel' => 'tracking',
        ],
        'terms_and_conditions_url' => [
            'label' => 'Terms and Conditions URL',
            'type' => 'text',
            'default' => '/terms-and-conditions',
            'public' => true,
        ],
        'cookie_policy_url' => [
            'label' => 'Cookie Policy URL',
            'type' => 'text',
            // No default: the cookie banner links only to pages that exist, and an empty field must stay empty.
            'public' => true,
            'panel' => 'tracking',
        ],
        'show_reject_button' => [
            'label' => 'Show Reject Button',
            'type' => 'boolean',
            'default' => true,
            'public' => true,
            'panel' => 'tracking',
        ],
        'consent_expiry_days' => [
            'label' => 'Consent Expiry (Days)',
            'type' => 'number',
            'default' => 180,
            'public' => true,
            'panel' => 'tracking',
        ],
        'newsletter_consent_text' => [
            'label' => 'Newsletter Consent Text',
            'type' => 'textarea',
            'default' => 'I agree to receive marketing emails and understand I can unsubscribe at any time.',
            'public' => true,
        ],
        'marketing_default_checked' => [
            'label' => 'Marketing Consent Default Checked',
            'type' => 'boolean',
            'default' => false,
            'public' => true,
        ],
        'analytics_tracking_enabled' => [
            'label' => 'Enable Analytics Tracking',
            'type' => 'boolean',
            'default' => false,
            'public' => true,
            'panel' => 'tracking',
        ],
        'analytics_require_consent' => [
            'label' => 'Require Consent For Analytics',
            'type' => 'boolean',
            'default' => true,
            'public' => true,
            'panel' => 'tracking',
        ],
        'analytics_anonymize_ip' => [
            'label' => 'Anonymize Visitor IP In Analytics',
            'type' => 'boolean',
            'default' => true,
            'public' => false,
        ],
        'analytics_retention_days' => [
            'label' => 'Analytics Retention (Days)',
            'type' => 'number',
            'default' => 180,
            'public' => false,
        ],
        'consent_version' => [
            'label' => 'Consent Policy Version',
            'type' => 'text',
            'default' => 'v1',
            'public' => true,
            'panel' => 'tracking',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Popup Settings
    |--------------------------------------------------------------------------
    */
    'popup' => [

        'enabled' => [
            'label' => 'Enable Popup',
            'type' => 'boolean',
            'default' => false,
            'public' => true,
        ],

        'title' => [
            'label' => 'Popup Title',
            'type' => 'text',
            'public' => true,
        ],

        'description' => [
            'label' => 'Popup Description',
            'type' => 'text',
            'public' => true,
        ],

        'image' => [
            'label' => 'Popup Image',
            'type' => 'file',
            'public' => true,
        ],

        'cta_text' => [
            'label' => 'Button Text',
            'type' => 'text',
            'default' => 'Shop Now',
            'public' => true,
        ],

        'cta_link' => [
            'label' => 'Button Link',
            'type' => 'text',
            'public' => true,
        ],

        'delay' => [
            'label' => 'Show Delay (seconds)',
            'type' => 'number',
            'default' => 3,
            'public' => true,
        ],

        'auto_close' => [
            'label' => 'Auto Close Popup',
            'type' => 'boolean',
            'default' => false,
            'public' => true,
        ],

        'auto_close_delay' => [
            'label' => 'Auto Close Delay (seconds)',
            'type' => 'number',
            'default' => 5,
            'public' => true,
        ],

        'allow_manual_close' => [
            'label' => 'Allow Manual Close',
            'type' => 'boolean',
            'default' => true,
            'public' => true,
        ],

        'show_once' => [
            'label' => 'Show Only Once',
            'type' => 'boolean',
            'default' => true,
            'public' => true,
        ],

        'background_color' => [
            'label' => 'Background Color',
            'type' => 'color',
            'default' => '#ffffff',
            'public' => true,
        ],

        'text_color' => [
            'label' => 'Text Color',
            'type' => 'color',
            'default' => '#000000',
            'public' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced / System (Private)
    |--------------------------------------------------------------------------
    */
    'system' => [
        'cache_enabled' => [
            'label' => 'Enable Cache',
            'type' => 'boolean',
            'default' => true,
            'public' => false,
        ],
        'debug_mode' => [
            'label' => 'Debug Mode',
            'type' => 'boolean',
            'default' => false,
            'public' => false,
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Social Links
    |--------------------------------------------------------------------------
    */
    'social' => [
        'facebook' => [
            'label'  => 'Facebook URL',
            'type'   => 'text',
            'public' => true,
        ],
        'instagram' => [
            'label'  => 'Instagram URL',
            'type'   => 'text',
            'public' => true,
        ],
        'twitter' => [
            'label'  => 'X / Twitter URL',
            'type'   => 'text',
            'public' => true,
        ],
        'youtube' => [
            'label'  => 'YouTube URL',
            'type'   => 'text',
            'public' => true,
        ],
        'tiktok' => [
            'label'  => 'TikTok URL',
            'type'   => 'text',
            'public' => true,
        ],
        'linkedin' => [
            'label'  => 'LinkedIn URL',
            'type'   => 'text',
            'public' => true,
        ],
        'pinterest' => [
            'label'  => 'Pinterest URL',
            'type'   => 'text',
            'public' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    | Configure price display format and currency symbol positioning.
    |--------------------------------------------------------------------------
    */
    'currency' => [
        'code' => [
            'label'   => 'Currency Code',
            'type'    => 'text',
            'default' => 'USD',
            'public'  => true,
        ],
        'symbol' => [
            'label'   => 'Currency Symbol',
            'type'    => 'text',
            'default' => '$',
            'public'  => true,
        ],
        'symbol_position' => [
            'label'   => 'Symbol Position',
            'type'    => 'select',
            'options' => ['before', 'after'],
            'default' => 'before',
            'public'  => true,
        ],
        'decimals' => [
            'label'   => 'Decimal Places',
            'type'    => 'number',
            'default' => 2,
            'public'  => true,
        ],
        'decimal_separator' => [
            'label'   => 'Decimal Separator',
            'type'    => 'select',
            'options' => ['.', ','],
            'default' => '.',
            'public'  => true,
        ],
        'thousands_separator' => [
            'label'   => 'Thousands Separator',
            'type'    => 'select',
            'options' => [',', '.', ' ', ''],
            'default' => ',',
            'public'  => true,
        ],
        'display_with_tax' => [
            'label'   => 'Show Prices With Tax',
            'type'    => 'boolean',
            'default' => false,
            'public'  => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Reviews & Ratings
    | Control review submission, moderation, and display settings.
    |--------------------------------------------------------------------------
    */
    'reviews' => [
        'enabled' => [
            'label'   => 'Enable Product Reviews',
            'type'    => 'boolean',
            'default' => true,
            'public'  => true,
        ],
        'require_purchase' => [
            'label'       => 'Require Verified Purchase',
            'type'        => 'boolean',
            'default'     => false,
            'public'      => true,
            'description' => 'Only allow reviews from customers who have purchased the product.',
        ],
        'auto_approve' => [
            'label'       => 'Auto-approve Reviews',
            'type'        => 'boolean',
            'default'     => false,
            'public'      => false,
            'description' => 'Publish reviews immediately without moderation.',
        ],
        'allow_guest' => [
            'label'   => 'Allow Guest Reviews',
            'type'    => 'boolean',
            'default' => false,
            'public'  => true,
        ],
        'per_page' => [
            'label'   => 'Reviews Per Page',
            'type'    => 'number',
            'default' => 10,
            'public'  => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    | SMTP driver config + admin / user / order / cart notification toggles.
    | Password fields are stored encrypted via SettingService.
    */
    'notifications' => [

        /*
        | SMTP / Mail Driver
        */
        'mail' => [

            'driver' => [
                'label'   => 'Mail Driver',
                'type'    => 'select',
                'options' => ['smtp', 'sendmail', 'mailgun', 'ses', 'postmark', 'log', 'array'],
                'default' => 'smtp',
                'public'  => false,
            ],
            'host' => [
                'label'       => 'SMTP Host',
                'type'        => 'text',
                'placeholder' => 'smtp.mailtrap.io',
                'public'      => false,
            ],
            'port' => [
                'label'   => 'SMTP Port',
                'type'    => 'number',
                'default' => 587,
                'public'  => false,
            ],
            'encryption' => [
                'label'   => 'Encryption',
                'type'    => 'select',
                'options' => ['tls', 'ssl', 'none'],
                'default' => 'tls',
                'public'  => false,
            ],
            'username' => [
                'label'  => 'SMTP Username',
                'type'   => 'text',
                'public' => false,
            ],
            'password' => [
                'label'  => 'SMTP Password',
                'type'   => 'password',
                'public' => false,
            ],
            'from_address' => [
                'label'       => 'From Address',
                'type'        => 'email',
                'placeholder' => 'no-reply@example.com',
                'required'    => true,
                'public'      => false,
            ],
            'from_name' => [
                'label'   => 'From Name',
                'type'    => 'text',
                'default' => 'Commerce',
                'public'  => false,
            ],
        ],

        /*
        | Admin Notifications
        */
        'admin' => [

            'email' => [
                'label'       => 'Admin Notification Email',
                'type'        => 'email',
                'placeholder' => 'admin@example.com',
                'description' => 'Where admin alerts are sent.',
                'required'    => true,
                'public'      => false,
            ],
            'notify_new_order' => [
                'label'   => 'New Order Placed',
                'type'    => 'boolean',
                'default' => true,
                'public'  => false,
            ],
            'notify_new_user' => [
                'label'   => 'New User Registration',
                'type'    => 'boolean',
                'default' => true,
                'public'  => false,
            ],
            'notify_low_stock' => [
                'label'   => 'Low Stock Alert',
                'type'    => 'boolean',
                'default' => true,
                'public'  => false,
            ],
            'low_stock_threshold' => [
                'label'   => 'Low Stock Threshold (qty)',
                'type'    => 'number',
                'default' => 5,
                'public'  => false,
            ],
            'notify_new_review' => [
                'label'   => 'New Product Review',
                'type'    => 'boolean',
                'default' => false,
                'public'  => false,
            ],
            'notify_contact_form' => [
                'label'   => 'Contact Form Submission',
                'type'    => 'boolean',
                'default' => true,
                'public'  => false,
            ],
        ],

        /*
        | User Account Notifications
        */
        'user' => [

            'welcome_email' => [
                'label'       => 'Welcome Email on Sign-up',
                'type'        => 'boolean',
                'default'     => true,
                'public'      => false,
                'description' => 'Send a welcome email after successful registration.',
            ],
            'email_verification' => [
                'label'   => 'Require Email Verification',
                'type'    => 'boolean',
                'default' => false,
                'public'  => false,
            ],
            'login_alert' => [
                'label'       => 'Login Alert Email',
                'type'        => 'boolean',
                'default'     => false,
                'public'      => false,
                'description' => 'Notify the user when a new sign-in is detected.',
            ],
            'password_reset' => [
                'label'   => 'Password Reset Email',
                'type'    => 'boolean',
                'default' => true,
                'public'  => false,
            ],
        ],


    ],

];
