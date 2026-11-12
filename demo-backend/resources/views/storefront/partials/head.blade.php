@php
    $appName = $storefront->get('general.app_name', config('app.name', 'Store'));
    $title = trim((string) ($metaTitle ?? ''));
    if ($title === '') {
        $title = trim((string) $storefront->get('seo.meta_title', ''));
    }
    $documentTitle = $title !== '' ? "{$title} · {$appName}" : $appName;

    $description = trim((string) ($metaDescription ?? ''));
    if ($description === '') {
        $description = trim((string) $storefront->get('seo.meta_description', ''));
    }

    $favicon = $storefront->assetUrl($storefront->get('general.favicon'));
@endphp

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- A preview of CMS content; the public website is the Next.js frontend. --}}
<meta name="robots" content="noindex, nofollow">
<title>{{ $documentTitle }}</title>
@if($description !== '')
    <meta name="description" content="{{ $description }}">
@endif
@if($favicon)
    <link rel="icon" href="{{ $favicon }}" type="image/x-icon">
@endif

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

@include('storefront.partials.theme-variables')

@vite(['resources/css/storefront.css', 'resources/js/storefront.js'])

@stack('styles')
