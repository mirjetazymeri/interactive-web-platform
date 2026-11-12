<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Widget, Page, PageWidget};
use App\Services\Cms\WidgetRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WidgetController extends Controller
{
    public function __construct(private readonly WidgetRenderer $renderer) {}

    public function project(Request $req, int $id, string $slug, \App\Services\Widgets\Types\ProjectsWidgetTypeService $projects)
    {
        return response()->json(['data' => $projects->publicItem($req, $id, $slug)]);
    }

    // ------------------------------------------------------------------
    // GET /api/v1/widgets/{id}
    // ------------------------------------------------------------------

    public function show(Request $req, int $id)
    {
        $channelId = app('currentChannel')?->id;
        $locale = app()->getLocale();

        $widget = Widget::active()->with(['channels', 'translations'])->findOrFail($id);

        // channel guard
        if ($widget->channels->isNotEmpty()
            && ! $widget->channels->pluck('id')->contains($channelId)) {
            abort(404);
        }

        $version = hash('sha256', $widget->toJson());
        $key     = "widget:projects-v2:{$id}:{$version}:{$locale}:{$channelId}:".hash('sha256', json_encode($req->query('device')));

        $payload = Cache::remember($key, now()->addMinutes(5), function () use ($widget, $locale) {
            $resolved = $this->renderer->resolve($widget, ['locale' => $locale]);

            return [
                'id'         => $widget->id,
                'type'       => $widget->type,
                'widget_key' => WidgetRenderer::widgetKey($widget),
                'title'      => $widget->localizedTitle($locale),
                'config'     => $resolved,
            ];
        });

        return response()->json(['data' => $payload]);
    }

    // ------------------------------------------------------------------
    // GET /api/v1/widgets?page=home&slot=hero
    // ------------------------------------------------------------------

    public function byPlacement(Request $req)
    {
        $device = $req->query('device', 'desktop');
        $slug   = $req->query('page');
        $slot   = $req->query('slot');
        $locale = app()->getLocale();

        $query = PageWidget::query()
            ->whereHas('widget', fn ($q) => $q->active()->visibleInChannel(app('currentChannel')?->id))
            ->whereHas('page', fn ($q) => $q->published()->forChannel(app('currentChannel')?->id))
            ->with('widget.channels', 'widget.translations')
            ->orderBy('slot')
            ->orderBy('position');

        if ($slug) {
            $page = app(\App\Services\Cms\PageContentService::class)->resolveByPath($slug, app('currentChannel')?->id);
            $query->where('page_id', $page->id);
        }
        if ($slot) {
            $query->where('slot', $slot);
        }

        $rows = $query->get()->filter(function ($pw) use ($device) {
            if (! $pw->active) return false;
            $now = now();
            if ($pw->starts_at && $pw->starts_at->gt($now)) return false;
            if ($pw->ends_at   && $pw->ends_at->lt($now))   return false;
            $vis = $pw->visibility ?? [];
            $dev = $vis['devices'] ?? null;
            if (is_array($dev) && ! in_array($device, $dev, true)) return false;
            return true;
        })->values();

        $widgets = $rows->map(function ($pw) use ($locale) {
            $w = $pw->widget;
            $resolved = $this->renderer->resolve($w, ['locale' => $locale]);

            return [
                'placement_id' => $pw->id,
                'placement_config' => $pw->presentationConfig(),
                'id'         => $w->id,
                'type'       => $w->type,
                'widget_key' => WidgetRenderer::widgetKey($w),
                'title'      => $w->localizedTitle($locale),
                'slot'       => $pw->slot,
                'position'   => $pw->position,
                'config'     => $resolved,
            ];
        })->groupBy('slot');

        return response()->json(['data' => $widgets]);
    }

    // ------------------------------------------------------------------
    // GET /api/v1/widgets/footer
    // ------------------------------------------------------------------

    /** The footer call to action shown on every page: the first active one, or null. */
    public function footer()
    {
        $locale = app()->getLocale();

        $widget = Widget::query()
            ->active()
            ->visibleInChannel(app('currentChannel')?->id)
            ->where('type', \App\Services\Widgets\Types\FooterCtaWidgetTypeService::TYPE)
            ->with('channels', 'translations')
            ->orderBy('position')
            ->orderBy('id')
            ->first();

        return response()->json(['data' => $widget ? [
            'id'     => $widget->id,
            'type'   => $widget->type,
            'title'  => $widget->localizedTitle($locale),
            'config' => $this->renderer->resolve($widget, ['locale' => $locale]),
        ] : null]);
    }

    // ------------------------------------------------------------------
    // GET /api/v1/widgets/home
    // ------------------------------------------------------------------

    public function home(Request $req)
    {
        $types = array_filter((array) $req->input('type'));
        $limit = (int) $req->query('limit', 50);
        $limit = $limit > 0 ? min($limit, 200) : 50;
        $locale = app()->getLocale();

        $widgets = Widget::query()
            ->visibleInChannel(app('currentChannel')?->id)
            ->where('status', 'active')
            ->where('hide_on_home', false)
            ->when($types, fn ($q) => $q->whereIn('type', $types))
            ->with('channels', 'translations')
            ->orderBy('position')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $payload = $widgets->map(function (Widget $w) use ($locale) {
            $resolved = $this->renderer->resolve($w, ['locale' => $locale]);

            return [
                'id'         => $w->id,
                'type'       => $w->type,
                'widget_key' => WidgetRenderer::widgetKey($w),
                'title'      => $w->localizedTitle($locale),
                'config'     => $resolved,
            ];
        })->values();

        return response()->json(['data' => $payload]);
    }
}
