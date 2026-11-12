<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Cms\PageContentService;
use App\Services\Seo\SeoService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * GET /api/v1/seo/{entity}/{slug}: resolved search metadata for one public
 * page, for clients that do not render pages themselves. The website builds
 * its own metadata from the page payload; both use the same fields.
 */
class SeoController extends Controller
{
    public function show(Request $request, string $entity, string $slug, SettingService $settings, SeoService $seo, PageContentService $pages)
    {
        $locale = app()->getLocale();
        $channelId = app('currentChannel')?->id;
        abort_if(! $channelId, 400, 'Channel not resolved');
        // The product and category entities belonged to a commerce template this CMS does not ship.
        abort_unless(in_array(strtolower($entity), ['page', 'pages'], true), 404, 'Unsupported SEO entity');

        $page = $pages->applyLocaleAndChannel($pages->resolveByPath($slug, (int) $channelId), (int) $channelId, $locale);
        $meta = $seo->pageMeta($page, $locale);

        $title = $meta['title'] ?: ($page->title ?: (string) $settings->get('seo.meta_title', '', $channelId));
        $description = $meta['description'] ?: (string) $settings->get('seo.meta_description', '', $channelId);
        $image = $meta['og_image'] ?: $this->fileUrl($settings->get('seo.og_image', null, $channelId));
        $canonical = $this->canonical($seo, $page, $meta['canonical'], $locale, (int) $channelId);
        $robots = ($meta['robots']['index'] ? 'index' : 'noindex').','.($meta['robots']['follow'] ? 'follow' : 'nofollow');

        return response()->json([
            'data' => [
                'entity' => 'page',
                'slug' => (string) $page->path,
                'locale' => $locale,
                'channel_id' => (int) $channelId,
                'locales' => $seo->pageLocales($page),
                'meta' => [
                    'title' => $title,
                    'description' => $description,
                    'keywords' => null,
                    'canonical' => $canonical,
                    'robots' => $robots,
                ],
                'open_graph' => [
                    'type' => 'website',
                    'title' => $meta['og_title'] ?: $title,
                    'description' => $meta['og_description'] ?: $description,
                    'url' => $canonical,
                    'image' => $image,
                ],
                'twitter' => [
                    'card' => $image ? 'summary_large_image' : 'summary',
                    'title' => $meta['og_title'] ?: $title,
                    'description' => $meta['og_description'] ?: $description,
                    'image' => $image,
                ],
            ],
        ]);
    }

    /** Absolute canonical on the website origin, or null while Settings → SEO has no production URL. */
    private function canonical(SeoService $seo, Page $page, ?string $override, string $locale, int $channelId): ?string
    {
        if ($override && ! str_starts_with($override, '/')) {
            return $override;
        }
        $site = $seo->siteUrl($channelId);
        if (! $site) {
            return null;
        }
        if ($override) {
            return $site.'/'.rawurlencode($locale).$override;
        }
        $path = $page->path === 'home' ? '' : '/'.implode('/', array_map('rawurlencode', explode('/', $page->path)));

        return $site.'/'.rawurlencode($locale).$path;
    }

    private function fileUrl(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return preg_match('#^https?://#i', $value) ? $value : Storage::disk('public')->url($value);
    }
}
