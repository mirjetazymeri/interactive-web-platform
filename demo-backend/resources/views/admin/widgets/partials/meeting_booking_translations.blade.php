@php
  $meetingBookingBase = $type === 'meeting_booking' ? (array) ($widgetConfig ?? []) : [];
@endphp
<div class="col-12 widget-translation-type {{ $type === 'meeting_booking' ? '' : 'd-none' }}" data-widget-translation-type="meeting_booking">
  <div class="border rounded p-3 bg-light-subtle">
    <div class="fw-semibold mb-2 fs-13 text-muted text-uppercase"><i class="ri-calendar-event-line me-1"></i> {{ __('meetings.widget_copy') }}</div>
    <p class="text-muted fs-13">{{ __('meetings.widget_translation_help') }}</p>
    <div class="row g-2">
      @foreach(\App\Services\Widgets\Types\MeetingBookingWidgetTypeService::TEXT_FIELDS as $field)
        @php
          $long = in_array($field, \App\Services\Widgets\Types\MeetingBookingWidgetTypeService::LONG_FIELDS, true);
          $placeholder = ($meetingBookingBase[$field] ?? '') ?: __('meetings.widget.'.$field, [], $locale);
          $value = old("translations.$index.$field", $translationConfig[$field] ?? '');
        @endphp
        <div class="{{ $long ? 'col-12' : 'col-md-4' }}">
          <label class="form-label fs-12 text-muted w-100">{{ __('meetings.widget_field.'.$field) }}
            @if($long)
              <textarea name="translations[{{ $index }}][{{ $field }}]" class="form-control form-control-sm" rows="2" maxlength="1000" placeholder="{{ $placeholder }}">{{ $value }}</textarea>
            @else
              <input name="translations[{{ $index }}][{{ $field }}]" class="form-control form-control-sm" maxlength="255" value="{{ $value }}" placeholder="{{ $placeholder }}">
            @endif
          </label>
        </div>
      @endforeach
    </div>
  </div>
</div>
