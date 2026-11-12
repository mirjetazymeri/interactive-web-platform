<article class="sf-project-card">
    @if(!empty($project['main_image']))
        <img class="sf-project-card__image" src="{{ $project['main_image']['url'] }}" alt="{{ $project['main_image']['alt'] }}" loading="lazy">
    @endif
    <div class="sf-project-card__body">
        <h3>{{ $project['name'] }}</h3>
        @if(filled($project['category'] ?? null) || filled($project['client_name'] ?? null) || filled($project['technologies'] ?? null))
        <dl class="sf-project-meta">
        @foreach(['category', 'client_name', 'technologies'] as $field)
            @php($value = is_array($project[$field] ?? null) ? implode(', ', $project[$field]) : ($project[$field] ?? ''))
            @if(filled($value))<div><dt>{{ __('projects.'.$field) }}</dt><dd>{{ $value }}</dd></div>@endif
        @endforeach
        </dl>
        @endif
        <a class="sf-btn sf-btn--primary" href="{{ $project['url'] }}">{{ __('projects.view_project') }}</a>
    </div>
</article>
