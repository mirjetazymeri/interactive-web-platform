<?php

namespace App\Http\Requests\Admin\Seo;

use App\Rules\PlainText;
use App\Rules\SeoUrl;
use App\Services\Seo\SeoService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTrackingSettingsRequest extends FormRequest
{
    /** Public ID formats; the website applies the same patterns before loading anything. */
    public const PATTERNS = [
        'gtm_id' => '/^GTM-[A-Z0-9]{4,10}$/',
        'ga4_measurement_id' => '/^G-[A-Z0-9]{4,15}$/',
        'meta_pixel_id' => '/^\d{10,20}$/',
    ];

    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->channels()->where('channel_id', $user->current_channel_id)->exists();
    }

    protected function prepareForValidation(): void
    {
        $tracking = (array) $this->input('tracking', []);
        foreach (['gtm_id', 'ga4_measurement_id'] as $key) {
            $tracking[$key] = filled($tracking[$key] ?? null) ? strtoupper(trim($tracking[$key])) : null;
        }
        $tracking['meta_pixel_id'] = filled($tracking['meta_pixel_id'] ?? null) ? trim($tracking['meta_pixel_id']) : null;
        $this->merge(['tracking' => $tracking]);
    }

    public function rules(): array
    {
        $locales = app(SeoService::class)->locales();

        return [
            'gdpr' => ['required', 'array'],
            'gdpr.analytics_tracking_enabled' => ['required', 'boolean'],
            'gdpr.analytics_require_consent' => ['required', 'boolean'],
            'gdpr.cookie_banner_enabled' => ['required', 'boolean'],
            'gdpr.show_reject_button' => ['required', 'boolean'],
            'gdpr.consent_version' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]{1,20}$/'],
            // At most 13 months, the common European guidance for consent validity.
            'gdpr.consent_expiry_days' => ['required', 'integer', 'between:1,395'],
            'gdpr.privacy_policy_url' => ['nullable', 'string', 'max:2048', new SeoUrl],
            'gdpr.cookie_policy_url' => ['nullable', 'string', 'max:2048', new SeoUrl],
            'tracking' => ['required', 'array'],
            'tracking.gtm_enabled' => ['required', 'boolean'],
            'tracking.gtm_id' => ['nullable', 'required_if_accepted:tracking.gtm_enabled', 'regex:'.self::PATTERNS['gtm_id']],
            'tracking.gtm_manages_tags' => ['required', 'boolean'],
            'tracking.ga4_enabled' => ['required', 'boolean'],
            'tracking.ga4_measurement_id' => ['nullable', 'required_if_accepted:tracking.ga4_enabled', 'regex:'.self::PATTERNS['ga4_measurement_id']],
            'tracking.meta_pixel_enabled' => ['required', 'boolean'],
            'tracking.meta_pixel_id' => ['nullable', 'required_if_accepted:tracking.meta_pixel_enabled', 'regex:'.self::PATTERNS['meta_pixel_id']],
            'consent_copy' => ['nullable', 'array'],
            'consent_copy.*' => ['array:title,text'],
            'consent_copy.*.title' => ['nullable', 'string', 'max:120', new PlainText],
            'consent_copy.*.text' => ['nullable', 'string', 'max:600', new PlainText],
        ] + collect(array_keys((array) $this->input('consent_copy', [])))
            ->reject(fn ($code) => in_array($code, $locales, true))
            ->mapWithKeys(fn ($code) => ["consent_copy.$code" => ['prohibited']])->all();
    }

    public function messages(): array
    {
        return [
            'tracking.gtm_id.regex' => __('seo.validation.gtm_id'),
            'tracking.ga4_measurement_id.regex' => __('seo.validation.ga4_id'),
            'tracking.meta_pixel_id.regex' => __('seo.validation.pixel_id'),
            'tracking.*.required_if_accepted' => __('seo.validation.id_required'),
        ];
    }
}
