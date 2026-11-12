<fieldset class="card card-body mb-3 project-item">
    <input type="hidden" data-project-id name="config[projects][{{ $projectIndex }}][id]" value="{{ $project['id'] ?? '' }}">
    <legend class="fs-6">{{ $project['name'] ?? __('projects.new_item') }}</legend>
    <details class="project-item-editor" @if(empty($project['id'])) open @endif>
    <summary class="mb-3 text-primary fw-semibold">{{ __('projects.edit_item') }}</summary>
    <div class="row g-3">
        @foreach(['name', 'slug', 'project_type', 'category', 'client_name', 'completion_year', 'website_url'] as $field)
        <div class="col-md-6"><label class="form-label d-block">{{ __('projects.'.$field) }}
            <input class="form-control" name="config[projects][{{ $projectIndex }}][{{ $field }}]" value="{{ $project[$field] ?? '' }}"
                type="{{ $field === 'completion_year' ? 'number' : ($field === 'website_url' ? 'url' : 'text') }}"
                @if($field === 'completion_year') min="1900" max="2100" @endif>
        </label></div>
        @endforeach
        <div class="col-md-6"><label class="form-label">{{ __('projects.technologies') }}</label>
            <div class="project-technologies">
                @foreach(($project['technologies'] ?? ['']) as $technology)
                <input class="form-control mb-1" aria-label="{{ __('projects.technologies') }}" name="config[projects][{{ $projectIndex }}][technologies][]" value="{{ $technology }}" maxlength="100">
                @endforeach
            </div>
            <button type="button" class="btn btn-sm btn-light" data-technology-add>{{ __('projects.add_technology') }}</button>
        </div>
        @foreach(['description', 'work_delivered', 'goal'] as $field)
        <div class="col-12"><label class="form-label d-block">{{ __('projects.'.$field) }}
            <textarea class="form-control" rows="4" name="config[projects][{{ $projectIndex }}][{{ $field }}]">{{ $project[$field] ?? '' }}</textarea>
        </label><div class="form-text">{{ __('projects.highlight_help') }}</div></div>
        @endforeach
        <div class="col-md-4"><label class="form-label">{{ __('projects.status') }}
            <select class="form-select" name="config[projects][{{ $projectIndex }}][status]">
                @foreach(['draft', 'published'] as $status)<option value="{{ $status }}" @selected(($project['status'] ?? 'draft') === $status)>{{ __('projects.'.$status) }}</option>@endforeach
            </select>
        </label></div>
        <div class="col-md-4"><label class="form-label">{{ __('projects.position') }}
            <input type="number" min="0" class="form-control" data-project-position name="config[projects][{{ $projectIndex }}][position]" value="{{ $project['position'] ?? 0 }}">
        </label></div>
        <div class="col-md-4"><input type="hidden" name="config[projects][{{ $projectIndex }}][featured]" value="0">
            <label><input type="checkbox" name="config[projects][{{ $projectIndex }}][featured]" value="1" @checked($project['featured'] ?? false)> {{ __('projects.featured') }}</label>
        </div>
    </div>
    <div class="project-seo border rounded p-3 mt-3">
        <h6 class="mb-3">{{ __('seo.project.heading') }}</h6>
        <div class="row g-3">
            <div class="col-lg-7">
                <label class="form-label d-block">{{ __('seo.project.seo_title') }}
                    <input class="form-control" data-project-seo-title name="config[projects][{{ $projectIndex }}][seo_title]" value="{{ $project['seo_title'] ?? '' }}" maxlength="255" data-seo-count="60">
                </label>
                <div class="form-text">{{ __('seo.project.seo_title_help') }}</div>
                <label class="form-label d-block mt-2">{{ __('seo.project.seo_description') }}
                    <textarea class="form-control" data-project-seo-description rows="2" name="config[projects][{{ $projectIndex }}][seo_description]" maxlength="1000" data-seo-count="160">{{ $project['seo_description'] ?? '' }}</textarea>
                </label>
                <div class="form-text">{{ __('seo.project.seo_description_help') }}</div>
                <input type="hidden" name="config[projects][{{ $projectIndex }}][robots_index]" value="0">
                <label class="mt-2"><input type="checkbox" name="config[projects][{{ $projectIndex }}][robots_index]" value="1" @checked($project['robots_index'] ?? true)> {{ __('seo.project.robots_index') }}</label>
            </div>
            <div class="col-lg-5">
                @include('admin.seo._preview', [
                    'scope' => '.project-item',
                    'titleSource' => '[data-project-seo-title]', 'titleFallback' => '[name$="[name]"]',
                    'descriptionSource' => '[data-project-seo-description]', 'descriptionFallback' => '[name$="[description]"]',
                    'url' => ($seoPreview = app(\App\Services\Seo\SeoService::class)->previewDefaults())['base'].'/'.$seoPreview['locale'].'/projects/…/'.($project['slug'] ?? '…'),
                ])
            </div>
        </div>
    </div>
    <h6 class="mt-3">{{ __('projects.images') }}</h6><p>{{ __('projects.images_help') }}</p>
    <div class="project-images">
        @for($imageIndex = 0; $imageIndex < 3; $imageIndex++)
        @php($image = $project['images'][$imageIndex] ?? [])
        <div class="project-image border rounded p-3 mb-2">
            <input type="hidden" data-image-id name="config[projects][{{ $projectIndex }}][images][{{ $imageIndex }}][id]" value="{{ $image['id'] ?? 'img-'.\Illuminate\Support\Str::uuid() }}">
            <input type="hidden" data-image-removal name="config[projects][{{ $projectIndex }}][images][{{ $imageIndex }}][remove]" value="{{ !empty($image['remove']) ? 1 : 0 }}">
            @if(!empty($image['path']))<img src="{{ $mediaUrl($image['path']) }}" alt="" style="max-width:180px;max-height:110px" class="mb-2">@endif
            <label class="form-label">{{ __('projects.photo') }} <span data-image-number>{{ $imageIndex + 1 }}</span>
                <input class="form-control" type="file" accept="image/jpeg,image/png,image/webp,image/gif" name="config[projects][{{ $projectIndex }}][images][{{ $imageIndex }}][file]">
            </label>
            <label class="form-label">{{ __('projects.alt') }}
                <input class="form-control" name="config[projects][{{ $projectIndex }}][images][{{ $imageIndex }}][alt]" value="{{ $image['alt'] ?? '' }}" maxlength="255">
            </label>
            <div class="d-flex gap-2 mt-2">
                <button class="btn btn-sm btn-light" type="button" data-image-up>{{ __('projects.move_up') }}</button>
                <button class="btn btn-sm btn-light" type="button" data-image-down>{{ __('projects.move_down') }}</button>
                <button class="btn btn-sm btn-outline-danger" type="button" data-image-remove>{{ __('projects.remove') }}</button>
            </div>
        </div>
        @endfor
    </div>
    @foreach(\App\Services\Widgets\Types\ProjectsWidgetTypeService::VIDEO_FIELDS as $videoField)
    <h6 class="mt-3">{{ __('projects.'.$videoField) }}</h6><p>{{ __('projects.'.$videoField.'_help', ['size' => config('widgets.hero_video_max_kb')]) }}</p>
    <div class="project-video border rounded p-3 mb-2">
        <input type="hidden" data-video-removal name="config[projects][{{ $projectIndex }}][{{ $videoField }}][remove]" value="0">
        @if(!empty($project[$videoField]['path']))<video src="{{ $mediaUrl($project[$videoField]['path']) }}" controls muted preload="metadata" style="max-width:320px;max-height:180px" class="d-block mb-2"></video>@endif
        <label class="form-label d-block">{{ __('projects.upload_video') }}
            <input class="form-control" type="file" accept=".mp4,.webm,.mov,video/mp4,video/webm,video/quicktime" name="config[projects][{{ $projectIndex }}][{{ $videoField }}][file]">
        </label>
        <button class="btn btn-sm btn-outline-danger mt-2" type="button" data-video-remove>{{ __('projects.remove_video') }}</button>
    </div>
    @endforeach
    </details>
    <div class="d-flex flex-wrap gap-2 mt-3">
        @if(!empty($project['id']))<a class="btn btn-outline-primary" href="#widgetTranslationTabs">{{ __('projects.translate_item') }}</a>@endif
        <button type="button" class="btn btn-light" data-project-up>{{ __('projects.move_up') }}</button>
        <button type="button" class="btn btn-light" data-project-down>{{ __('projects.move_down') }}</button>
        <button type="button" class="btn btn-outline-danger" data-project-remove>{{ __('projects.remove_item') }}</button>
    </div>
</fieldset>
