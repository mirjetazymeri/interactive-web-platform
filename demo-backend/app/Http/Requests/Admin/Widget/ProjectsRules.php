<?php

namespace App\Http\Requests\Admin\Widget;

use App\Models\Widget;
use App\Rules\HeroVideo;
use App\Rules\PlainText;
use App\Services\Widgets\Types\ProjectsWidgetTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectsRules
{
    public static function prepare(Request $request): void
    {
        if ($request->input('type') !== 'projects') {
            return;
        }
        $config = $request->all()['config'] ?? [];
        if (! is_array($config) || ! is_array($config['projects'] ?? [])) {
            return;
        }
        if (array_key_exists('variant', $config)) {
            $config['variant'] = ProjectsWidgetTypeService::variant($config['variant']);
        }
        if (! array_key_exists('projects', $config)) {
            $request->merge(['config' => $config]);

            return;
        }
        $config['projects'] ??= [];
        foreach ($config['projects'] as &$item) {
            if (! is_array($item)) {
                continue;
            }
            $item['id'] = $item['id'] ?? 'prj-'.Str::uuid();
            $item['slug'] = filled($item['slug'] ?? null) ? $item['slug'] : Str::slug((string) ($item['name'] ?? ''));
            if (is_array($item['images'] ?? null)) {
                foreach ($item['images'] as &$image) {
                    if (is_array($image)) {
                        $image['id'] = $image['id'] ?? 'img-'.Str::uuid();
                    }
                }
            }
            unset($image);
        }
        unset($item);
        $request->merge(['config' => $config]);
    }

    public static function rules(): array
    {
        $rules = [
            'config.variant' => ['required', 'in:horizontal_scroll,vertical_scroll'],
            'config.projects' => ['nullable', 'array', 'list', 'max:100'],
            'config.projects.*' => ['array'],
            'config.projects.*.id' => ['required', 'string', 'regex:/^[a-zA-Z][a-zA-Z0-9_-]{0,63}$/', 'distinct:strict'],
            'config.projects.*.slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'distinct:strict'],
            'config.projects.*.status' => ['required', 'in:draft,published'],
            'config.projects.*.client_name' => ['nullable', 'string', 'max:255'],
            'config.projects.*.technologies' => ['nullable', 'array', 'max:30'],
            'config.projects.*.technologies.*' => ['nullable', 'string', 'max:100'],
            'config.projects.*.completion_year' => ['nullable', 'integer', 'between:1900,2100'],
            'config.projects.*.website_url' => ['nullable', 'url:http,https', 'max:2048'],
            'config.projects.*.position' => ['sometimes', 'integer', 'min:0'],
            'config.projects.*.featured' => ['sometimes', 'boolean'],
            'config.projects.*.robots_index' => ['sometimes', 'boolean'],
            'config.projects.*.images' => ['nullable', 'array', 'list', 'max:3'],
            'config.projects.*.images.*' => ['array'],
            'config.projects.*.images.*.id' => ['required', 'string', 'regex:/^[a-zA-Z][a-zA-Z0-9_-]{0,63}$/', 'distinct:strict'],
            'config.projects.*.images.*.path' => ['prohibited'],
            'config.projects.*.images.*.file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'config.projects.*.images.*.remove' => ['sometimes', 'boolean'],
            'config.projects.*.images.*.alt' => ['nullable', 'string', 'max:255'],
            'translations.*.projects' => ['nullable', 'array', 'max:100'],
            'translations.*.projects.*' => ['array:'.implode(',', [...ProjectsWidgetTypeService::TEXT_FIELDS, 'images'])],
            'translations.*.projects.*.images' => ['nullable', 'array', 'max:3'],
            'translations.*.projects.*.images.*' => ['array:alt'],
            'translations.*.projects.*.images.*.alt' => ['nullable', 'string', 'max:255'],
        ];
        foreach (ProjectsWidgetTypeService::TEXT_FIELDS as $field) {
            $length = match ($field) {
                'name', 'project_type', 'category', 'seo_title' => 255,
                'seo_description' => 1000,
                default => 50000,
            };
            // Search snippets are plain text (they end up in <title>, meta tags and JSON-LD).
            $plain = str_starts_with($field, 'seo_') ? [new PlainText] : [];
            $rules["config.projects.*.$field"] = [$field === 'name' ? 'required' : 'nullable', 'string', "max:$length", ...$plain];
            $rules["translations.*.projects.*.$field"] = ['nullable', 'string', "max:$length", ...$plain];
        }
        foreach (ProjectsWidgetTypeService::VIDEO_FIELDS as $field) {
            $rules["config.projects.*.$field"] = ['nullable', 'array:file,remove'];
            $rules["config.projects.*.$field.file"] = ['bail', 'nullable', 'file', new HeroVideo('project video')];
            $rules["config.projects.*.$field.remove"] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    public static function validateImages(Request $request, $validator): void
    {
        if ($request->input('type') !== 'projects' || $validator->errors()->any()) {
            return;
        }
        foreach ($request->all()['config']['projects'] ?? [] as $index => $item) {
            foreach (ProjectsWidgetTypeService::VIDEO_FIELDS as $field) {
                if ($request->hasFile("config.projects.$index.$field.file") && $request->boolean("config.projects.$index.$field.remove")) {
                    $validator->errors()->add("config.projects.$index.$field.file", __('projects.video_conflict'));
                }
            }
            // Images are optional, also when published: the website shows a neutral frame until mockups are uploaded.
            foreach ($item['images'] ?? [] as $imageIndex => $image) {
                $key = "config.projects.$index.images.$imageIndex";
                if ($request->hasFile("$key.file") && $request->boolean("$key.remove")) {
                    $validator->errors()->add("$key.file", __('projects.image_conflict'));
                }
            }
        }
    }
}
