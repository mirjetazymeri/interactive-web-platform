@php
    $cols = max(1, min(6, (int) ($config['columns'] ?? 3)));
    $gap = max(0, (int) ($config['gap'] ?? 16));
    $ratio = match ((string) ($config['aspect_ratio'] ?? 'square')) {
        'landscape' => '16 / 10',
        'portrait' => '3 / 4',
        default => '1 / 1',
    };
    $lightbox = !isset($config['lightbox']) || !empty($config['lightbox']);
@endphp

<section class="sf-card">
    @include('storefront.partials.widgets.section-header', ['widget' => $widget, 'config' => $config])

    <div
        class="sf-gallery"
        style="--sf-gallery-cols: {{ $cols }}; --sf-gallery-gap: {{ $gap }}px; --sf-gallery-ratio: {{ $ratio }};"
    >
        @foreach($config['items'] ?? [] as $index => $item)
            @php
                $src = $item['image'] ?? '';
                $alt = $item['alt'] ?? ($item['caption'] ?? '');
                $caption = $item['caption'] ?? '';
                $link = trim((string) ($item['link'] ?? ''));
            @endphp
            @if($src === '')
                @continue
            @endif

            @if($lightbox)
                <button
                    type="button"
                    class="sf-gallery__item"
                    data-sf-gallery-item
                    data-src="{{ $src }}"
                    data-alt="{{ $alt }}"
                >
                    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy">
                    @if($caption !== '')
                        <span class="sf-gallery__caption">{{ $caption }}</span>
                    @endif
                </button>
            @elseif($link !== '')
                <a href="{{ $link }}" class="sf-gallery__item" style="text-decoration:none;color:inherit;">
                    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy">
                    @if($caption !== '')
                        <span class="sf-gallery__caption">{{ $caption }}</span>
                    @endif
                </a>
            @else
                <figure class="sf-gallery__item" style="cursor:default;">
                    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy">
                    @if($caption !== '')
                        <figcaption class="sf-gallery__caption">{{ $caption }}</figcaption>
                    @endif
                </figure>
            @endif
        @endforeach
    </div>
</section>
