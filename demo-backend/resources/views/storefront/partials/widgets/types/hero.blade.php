@php
    $slides = $config['slides'] ?? [];
    $slide = $slides[0] ?? [];
    $bg = $slide['image'] ?? ($config['bg_image'] ?? null);
@endphp

<section class="sf-widget-hero">
    @if($bg)
        <div class="sf-hero__bg" style="background-image: url('{{ $bg }}');"></div>
    @endif
    <div class="sf-hero__inner">
        @if(!empty($config['section_label']))
            <p class="sf-hero__eyebrow">{{ $config['section_label'] }}</p>
        @endif
        @if(!empty($slide['headline']))
            <h2 class="sf-hero__headline">{{ $slide['headline'] }}</h2>
        @elseif(!empty($widget['title']))
            <h2 class="sf-hero__headline">{{ $widget['title'] }}</h2>
        @endif
        @if(!empty($slide['subtitle']))
            <p class="sf-hero__subtitle">{{ $slide['subtitle'] }}</p>
        @endif
        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
            @if(!empty($slide['cta_text']) && !empty($slide['cta_link']))
                <a href="{{ $slide['cta_link'] }}" class="sf-btn sf-btn--primary">{{ $slide['cta_text'] }}</a>
            @endif
            @if(!empty($slide['cta_text_secondary']) && !empty($slide['cta_link_secondary']))
                <a href="{{ $slide['cta_link_secondary'] }}" class="sf-btn sf-btn--ghost">{{ $slide['cta_text_secondary'] }}</a>
            @endif
        </div>
    </div>
</section>
