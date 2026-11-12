<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Cms\PageContentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(private readonly PageContentService $pages) {}

    public function home(Request $request): View
    {
        $page = $this->pages->resolveHomePage();
        $data = $this->pages->forStorefront($page, $request->query('device', 'desktop'));

        return $this->pageView($data, isHome: true);
    }

    public function show(Request $request, string $slugOrPath): View
    {
        $page = $this->pages->resolveByPath($slugOrPath);
        $data = $this->pages->forStorefront($page, $request->query('device', 'desktop'));

        $isHome = $page->slug === 'home' && $page->parent_id === null;

        return $this->pageView($data, isHome: $isHome);
    }

    /**
     * @param  array{page: \App\Models\Page, breadcrumbs: list<array>, widgetsBySlot: array}  $data
     */
    private function pageView(array $data, bool $isHome): View
    {
        $page = $data['page'];

        return view('storefront.page', [
            'page'            => $page,
            'breadcrumbs'     => $data['breadcrumbs'],
            'widgetsBySlot'   => $data['widgetsBySlot'],
            'metaTitle'       => $page->meta_title ?: $page->title,
            'metaDescription' => $page->meta_description,
            'isHome'          => $isHome,
        ]);
    }
}
