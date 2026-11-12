<?php

namespace App\Services\Seo;

use App\Models\Locale;
use App\Models\Page;
use App\Models\PageWidget;
use App\Models\Widget;
use App\Models\WidgetTranslation;
use App\Services\SettingService;
use App\Services\Widgets\Types\ProjectsWidgetTypeService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Search metadata the public website needs and cannot derive itself: which
 * languages really have content, per-page robots and sharing overrides, which
 * widget owns a project slug shared by several widgets, and the sitemap list.
 * Absolute URLs are built by the website from its own SITE_URL.
 */
class SeoService
{
    /** schema.org page types an editor may choose; empty lets the website infer one. */
    public const SCHEMA_TYPES = ['WebPage', 'AboutPage', 'ContactPage', 'CollectionPage', 'FAQPage', 'ItemPage'];

    private ?array $locales = null;

    public function __construct(private readonly SettingService $settings) {}

    /** @return list<string> Active locale codes, default first. */
    public function locales(): array
    {
        return $this->locales ??= (function () {
            try {
                $codes = Locale::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('code')->pluck('code')->all();
            } catch (\Illuminate\Database\QueryException) {
                $codes = []; // Installations without the locales table use the app locale.
            }

            return $codes ?: [(string) config('app.locale', 'en')];
        })();
    }

    public function defaultLocale(): string
    {
        return $this->locales()[0];
    }

    /**
     * Languages in which a page has its own content: the base (default) language
     * plus every language with a page translation or a translated active widget.
     * Other languages render fallback content and must not be advertised.
     *
     * @return list<string>
     */
    public function pageLocales(Page $page, ?Collection $placements = null): array
    {
        $page->loadMissing('translations');
        $translated = $page->translations
            ->filter(fn ($t) => collect(['title', 'content', 'meta_title', 'meta_description'])->contains(fn ($field) => filled($t->{$field})))
            ->pluck('locale');

        $placements ??= PageWidget::query()->with('widget.translations')->where('page_id', $page->id)->get();
        $widgetLocales = $placements
            ->filter(fn (PageWidget $pw) => $pw->active && $pw->widget && $pw->widget->status === 'active')
            ->flatMap(fn (PageWidget $pw) => $pw->widget->translations
                ->filter(fn (WidgetTranslation $t) => filled($t->title) || ! empty($t->config))
                ->pluck('locale'));

        return $this->ordered([$this->defaultLocale(), ...$translated, ...$widgetLocales]);
    }

    /** @return list<string> Languages with their own text for one project item. */
    public function projectLocales(Widget $widget, array $item): array
    {
        $widget->loadMissing('translations');
        $translated = $widget->translations
            ->filter(fn (WidgetTranslation $t) => collect((array) ($t->config['projects'][$item['id']] ?? []))
                ->except('images')->filter(fn ($value) => filled($value))->isNotEmpty())
            ->pluck('locale');

        return $this->ordered([$this->defaultLocale(), ...$translated]);
    }

    /** The SEO block of the public page payload, for the page already localized to $locale. */
    public function pageMeta(Page $page, string $locale): array
    {
        $translation = $page->translation($locale);

        return [
            'title' => $translation?->meta_title ?: $page->meta_title,
            'description' => $translation?->meta_description ?: $page->meta_description,
            'canonical' => filled($page->canonical_url) ? (string) $page->canonical_url : null,
            'og_title' => $translation?->og_title ?: $page->og_title,
            'og_description' => $translation?->og_description ?: $page->og_description,
            'og_image' => filled($page->og_image) ? Storage::disk('public')->url($page->og_image) : null,
            'robots' => ['index' => self::flag($page->robots_index), 'follow' => self::flag($page->robots_follow)],
            'schema_type' => in_array($page->schema_type, self::SCHEMA_TYPES, true) ? $page->schema_type : null,
        ];
    }

    /**
     * Published project items reachable on the website (their widget is active,
     * visible in the channel and placed on a published page). A slug shared by
     * several widgets is owned by one of them: indexable items first, then the
     * lowest widget id. The rest canonicalize to the owner.
     *
     * @return Collection<int, array{widget: Widget, item: array, owner: bool}>
     */
    public function projectEntries(int $channelId): Collection
    {
        $placed = PageWidget::active()->whereHas('page', fn ($q) => $q->published()->forChannel($channelId))->pluck('widget_id');
        $entries = Widget::active()->visibleInChannel($channelId)->where('type', 'projects')->whereIn('id', $placed)
            ->with('translations')->orderBy('id')->get()
            ->flatMap(fn (Widget $widget) => collect($widget->config['projects'] ?? [])
                ->where('status', 'published')
                ->filter(fn ($item) => is_array($item) && filled($item['slug'] ?? null))
                ->map(fn ($item) => ['widget' => $widget, 'item' => $item]))
            ->values();

        $owners = $entries->groupBy(fn ($entry) => (string) $entry['item']['slug'])
            ->map(fn (Collection $group) => $group->sortBy(fn ($entry) => [self::indexable($entry['item']) ? 0 : 1, $entry['widget']->id])->first());

        return $entries->map(fn ($entry) => $entry + ['owner' => $owners[(string) $entry['item']['slug']] === $entry]);
    }

