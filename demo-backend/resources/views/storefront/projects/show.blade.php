@extends('storefront.layouts.app')
@section('content')
<article class="sf-shell sf-project-detail">
    <h1>{{ $project['name'] }}</h1>
    @php($hasMeta = collect(['project_type', 'category', 'client_name', 'technologies', 'completion_year'])->contains(fn ($field) => filled($project[$field] ?? null)))
    @if($hasMeta)
    <dl class="sf-project-meta">
    @foreach(['project_type', 'category', 'client_name', 'technologies', 'completion_year'] as $field)
        @php($value = is_array($project[$field] ?? null) ? implode(', ', $project[$field]) : ($project[$field] ?? ''))
        @if(filled($value))<div><dt>{{ __('projects.'.$field) }}</dt><dd>{{ $value }}</dd></div>@endif
    @endforeach
    </dl>
    @endif
    @if(!empty($project['images']))
    <div class="sf-project-detail__images">
        @foreach($project['images'] as $image)
        <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" @if(!$loop->first) loading="lazy" @endif>
        @endforeach
    </div>
    @endif
    @foreach(['description', 'work_delivered', 'goal'] as $field)
        @if(filled($project[$field]))<section><h2>{{ __('projects.'.$field) }}</h2><div class="sf-project-prose">{{ $project[$field] }}</div></section>@endif
    @endforeach
    @if(!empty($project['website_url']))
        <a class="sf-btn sf-btn--primary" href="{{ $project['website_url'] }}" target="_blank" rel="noopener noreferrer">{{ __('projects.visit_website') }}</a>
    @endif
</article>
@endsection
