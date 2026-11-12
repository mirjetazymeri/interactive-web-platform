<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="horizontal" data-nav-style="menu-hover" data-menu-position="fixed" data-theme-mode="light">

    <head>

        <!-- Meta Data -->
        <meta charset="UTF-8">
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="Description" content="Laravel Bootstrap Responsive Admin Web Dashboard Template">
        <meta name="Author" content="Spruko Technologies Private Limited">
        <meta name="keywords" content="laravel, laravel admin panel, laravel dashboard, bootstrap dashboard, bootstrap admin panel, vite laravel, admin dashboard, admin panel in laravel, admin dashboard ui, laravel admin, admin panel template, laravel framework, dashboard, admin dashboard template, laravel template.">

        <!-- Title-->
        <title> Master Your Workflows </title>

        <!-- Favicon -->
        <link rel="icon" href="{{asset('build/assets/images/brand-logos/favicon.ico')}}" type="image/x-icon">

        <!-- Icons CSS -->
        <link href="{{asset('build/assets/icon-fonts/icons.css')}}" rel="stylesheet">

        @include('layouts.components.landingpage.styles')

        <!-- APP CSS & APP SCSS -->
        @vite(['resources/sass/app.scss' ])

        @yield('styles')

    </head>

    <body class="landing-body">

        <!-- Start::main-switcher -->
        @include('layouts.components.landingpage.switcher')
        <!-- End::main-switcher -->

        <div class="landing-page-wrapper">

            <!-- Start::main-header -->
            @include('layouts.components.landingpage.main-header')
            <!-- End::main-header -->

            <!-- Start::main-sidebar -->
            @include('layouts.components.landingpage.main-sidebar')
            <!-- End::main-sidebar -->

            <!-- Start::app-content -->
            <div class="main-content landing-main">

                @yield('content')

            </div>
            <!-- End::main-content -->

            <!-- Start::main-footer -->
            @include('layouts.components.landingpage.footer')
            <!-- End::main-footer -->

        </div>
        <!--app-content closed-->

        @yield('modals')

        <!-- Scripts -->
        @include('layouts.components.landingpage.scripts')

    </body>

</html>
