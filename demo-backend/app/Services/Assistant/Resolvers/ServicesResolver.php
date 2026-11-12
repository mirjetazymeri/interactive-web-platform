<?php

namespace App\Services\Assistant\Resolvers;

/**
 * Services and subservices from the visible ServiceX widgets, in the
 * widget's order and the visitor's language. Nothing is stored twice: the
 * assistant reads the same widget content the site shows.
 */
class ServicesResolver
{
    public function __construct(private WidgetPlacementResolver $placements) {}

    /**
     * @return list<array{key: string, type: string, name: string, parent: ?string, parent_key: ?string, ref: string, text: string, path: string, anchor: string}>
     */
    public function all(int $channelId, string $locale): array
    {
        $out = [];
        // The same services can be shown by several ServiceX widgets (home and services page);
        // each is listed once, from the widget on the most specific page.
        $seen = [];
        foreach ($this->placements->visible($channelId, 'service_x') as $entry) {
            $widget = $entry['widget'];
            $services = collect($widget->localizedConfig($locale)['services'] ?? [])
                ->filter(fn ($service) => is_array($service) && filled($service['id'] ?? null) && ($service['active'] ?? true) !== false)
                ->sortBy(fn ($service) => (int) ($service['position'] ?? 0));
            $anchor = 'service-x-'.$entry['placement_id'];

            foreach ($services as $service) {
                $name = self::displayName((string) ($service['name'] ?? ''));
                if ($name === '' || isset($seen[$service['id']])) {
                    continue;
                }
                $seen[$service['id']] = true;
                $serviceKey = $widget->id.':'.$service['id'];
                $out[] = [
                    'key' => 'service:'.$serviceKey, 'type' => 'service', 'name' => $name, 'parent' => null, 'parent_key' => null, 'ref' => (string) $service['id'],
                    'text' => Text::plain(trim(($service['title'] ?? '').' '.($service['subtitle'] ?? ''))),
                    'path' => $entry['path'], 'anchor' => $anchor,
                ];
                $subservices = collect($service['subservices'] ?? [])
                    ->filter(fn ($sub) => is_array($sub) && filled($sub['id'] ?? null) && filled($sub['title'] ?? null) && ($sub['active'] ?? true) !== false)
                    ->sortBy(fn ($sub) => (int) ($sub['position'] ?? 0));
                foreach ($subservices as $sub) {
                    $out[] = [
                        'key' => 'subservice:'.$serviceKey.'/'.$sub['id'], 'type' => 'subservice', 'name' => trim((string) $sub['title']),
                        'parent' => $name, 'parent_key' => 'service:'.$serviceKey, 'ref' => $service['id'].'/'.$sub['id'],
                        'text' => Text::plain((string) ($sub['description'] ?? '')),
                        'path' => $entry['path'], 'anchor' => $anchor,
                    ];
                }
            }
        }

        return $out;
    }

    /** "01 Development" → "Development": the decorative index is not part of the name. */
    public static function displayName(string $name): string
    {
        return trim(preg_replace('/^0?\d+(?:\s+|[.\-–—]\s*)/u', '', trim($name)) ?? $name);
    }
}
