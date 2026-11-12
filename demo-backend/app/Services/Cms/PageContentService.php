<?php

namespace App\Services\Cms;

use App\Models\Page;
use App\Models\PageWidget;
use Illuminate\Support\Collection;

class PageContentService
{
    public function __construct(private readonly WidgetRenderer $renderer) {}

    public function resolveByPath(string $slugOrPath, ?int $channelId = null): Page
    {
        $channelId = $channelId ?? (int) (current_channel()?->id ?? 0);

        if (is_numeric($slugOrPath)) {
            $query = Page::published()->with($this->relations());

            if ($channelId > 0) {
                $query->forChannel($channelId);
            }

            return $query->findOrFail((int) $slugOrPath);
        }

        $slugs = array_values(array_filter(explode('/', trim($slugOrPath, '/'))));
        if ($slugs === []) {
            return $this->resolveHomePage($channelId);
        }

        $parentId = null;
        $page = null;

        foreach ($slugs as $slug) {
            $query = Page::published()
                ->with($this->relations())
                ->where('slug', $slug)
                ->where('parent_id', $parentId);

            if ($channelId > 0) {
                $query->forChannel($channelId);
            }

            $page = $query->firstOrFail();
            $parentId = $page->id;
        }

        return $page;
    }

    public function resolveHomePage(?int $channelId = null): Page
    {
        $channelId = $channelId ?? (int) (current_channel()?->id ?? 0);

        $query = Page::published()
            ->with($this->relations())
            ->whereNull('parent_id')
            ->where('slug', 'home');

        if ($channelId > 0) {
            $query->forChannel($channelId);
        }

        $home = $query->first();

        if ($home) {
            return $home;
        }

        $fallback = Page::published()->with($this->relations())->whereNull('parent_id');

        if ($channelId > 0) {
            $fallback->forChannel($channelId);
        }

        return $fallback->orderBy('position')->orderBy('id')->firstOrFail();
    }

    /**
     * @return array{
     *   page: Page,
     *   breadcrumbs: Collection,
     *   widgetsBySlot: array<string, list<array<string, mixed>>>
     * }
     */
    public function forStorefront(Page $page, string $device = 'desktop', ?string $slot = null): array
    {
        $locale = app()->getLocale();
        $channelId = (int) (current_channel()?->id ?? 0);

        $page = $this->applyLocaleAndChannel($page, $channelId, $locale);

        return [
            'page'          => $page,
            'breadcrumbs'   => $this->breadcrumbs($page, $locale),
            'widgetsBySlot' => $this->widgetsBySlot($page, $channelId, $locale, $device, $slot),
        ];
    }

    public function applyLocaleAndChannel(Page $page, int $channelId, string $locale): Page
    {
        $translation = $page->translation($locale);

        if ($translation) {
            $page->title = $translation->title ?: $page->title;
            $page->content = $translation->content ?: $page->content;
            $page->meta_title = $translation->meta_title ?: $page->meta_title;
            $page->meta_description = $translation->meta_description ?: $page->meta_description;
            $page->nav_label = $translation->nav_label ?: $page->nav_label;
        }

        if ($page->relationLoaded('channelContents')) {
            $override = $page->channelContents->firstWhere('channel_id', $channelId);

            if ($override) {
                $page->title = $override->title ?? $page->title;
                $page->content = $override->content ?? $page->content;
                $page->meta_title = $override->meta_title ?? $page->meta_title;
                $page->meta_description = $override->meta_description ?? $page->meta_description;
            }
        }

        return $page;
    }

    /** @return list<array{id: int, slug: string, title: string, path: string, url: string}> */
    public function breadcrumbs(Page $page, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $menu = app(MenuBuilderService::class);

        return $page->ancestors()
            ->push($page)
            ->map(function (Page $node) use ($locale, $menu) {
                $t = $node->translation($locale);

                return [
                    'id'    => $node->id,
                    'slug'  => $node->slug,
                    'title' => $t?->title ?: $node->title,
                    'path'  => $node->path,
                    'url'   => $menu->pageUrl($node),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function widgetsBySlot(
        Page $page,
        int $channelId,
        string $locale,
        string $device = 'desktop',
        ?string $slot = null,
        ?Collection $placements = null
    ): array {
        $placements = ($placements ?? $this->placements($page))
            ->when($slot, fn ($items) => $items->where('slot', $slot))
            ->filter(function (PageWidget $pw) use ($device, $channelId) {
                if (! $pw->active || ! $pw->widget || $pw->widget->status !== 'active') {
                    return false;
                }

                $now = now();
                if ($pw->starts_at && $pw->starts_at->gt($now)) {
                    return false;
                }
                if ($pw->ends_at && $pw->ends_at->lt($now)) {
                    return false;
                }

                if ($pw->widget->channels->isNotEmpty()
                    && ! $pw->widget->channels->pluck('id')->contains($channelId)) {
                    return false;
                }

                $vis = $pw->visibility ?? [];
                $dev = $vis['devices'] ?? null;

                if (is_array($dev) && ! in_array($device, $dev, true)) {
                    return false;
                }

                return true;
            })
            ->values();

        return $placements
            ->map(function (PageWidget $pw) use ($locale) {
                $resolved = $this->renderer->resolve($pw->widget, ['locale' => $locale]);

                return [
                    'placement_id' => $pw->id,
                    'placement_config' => $pw->presentationConfig(),
                    'id'         => $pw->widget->id,
                    'type'       => $pw->widget->type,
                    'widget_key' => WidgetRenderer::widgetKey($pw->widget),
                    'title'      => $pw->widget->localizedTitle($locale),
                    'slot'       => $pw->slot,
                    'position'   => $pw->position,
                    'config'     => $resolved,
                ];
            })
            ->groupBy('slot')
            ->map(fn (Collection $items) => $items->values()->all())
            ->all();
    }

    public function placements(Page $page): Collection
    {
        $placements = PageWidget::query()
            ->with(['widget.channels', 'widget.translations'])
            ->where('page_id', $page->id)
            ->orderBy('slot')->orderBy('position')->orderBy('id')->get();
        return $placements;
    }

    public function placementRevision(Collection $placements): string
    {
        // Include translations, publication, channels, presentation and time windows.
        // Hashing the loaded snapshot also catches edits made within the same second.
        $now = now();
        $windows = $placements->map(fn (PageWidget $pw) =>
            (! $pw->starts_at || $pw->starts_at->lte($now)) && (! $pw->ends_at || $pw->ends_at->gte($now)));

        return hash('sha256', $placements->toJson().$windows->toJson());
    }

    /** @return list<string> */
    protected function relations(): array
    {
        return ['translations', 'parent', 'children.translations', 'channelContents'];
    }
}
