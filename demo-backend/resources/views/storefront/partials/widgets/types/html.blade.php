@if(!empty($config['css']))
    <style>{!! $config['css'] !!}</style>
@endif
<section class="sf-html-widget sf-card sf-card--flat">
    {!! $config['html'] ?? '' !!}
</section>
