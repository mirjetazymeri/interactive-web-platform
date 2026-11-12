<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Page\StorePageRequest;
use App\Http\Requests\Admin\Page\UpdatePageRequest;
use App\Http\Requests\Admin\PageWidget\AttachWidgetRequest;
use App\Http\Requests\Admin\PageWidget\ReorderWidgetsRequest;
use App\Http\Requests\Admin\PageWidget\UpdatePlacementRequest;
use App\Models\{Page, Widget, PageWidget};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::with(['children' => fn($q)=>$q->orderBy('position')->orderBy('id')])
            ->roots()
            ->orderBy('position')
            ->get();

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create', [
            'page'     => new Page(),
            'parents'  => Page::pluck('title','id'),
            'channels' => auth()->user()->channels
        ]);
    }

    public function store(StorePageRequest $req)
    {
        DB::transaction(function () use ($req, &$page) {

            $data = $req->validated();

            $data['show_in_header'] = $req->boolean('show_in_header');
            $data['show_in_footer'] = $req->boolean('show_in_footer');
            $data['nav_new_tab']    = $req->boolean('nav_new_tab');

            $page = Page::create($this->seoFields($req, $data));

            $this->syncChannels($page, $req->channels);
            $this->syncTranslations($page, $req->input('translations', []));
        });

        return redirect()->route('admin.pages.edit', $page->id);
    }

    public function edit(int $id)
    {
        $page = Page::with([
            'pageWidgets.widget.channels',
            'translations',
            'channels'
        ])->findOrFail($id);

        return view('admin.pages.edit', [
            'page'     => $page,
            'widgets'  => Widget::active()->manageableBy(auth()->user())->get(),
            'channels' => auth()->user()->channels
        ]);
    }

    public function update(UpdatePageRequest $req, int $id)
    {
        $page = Page::findOrFail($id);

        DB::transaction(function () use ($req, $page) {

            $data = $req->validated();

            $data['show_in_header'] = $req->boolean('show_in_header');
            $data['show_in_footer'] = $req->boolean('show_in_footer');
            $data['nav_new_tab']    = $req->boolean('nav_new_tab');

            $page->update($this->seoFields($req, $data, $page));

            $this->syncChannels($page, $req->channels);
            $this->syncTranslations($page, $req->input('translations', []));
        });

        return back();
    }

    public function destroy(int $id)
    {
        $page = Page::findOrFail($id);
        $page->delete();

        return redirect()->route('admin.pages.index');
    }

    // --------------------------------------------------
    // CHANNELS
    // --------------------------------------------------

    private function syncChannels(Page $page, ?array $channels): void
    {
        $user = auth()->user();

        $channels = $channels ?: [$user->current_channel_id];

        $allowed = $user->channels->pluck('id')->toArray();

        $channels = array_values(array_intersect($channels, $allowed));

        $page->channels()->sync($channels ?: [$user->current_channel_id]);
    }

    /**
     * Robots switches (absent means unchanged, or indexable for a new page) and the
     * optional share image, stored on the public disk like other page media.
     */
    private function seoFields(Request $req, array $data, ?Page $page = null): array
    {
        unset($data['og_image_file'], $data['og_image_remove']);
        foreach (['robots_index', 'robots_follow'] as $flag) {
            $data[$flag] = $req->has($flag) ? $req->boolean($flag) : ($page?->{$flag} ?? true);
        }
        if (array_key_exists('canonical_url', $data)) {
            $data['canonical_url'] = filled($data['canonical_url']) ? trim($data['canonical_url']) : null;
        }

        $old = $page?->og_image;
        if ($req->hasFile('og_image_file')) {
            $data['og_image'] = $req->file('og_image_file')->store('seo/pages', 'public');
        } elseif ($req->boolean('og_image_remove')) {
            $data['og_image'] = null;
        }
        if ($old && array_key_exists('og_image', $data) && $data['og_image'] !== $old) {
            Storage::disk('public')->delete($old);
        }

        return $data;
    }

    private function syncTranslations(Page $page, array $translations): void
    {
        foreach ($translations as $translation) {
            $locale = trim((string) ($translation['locale'] ?? ''));

            if ($locale === '') {
                continue;
            }

            $payload = [
                'title' => $translation['title'] ?? null,
                'content' => $translation['content'] ?? null,
                'meta_title' => $translation['meta_title'] ?? null,
                'meta_description' => $translation['meta_description'] ?? null,
                'og_title' => $translation['og_title'] ?? null,
                'og_description' => $translation['og_description'] ?? null,
                'nav_label' => $translation['nav_label'] ?? null,
            ];

            if (collect($payload)->filter(fn ($value) => filled($value))->isEmpty()) {
                $page->translations()->where('locale', $locale)->delete();
                continue;
            }

            $page->translations()->updateOrCreate(
                ['locale' => $locale],
                $payload
            );
        }
    }

    // --------------------------------------------------
    // WIDGETS
    // --------------------------------------------------

    public function attachWidget(AttachWidgetRequest $req, int $pageId)
    {
        $page   = $this->manageablePage($req, $pageId);
        $widget = Widget::with('channels')->manageableBy($req->user())->findOrFail($req->widget_id);

        abort_if($widget->type !== 'service_x' && $req->has('config.display_mode'), 422, 'Display mode is only supported by ServiceX.');

        // ✅ enforce channel compatibility
        if (!array_intersect(
            $page->channels->pluck('id')->toArray(),
            $widget->channels->pluck('id')->toArray()
        )) {
            return back()->withErrors(['widget_id'=>'Channel mismatch']);
        }

        PageWidget::create([
            'page_id'   => $pageId,
            'widget_id' => $widget->id,
            'slot'      => 'content',
            'position'  => PageWidget::where('page_id',$pageId)->max('position') + 1,
            'active'    => true,
            'config'    => $widget->type === 'service_x'
                ? ['display_mode' => $req->input('config.display_mode', 'slider')]
                : null,
        ]);

        return back();
    }

    public function detachWidget(int $pageId, int $pivotId)
    {
        PageWidget::where('page_id',$pageId)->whereKey($pivotId)->delete();
        return back();
    }

    public function updatePlacement(UpdatePlacementRequest $req, int $pageId, int $pivotId)
    {
        $this->manageablePage($req, $pageId);
        $placement = PageWidget::with('widget')->where('page_id', $pageId)->findOrFail($pivotId);
        abort_unless($placement->widget?->type === 'service_x', 422, 'Display mode is only supported by ServiceX.');
        $placement->update(['config' => array_replace($placement->config ?? [], $req->validated('config'))]);

        return $req->expectsJson()
            ? response()->json(['ok' => true, 'placement_id' => $placement->id, 'placement_config' => $placement->presentationConfig()])
            : back()->with('success', 'Presentation updated.');
    }

    private function manageablePage(Request $request, int $id): Page
    {
        $allowed = $request->user()->channels->pluck('id')->all();

        return Page::with('channels')
            ->whereHas('channels', fn ($q) => $q->whereIn('channels.id', $allowed))
            ->whereDoesntHave('channels', fn ($q) => $q->whereNotIn('channels.id', $allowed))
            ->findOrFail($id);
    }

    public function reorderWidgets(ReorderWidgetsRequest $req, int $pageId)
    {
        DB::transaction(function() use ($req,$pageId){
            foreach ($req->ordering as $i => $id) {
                PageWidget::where('page_id',$pageId)
                    ->whereKey($id)
                    ->update(['position'=>$i+1]);
            }
        });

        return response()->json(['ok'=>true]);
    }

    public function toggleWidget(Request $req, int $pageId, int $pivotId)
    {
        PageWidget::where('page_id',$pageId)
            ->whereKey($pivotId)
            ->update(['active'=>$req->boolean('active')]);

        return response()->json(['ok'=>true]);
    }
}
