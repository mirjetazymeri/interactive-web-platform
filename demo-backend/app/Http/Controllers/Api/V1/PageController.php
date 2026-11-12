<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PageResource;
use App\Services\Cms\PageContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller
{
    public function __construct(private readonly PageContentService $pages) {}

    public function show(Request $req, string $slugOrPath)
    {
        $lc        = app()->getLocale();
        $channel   = app('currentChannel');
        $channelId = $channel?->id;

        $device  = $req->query('device', 'desktop');
        $slot    = $req->query('slot');
        $include = filter_var($req->query('include_widgets', '1'), FILTER_VALIDATE_BOOLEAN);
        $groupId = $req->user('sanctum')?->user_group_id;

        $page = $this->pages->resolveByPath($slugOrPath, $channelId);

        $placements = $this->pages->placements($page);
        $revision = $this->pages->placementRevision($placements);

        // The page's own edits (SEO fields, translations) refresh the payload too, not only its placements.
        $cacheKey = sprintf(
            'page:placements-v4:%s:%s:%s:%s:%s:%s:%s:%s:%s',
            $page->id,
            hash('sha256', $page->updated_at?->toISOString().$page->translations->max('updated_at')),
            $channelId,
            $lc,
            (string) $groupId,
            $device,
            $slot ?: '*',
            $revision,
            (int) $include
        );

        $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use (
            $page,
            $include,
            $slot,
            $lc,
            $device,
            $channelId,
            $placements
        ) {
            $page = $this->pages->applyLocaleAndChannel($page, (int) $channelId, $lc);

            $base = (new PageResource(
                $page->load(['translations', 'parent', 'children.translations'])
            ))->toArray(request());

            if (! $include) {
                return $base;
            }

            $base['widgets'] = $this->pages->widgetsBySlot($page, (int) $channelId, $lc, $device, $slot, $placements);

            return $base;
        });

        return response()->json(['data' => $payload]);
    }
}
