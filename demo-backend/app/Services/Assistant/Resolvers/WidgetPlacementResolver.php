<?php

namespace App\Services\Assistant\Resolvers;

use App\Models\Page;
use App\Models\PageWidget;
use App\Models\Widget;

/**
 * Widgets of a type that visitors can actually see: active, visible in the
 * channel and placed (active, in schedule) on a reachable published page.
 * Each widget comes with the page it is shown on.
 */
class WidgetPlacementResolver
{
    public function __construct(private PagesResolver $pages) {}

    /**
     * Widgets on dedicated pages come first, so answers link to e.g. /services or /projects
     * rather than the home page when the content is shown on both.
     *
     * @return list<array{widget: Widget, placement_id: int, path: string, page: Page}> one entry per widget
     */
    public function visible(int $channelId, string $type): array
    {
        $reachable = $this->pages->reachable($channelId);
        $placements = PageWidget::query()->active()
            ->whereIn('page_id', array_keys($reachable) ?: [0])
            ->whereHas('widget', fn ($q) => $q->active()->visibleInChannel($channelId)->where('type', $type))
            ->with('widget.translations')
            ->orderBy('id')
            ->get();

        $out = [];
        foreach ($placements as $placement) {
            $widgetId = $placement->widget_id;
            $entry = ['widget' => $placement->widget, 'placement_id' => $placement->id] + $reachable[$placement->page_id];
            // Prefer a dedicated page over the home page when the widget is placed on both.
            if (! isset($out[$widgetId]) || ($out[$widgetId]['path'] === 'home' && $entry['path'] !== 'home')) {
                $out[$widgetId] = $entry;
            }
        }

        usort($out, fn ($a, $b) => [$a['path'] === 'home', $a['placement_id']] <=> [$b['path'] === 'home', $b['placement_id']]);

        return $out;
    }
}
