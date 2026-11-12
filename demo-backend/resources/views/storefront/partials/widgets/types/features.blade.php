@php
    $cols = max(1, min(4, (int) ($config['columns'] ?? 3)));
@endphp

<section>
    @include('storefront.partials.widgets.section-header', ['widget' => $widget, 'config' => $config])

    <div class="sf-grid" style="grid-template-columns: repeat(auto-fit, minmax({{ max(220, 280 / $cols) }}px, 1fr));">
        @foreach($config['items'] ?? [] as $item)
            <article class="sf-card sf-feature">
                @if(!empty($item['icon']))
                    <img class="sf-feature__icon" src="{{ $item['icon'] }}" alt="">
                @elseif(!empty($item['image']))
                    <img class="sf-feature__image" src="{{ $item['image'] }}" alt="">
                @endif
                <h3>{{ $item['title'] ?? '' }}</h3>
                <p>{{ $item['description'] ?? '' }}</p>
            </article>
        @endforeach
    </div>
</section>
