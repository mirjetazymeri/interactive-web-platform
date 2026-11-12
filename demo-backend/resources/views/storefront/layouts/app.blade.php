<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('storefront.partials.head')
    @stack('head')
</head>
<body @class([
    'sf-body',
    'sf-body--home' => $isHome ?? false,
    'sf-body--header-sticky' => $storefront->bool('header.main.sticky', true),
    'sf-body--header-transparent' => ($isHome ?? false) && $storefront->bool('header.main.transparent_on_home'),
])>
    <a class="sf-skip-link" href="#main-content" style="position:absolute;left:-9999px;top:auto;">Skip to content</a>

    @include('storefront.partials.top-bar')
    @include('storefront.partials.header')

    <main id="main-content" class="sf-main">
        @yield('content')
    </main>

    @include('storefront.partials.footer')

    <div class="sf-lightbox" data-sf-lightbox aria-hidden="true">
        <button type="button" class="sf-lightbox__close" data-sf-lightbox-close aria-label="Close">×</button>
        <img src="" alt="">
    </div>

    @stack('scripts')
</body>
</html>
