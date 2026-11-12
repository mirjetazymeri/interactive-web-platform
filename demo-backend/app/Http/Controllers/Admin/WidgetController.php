<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Widget\StoreWidgetRequest;
use App\Http\Requests\Admin\Widget\UpdateWidgetRequest;
use App\Models\Widget;
use App\Services\Widgets\WidgetAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WidgetController extends Controller
{
    public function __construct(private readonly WidgetAdminService $widgetAdminService)
    {
    }

    public function index()
    {
        $widgets = Widget::query()
            ->manageableBy(auth()->user())
            ->with('channels')
            ->orderBy('position')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.widgets.index', compact('widgets'));
    }

    public function create()
    {
        $channels = auth()->user()->channels;
        $types = $this->widgetAdminService->getTypes();
        $layouts = $this->widgetAdminService->getLayouts();

        return view('admin.widgets.create', compact('channels', 'types', 'layouts'));
    }

    public function store(StoreWidgetRequest $req)
    {
        $data = $this->widgetAdminService->validatedPayload($req);

        $widget = null;
        $this->widgetAdminService->heroMedia->transaction(function () use ($req, $data, &$widget) {
            $widget = Widget::create([
                'type'         => $data['type'],
                'title'        => $data['title'] ?? null,
                'status'       => $data['status'],
                'position'     => (int) (Widget::max('position') ?? 0) + 1,
                'hide_on_home' => false,
                'config'       => $this->widgetAdminService->buildConfig($req, $data['type'], $data['config'] ?? [], null),
            ]);
            if (! $widget->exists) {
                throw new \RuntimeException('Unable to create widget.');
            }

            $this->widgetAdminService->syncChannels($widget, $req->input('channels'));
            $this->widgetAdminService->syncTranslations($widget, $req->input('translations', []));
        });

        if ($data['type'] === 'projects') {
            return redirect()->route('admin.widgets.edit', $widget->id)->with('success', __('projects.continue_saved'));
        }

        return redirect()
            ->route('admin.widgets.index')
            ->with('success', 'Widget created.');
    }

    public function edit(int $id)
    {
        $widget   = Widget::with(['channels', 'translations'])->manageableBy(auth()->user())->findOrFail($id);
        $channels = auth()->user()->channels;
        $types = $this->widgetAdminService->getTypes();
        $layouts = $this->widgetAdminService->getLayouts();

        return view('admin.widgets.edit', compact('widget', 'channels', 'types', 'layouts'));
    }

    public function update(UpdateWidgetRequest $req, int $id)
    {
        $data = $this->widgetAdminService->validatedPayload($req);

        $this->widgetAdminService->heroMedia->transaction(function () use ($req, $id, $data) {
            $widget  = Widget::manageableBy($req->user())->lockForUpdate()->findOrFail($id);
            $oldConfig = $widget->type === 'hero' ? $widget->config : [];
            $config  = $this->widgetAdminService->buildConfig($req, $data['type'], $data['config'] ?? [], $widget->config);

            $saved = $widget->update([
                'type'         => $data['type'],
                'title'        => $data['type'] === 'projects' && ! array_key_exists('title', $data) ? $widget->title : ($data['title'] ?? null),
                'status'       => $data['status'],
                'hide_on_home' => false,
                'config'       => $config,
            ]);
            if (! $saved) {
                throw new \RuntimeException('Unable to update widget.');
            }

            if ($req->has('channels')) {
                $this->widgetAdminService->syncChannels($widget, $req->input('channels'));
            }
            $this->widgetAdminService->syncTranslations($widget, $req->input('translations', []));
            $this->widgetAdminService->heroMedia->retire($oldConfig ?? [], $config);
        });

        return redirect()
            ->route('admin.widgets.edit', $id)
            ->with('success', 'Widget updated.');
    }

    public function destroy(int $id)
    {
        DB::transaction(function () use ($id) {
            $widget = Widget::manageableBy(auth()->user())->lockForUpdate()->findOrFail($id);
            $oldConfig = $widget->type === 'hero' ? $widget->config : [];
            $widget->pages()->detach();
            $widget->channels()->detach();
            if (! $widget->delete()) {
                throw new \RuntimeException('Unable to delete widget.');
            }
            $this->widgetAdminService->heroMedia->retire($oldConfig ?? [], [], $widget->id);
        });

        return redirect()
            ->route('admin.widgets.index')
            ->with('success', 'Widget deleted.');
    }

    /**
     * POST /admin/widgets/reorder  {ordering: [id, id, …]}
     */
    public function reorder(Request $req)
    {
        $req->validate([
            'ordering'   => ['required', 'array', 'min:1'],
            'ordering.*' => ['integer', 'exists:widgets,id'],
        ]);

        DB::transaction(function () use ($req) {
            foreach ($req->input('ordering') as $pos => $id) {
                Widget::manageableBy($req->user())->findOrFail($id)->update(['position' => $pos + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }
}
