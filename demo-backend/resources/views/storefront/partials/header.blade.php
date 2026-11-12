@php
    $logo = $storefront->assetUrl($storefront->get('general.logo'));
    $appName = $storefront->get('general.app_name', config('app.name', 'Store'));
    $menuType = $storefront->get('header.menu.menu_type', 'mega');
@endphp

<header class="sf-header" role="banner">
    <div class="sf-shell sf-header__inner">
        <a class="sf-brand" href="{{ route('storefront.home') }}">
            @if($logo)
                <img src="{{ $logo }}" alt="{{ $appName }}">
            @endif
            <span>{{ $appName }}</span>
        </a>

        <button type="button" class="sf-header-toggle" data-sf-nav-toggle aria-label="Open menu" aria-expanded="false">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
        </button>

        <nav class="sf-nav sf-nav--desktop" aria-label="Primary">
            @if(!empty($headerMenu))
                <ul class="sf-menu sf-menu--{{ $menuType }}" role="menubar">
                    @include('storefront.partials.menu-items', ['items' => $headerMenu])
                </ul>
            @else
                <a href="{{ route('storefront.home') }}">Home</a>
            @endif
        </nav>
    </div>
</header>

<div class="sf-nav-drawer" data-sf-nav-drawer aria-hidden="true">
    <div class="sf-nav-drawer__backdrop" data-sf-nav-backdrop></div>
    <div class="sf-nav-drawer__panel" role="dialog" aria-modal="true" aria-label="Menu">
        <button type="button" class="sf-nav-drawer__close" data-sf-nav-close>Close menu</button>
        @if(!empty($headerMenu))
            <ul class="sf-menu sf-menu--mobile">
                @include('storefront.partials.menu-items', ['items' => $headerMenu])
            </ul>
        @endif
    </div>
</div>
