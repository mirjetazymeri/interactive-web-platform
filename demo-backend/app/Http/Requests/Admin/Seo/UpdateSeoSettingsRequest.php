<?php

namespace App\Http\Requests\Admin\Seo;

use App\Rules\PlainText;
use App\Services\Seo\SeoService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSeoSettingsRequest extends FormRequest
{
    public const SEPARATORS = ['|', '—', '–', '-', '·', '/'];

    public function authorize(): bool
    {
        // Admin routes require the Admin role; settings are scoped to a channel the user belongs to.
        $user = $this->user();

        return $user && $user->channels()->where('channel_id', $user->current_channel_id)->exists();
    }

    /** Pasting the whole verification <meta> tag is common; keep only its content value. */
    protected function prepareForValidation(): void
    {
        $seo = (array) $this->input('seo', []);
        foreach (['google_site_verification', 'bing_site_verification', 'facebook_domain_verification'] as $key) {
            $value = trim((string) ($seo[$key] ?? ''));
            if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $value, $match)) {
                $value = $match[1];
            }
            $seo[$key] = $value === '' ? null : $value;
        }
        $seo['site_url'] = filled($seo['site_url'] ?? null) ? rtrim(trim($seo['site_url']), '/') : null;
        $seo['twitter_handle'] = filled($seo['twitter_handle'] ?? null) ? '@'.ltrim(trim($seo['twitter_handle']), '@') : null;
        $this->merge(['seo' => $seo]);
    }

    public function rules(): array
    {
        $text = fn (int $max) => ['nullable', 'string', "max:$max", new PlainText];
        $locales = app(SeoService::class)->locales();

        return [
            'seo' => ['required', 'array'],
            'seo.meta_title' => $text(255),
            'seo.meta_description' => $text(1000),
            'seo.site_name' => $text(100),
            'seo.title_separator' => ['required', 'in:'.implode(',', self::SEPARATORS)],
            // {title} is required; only the three placeholders and no markup.
            'seo.title_template' => ['nullable', 'string', 'max:120', 'regex:/^(?=.*\{title\})(?:[^{}<>]|\{(?:title|separator|site)\})*$/u'],
            'seo.site_url' => ['nullable', 'string', 'max:255', 'url:http,https', function (string $attribute, mixed $value, Closure $fail) {
                if ($value && SeoService::origin($value) === null) {
                    $fail(__('seo.validation.site_url'));
                }
            }],
            'seo.organization_name' => $text(255),
            'seo.organization_description' => $text(1000),
            'seo.twitter_handle' => ['nullable', 'regex:/^@[A-Za-z0-9_]{1,15}$/'],
            'seo.allow_indexing' => ['required', 'boolean'],
            'seo.google_site_verification' => ['nullable', 'regex:/^[A-Za-z0-9_\-]{10,100}$/'],
            'seo.bing_site_verification' => ['nullable', 'regex:/^[A-Za-z0-9]{16,64}$/'],
            'seo.facebook_domain_verification' => ['nullable', 'regex:/^[A-Za-z0-9]{10,64}$/'],
            'og_image_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'og_image_remove' => ['sometimes', 'boolean'],
            'localized' => ['nullable', 'array'],
            'localized.*' => ['array:meta_title,meta_description,organization_description'],
            'localized.*.meta_title' => $text(255),
            'localized.*.meta_description' => $text(1000),
            'localized.*.organization_description' => $text(1000),
        ] + collect(array_keys((array) $this->input('localized', [])))
            ->reject(fn ($code) => in_array($code, $locales, true))
            ->mapWithKeys(fn ($code) => ["localized.$code" => ['prohibited']])->all();
    }

    public function attributes(): array
    {
        return [
            'seo.meta_title' => __('seo.settings.meta_title'),
            'seo.meta_description' => __('seo.settings.meta_description'),
            'seo.title_template' => __('seo.settings.title_template'),
            'seo.site_url' => __('seo.settings.site_url'),
            'seo.twitter_handle' => __('seo.settings.twitter_handle'),
            'seo.google_site_verification' => __('seo.settings.google_verification'),
            'seo.bing_site_verification' => __('seo.settings.bing_verification'),
            'seo.facebook_domain_verification' => __('seo.settings.facebook_verification'),
        ];
    }
}
