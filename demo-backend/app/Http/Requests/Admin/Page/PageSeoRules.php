<?php

namespace App\Http\Requests\Admin\Page;

use App\Rules\PlainText;
use App\Rules\SeoUrl;
use App\Services\Seo\SeoService;
use Illuminate\Validation\Rule;

/** Search and sharing fields of the page editor (SEO tab and per-language overrides). */
class PageSeoRules
{
    public static function rules(): array
    {
        return [
            'meta_title' => ['nullable', 'string', 'max:255', new PlainText],
            'meta_description' => ['nullable', 'string', 'max:1000', new PlainText],
            'canonical_url' => ['nullable', 'string', 'max:2048', new SeoUrl],
            'og_title' => ['nullable', 'string', 'max:255', new PlainText],
            'og_description' => ['nullable', 'string', 'max:1000', new PlainText],
            // Social networks do not render SVG previews.
            'og_image_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'og_image_remove' => ['sometimes', 'boolean'],
            'robots_index' => ['sometimes', 'boolean'],
            'robots_follow' => ['sometimes', 'boolean'],
            'schema_type' => ['nullable', Rule::in(SeoService::SCHEMA_TYPES)],
            'translations.*.meta_title' => ['nullable', 'string', 'max:255', new PlainText],
            'translations.*.meta_description' => ['nullable', 'string', 'max:1000', new PlainText],
            'translations.*.og_title' => ['nullable', 'string', 'max:255', new PlainText],
            'translations.*.og_description' => ['nullable', 'string', 'max:1000', new PlainText],
        ];
    }
}
