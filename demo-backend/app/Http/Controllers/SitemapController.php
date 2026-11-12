<?php

namespace App\Http\Controllers;

use App\Services\Seo\SeoService;

class SitemapController extends Controller
{
    /**
     * /sitemap.xml and /sitemaps/pages.xml on the CMS host. The public website
     * (Next.js) owns the sitemap: its URLs carry the language prefix and the
     * website origin, which this host cannot know. Search engines are sent
     * there once Settings → SEO has the production website URL, otherwise
     * there is nothing truthful to list.
     */
    public function index(SeoService $seo)
    {
        $site = $seo->siteUrl($this->channelId());
        abort_unless($site, 404);

        return redirect()->away($site.'/sitemap.xml', 301);
    }

    public function pages(SeoService $seo)
    {
        return $this->index($seo);
    }

    /**
     * GET /api/v1/sitemap: public paths for the frontend's sitemap.xml. Pages
     * use the same CMS paths as /api/v1/pages; projects are the published
     * items of active projects widgets placed on a published page. Noindex
     * entries, pages canonicalized elsewhere and duplicate project slugs are
     * left out; `locales` lists the languages with their own content.
     */
    public function api(SeoService $seo)
    {
        return response()->json(['data' => $seo->sitemap($this->channelId())]);
    }

    private function channelId(): int
    {
        $channelId = app('currentChannel')?->id;
        abort_if(! $channelId, 400, 'Channel not resolved');

        return (int) $channelId;
    }
}
