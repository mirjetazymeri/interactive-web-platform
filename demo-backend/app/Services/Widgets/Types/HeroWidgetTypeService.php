<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;

class HeroWidgetTypeService implements WidgetTypeConfigService
{
    /** Globe labels the frontend cycles through; kept short so chips stay on one line. */
    public const MAX_LABELS = 12;

    public const MAX_LABEL_LENGTH = 60;

    public function type(): string
    {
        return 'hero';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['bg_color'] = (string) $request->input('config.bg_color', $existing['bg_color'] ?? '#1a1a2e');
        $config['hero_overlay_color'] = (string) $request->input('config.hero_overlay_color', $existing['hero_overlay_color'] ?? '#000000');
        $config['hero_overlay_opacity'] = max(0, min(100, (int) $request->input('config.hero_overlay_opacity', $existing['hero_overlay_opacity'] ?? 40)));
        $config['hero_min_height'] = (string) $request->input('config.hero_min_height', $existing['hero_min_height'] ?? '400px');
        $config['hero_text_align'] = (string) $request->input('config.hero_text_align', $existing['hero_text_align'] ?? 'center');
        $config['hero_content_width'] = (string) $request->input('config.hero_content_width', $existing['hero_content_width'] ?? 'medium');
        $config['hero_fullscreen'] = $request->boolean('config.hero_fullscreen', $existing['hero_fullscreen'] ?? false);
        $config['headline'] = (string) $request->input('config.headline', $existing['headline'] ?? '');
        $config['subtitle'] = (string) $request->input('config.subtitle', $existing['subtitle'] ?? '');
        $config['cta_text'] = (string) $request->input('config.cta_text', $existing['cta_text'] ?? '');
        $config['cta_link'] = (string) $request->input('config.cta_link', $existing['cta_link'] ?? '');
        $config['cta_style'] = (string) $request->input('config.cta_style', $existing['cta_style'] ?? 'primary');
        $config['cta_color'] = (string) $request->input('config.cta_color', $existing['cta_color'] ?? '#ffffff');
        $config['cta_bg'] = (string) $request->input('config.cta_bg', $existing['cta_bg'] ?? '#0d6efd');
        // Absent from the request (older forms, API clients) keeps what is stored.
        $config['rotating_labels'] = $request->has('config.rotating_labels')
            ? self::labels($request->input('config.rotating_labels'))
            : self::labels($existing['rotating_labels'] ?? []);

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        $config = [];

        foreach (['headline', 'subtitle', 'cta_text', 'cta_link'] as $key) {
            $value = trim((string) ($translation[$key] ?? ''));
            if ($value !== '') {
                $config[$key] = $value;
            }
        }

        $labels = self::labels($translation['rotating_labels'] ?? []);
        if ($labels !== []) {
            $config['rotating_labels'] = $labels;
        }

        return $config;
    }

    /**
     * Accepts the admin textarea (one label per line) or a stored list and
     * returns trimmed, non-empty, de-duplicated labels in their given order.
     *
     * @return list<string>
     */
    public static function labels(mixed $value): array
    {
        $lines = is_array($value) ? $value : preg_split('/\R/', (string) $value);
        $labels = [];
        foreach ($lines as $line) {
            $label = trim((string) (is_scalar($line) ? $line : ''));
            if ($label !== '' && ! in_array($label, $labels, true)) {
                $labels[] = mb_substr($label, 0, self::MAX_LABEL_LENGTH);
            }
        }

        return array_slice($labels, 0, self::MAX_LABELS);
    }
}
