<?php

namespace App\View\Composers;

use App\Services\SettingService;
use App\Support\AdminNavigation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Supplies the admin shell (sidebar, topbar, <head>) with navigation, the active
 * breadcrumb trail and the branding configured under Settings → General
 * (app name, logo, favicon) of the current channel.
 */
class AdminLayoutComposer
{
    public function __construct(private SettingService $settings)
    {
    }

    public function compose(View $view): void
    {
        $view->with('adminBrand', $this->remember('admin.brand', fn () => $this->brand()));

        $nav = $this->remember('admin.nav', fn () => AdminNavigation::resolve());
        $view->with('adminNav', $nav);
        $view->with('adminTrail', $this->remember('admin.trail', fn () => $this->trail($nav)));
    }

    /** Several shell views share these values; resolve each once per request. */
    private function remember(string $key, callable $resolve): mixed
    {
        $attributes = request()->attributes;
        if (!$attributes->has($key)) {
            $attributes->set($key, $resolve());
        }

        return $attributes->get($key);
    }

    private function brand(): array
    {
        try {
            $name = trim((string) $this->settings->get('general.app_name'));
            $logo = $this->fileUrl($this->settings->get('general.logo'));
            $favicon = $this->fileUrl($this->settings->get('general.favicon'));
        } catch (QueryException $e) {
            // Settings unavailable (e.g. not migrated yet): the shell still renders with defaults.
            report($e);
            $name = '';
            $logo = $favicon = null;
        }

        $name = $name ?: config('app.name', 'Admin');

        return [
            'name' => $name,
            'initial' => mb_strtoupper(mb_substr($name, 0, 1)),
            'logo' => $logo,
            'favicon' => $favicon,
        ];
    }

    /** Labels of the active group and item, e.g. ["Meetings", "Bookings"]. */
    private function trail(array $nav): array
    {
        foreach ($nav as $section) {
            foreach ($section['items'] as $item) {
                if (empty($item['active'])) {
                    continue;
                }
                if (empty($item['children'])) {
                    return [$item['label']];
                }
                $child = collect($item['children'])->firstWhere('active', true);

                return array_values(array_filter([$item['label'], $child['label'] ?? null]));
            }
        }

        return [];
    }

    private function fileUrl(mixed $path): ?string
    {
        if (!is_string($path) || $path === '' || !Storage::disk('public')->exists($path)) {
            return null;
        }

        // The file name changes on every upload, so the URL never serves a stale image.
        return asset('storage/'.$path);
    }
}
