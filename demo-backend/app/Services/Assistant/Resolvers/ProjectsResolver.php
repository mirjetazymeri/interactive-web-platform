<?php

namespace App\Services\Assistant\Resolvers;

use Illuminate\Support\Facades\Storage;

/**
 * Published projects from the visible Projects widgets, with links to the
 * frontend's project pages (/{locale}/projects/{widget}/{slug}).
 */
class ProjectsResolver
{
    public function __construct(private WidgetPlacementResolver $placements) {}

    /**
     * @return list<array{key: string, name: string, slug: string, widget_id: int, category: string, client: string,
     *   technologies: list<string>, text: string, type: string, details: string, image: ?string, featured: bool, path: string, list_path: string}>
     */
    public function all(int $channelId, string $locale): array
    {
        $out = [];
        foreach ($this->placements->visible($channelId, 'projects') as $entry) {
            $widget = $entry['widget'];
            $projects = collect($widget->localizedConfig($locale)['projects'] ?? [])
                ->filter(fn ($project) => is_array($project) && ($project['status'] ?? null) === 'published'
                    && preg_match('/^[A-Za-z0-9_-]+$/', (string) ($project['slug'] ?? '')) && filled($project['name'] ?? null))
                ->sortBy(fn ($project) => [! ($project['featured'] ?? false), (int) ($project['position'] ?? 0)]);

            foreach ($projects as $project) {
                $image = $project['images'][0]['path'] ?? null;
                $out[] = [
                    'key' => 'project:'.$widget->id.':'.$project['slug'],
                    'name' => trim((string) $project['name']),
                    'slug' => (string) $project['slug'],
                    'widget_id' => (int) $widget->id,
                    'category' => Text::plain((string) ($project['category'] ?? ''), 80),
                    'client' => Text::plain((string) ($project['client_name'] ?? ''), 80),
                    'technologies' => array_values(array_filter(array_map(fn ($t) => Text::plain((string) $t, 40), (array) ($project['technologies'] ?? [])))),
                    'text' => Text::plain((string) ($project['description'] ?? ''), 300),
                    // Used to relate the project to services ("E-commerce"), never shown as is.
                    'type' => Text::plain((string) ($project['project_type'] ?? ''), 80),
                    'details' => Text::plain(trim(($project['description'] ?? '').' '.($project['work_delivered'] ?? '')), 3000),
                    'image' => is_string($image) && $image !== '' && ! str_contains($image, '..') ? Storage::disk('public')->url($image) : null,
                    'featured' => (bool) ($project['featured'] ?? false),
                    'path' => 'projects/'.$widget->id.'/'.$project['slug'],
                    'list_path' => $entry['path'],
                ];
            }
        }

        return $out;
    }
}
