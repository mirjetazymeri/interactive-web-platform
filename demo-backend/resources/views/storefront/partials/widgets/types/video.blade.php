@php
    $rawUrl = trim((string) ($config['video_url'] ?? ''));
    $embed = $rawUrl;

    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $rawUrl, $m)) {
        $embed = 'https://www.youtube.com/embed/' . $m[1];
    } elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $rawUrl, $m)) {
        $embed = 'https://player.vimeo.com/video/' . $m[1];
    }
@endphp

<section class="sf-card sf-video">
    @include('storefront.partials.widgets.section-header', [
        'widget' => $widget,
        'config' => $config,
        'heading' => $config['section_label'] ?? null,
    ])

    @if($embed !== '')
        <div class="sf-video__wrap">
            <iframe
                class="sf-video__frame"
                src="{{ $embed }}"
                title="{{ $widget['title'] ?? 'Video' }}"
                loading="lazy"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            ></iframe>
        </div>
    @endif
</section>
