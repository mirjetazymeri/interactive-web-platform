@php
    $cols = max(1, min(4, (int) ($config['columns'] ?? 2)));
    $showRating = !isset($config['show_rating']) || !empty($config['show_rating']);
@endphp

<section>
    @include('storefront.partials.widgets.section-header', ['widget' => $widget, 'config' => $config])

    <div class="sf-testimonials__grid" style="grid-template-columns: repeat(auto-fit, minmax({{ max(240, 280 / $cols) }}px, 1fr));">
        @foreach($config['items'] ?? [] as $item)
            <article class="sf-card sf-testimonial">
                @if($showRating && !empty($item['rating']))
                    <div class="sf-stars" aria-label="Rating {{ (int) $item['rating'] }} out of 5">
                        {{ str_repeat('★', min(5, max(0, (int) $item['rating']))) }}{{ str_repeat('☆', 5 - min(5, max(0, (int) $item['rating']))) }}
                    </div>
                @endif

                @if(!empty($item['quote']))
                    <blockquote class="sf-testimonial__quote">“{{ $item['quote'] }}”</blockquote>
                @endif

                <div class="sf-testimonial__author">
                    @if(!empty($item['image']))
                        <img class="sf-testimonial__avatar" src="{{ $item['image'] }}" alt="">
                    @elseif(!empty($item['logo']))
                        <img class="sf-testimonial__avatar" src="{{ $item['logo'] }}" alt="">
                    @endif
                    <div>
                        @if(!empty($item['name']))
                            <p class="sf-testimonial__name">{{ $item['name'] }}</p>
                        @endif
                        @if(!empty($item['role']))
                            <p class="sf-testimonial__role">{{ $item['role'] }}</p>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
