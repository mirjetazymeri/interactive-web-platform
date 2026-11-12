<?php

namespace App\Services\Cms;

use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MenuBuilderService
{
    public function build(string $location, ?int $channelId = null, ?string $locale = null): array
    {
        abort_unless(in_array($location, ['header', 'footer'], true), 404);

        $channelId = $channelId ?? (int) (current_channel()?->id ?? 0);
        $locale = $locale ?? app()->getLocale();
        $cacheKey = "menu:{$location}:channel:{$channelId}:locale:{$locale}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($location, $channelId, $locale) {
            return $this->buildUncached($location, $channelId, $locale);
        });
    }

    public function buildUncached(string $location, int $channelId, string $locale): array
    {
        $query = $location === 'header'
            ? Page::with(['parent', 'translations'])->headerMenu()
            : Page::with(['parent', 'translations'])->footerMenu();

        if ($channelId > 0) {
            $query->forChannel($channelId);
        }

        $pages = $query->get([
            'id', 'slug', 'title', 'nav_label', 'nav_new_tab', 'parent_id',
            'header_position', 'footer_position', 'footer_section', 'position',
        ]);

        $byParent = $pages->groupBy(fn (Page $p) => $p->parent_id ?: 0);

        $sectionDefs = $location === 'footer'
            ? $this->resolveFooterSections()
            : collect();

        $sectionOrder = $sectionDefs->pluck('order', 'key');
        $sectionLabel = $sectionDefs->pluck('label', 'key');

        $build = function ($parentId, ?string $inheritedSection = null) use (
            &$build,
            $byParent,
            $location,
            $sectionLabel,
            $locale
        ) {
            return ($byParent[$parentId] ?? collect())->map(function (Page $p) use (
                $build,
                $location,
                $inheritedSection,
                $sectionLabel,
                $locale
            ) {
                $t = $p->translation($locale);
                $label = $t?->nav_label ?: $p->nav_label ?: $t?->title ?: $p->title;

                $section = $location === 'footer'
                    ? (trim((string) ($p->footer_section ?? '')) !== ''
                        ? (string) $p->footer_section
                        : ($inheritedSection ?: 'General'))
                    : null;

                return [
                    'id'            => $p->id,
                    'label'         => $label,
                    'url'           => $this->pageUrl($p),
                    'path'          => $p->path,
                    'target'        => $p->nav_new_tab ? '_blank' : '_self',
                    'section'       => $section,
                    'section_key'   => $section ? Str::slug($section, '_') : null,
                    'section_label' => $section ? ($sectionLabel[Str::slug($section, '_')] ?? $section) : null,
                    'children'      => $build($p->id, $section),
                    'position'      => $location === 'header'
                        ? ($p->header_position ?? $p->position ?? 0)
                        : ($p->footer_position ?? $p->position ?? 0),
                ];
            })->sortBy('position')->values()->all();
        };

        $tree = $build(0);

        if ($location !== 'footer') {
            return $tree;
        }

        return collect($tree)
            ->sortBy(function (array $item) use ($sectionOrder) {
                $key = (string) ($item['section_key'] ?? 'general');

                return [
                    $sectionOrder[$key] ?? 9999,
                    (int) ($item['position'] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    public function pageUrl(Page $page): string
    {
        if ($page->slug === 'home' && $page->parent_id === null) {
            return route('storefront.home');
        }

        return route('storefront.page', ['slugOrPath' => $page->path]);
    }

    /** @return Collection<int, array{key: string, label: string, order: int}> */
    protected function resolveFooterSections(): Collection
    {
        $raw = setting('footer.sections', "Company\nCustomer Care\nLegal\nSocial");

        if (is_array($raw)) {
            $items = collect($raw);
        } else {
            $text = trim((string) $raw);
            $decoded = $text !== '' ? json_decode($text, true) : null;

            if (is_array($decoded)) {
                $items = collect($decoded);
            } else {
                $items = collect(preg_split('/\r\n|\r|\n|,/', $text ?: ''));
            }
        }

        return $items
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->map(fn (string $label, int $index) => [
                'key'   => Str::slug($label, '_'),
                'label' => $label,
                'order' => $index,
            ])
            ->values();
    }
}
