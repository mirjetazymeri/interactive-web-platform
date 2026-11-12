@php
    $embed = $config['map_embed_url'] ?? ($config['embed_url'] ?? null);
    $title = $config['marker_title'] ?? ($config['markers'][0]['title'] ?? null);
    $text = $config['marker_text'] ?? ($config['markers'][0]['text'] ?? null);
    $mapLink = $config['map_url'] ?? null;
@endphp

<section class="sf-card sf-map">
    @include('storefront.partials.widgets.section-header', ['widget' => $widget, 'config' => $config])

    @if($embed)
        <iframe
            class="sf-map__frame"
            src="{{ $embed }}"
            title="{{ $title ?: 'Map' }}"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
        ></iframe>
    @endif

    @if($title || $text || $mapLink)
        <div class="sf-map__meta">
            @if($title)<h3>{{ $title }}</h3>@endif
            @if($text)<p>{{ $text }}</p>@endif
            @if($mapLink)
                <p style="margin-top:0.5rem;">
                    <a href="{{ $mapLink }}" target="_blank" rel="noopener noreferrer">Open in OpenStreetMap</a>
                </p>
            @endif
        </div>
    @endif
</section>
