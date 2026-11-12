@php
  $type = $type ?? null;
  $value = fn ($field, $default = null) => old($field, $type?->getAttribute($field) instanceof \BackedEnum ? $type->getAttribute($field)->value : ($type?->getAttribute($field) ?? $default));
  $mode = $value('location_mode', \App\Enums\MeetingLocationMode::Online->value);
  $translation = fn ($locale, $field) => old("translations.$locale.$field", optional($type?->translations->firstWhere('locale', $locale))->content[$field] ?? '');
  $number = function ($field, $label, $help = null, $required = false, $min = 0, $max = 240, $default = null) use ($value) {
      return compact('field', 'label', 'help', 'required', 'min', 'max', 'default') + ['value' => $value($field, $default)];
  };
@endphp

<h2 class="fs-15 mb-3">{{ __('meetings.section_basics') }}</h2>
<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">{{ __('meetings.name') }} *</label>
    <input name="name" class="form-control" value="{{ $value('name') }}" required maxlength="255">
    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-3">
    <label class="form-label">{{ __('meetings.slug') }}</label>
    <input name="slug" class="form-control" value="{{ $value('slug') }}" maxlength="100">
    <div class="form-text">{{ __('meetings.slug_help') }}</div>
    @error('slug')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-2">
    <label class="form-label">{{ __('meetings.position') }}</label>
    <input type="number" min="0" name="position" class="form-control" value="{{ $value('position', 0) }}">
    @error('position')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-1 d-flex align-items-end">
    <div class="form-check mb-2">
      <input type="hidden" name="active" value="0">
      <input type="checkbox" class="form-check-input" id="type-active" name="active" value="1" @checked(old('active', $type->active ?? true))>
      <label class="form-check-label" for="type-active">{{ __('meetings.active') }}</label>
    </div>
  </div>
  <div class="col-12">
    <label class="form-label">{{ __('meetings.description') }}</label>
    <textarea name="description" class="form-control" rows="3" maxlength="5000">{{ $value('description') }}</textarea>
    @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
</div>

<h2 class="fs-15 mt-4 mb-3">{{ __('meetings.section_formats') }}</h2>
<div class="row g-3">
  <div class="col-md-7">
    <label class="form-label d-block">{{ __('meetings.location_mode') }} *</label>
    <div class="btn-group flex-wrap" role="radiogroup">
      @foreach($modes as $option)
        <input type="radio" class="btn-check" name="location_mode" id="mode-{{ $option->value }}" value="{{ $option->value }}" data-meeting-mode @checked($mode === $option->value)>
        <label class="btn btn-outline-primary" for="mode-{{ $option->value }}">{{ $option->label() }}</label>
      @endforeach
    </div>
    @error('location_mode')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-5">
    <label class="form-label">{{ __('meetings.default_format') }} *</label>
    <select name="default_format" class="form-select" data-meeting-default-format>
      @foreach($formats as $format)
        <option value="{{ $format->value }}" @selected($value('default_format', $formats[0]->value) === $format->value)>{{ $format->label() }}</option>
      @endforeach
    </select>
    <div class="form-text">{{ __('meetings.default_format_help') }}</div>
    @error('default_format')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
</div>

<h2 class="fs-15 mt-4 mb-3">{{ __('meetings.section_schedule') }}</h2>
<div class="row g-3">
  @foreach([
    $number('duration_minutes', __('meetings.duration'), null, true, 5, 480, $defaults['duration_minutes']),
    $number('buffer_before_minutes', __('meetings.buffer_before'), null, false, 0, 240, 0),
    $number('buffer_after_minutes', __('meetings.buffer_after'), null, false, 0, 240, 0),
    $number('slot_interval_minutes', __('meetings.slot_interval'), null, true, 5, 240, $defaults['slot_interval_minutes']),
    $number('min_notice_minutes', __('meetings.min_notice'), null, true, 0, 43200, $defaults['min_notice_minutes']),
    $number('max_advance_days', __('meetings.max_advance'), null, true, 1, 365, $defaults['max_advance_days']),
  ] as $input)
    <div class="col-md-4 col-lg-2">
      <label class="form-label">{{ $input['label'] }}@if($input['required']) *@endif</label>
      <input type="number" name="{{ $input['field'] }}" class="form-control" min="{{ $input['min'] }}" max="{{ $input['max'] }}" value="{{ $input['value'] }}" @required($input['required'])>
      @error($input['field'])<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
  @endforeach
</div>

