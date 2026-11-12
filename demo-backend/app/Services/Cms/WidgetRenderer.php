<?php

namespace App\Services\Cms;

use App\Models\Widget;
use App\Services\Widgets\HeroMediaService;
use Illuminate\Support\Str;

class WidgetRenderer
{
    /**
     * Build a unique component key from type + layout.
     * e.g. "featured_products.1x4", "category_list.2x3", "carousel.carousel"
     */
    public static function widgetKey(Widget $widget): string
    {
        $cfg    = (array) ($widget->config ?? []);
        $parts  = [$widget->type, $cfg['layout'] ?? '1x1'];

        return implode('.', $parts);
    }

    /**
     * Resolve a widget config for API output.
     *
     * Returns the raw config with image/slide URLs resolved.
     * The frontend uses products-projection endpoints for product data.
     */
    public function resolve(Widget $widget, array $ctx = []): array
    {
        $locale = $ctx['locale'] ?? app()->getLocale();
        $config = $widget->localizedConfig($locale);

        if ($widget->type === 'projects') {
            $service = app(\App\Services\Widgets\Types\ProjectsWidgetTypeService::class);
            $config['projects'] = collect($config['projects'] ?? [])->where('status', 'published')->sortBy('position', SORT_NUMERIC)->values()
                ->map(fn ($item) => $service->representation($widget, $item, $locale))->all();
        }

        if ($widget->type === 'service_x') {
            $config['services'] = $this->orderedActiveItems($config['services'] ?? []);
            foreach ($config['services'] as &$service) {
                $service['image'] = empty($service['image']) ? null : $this->mediaUrl($service['image']);
                $service['subservices'] = $this->orderedActiveItems($service['subservices'] ?? []);
            }
            unset($service);
        }

        if ($widget->type === \App\Services\Widgets\Types\HeroWorldWidgetTypeService::TYPE) {
            $config = app(\App\Services\Widgets\Types\HeroWorldWidgetTypeService::class)->representation($config);
        }

        if ($widget->type === \App\Services\Widgets\Types\MeetingBookingWidgetTypeService::TYPE) {
            $config = app(\App\Services\Widgets\Types\MeetingBookingWidgetTypeService::class)->representation($widget, $config, $locale);
        }

        if ($widget->type === \App\Services\Widgets\Types\FooterCtaWidgetTypeService::TYPE) {
            $config = app(\App\Services\Widgets\Types\FooterCtaWidgetTypeService::class)->representation($config);
        }

        if ($widget->type === 'html') {
            $config['html'] = $this->sanitizeHtml((string) ($config['html'] ?? ''));
            $config['css'] = $this->sanitizeCss((string) ($config['css'] ?? ''));
            $config['js'] = '';
        }

        if ($widget->type === 'hero') {
            // Media belongs to the base widget; locale overrides only change text.
            $config['media'] = app(HeroMediaService::class)->representation($widget->config ?? []);
            if (($config['media']['type'] ?? null) === 'image') {
                $config['bg_image'] = $config['media']['url'];
            } else {
                unset($config['bg_image']);
            }
        }

        // Resolve carousel slide image paths to full URLs
        if (!empty($config['slides']) && is_array($config['slides'])) {
            $config['slides'] = array_values(array_map(function ($slide) {
                $slide = (array) $slide;
                if (!empty($slide['image'])) {
                    $slide['image'] = $this->mediaUrl((string) $slide['image']);
                }
                unset($slide['keep_image'], $slide['existing_image']);
                return $slide;
            }, $config['slides']));
        }

        // Resolve flat images (legacy / non-slide types)
        if (!empty($config['images']) && is_array($config['images'])) {
            $alts = (array) ($config['images_alt'] ?? []);
            $config['images'] = array_values(array_map(
                fn ($path, $i) => [
                    'url' => $this->mediaUrl((string) $path),
                    'alt' => $alts[$i] ?? '',
                ],
                $config['images'],
                array_keys($config['images'])
            ));
            unset($config['images_alt']);
        }

        if ($widget->type === 'image_gallery' && !empty($config['items']) && is_array($config['items'])) {
            $config['items'] = array_values(array_map(function ($item) {
                $item = (array) $item;

                if (!empty($item['image'])) {
                    $item['image'] = $this->mediaUrl((string) $item['image']);
                }

                unset($item['keep_image'], $item['existing_image']);

                return $item;
            }, $config['items']));
        }

        if (in_array($widget->type, ['features', 'faq'], true) && !empty($config['items']) && is_array($config['items'])) {
            $config['items'] = array_values(array_map(function ($item) {
                $item = (array) $item;

                if (!empty($item['icon'])) {
                    $item['icon'] = $this->mediaUrl((string) $item['icon']);
                }

                if (!empty($item['image'])) {
                    $item['image'] = $this->mediaUrl((string) $item['image']);
                }

                unset($item['keep_icon'], $item['existing_icon'], $item['keep_image'], $item['existing_image']);

                return $item;
            }, $config['items']));
        }

        if ($widget->type === 'testimonials' && !empty($config['items']) && is_array($config['items'])) {
            $config['items'] = array_values(array_map(function ($item) {
                $item = (array) $item;

                if (!empty($item['logo'])) {
                    $item['logo'] = $this->mediaUrl((string) $item['logo']);
                }

                if (!empty($item['image'])) {
                    $item['image'] = $this->mediaUrl((string) $item['image']);
                }

                unset($item['keep_logo'], $item['existing_logo'], $item['keep_image'], $item['existing_image']);

                return $item;
            }, $config['items']));
        }

        if ($widget->type === 'map') {
            $modeValue = (string) ($config['mode'] ?? 'coordinates');
            $mode = in_array($modeValue, ['coordinates', 'embed'], true)
                ? $modeValue
                : 'coordinates';

            $config['mode'] = $mode;
            $locationModeValue = (string) ($config['location_mode'] ?? 'single');
            $config['location_mode'] = in_array($locationModeValue, ['single', 'multi'], true)
                ? $locationModeValue
                : 'single';
            $providerValue = (string) ($config['provider'] ?? 'osm');
            $config['provider'] = in_array($providerValue, ['osm', 'google', 'mapbox'], true)
                ? $providerValue
                : 'osm';
            $config['zoom'] = max(1, min(20, (int) ($config['zoom'] ?? 13)));

            if ($mode === 'coordinates') {
                $lat = isset($config['lat']) && is_numeric($config['lat']) ? (float) $config['lat'] : null;
                $lng = isset($config['lng']) && is_numeric($config['lng']) ? (float) $config['lng'] : null;

                $markers = [];
                foreach ((array) ($config['markers'] ?? []) as $marker) {
                    $marker = (array) $marker;
                    $mLat = isset($marker['lat']) && is_numeric($marker['lat']) ? (float) $marker['lat'] : null;
                    $mLng = isset($marker['lng']) && is_numeric($marker['lng']) ? (float) $marker['lng'] : null;
                    if ($mLat === null || $mLng === null) {
                        continue;
                    }

                    $markers[] = [
                        'lat' => $mLat,
                        'lng' => $mLng,
                        'title' => trim((string) ($marker['title'] ?? '')),
                        'text' => trim((string) ($marker['text'] ?? '')),
                    ];
                }

                if (!empty($markers)) {
                    $lat = $markers[0]['lat'];
                    $lng = $markers[0]['lng'];
                } elseif ($lat !== null && $lng !== null) {
                    $markers[] = [
                        'lat' => $lat,
                        'lng' => $lng,
                        'title' => trim((string) ($config['marker_title'] ?? '')),
                        'text' => trim((string) ($config['marker_text'] ?? '')),
                    ];
                }

                $config['lat'] = $lat;
                $config['lng'] = $lng;
                $config['markers'] = $markers;

                if ($lat !== null && $lng !== null) {
                    $config['map_embed_url'] = $this->buildOsmEmbedUrl($lat, $lng, $config['zoom']);
                    $config['map_url'] = sprintf('https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=%d/%s/%s', $lat, $lng, $config['zoom'], $lat, $lng);
                }
            } elseif (!empty($config['embed_url'])) {
                $config['map_embed_url'] = (string) $config['embed_url'];
                $config['markers'] = [];
            }
        }

        return $config;
    }

