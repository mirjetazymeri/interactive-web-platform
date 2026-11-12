<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;

/**
 * Footer call to action: the closing band above the site footer, shown on
 * every page. The active widget of this type (first by position) is used;
 * its copy is translatable, the button link and email toggle are shared.
 */
class FooterCtaWidgetTypeService implements WidgetTypeConfigService
{
    public const TYPE = 'footer_cta';

    /** Translatable copy. */
    public const TEXT_FIELDS = ['eyebrow', 'heading', 'text', 'button_label'];

    /** Longer fields; the rest are single lines. */
    public const LONG_FIELDS = ['heading', 'text'];

    public function type(): string
    {
        return self::TYPE;
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        foreach (self::TEXT_FIELDS as $field) {
            $config[$field] = trim((string) ($incoming[$field] ?? ''));
        }
        $config['button_link'] = trim((string) ($incoming['button_link'] ?? ''));
        $config['show_email'] = $request->boolean('config.show_email');

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        return collect(self::TEXT_FIELDS)
            ->mapWithKeys(fn ($field) => [$field => trim((string) ($translation[$field] ?? ''))])
            ->filter()
            ->all();
    }

    /** The public config: only this type's fields. */
    public function representation(array $config): array
    {
        return [
            ...collect(self::TEXT_FIELDS)->mapWithKeys(fn ($field) => [$field => trim((string) ($config[$field] ?? ''))])->all(),
            'button_link' => trim((string) ($config['button_link'] ?? '')),
            'show_email' => (bool) ($config['show_email'] ?? false),
        ];
    }
}
