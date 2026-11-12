<div id="cfg-projects" class="cfg d-none">
    <label class="form-label">{{ __('projects.variant') }}
        <select name="config[variant]" class="form-select">
            @foreach(['horizontal_scroll' => 'horizontal', 'vertical_scroll' => 'vertical'] as $variant => $label)
                <option value="{{ $variant }}" @selected(\App\Services\Widgets\Types\ProjectsWidgetTypeService::variant($cfg['variant'] ?? 'horizontal_scroll') === $variant)>{{ __('projects.'.$label) }}</option>
            @endforeach
        </select>
    </label>
    <p class="text-muted">{{ __('projects.nested_help') }}</p>
    <div id="projectItems">
        @foreach(($cfg['projects'] ?? []) as $projectIndex => $project)
            @include('admin.widgets.partials.project_item')
        @endforeach
    </div>
    <button class="btn btn-outline-primary" type="button" id="addProjectItem">{{ __('projects.add_item') }}</button>
    <button class="btn btn-primary" type="submit" name="_continue" value="1">{{ __('projects.save_continue') }}</button>
    <template id="projectItemTemplate">
        @include('admin.widgets.partials.project_item', ['projectIndex' => '__PROJECT__', 'project' => []])
    </template>
</div>

@include('admin.seo._preview_script')
