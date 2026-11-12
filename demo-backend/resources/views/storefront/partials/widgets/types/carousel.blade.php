@php
    $slides = $config['slides'] ?? [];
    $images = $config['images'] ?? [];
    $items = count($slides) > 0 ? $slides : $images;
@endphp

<section class="sf-card sf-carousel" data-sf-carousel>
    @include('storefront.partials.widgets.section-header', [
        'widget' => $widget,
        'config' => $config,
        'showWidgetTitle' => !empty($config['show_title']),
    ])

    <div class="sf-carousel__viewport" data-sf-carousel-viewport>
        @foreach($items as $index => $item)
            @php
                $item = (array) $item;
                $img = $item['image'] ?? ($item['url'] ?? '');
                $alt = $item['alt'] ?? '';
            @endphp
            <figure class="sf-carousel__slide" data-sf-carousel-slide>
                @if($img !== '')
                    <img src="{{ $img }}" alt="{{ $alt }}" loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                @endif
                <figcaption class="sf-carousel__caption">
                    @if(!empty($item['headline']))<strong>{{ $item['headline'] }}</strong>@endif
                    @if(!empty($item['subtitle']))<span>{{ $item['subtitle'] }}</span>@endif
                    @if(!empty($item['cta_text']) && !empty($item['cta_link']))
                        <a href="{{ $item['cta_link'] }}" class="sf-btn sf-btn--secondary" style="margin-top:0.5rem;width:fit-content;">{{ $item['cta_text'] }}</a>
                    @endif
                </figcaption>
            </figure>
        @endforeach
    </div>

    @if(count($items) > 1)
        <div class="sf-carousel__dots" data-sf-carousel-dots></div>
    @endif
</section>
