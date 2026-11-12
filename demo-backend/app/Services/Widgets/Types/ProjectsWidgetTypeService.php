<?php

namespace App\Services\Widgets\Types;

use App\Models\PageWidget;
use App\Models\Widget;
use App\Services\Seo\SeoService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProjectsWidgetTypeService implements WidgetTypeConfigService
{
    /** Translatable text; seo_title/seo_description override the search snippet (empty uses name/description). */
    public const TEXT_FIELDS = ['name', 'project_type', 'category', 'description', 'work_delivered', 'goal', 'seo_title', 'seo_description'];

    /** mobile_video is an optional portrait cut shown on phones instead of video. */
    public const VIDEO_FIELDS = ['video', 'mobile_video'];

    public static function variant(mixed $value): mixed
    {
        return match ($value) {
            'horizontal' => 'horizontal_scroll',
            'vertical' => 'vertical_scroll',
            default => $value,
        };
    }

    /** Streams from the temp path: realpath() fails for uploads in C:\Windows\Temp under Laragon fcgid. */
    private static function store(UploadedFile $file, string $directory): string|false
    {
        return Storage::disk('public')->putFileAs($directory, $file->getPathname(), $file->hashName());
    }

    public function type(): string
    {
        return 'projects';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['variant'] = self::variant($incoming['variant']);
        if (! array_key_exists('projects', $incoming)) {
            $config['projects'] = $existing['projects'] ?? [];

            return $config;
        }
        $config['projects'] = [];
        $previous = collect($existing['projects'] ?? [])->keyBy('id');
        foreach ($incoming['projects'] ?? [] as $index => $project) {
            $old = $previous->get($project['id'], []);
            $item = ['id' => $project['id'], 'slug' => $project['slug'],
                'created_at' => $old['created_at'] ?? now()->toISOString(), 'updated_at' => now()->toISOString()];
            foreach (array_merge(self::TEXT_FIELDS, ['client_name', 'website_url']) as $field) {
                $item[$field] = trim((string) (array_key_exists($field, $project) ? $project[$field] : ($old[$field] ?? '')));
            }
            $item['technologies'] = array_values(array_filter(array_map(fn ($value) => trim((string) $value), $project['technologies'] ?? $old['technologies'] ?? [])));
            $year = array_key_exists('completion_year', $project) ? $project['completion_year'] : ($old['completion_year'] ?? null);
            $item['completion_year'] = filled($year) ? (int) $year : null;
            $item['status'] = $project['status'];
            $item['position'] = (int) ($project['position'] ?? $old['position'] ?? $index);
            $item['featured'] = (bool) ($project['featured'] ?? $old['featured'] ?? false);
            $item['robots_index'] = (bool) ($project['robots_index'] ?? $old['robots_index'] ?? true);
            $item['images'] = [];
            $oldImages = collect($old['images'] ?? [])->keyBy('id');
            $submittedImages = array_key_exists('images', $project) ? ($project['images'] ?? []) : ($old['images'] ?? []);
            foreach ($submittedImages as $imageIndex => $image) {
                $key = "config.projects.$index.images.$imageIndex";
                if ($request->boolean("$key.remove")) {
                    continue;
                }
                $path = $oldImages->get($image['id'])['path'] ?? null;
                if ($request->hasFile("$key.file")) {
                    $path = self::store($request->file("$key.file"), 'widgets/projects');
                    if ($path === false) {
                        throw new \RuntimeException('Unable to store project image.');
                    }
                }
                if ($path) {
                    $alt = array_key_exists('alt', $image) ? $image['alt'] : ($oldImages->get($image['id'])['alt'] ?? '');
                    $item['images'][] = ['id' => $image['id'], 'path' => $path, 'alt' => trim((string) $alt)];
                }
            }
            foreach (self::VIDEO_FIELDS as $field) {
                $key = "config.projects.$index.$field";
                $item[$field] = $request->boolean("$key.remove") ? null : ($old[$field] ?? null);
                if ($request->hasFile("$key.file")) {
                    $file = $request->file("$key.file");
                    $path = self::store($file, 'widgets/projects/videos');
                    if ($path === false) {
                        throw new \RuntimeException('Unable to store project video.');
                    }
                    $item[$field] = ['path' => $path, 'mime' => $file->getMimeType()];
                }
            }
            if (count($item['images']) > 3) {
                throw ValidationException::withMessages(["config.projects.$index.images" => __('projects.max_images')]);
            }
            // Saving the widget rewrites every item; only a changed item gets a new modification time (sitemap lastmod).
            if ($old && filled($old['updated_at'] ?? null) && self::fingerprint($item) === self::fingerprint($old)) {
                $item['updated_at'] = $old['updated_at'];
            }
            $config['projects'][] = $item;
        }

        return $config;
    }

    /** Item content without its timestamps, with keys ordered so that equal content compares equal. */
    private static function fingerprint(array $item): string
    {
        $normalize = function ($value) use (&$normalize) {
            if (! is_array($value)) {
                return $value;
            }
            if (! array_is_list($value)) {
                ksort($value);
            }

            return array_map($normalize, $value);
        };
        $item += ['robots_index' => true];

        return json_encode($normalize(array_diff_key($item, ['created_at' => true, 'updated_at' => true, 'position' => true])));
    }

    public function extractTranslationConfig(array $translation): array
    {
        $out = [];
        foreach ($translation['projects'] ?? [] as $id => $project) {
            $text = $this->textOnly($project);
            foreach ($project['images'] ?? [] as $imageId => $image) {
                if (filled($image['alt'] ?? null)) {
                    $text['images'][$imageId] = ['alt' => trim($image['alt'])];
                }
            }
            if ($text) {
                $out['projects'][$id] = $text;
            }
        }

        return $out;
    }

    /** Omitted fields/locales survive; explicit blanks clear just that override. */
    public function mergeTranslation(array $existing, array $incoming): array
    {
        $mergeText = function (array $saved, array $submitted, array $fields): array {
            foreach ($fields as $field) {
                if (! array_key_exists($field, $submitted)) {
                    continue;
                }
                $value = trim((string) ($submitted[$field] ?? ''));
                if ($value === '') {
                    unset($saved[$field]);
                } else {
                    $saved[$field] = $value;
                }
            }

            return $saved;
        };
        $existing = $mergeText($existing, $incoming, ['section_label']);
        foreach ($incoming['projects'] ?? [] as $id => $project) {
            $saved = $mergeText($existing['projects'][$id] ?? [], $project, self::TEXT_FIELDS);
            foreach ($project['images'] ?? [] as $imageId => $image) {
                $alt = $mergeText($saved['images'][$imageId] ?? [], $image, ['alt']);
                if ($alt) {
                    $saved['images'][$imageId] = $alt;
                } else {
                    unset($saved['images'][$imageId]);
                }
            }
            if (empty($saved['images'])) {
                unset($saved['images']);
            }
            if ($saved) {
                $existing['projects'][$id] = $saved;
            } else {
                unset($existing['projects'][$id]);
            }
        }
        if (empty($existing['projects'])) {
            unset($existing['projects']);
        }

        return $existing;
    }

    private function textOnly(array $input): array
    {
        return array_filter(array_intersect_key($input, array_flip(self::TEXT_FIELDS)), fn ($value) => filled($value));
    }

    public function localize(array $base, array $translation): array
    {
        $base['variant'] = self::variant($base['variant'] ?? 'horizontal_scroll');
        $text = $this->extractTranslationConfig($translation);
        if (filled($translation['section_label'] ?? null)) {
            $base['section_label'] = $translation['section_label'];
        }
        $base['projects'] = array_map(function ($project) use ($text) {
            $override = $text['projects'][$project['id']] ?? [];
            $project = array_replace($project, $this->textOnly($override));
            $project['images'] = array_map(fn ($image) => array_replace($image, $override['images'][$image['id']] ?? []), $project['images'] ?? []);

            return $project;
        }, $base['projects'] ?? []);

        return $base;
    }

    public function representation(Widget $widget, array $item, string $locale, bool $detail = false): array
    {
        $images = [];
        foreach ($item['images'] ?? [] as $position => $image) {
            $images[] = ['id' => $image['id'], 'url' => Storage::disk('public')->url($image['path']), 'alt' => $image['alt'] ?? '', 'position' => $position];
        }
        $out = array_intersect_key($item, array_flip(['id', 'name', 'slug', 'category', 'client_name', 'technologies', 'featured', 'position', 'website_url']));
        $out['widget_id'] = $widget->id;
        $parameters = ['widgetId' => $widget->id, 'slug' => $item['slug'], 'locale' => $locale];
        if (app()->bound('currentChannel') && app('currentChannel')?->code) {
            $parameters['channel'] = app('currentChannel')->code;
        }
        if (is_string(request()->query('device'))) {
            $parameters['device'] = request()->query('device');
        }
        $out['url'] = route('storefront.projects.show', $parameters);
        $out['detail_url'] = route('api.widgets.projects.show', ['id' => $widget->id, 'slug' => $item['slug'], 'locale' => $locale, ...array_intersect_key($parameters, ['device' => true, 'channel' => true])]);
        $out['main_image'] = $images[0] ?? null;
        foreach (self::VIDEO_FIELDS as $field) {
            $out[$field] = filled($item[$field]['path'] ?? null)
                ? ['url' => Storage::disk('public')->url($item[$field]['path']), 'mime' => $item[$field]['mime'] ?? null]
                : null;
        }
        $out['images'] = $images;
        if ($detail) {
            $out = array_merge($out, array_intersect_key($item, array_flip(['project_type', 'completion_year', 'description', 'work_delivered', 'goal', 'website_url'])), ['images' => $images]);
            $out['meta'] = [
                'title' => filled($item['seo_title'] ?? null) ? $item['seo_title'] : null,
                'description' => filled($item['seo_description'] ?? null) ? $item['seo_description'] : null,
                'robots' => ['index' => SeoService::indexable($item), 'follow' => true],
            ];
            $out['created_at'] = $item['created_at'] ?? null;
            $out['updated_at'] = $item['updated_at'] ?? null;
        }

        return $out;
    }

    public function publicItem(Request $request, int $widgetId, string $slug): array
    {
        $channelId = app('currentChannel')?->id;
        $widget = Widget::active()->visibleInChannel($channelId)->where('type', 'projects')->with('translations')->findOrFail($widgetId);
        $placements = PageWidget::active()->where('widget_id', $widget->id)
            ->whereHas('page', fn ($q) => $q->published()->forChannel($channelId))->get();
        abort_unless($placements->contains(function ($placement) use ($request) {
            $devices = $placement->visibility['devices'] ?? null;

            return ! is_array($devices) || in_array($request->query('device', 'desktop'), $devices, true);
        }), 404);
        $item = collect($widget->localizedConfig()['projects'] ?? [])->where('status', 'published')->firstWhere('slug', $slug);
        abort_unless($item, 404);

        $seo = app(SeoService::class);
        $base = collect($widget->config['projects'] ?? [])->firstWhere('id', $item['id']) ?? $item;

        return $this->representation($widget, $item, app()->getLocale(), true) + [
            // The same slug in several widgets is one project: the others point their canonical URL here.
            'canonical_widget_id' => $channelId ? ($seo->canonicalProjectWidgetId((int) $channelId, $slug) ?? $widget->id) : $widget->id,
            'locales' => $seo->projectLocales($widget, $base),
        ];
    }
}
