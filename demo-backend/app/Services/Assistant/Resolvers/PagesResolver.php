<?php

namespace App\Services\Assistant\Resolvers;

use App\Models\Page;

/**
 * Public page paths and links, resolved the way /api/v1/pages resolves them:
 * a page is reachable only when it and all its ancestors are published in the
 * channel. Links use the frontend's locale-prefixed paths.
 */
class PagesResolver
{
    /** @return array<int, array{path: string, page: Page}> keyed by page id */
    public function reachable(int $channelId): array
    {
        $published = Page::query()->published()->forChannel($channelId)->with('translations')->get()->keyBy('id');
        $pages = [];
        foreach ($published as $page) {
            $path = $this->path($page, $published);
            if ($path !== null) {
                $pages[$page->id] = ['path' => $path, 'page' => $page];
            }
        }

        return $pages;
    }

    public function find(int $channelId, ?int $pageId): ?array
    {
        return $pageId ? ($this->reachable($channelId)[$pageId] ?? null) : null;
    }

    /** The first reachable page with this slug, preferring top-level pages. */
    public function findBySlug(int $channelId, string $slug): ?array
    {
        $matches = array_filter($this->reachable($channelId), fn ($entry) => $entry['page']->slug === $slug);
        usort($matches, fn ($a, $b) => [$a['page']->parent_id !== null, $a['page']->id] <=> [$b['page']->parent_id !== null, $b['page']->id]);

        return $matches[0] ?? null;
    }

    /** The page's navigation label or title in the locale. */
    public function label(Page $page, string $locale): string
    {
        $translation = $page->translations->firstWhere('locale', $locale);

        return (string) ($translation?->nav_label ?: $translation?->title ?: $page->nav_label ?: $page->title);
    }

    /** A page link action, or null when the path or anchor is not safe. */
    public function action(string $locale, string $path, ?string $anchor, string $label): ?array
    {
        $href = self::href($locale, $path, $anchor);

        return $href ? ['type' => 'page', 'label' => $label, 'path' => $path, 'anchor' => $anchor ?: null, 'href' => $href] : null;
    }

    /** "/en", "/sq/services#service-x-4": the frontend's public URL for a CMS path. */
    public static function href(string $locale, string $path, ?string $anchor = null): ?string
    {
        if (! preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]+)?$/', $locale)
            || ! preg_match('#^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*$#', $path)
            || ($anchor !== null && $anchor !== '' && ! self::validAnchor($anchor))) {
            return null;
        }
        $href = '/'.$locale.($path === 'home' ? '' : '/'.$path);

        return $anchor ? $href.'#'.$anchor : $href;
    }

    /** The same page in another language: "/sq/services#x" → "/en/services#x"; other links are left as they are. */
    public static function relocate(string $href, string $from, string $to): string
    {
        if (! preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]+)?$/', $to)) {
            return $href;
        }
        foreach (['/'.$from.'/', '/'.$from.'#'] as $prefix) {
            if (str_starts_with($href, $prefix)) {
                return '/'.$to.substr($href, strlen('/'.$from));
            }
        }

        return $href === '/'.$from ? '/'.$to : $href;
    }

    public static function validAnchor(string $anchor): bool
    {
        return (bool) preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,99}$/', $anchor);
    }

    private function path(Page $page, $published): ?string
    {
        $segments = [];
        $visited = [];
        $node = $page;
        while ($node) {
            if (isset($visited[$node->id])) {
                return null;
            }
            $visited[$node->id] = true;
            $segments[] = $node->slug;
            if (! $node->parent_id) {
                break;
            }
            // An unpublished ancestor makes the page unreachable.
            $node = $published->get($node->parent_id);
            if (! $node) {
                return null;
            }
        }

        return implode('/', array_reverse($segments));
    }
}
