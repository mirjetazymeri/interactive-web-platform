@extends('storefront.layouts.app')

@php
    $hasHero = !empty($widgetsBySlot['hero']);
    $hidePageHeader = ($isHome ?? false) && $hasHero;
@endphp

@section('content')
    <div class="sf-shell">
        @if(!empty($breadcrumbs) && count($breadcrumbs) > 1)
            @include('storefront.partials.breadcrumbs')
        @endif

        @foreach($widgetsBySlot['hero'] ?? [] as $widget)
            @include('storefront.partials.widgets.render', ['widget' => $widget])
        @endforeach

        @if(!$hidePageHeader || trim((string) $page->content) !== '')
            <article @class(['sf-page', 'sf-page--flush' => $hidePageHeader && trim((string) $page->content) === ''])>
                @unless($hidePageHeader)
                    <header class="sf-page__header">
                        <h1 class="sf-page__title">{{ $page->title }}</h1>
                    </header>
                @endunless

                @if(trim((string) $page->content) !== '')
                    <div class="sf-page__content">
                        {!! $page->content !!}
                    </div>
                @endif
            </article>
        @endif

        @php
            $slotOrder = ['main', 'content', 'sidebar', 'below', 'footer'];
            $usedSlots = array_keys($widgetsBySlot);
            $remaining = array_diff($usedSlots, array_merge(['hero'], $slotOrder));
            $orderedSlots = array_merge(
                array_values(array_intersect($slotOrder, $usedSlots)),
                $remaining
            );
        @endphp

        @foreach($orderedSlots as $slot)
            @include('storefront.partials.widgets.slot', [
                'slot' => $slot,
                'widgets' => $widgetsBySlot[$slot] ?? [],
            ])
        @endforeach
    </div>
@endsection