@foreach($formats as $format)
  @php($prefix = $format->value)
  <fieldset class="border rounded p-3 mt-4" data-meeting-format="{{ $format->value }}">
    <legend class="fs-15 float-none w-auto px-2 mb-0">{{ __('meetings.section_'.$format->value) }}</legend>
    <p class="text-muted small">{{ __('meetings.override_help') }}</p>
    <div class="row g-3">
      @foreach([
        $number($prefix.'_duration_minutes', __('meetings.duration'), null, false, 5, 480),
        $number($prefix.'_buffer_before_minutes', __('meetings.buffer_before')),
        $number($prefix.'_buffer_after_minutes', __('meetings.buffer_after')),
      ] as $input)
        <div class="col-md-4">
          <label class="form-label">{{ $input['label'] }}</label>
          <input type="number" name="{{ $input['field'] }}" class="form-control" min="{{ $input['min'] }}" max="{{ $input['max'] }}" value="{{ $input['value'] }}">
          @error($input['field'])<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
      @endforeach

      @if($format === \App\Enums\MeetingFormat::Online)
        <div class="col-md-6">
          <label class="form-label">{{ __('meetings.video_provider') }}</label>
          <select name="video_provider" class="form-select">
            <option value="">{{ __('meetings.use_global_provider', ['name' => $globalProvider->label()]) }}</option>
            @foreach(\App\Enums\VideoProvider::cases() as $provider)
              <option value="{{ $provider->value }}" @selected($value('video_provider') === $provider->value)>{{ $provider->label() }}</option>
            @endforeach
          </select>
          @error('video_provider')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
      @else
        <div class="col-md-6">
          <label class="form-label">{{ __('meetings.office') }}</label>
          <select name="office_location_id" class="form-select">
            <option value="">{{ $defaultOffice ? __('meetings.use_default_office', ['name' => $defaultOffice->name]) : __('meetings.no_default_office') }}</option>
            @foreach($offices as $office)
              <option value="{{ $office->id }}" @selected((int) $value('office_location_id') === $office->id)>{{ $office->name }} — {{ $office->city }}</option>
            @endforeach
          </select>
          @error('office_location_id')<div class="text-danger small">{{ $message }}</div>@enderror
          @if($offices->isEmpty())
            <div class="form-text"><a href="{{ route('admin.meetings.locations.create') }}">{{ __('meetings.add_location') }}</a></div>
          @endif
        </div>
      @endif

      <div class="col-12">
        <label class="form-label">{{ __('meetings.'.$prefix.'_instructions') }}</label>
        <textarea name="{{ $prefix }}_instructions" class="form-control" rows="2" maxlength="5000">{{ $value($prefix.'_instructions') }}</textarea>
        <div class="form-text">{{ __('meetings.instructions_help') }}</div>
        @error($prefix.'_instructions')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    </div>
  </fieldset>
@endforeach

@if($translationLocales)
  <h2 class="fs-15 mt-4 mb-2">{{ __('meetings.translations') }}</h2>
  <p class="text-muted small mb-2">{{ __('meetings.translation_help') }}</p>
  <ul class="nav nav-tabs mb-0 border-bottom-0" role="tablist">
    @foreach($translationLocales as $locale)
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#type-translation-{{ $locale }}" type="button" role="tab">
          {{ config("locales.$locale.name") ?? strtoupper($locale) }}
        </button>
      </li>
    @endforeach
  </ul>
  <div class="tab-content border rounded-bottom p-3">
    @foreach($translationLocales as $locale)
      <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="type-translation-{{ $locale }}" role="tabpanel">
        <div class="row g-3">
          @foreach(\App\Models\MeetingType::TEXT_FIELDS as $field)
            <div class="{{ $field === 'name' ? 'col-md-6' : 'col-12' }}" @if(str_ends_with($field, '_instructions')) data-meeting-format="{{ str_replace('_instructions', '', $field) }}" @endif>
              <label class="form-label">{{ __('meetings.'.$field) }}</label>
              @if($field === 'name')
                <input name="translations[{{ $locale }}][name]" class="form-control" value="{{ $translation($locale, 'name') }}" maxlength="255">
              @else
                <textarea name="translations[{{ $locale }}][{{ $field }}]" class="form-control" rows="2" maxlength="5000">{{ $translation($locale, $field) }}</textarea>
              @endif
              @error("translations.$locale.$field")<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>
@endif

@section('scripts')
<script>
  // Show only the sections and default-format options the chosen location mode offers.
  (function () {
    const formatsByMode = @json(collect($modes)->mapWithKeys(fn ($m) => [$m->value => array_map(fn ($f) => $f->value, $m->formats())]));
    const select = document.querySelector('[data-meeting-default-format]');
    function sync() {
      const mode = document.querySelector('[data-meeting-mode]:checked')?.value;
      const allowed = formatsByMode[mode] || [];
      document.querySelectorAll('[data-meeting-format]').forEach((el) => el.classList.toggle('d-none', !allowed.includes(el.dataset.meetingFormat)));
      Array.from(select.options).forEach((option) => { option.disabled = !allowed.includes(option.value); });
      if (!allowed.includes(select.value)) select.value = allowed[0];
    }
    document.querySelectorAll('[data-meeting-mode]').forEach((input) => input.addEventListener('change', sync));
    sync();
  })();
</script>
@endsection
