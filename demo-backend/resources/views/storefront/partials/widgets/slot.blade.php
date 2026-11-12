@if(!empty($widgets))
    <section class="sf-widget-slot" data-slot="{{ $slot }}" aria-label="{{ ucfirst($slot) }} widgets">
        @foreach($widgets as $widget)
            @include('storefront.partials.widgets.render', ['widget' => $widget])
        @endforeach
    </section>
@endif
