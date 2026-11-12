@if(!empty($config['projects']))
<section class="sf-projects" aria-label="{{ $widget['title'] ?: __('projects.projects') }}">
    @include('storefront.partials.widgets.section-header', ['widget' => $widget, 'config' => $config])
    @include('storefront.projects.variants.'.(\App\Services\Widgets\Types\ProjectsWidgetTypeService::variant($config['variant'] ?? 'horizontal_scroll') === 'vertical_scroll' ? 'vertical' : 'horizontal'), ['projects' => $config['projects']])
</section>
@endif
