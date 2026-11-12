<div class="sf-projects-vertical">
    @foreach($projects as $project)
        @include('storefront.projects.card', ['project' => $project])
    @endforeach
</div>
