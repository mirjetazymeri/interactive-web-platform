@php
    $currency = (string) ($config['currency'] ?? '$');
    $highlightPopular = !empty($config['highlight_popular']);
    $cols = max(1, min(4, (int) ($config['columns'] ?? 3)));
@endphp

<section>
    @include('storefront.partials.widgets.section-header', ['widget' => $widget, 'config' => $config])

    @if(!empty($config['billing_note']))
        <p style="text-align:center;color:var(--sf-color-muted);margin:0 0 var(--sf-spacing-md);">{{ $config['billing_note'] }}</p>
    @endif

    <div class="sf-pricing__grid" style="grid-template-columns: repeat(auto-fit, minmax({{ max(220, 260 / $cols) }}px, 1fr));">
        @foreach($config['items'] ?? [] as $plan)
            @php
                $isPopular = !empty($plan['is_popular']) || (!empty($plan['badge']) && $highlightPopular);
                $features = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($plan['features'] ?? ''))));
            @endphp
            <article @class(['sf-card', 'sf-plan', 'sf-plan--popular' => $isPopular])>
                @if(!empty($plan['badge']))
                    <span class="sf-plan__badge">{{ $plan['badge'] }}</span>
                @endif

                <h3 style="margin:0;font-size:1.125rem;">{{ $plan['name'] ?? '' }}</h3>

                @if(!empty($plan['description']))
                    <p style="margin:0.5rem 0 0;color:var(--sf-color-muted);font-size:0.9375rem;">{{ $plan['description'] }}</p>
                @endif

                <div class="sf-plan__price">
                    <span class="sf-plan__amount">{{ $currency }}{{ $plan['price'] ?? '' }}</span>
                    @if(!empty($plan['period']))
                        <span class="sf-plan__period">{{ $plan['period'] }}</span>
                    @endif
                </div>

                @if(!empty($features))
                    <ul class="sf-plan__features">
                        @foreach($features as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                @endif

                @if(!empty($plan['cta_text']) && !empty($plan['cta_link']))
                    <a href="{{ $plan['cta_link'] }}" class="sf-btn {{ $isPopular ? 'sf-btn--primary' : 'sf-btn--secondary' }}" style="width:100%;">
                        {{ $plan['cta_text'] }}
                    </a>
                @endif
            </article>
        @endforeach
    </div>
</section>
