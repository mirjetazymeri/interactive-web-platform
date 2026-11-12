<?php

namespace App\Http\Controllers\Admin\Seo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seo\UpdateSeoSettingsRequest;
use App\Models\Locale;
use App\Services\Seo\SeoService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Storage;

/**
 * Admin → SEO & Tracking → SEO Settings: site-wide search and sharing defaults
 * (stored as channel settings under seo.*, served by GET /api/v1/settings).
 */
class SeoSettingsController extends Controller
{
    public function edit(SettingService $settings, SeoService $seo)
    {
        $channelId = (int) auth()->user()->current_channel_id;
        $get = fn (string $key, $default = null) => $settings->get($key, $default, $channelId);

        return view('admin.seo.settings', [
            'get' => $get,
            'localized' => (array) ($get('seo.localized') ?: []),
            'locales' => $this->locales(),
            'siteUrl' => $seo->siteUrl($channelId),
            'separators' => UpdateSeoSettingsRequest::SEPARATORS,
        ]);
    }

    public function update(UpdateSeoSettingsRequest $request, SettingService $settings, SeoService $seo)
    {
        $channelId = (int) $request->user()->current_channel_id;
        $data = collect($request->validated('seo'))->mapWithKeys(fn ($value, $key) => ["seo.$key" => $value])->all();
        $data['seo.allow_indexing'] = $request->boolean('seo.allow_indexing');

        // Per-language defaults for the non-default languages; empty values fall back to the defaults above.
        $default = $seo->defaultLocale();
        $data['seo.localized'] = collect($request->validated('localized') ?? [])
            ->except($default)
            ->map(fn (array $fields) => array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : $value, $fields), 'filled'))
            ->filter()
            ->all();

        $old = $settings->get('seo.og_image', null, $channelId);
        if ($request->hasFile('og_image_file')) {
            $data['seo.og_image'] = $request->file('og_image_file')->store('settings', 'public');
        } elseif ($request->boolean('og_image_remove')) {
            $data['seo.og_image'] = null;
        }
        if ($old && array_key_exists('seo.og_image', $data) && $data['seo.og_image'] !== $old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $settings->setMany($data, $channelId);

        return redirect()->route('admin.seo.settings.edit')->with('success', __('seo.settings.saved'));
    }

    /** @return \Illuminate\Support\Collection<int, Locale> */
    private function locales()
    {
        return Locale::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('code')->get();
    }
}
