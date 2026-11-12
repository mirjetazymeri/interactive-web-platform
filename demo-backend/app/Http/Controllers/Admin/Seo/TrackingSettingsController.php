<?php

namespace App\Http\Controllers\Admin\Seo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\UpdateTrackingSettingsRequest;
use App\Models\Locale;
use App\Services\SettingService;

/**
 * Admin → SEO & Tracking → Analytics & Tracking: provider IDs, their switches
 * and the consent rules the website applies before loading any of them.
 */
class TrackingSettingsController extends Controller
{
    public const BOOLEANS = [
        'gdpr.analytics_tracking_enabled', 'gdpr.analytics_require_consent', 'gdpr.cookie_banner_enabled', 'gdpr.show_reject_button',
        'tracking.gtm_enabled', 'tracking.gtm_manages_tags', 'tracking.ga4_enabled', 'tracking.meta_pixel_enabled',
    ];

    public function edit(SettingService $settings)
    {
        $channelId = (int) auth()->user()->current_channel_id;
        $get = fn (string $key, $default = null) => $settings->get($key, $default, $channelId);

        return view('admin.seo.tracking', [
            'get' => $get,
            'copy' => (array) ($get('tracking.consent_copy') ?: []),
            'locales' => Locale::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('code')->get(),
            'providers' => $this->providers($get),
        ]);
    }

    public function update(UpdateTrackingSettingsRequest $request, SettingService $settings)
    {
        $channelId = (int) $request->user()->current_channel_id;
        $data = [];
        foreach (['gdpr', 'tracking'] as $group) {
            foreach ($request->validated($group) as $key => $value) {
                $data["$group.$key"] = $value;
            }
        }
        foreach (self::BOOLEANS as $key) {
            $data[$key] = $request->boolean($key);
        }
        $data['gdpr.consent_expiry_days'] = (int) $data['gdpr.consent_expiry_days'];
        $data['tracking.consent_copy'] = collect($request->validated('consent_copy') ?? [])
            ->map(fn (array $fields) => array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : $value, $fields), 'filled'))
            ->filter()
            ->all();

        $settings->setMany($data, $channelId);

        return redirect()->route('admin.seo.tracking.edit')->with('success', __('seo.tracking.saved'));
    }

    /** Status of each provider as the website will see it (it still needs SITE_ENV=production and consent). */
    private function providers(callable $get): array
    {
        $master = (bool) $get('gdpr.analytics_tracking_enabled', false);
        $gtm = (bool) $get('tracking.gtm_enabled', false) && preg_match(UpdateTrackingSettingsRequest::PATTERNS['gtm_id'], (string) $get('tracking.gtm_id'));
        $managed = $gtm && (bool) $get('tracking.gtm_manages_tags', true);
        $status = function (string $switch, string $key) use ($get, $master, $managed) {
            if (! $get("tracking.$switch", false)) {
                return 'disabled';
            }
            if (! preg_match(UpdateTrackingSettingsRequest::PATTERNS[$key], (string) $get("tracking.$key"))) {
                return 'invalid';
            }
            if ($key !== 'gtm_id' && $managed) {
                return 'managed';
            }

            return $master ? 'ready' : 'paused';
        };

        return [
            'gtm' => $status('gtm_enabled', 'gtm_id'),
            'ga4' => $status('ga4_enabled', 'ga4_measurement_id'),
            'meta_pixel' => $status('meta_pixel_enabled', 'meta_pixel_id'),
        ];
    }
}
