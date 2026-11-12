@php
    $heading = $heading ?? ($config['heading'] ?? null);
    $subheading = $subheading ?? ($config['subheading'] ?? null);
    $eyebrow = $eyebrow ?? ($config['section_label'] ?? null);
@endphp
@if($eyebrow || $heading || $subheading || !empty($widget['title']))
    <header class="sf-section-header">
        @if($eyebrow)
            <p class="sf-section-header__eyebrow">{{ $eyebrow }}</p>
        @endif
        @if($heading)
            <h2 class="sf-section-header__title">{{ $heading }}</h2>
        @elseif(!empty($widget['title']) && !empty($showWidgetTitle))
            <h2 class="sf-section-header__title">{{ $widget['title'] }}</h2>
        @endif
        @if($subheading)
            <p class="sf-section-header__subtitle">{{ $subheading }}</p>
        @endif
    </header>
@endif
