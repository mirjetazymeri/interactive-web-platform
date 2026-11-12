<div class="sf-projects-horizontal" tabindex="0" role="region" aria-label="{{ __('projects.projects') }}">
    @foreach($projects as $project)
        @include('storefront.projects.card', ['project' => $project])
    @endforeach
</div>
