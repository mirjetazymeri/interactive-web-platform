@php
    $type = $widget['type'] ?? 'unknown';
    $partial = 'storefront.partials.widgets.types.' . $type;
    $exists = view()->exists($partial);
@endphp

<div class="sf-widget sf-widget--{{ $type }}" data-widget-id="{{ $widget['id'] ?? '' }}" data-widget-key="{{ $widget['widget_key'] ?? '' }}">
    @if($exists)
        @include($partial, ['widget' => $widget, 'config' => $widget['config'] ?? []])
    @else
        @include('storefront.partials.widgets.types.unknown', ['widget' => $widget, 'config' => $widget['config'] ?? []])
    @endif
</div>
