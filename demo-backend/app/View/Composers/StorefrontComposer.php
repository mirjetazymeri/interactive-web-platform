<?php

namespace App\View\Composers;

use App\Services\Cms\MenuBuilderService;
use App\Services\Storefront\StorefrontSettingsPresenter;
use Illuminate\View\View;

class StorefrontComposer
{
    public function __construct(
        private readonly StorefrontSettingsPresenter $presenter,
        private readonly MenuBuilderService $menus,
    ) {}

    public function compose(View $view): void
    {
        if (! $view->offsetExists('storefront')) {
            $view->with('storefront', $this->presenter->present());
        }

        if (! $view->offsetExists('headerMenu')) {
            $view->with('headerMenu', $this->menus->build('header'));
        }

        if (! $view->offsetExists('footerMenu')) {
            $view->with('footerMenu', $this->menus->build('footer'));
        }
    }
}
