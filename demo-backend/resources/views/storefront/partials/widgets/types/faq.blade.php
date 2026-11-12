<section>
    @include('storefront.partials.widgets.section-header', ['widget' => $widget, 'config' => $config])

    <div class="sf-faq">
        @foreach($config['items'] ?? [] as $item)
            <details class="sf-faq__item" @if(empty($config['allow_multiple_open'])) name="sf-faq-{{ $widget['id'] ?? 'x' }}" @endif>
                <summary>{{ $item['question'] ?? '' }}</summary>
                <div class="sf-faq__answer">{!! nl2br(e((string) ($item['answer'] ?? ''))) !!}</div>
            </details>
        @endforeach
    </div>
</section>