    private function orderedActiveItems(array $items): array
    {
        return collect($items)->filter(fn (array $item) => $item['active'] ?? true)
            ->sortBy('position', SORT_NUMERIC)->values()->all();
    }

    private function sanitizeHtml(string $html): string
    {
        $allowed = '<p><br><strong><em><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><span><div><img>';
        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/on[a-z]+\\s*=\\s*"[^"]*"/i', '', $clean) ?? $clean;
        $clean = preg_replace("/on[a-z]+\\s*=\\s*'[^']*'/i", '', $clean) ?? $clean;
        $clean = preg_replace('/javascript:/i', '', $clean) ?? $clean;

        return trim($clean);
    }

    private function sanitizeCss(string $css): string
    {
        $clean = preg_replace('/expression\\s*\\(/i', '', $css) ?? $css;
        $clean = preg_replace('/url\\s*\\(\\s*javascript:/i', 'url(', $clean) ?? $clean;

        return trim($clean);
    }

    private function mediaUrl(string $path): string
    {
        $raw = trim($path);

        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            $raw = (string) (parse_url($raw, PHP_URL_PATH) ?? '');
        }

        $normalized = ltrim($raw, '/');

        if ($normalized === '') {
            return '';
        }

        if (Str::startsWith($normalized, 'storage/')) {
            return asset($normalized);
        }

        return asset('storage/' . $normalized);
    }

    private function buildOsmEmbedUrl(float $lat, float $lng, int $zoom): string
    {
        $delta = max(0.0015, 360 / pow(2, $zoom + 2));
        $left = number_format($lng - $delta, 6, '.', '');
        $right = number_format($lng + $delta, 6, '.', '');
        $top = number_format($lat + $delta, 6, '.', '');
        $bottom = number_format($lat - $delta, 6, '.', '');
        $marker = rawurlencode(number_format($lat, 6, '.', '') . ',' . number_format($lng, 6, '.', ''));
        $bbox = rawurlencode("$left,$bottom,$right,$top");

        return "https://www.openstreetmap.org/export/embed.html?layer=mapnik&marker=$marker&bbox=$bbox";
    }
}
