<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;

/**
 * Hero World: a homepage hero with a dotted 3D globe. Only content lives here;
 * the globe, motion and layout belong to the frontend.
 */
class HeroWorldWidgetTypeService implements WidgetTypeConfigService
{
    public const TYPE = 'hero-world';

    /** Translatable widget-level text. */
    public const TEXT_FIELDS = ['headline', 'subtitle', 'cta_text', 'seo_title', 'seo_description'];

    /** Translatable text of each service point. */
    public const SERVICE_TEXT_FIELDS = ['title', 'description'];

    /** Icon keys the frontend draws as inline, theme-colored SVG. */
    public const ICONS = ['dot', 'code', 'cloud', 'data', 'ai', 'project', 'design', 'security', 'support', 'mobile', 'analytics', 'globe'];

    public const MAX_SERVICES = 60;

    public function type(): string
    {
        return self::TYPE;
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        foreach (self::TEXT_FIELDS as $field) {
            $config[$field] = trim((string) ($incoming[$field] ?? ''));
        }
        $config['cta_link'] = trim((string) ($incoming['cta_link'] ?? ''));
        $config['services'] = [];

        foreach (array_values((array) ($incoming['services'] ?? [])) as $index => $service) {
            $service = (array) $service;
            $item = ['id' => (string) $service['id']];
            foreach (self::SERVICE_TEXT_FIELDS as $field) {
                $item[$field] = trim((string) ($service[$field] ?? ''));
            }
            $icon = (string) ($service['icon'] ?? '');
            $item['icon'] = in_array($icon, self::ICONS, true) ? $icon : null;
            [$item['lat'], $item['lng']] = self::coordinates($service['lat'] ?? null, $service['lng'] ?? null);
            $item['position'] = (int) ($service['position'] ?? $index + 1);
            $item['active'] = (bool) ($service['active'] ?? true);
            $config['services'][] = $item;
        }

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        $out = self::textOnly($translation, self::TEXT_FIELDS);
        foreach ((array) ($translation['services'] ?? []) as $id => $service) {
            $text = self::textOnly((array) $service, self::SERVICE_TEXT_FIELDS);
            if ($text !== []) {
                $out['services'][(string) $id] = $text;
            }
        }

        return $out;
    }

    /**
     * Applies translation layers (fallback locale first, then the requested
     * locale) to the base content. Services are matched by their stable id, so
     * reordering or removing a service never mixes up its translations.
     */
    public function localize(array $base, array ...$translations): array
    {
        $config = $base;
        $services = collect($base['services'] ?? [])->keyBy('id')->all();
        foreach ($translations as $translation) {
            $text = $this->extractTranslationConfig($translation);
            $config = array_replace($config, self::textOnly($translation, ['section_label']), array_diff_key($text, ['services' => true]));
            foreach ($text['services'] ?? [] as $id => $override) {
                if (isset($services[$id])) {
                    $services[$id] = array_replace($services[$id], $override);
                }
            }
        }
        $config['services'] = array_values($services);

        return $config;
    }

    /**
     * Public representation: active services with a title, in display order,
     * with coordinates normalized (both or neither).
     */
    public function representation(array $config): array
    {
        $config['services'] = collect($config['services'] ?? [])
            ->filter(fn ($service) => is_array($service) && ($service['active'] ?? true) && trim((string) ($service['title'] ?? '')) !== '')
            ->sortBy(fn ($service) => (int) ($service['position'] ?? 0), SORT_NUMERIC)
            ->values()
            ->map(function (array $service) {
                [$lat, $lng] = self::coordinates($service['lat'] ?? null, $service['lng'] ?? null);
                $icon = (string) ($service['icon'] ?? '');

                return [
                    'id' => (string) $service['id'],
                    'title' => trim((string) $service['title']),
                    'description' => trim((string) ($service['description'] ?? '')),
                    'icon' => in_array($icon, self::ICONS, true) ? $icon : null,
                    'lat' => $lat,
                    'lng' => $lng,
                    'position' => (int) ($service['position'] ?? 0),
                ];
            })
            ->all();

        return $config;
    }

    /**
     * A marker needs both coordinates within range; anything else lets the
     * frontend place the service automatically.
     *
     * @return array{0: ?float, 1: ?float}
     */
    public static function coordinates(mixed $lat, mixed $lng): array
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return [null, null];
        }
        $lat = (float) $lat;
        $lng = (float) $lng;
        if (! is_finite($lat) || ! is_finite($lng) || abs($lat) > 90 || abs($lng) > 180) {
            return [null, null];
        }

        return [round($lat, 4), round($lng, 4)];
    }

    private static function textOnly(array $input, array $fields): array
    {
        $output = [];
        foreach ($fields as $field) {
            $value = trim((string) (is_scalar($input[$field] ?? null) ? $input[$field] : ''));
            if ($value !== '') {
                $output[$field] = $value;
            }
        }

        return $output;
    }
}
