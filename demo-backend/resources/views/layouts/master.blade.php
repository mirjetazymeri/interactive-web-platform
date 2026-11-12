<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="transparent" data-width="fullwidth" data-menu-styles="transparent" data-page-style="flat">

    <head>

        <!-- Meta Data -->
        <meta charset="UTF-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="robots" content="noindex, nofollow">

        <title>@hasSection('title')@yield('title')@elseif(!empty($adminTrail)){{ last($adminTrail) }}@else Admin @endif · {{ $adminBrand['name'] }}</title>

        <!-- Restore the saved sidebar state before first paint (no layout shift). -->
        <script>
            try { if (localStorage.getItem('ax-sidebar') === 'collapsed') document.documentElement.classList.add('ax-collapsed'); } catch (e) {}
        </script>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

        <!-- Favicon: Settings → General → Favicon, with the template icon as fallback -->
        <link rel="icon" href="{{ $adminBrand['favicon'] ?? asset('build/assets/images/brand-logos/favicon.ico') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Main Theme Js (restores the saved light/dark mode before paint) -->
        <script src="{{asset('build/assets/main.js')}}"></script>

        <!-- ICONS CSS -->
        <link href="{{asset('build/assets/icon-fonts/icons.css')}}" rel="stylesheet">

        @include('layouts.components.styles')

        <!-- APP CSS & APP SCSS, then the admin design system on top -->
        @vite(['resources/sass/app.scss', 'resources/css/admin-ui.css'])

        @yield('styles')
        @stack('styles')

    </head>

    <body>

        <a class="ax-skip-link" href="#ax-main">Skip to content</a>

        <div class="progress-top-bar"></div>

        <!-- Loader -->
        <div id="loader">
            <img src="{{asset('build/assets/images/media/loader.svg')}}" alt="">
        </div>
        <!-- Loader -->

        <div class="ax-shell">

            @include('layouts.components.admin-sidebar')

            <div class="ax-main">

                @include('layouts.components.admin-topbar')

                <main class="ax-content" id="ax-main" tabindex="-1">
                    <div class="ax-container">
                        @yield('content')
                    </div>
                </main>

                @include('layouts.components.footer')

            </div>

            @include('layouts.components.modal')

            @yield('modals')

        </div>

        <div class="ax-toasts" id="ax-toasts" aria-live="polite" aria-atomic="false"></div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script>window.$ = window.jQuery;</script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
        <!-- Scripts -->
        @include('layouts.components.scripts')

        <!-- Admin shell: sidebar, drawer, theme-aware logo, toasts -->
        @vite('resources/js/admin-shell.js')

        <!-- App JS-->
        @vite('resources/js/app.js')

        @stack('scripts')

        <!-- End Scripts -->

    </body>

</html>
