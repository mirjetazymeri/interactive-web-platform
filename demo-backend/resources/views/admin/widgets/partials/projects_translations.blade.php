<div class="col-12 widget-translation-type {{ $type === 'projects' ? '' : 'd-none' }}" data-widget-translation-type="projects">
    <p class="text-muted">{{ __('projects.translation_help') }}</p>
    @if(empty($cfg['projects']))<p class="alert alert-info">{{ __('projects.translation_empty') }}</p>@endif
    @foreach(($cfg['projects'] ?? []) as $project)
        @continue(empty($project['id']))
        @php($projectId = $project['id'])
        <fieldset class="border rounded p-3 mb-3">
            <legend class="fs-6">{{ $project['name'] ?? __('projects.new_item') }}</legend>
            @foreach(\App\Services\Widgets\Types\ProjectsWidgetTypeService::TEXT_FIELDS as $field)
                <label class="form-label d-block">{{ __('projects.'.$field) }}
                    <small class="d-block text-muted">{{ __('projects.source_content') }}: {{ \Illuminate\Support\Str::limit((string) ($project[$field] ?? ''), 300) }}</small>
                    <textarea class="form-control" rows="{{ in_array($field, ['name', 'project_type', 'category', 'seo_title']) ? 1 : ($field === 'seo_description' ? 2 : 4) }}" name="translations[{{ $index }}][projects][{{ $projectId }}][{{ $field }}]">{{ old("translations.$index.projects.$projectId.$field", $translationConfig['projects'][$projectId][$field] ?? '') }}</textarea>
                </label>
            @endforeach
            @foreach(($project['images'] ?? []) as $image)
                @continue(empty($image['id']))
                @php($imageId = $image['id'])
                <label class="form-label d-block">{{ __('projects.alt') }} — {{ __('projects.photo') }} {{ $loop->iteration }}
                    <small class="d-block text-muted">{{ __('projects.source_content') }}: {{ $image['alt'] ?? '' }}</small>
                    <input class="form-control" name="translations[{{ $index }}][projects][{{ $projectId }}][images][{{ $imageId }}][alt]" value="{{ old("translations.$index.projects.$projectId.images.$imageId.alt", $translationConfig['projects'][$projectId]['images'][$imageId]['alt'] ?? '') }}">
                </label>
            @endforeach
        </fieldset>
    @endforeach
</div>