    public function canonicalProjectWidgetId(int $channelId, string $slug): ?int
    {
        return $this->projectEntries($channelId)->first(fn ($entry) => $entry['owner'] && $entry['item']['slug'] === $slug)['widget']->id ?? null;
    }

    public static function indexable(array $item): bool
    {
        return self::flag($item['robots_index'] ?? null);
    }

    /**
     * GET /api/v1/sitemap: indexable page paths and project items, each with the
     * languages that have their own content and a real modification time.
     */
    public function sitemap(int $channelId): array
    {
        $pages = Page::query()->published()->forChannel($channelId)->with('translations')->orderBy('id')->get();
        $byId = $pages->keyBy('id');
        $placements = PageWidget::query()->with('widget.translations')->whereIn('page_id', $pages->modelKeys())->get()->groupBy('page_id');

        $pageEntries = $pages
            ->filter(fn (Page $page) => self::flag($page->robots_index))
            ->map(fn (Page $page) => ['page' => $page, 'path' => self::pathFor($page, $byId)])
            ->filter(fn ($entry) => $entry['path'] !== '' && ! $this->canonicalizesElsewhere($entry['page'], $entry['path']))
            ->map(fn ($entry) => [
                'path' => $entry['path'],
                'updated_at' => self::atom($entry['page']->updated_at),
                'locales' => $this->pageLocales($entry['page'], $placements->get($entry['page']->id, collect())),
            ])->values();

        $projects = $this->projectEntries($channelId)
            ->filter(fn ($entry) => $entry['owner'] && self::indexable($entry['item']))
            ->map(fn ($entry) => [
                'widget_id' => $entry['widget']->id,
                'slug' => (string) $entry['item']['slug'],
                'updated_at' => self::atom($entry['item']['updated_at'] ?? null) ?? self::atom($entry['widget']->updated_at),
                'locales' => $this->projectLocales($entry['widget'], $entry['item']),
                'image' => filled($entry['item']['images'][0]['path'] ?? null) ? Storage::disk('public')->url($entry['item']['images'][0]['path']) : null,
            ])->values();

        return ['pages' => $pageEntries, 'projects' => $projects];
    }

    /**
     * The website origin from Settings → SEO, or null. Only an http(s) origin
     * without credentials, path or query is accepted.
     */
    public function siteUrl(?int $channelId = null): ?string
    {
        return self::origin($this->settings->get('seo.site_url', null, $channelId));
    }

    /**
     * What the admin search preview needs (site name, separator, template, URL base).
     * Editors render even where settings are unavailable; the preview then uses neutral values.
     *
     * @return array{site: string, separator: string, template: string, base: string, locale: string}
     */
    public function previewDefaults(): array
    {
        try {
            $site = $this->settings->get('seo.site_name') ?: $this->settings->get('general.app_name', config('app.name'));
            $separator = $this->settings->get('seo.title_separator') ?: '|';
            $template = $this->settings->get('seo.title_template') ?: '{title} {separator} {site}';
            $base = $this->siteUrl();
        } catch (\Illuminate\Database\QueryException) {
            [$site, $separator, $template, $base] = [config('app.name'), '|', '{title} {separator} {site}', null];
        }

        return ['site' => (string) $site, 'separator' => (string) $separator, 'template' => (string) $template,
            'base' => $base ?: 'https://www.example.com', 'locale' => $this->defaultLocale()];
    }

    public static function origin(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $parts = parse_url(trim($value));
        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || ! in_array($parts['path'] ?? '', ['', '/'], true)) {
            return null;
        }

        return strtolower($parts['scheme']).'://'.strtolower($parts['host']).(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    /** A canonical override that names another page keeps this one out of the sitemap. */
    private function canonicalizesElsewhere(Page $page, string $path): bool
    {
        $canonical = trim((string) $page->canonical_url);
        if ($canonical === '') {
            return false;
        }
        if (! str_starts_with($canonical, '/')) {
            return true;
        }
        $target = trim((string) parse_url($canonical, PHP_URL_PATH), '/');
        $segments = explode('/', $target);
        if (in_array($segments[0] ?? '', $this->locales(), true)) {
            array_shift($segments);
        }
        $target = implode('/', $segments);

        return ($target === '' ? 'home' : $target) !== $path;
    }

    /** The public path, or '' when an ancestor is not published here (the page is unreachable) or the tree loops. */
    private static function pathFor(Page $page, Collection $byId): string
    {
        $segments = [];
        $visited = [];
        for ($cursor = $page; $cursor && ! isset($visited[$cursor->id]); $cursor = $byId->get($cursor->parent_id)) {
            $visited[$cursor->id] = true;
            if ($cursor->slug) {
                $segments[] = $cursor->slug;
            }
            if (! $cursor->parent_id) {
                return implode('/', array_reverse($segments));
            }
        }

        return '';
    }

    private static function flag(mixed $value): bool
    {
        return $value === null || filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private static function atom(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toAtomString();
        }
        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->toAtomString();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function ordered(array $codes): array
    {
        return array_values(array_intersect($this->locales(), array_unique($codes)));
    }
}
