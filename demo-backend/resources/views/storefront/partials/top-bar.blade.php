@if($storefront->bool('header.top_bar.enabled'))
    @php
        $text = trim((string) $storefront->get('header.top_bar.text', ''));
        $link = trim((string) $storefront->get('header.top_bar.link', ''));
        $bg = $storefront->get('header.top_bar.background_color', '#000000');
        $color = $storefront->get('header.top_bar.text_color', '#ffffff');
        $closable = $storefront->bool('header.top_bar.closable', true);
    @endphp
    @if($text !== '')
        <div
            class="sf-top-bar"
            id="sf-top-bar"
            style="background: {{ $bg }}; color: {{ $color }};"
            @if($closable) data-closable="1" @endif
            role="region"
            aria-label="Announcement"
        >
            @if($link !== '')
                <a href="{{ $link }}">{{ $text }}</a>
            @else
                {{ $text }}
            @endif
            @if($closable)
                <button type="button" class="sf-top-bar__close" aria-label="Dismiss announcement" style="margin-left: 1rem; background: transparent; border: 0; color: inherit; cursor: pointer;">×</button>
            @endif
        </div>
        @if($closable)
            @push('scripts')
                <script>
                    (function () {
                        var bar = document.getElementById('sf-top-bar');
                        if (!bar || bar.dataset.closable !== '1') return;
                        var key = 'sf_top_bar_dismissed';
                        if (sessionStorage.getItem(key) === '1') { bar.remove(); return; }
                        var btn = bar.querySelector('.sf-top-bar__close');
                        if (!btn) return;
                        btn.addEventListener('click', function () {
                            sessionStorage.setItem(key, '1');
                            bar.remove();
                        });
                    })();
                </script>
            @endpush
        @endif
    @endif
@endif
