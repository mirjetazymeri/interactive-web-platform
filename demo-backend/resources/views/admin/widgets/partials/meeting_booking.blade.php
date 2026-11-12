@php
  $meetingBookingTypes = \App\Models\MeetingType::query()->forChannel((int) auth()->user()->current_channel_id)
      ->orderBy('position')->orderBy('name')->get();
  // Other widget types share field names like config[title]; only show this type's own values.
  $meetingBookingCfg = $type === 'meeting_booking' ? (array) $cfg : [];
  $meetingBookingSelected = array_map('intval', (array) ($meetingBookingCfg['meeting_types'] ?? []));
  $meetingBookingText = fn ($field) => $meetingBookingCfg[$field] ?? '';
@endphp
{{-- Meeting booking type config block --}}
<div id="cfg-meeting_booking" class="cfg d-none">
  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="ri-calendar-event-line text-secondary fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">{{ __('meetings.widget_types') }}</span>
  </div>
  <p class="text-muted fs-13">{{ __('meetings.widget_types_help') }}</p>

  @if($meetingBookingTypes->isEmpty())
    <div class="alert alert-info">{{ __('meetings.no_types') }} <a href="{{ route('admin.meetings.types.create') }}">{{ __('meetings.add_type') }}</a></div>
  @else
    <div class="row g-2 mb-4">
      @foreach($meetingBookingTypes as $meetingType)
        <div class="col-md-6">
          <label class="border rounded p-2 d-flex gap-2 align-items-start w-100">
            <input type="checkbox" class="form-check-input mt-1" name="config[meeting_types][]" value="{{ $meetingType->id }}"
                   @checked(in_array($meetingType->id, $meetingBookingSelected, true))>
            <span>
              <span class="fw-semibold d-block">{{ $meetingType->name }}</span>
              <span class="d-flex flex-wrap gap-1 mt-1">
                @foreach($meetingType->allowedFormats() as $format)
                  <span class="badge {{ $format === \App\Enums\MeetingFormat::Online ? 'bg-info-transparent text-info' : 'bg-warning-transparent text-warning' }}">{{ $format->label() }}</span>
                @endforeach
                @unless($meetingType->active)<span class="badge bg-secondary">{{ __('meetings.inactive') }}</span>@endunless
              </span>
            </span>
          </label>
        </div>
      @endforeach
    </div>
  @endif
  @error('config.meeting_types')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
  @error('config.meeting_types.*')<div class="text-danger small mb-3">{{ $message }}</div>@enderror

  <div class="d-flex align-items-center gap-2 mb-2">
    <i class="ri-text fs-16 text-muted"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">{{ __('meetings.widget_copy') }}</span>
  </div>
  <p class="text-muted fs-13">{{ __('meetings.widget_copy_help') }}</p>
  <div class="row g-3">
    @foreach(\App\Services\Widgets\Types\MeetingBookingWidgetTypeService::TEXT_FIELDS as $field)
      @php($long = in_array($field, \App\Services\Widgets\Types\MeetingBookingWidgetTypeService::LONG_FIELDS, true))
      <div class="{{ $long ? 'col-12' : 'col-md-4' }}">
        <label class="form-label">{{ __('meetings.widget_field.'.$field) }}</label>
        @if($long)
          <textarea name="config[{{ $field }}]" class="form-control" rows="2" maxlength="1000" placeholder="{{ __('meetings.widget.'.$field) }}">{{ $meetingBookingText($field) }}</textarea>
        @else
          <input name="config[{{ $field }}]" class="form-control" maxlength="255" value="{{ $meetingBookingText($field) }}" placeholder="{{ __('meetings.widget.'.$field) }}">
        @endif
        @error("config.$field")<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    @endforeach
  </div>
</div>
