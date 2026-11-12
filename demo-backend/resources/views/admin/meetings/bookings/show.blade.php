@extends('layouts.master')
@section('content')
@php
  $local = fn ($time) => $time->copy()->setTimezone($timezone)->locale(app()->getLocale());
  $start = $local($booking->starts_at);
  $type = $booking->meetingType;
  $isOnline = $booking->format === \App\Enums\MeetingFormat::Online;
  $notify = fn ($id) => '<div class="form-check mt-2"><input type="hidden" name="notify" value="0"><input type="checkbox" class="form-check-input" name="notify" value="1" id="'.$id.'" checked><label class="form-check-label" for="'.$id.'">'.e(__('meetings.notify_visitor')).'</label></div>';
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="fs-18 mb-1">{{ $booking->name }} · {{ $type?->name }}</h1>
    <div class="d-flex gap-2 align-items-center">
      @include('admin.meetings.bookings._format-badge', ['format' => $booking->format])
      @include('admin.meetings.bookings._status-badge', ['status' => $booking->status])
      <span class="text-muted small">{{ __('meetings.mail.reference') }}: {{ $booking->reference }}</span>
    </div>
  </div>
  <a href="{{ route('admin.meetings.bookings.index') }}" class="btn btn-light">{{ __('meetings.back') }}</a>
</div>

@include('admin.meetings._alerts')
@if($errors->any() && ! $errors->has('booking'))
  <div class="alert alert-danger">@foreach($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
@endif

<div class="row">
  <div class="col-xl-7">
    <div class="card">
      <div class="card-header"><div class="card-title">{{ __('meetings.booking_details') }}</div></div>
      <div class="card-body">
        <table class="table mb-0">
          <tr><th style="width: 35%;">{{ __('meetings.mail.when') }}</th><td>{{ $start->isoFormat('dddd, LL') }}, {{ $start->isoFormat('LT') }}–{{ $local($booking->ends_at)->isoFormat('LT') }} ({{ $timezone }})
            @if($booking->visitor_timezone !== $timezone)<div class="text-muted small">{{ __('meetings.visitor_time', ['time' => $booking->starts_at->copy()->setTimezone($booking->visitor_timezone)->isoFormat('LT'), 'timezone' => $booking->visitor_timezone]) }}</div>@endif
          </td></tr>
          <tr><th>{{ __('meetings.email') }}</th><td><a href="mailto:{{ $booking->email }}">{{ $booking->email }}</a></td></tr>
          @if($booking->phone)<tr><th>{{ __('meetings.phone') }}</th><td>{{ $booking->phone }}</td></tr>@endif
          @if($booking->company)<tr><th>{{ __('meetings.company') }}</th><td>{{ $booking->company }}</td></tr>@endif
          <tr><th>{{ __('meetings.language') }}</th><td>{{ strtoupper($booking->locale) }}</td></tr>
          @if($booking->project_details)<tr><th>{{ __('meetings.project_details') }}</th><td style="white-space: pre-wrap;">{{ $booking->project_details }}</td></tr>@endif
          @if($booking->cancel_reason)<tr><th>{{ __('meetings.cancel_reason') }}</th><td>{{ $booking->cancel_reason }}</td></tr>@endif
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">{{ $isOnline ? __('meetings.section_online') : __('meetings.section_in_person') }}</div></div>
      <div class="card-body">
        @if($isOnline)
          <p class="mb-2">{{ __('meetings.video_provider') }}: {{ $location['provider'] ?? __('meetings.none') }}</p>
          @if($location['link_ready'])
            <p class="mb-2"><a href="{{ $location['url'] }}" target="_blank" rel="noopener">{{ $location['url'] }}</a></p>
          @else
            <div class="alert alert-warning py-2">{{ __('meetings.link_missing_admin') }}</div>
          @endif
          @if($changeable)
            <form method="POST" action="{{ route('admin.meetings.bookings.video-link', $booking->id) }}" class="d-flex gap-2 flex-wrap">
              @csrf
              <input type="url" name="video_url" class="form-control" style="max-width: 420px;" placeholder="https://" value="{{ old('video_url', $booking->video_url) }}" required maxlength="2048">
              <button class="btn btn-primary">{{ $location['link_ready'] ? __('meetings.replace_link') : __('meetings.set_link') }}</button>
            </form>
            <div class="form-text">{{ __('meetings.set_link_help') }}</div>
          @endif
        @else
          <p class="mb-1 fw-semibold">{{ $location['name'] }}</p>
          <p class="mb-1">{{ $location['address'] }}</p>
          @if($location['maps_url'])<p class="mb-0"><a href="{{ $location['maps_url'] }}" target="_blank" rel="noopener">{{ __('meetings.map_preview') }}</a></p>@endif
        @endif
        @foreach($location['instructions'] as $instruction)
          <div class="border-start border-3 ps-2 mt-2 small text-muted" style="white-space: pre-wrap;">{{ $instruction }}</div>
        @endforeach
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">{{ __('meetings.timeline') }}</div></div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          @foreach($booking->events->sortByDesc('id') as $event)
            <li class="mb-2">
              <span class="text-muted small">{{ $local($event->created_at)->isoFormat('ll LT') }}</span>
              · {{ __('meetings.event_'.$event->type) }}
              @if(isset($event->payload['kind'])) ({{ __('meetings.mail_kind_'.$event->payload['kind']) }})@endif
              @if($event->user) · <span class="text-muted small">{{ $event->user->name }}</span>@endif
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
    @if($changeable)
      <div class="card">
        <div class="card-header"><div class="card-title">{{ __('meetings.actions') }}</div></div>
        <div class="card-body d-flex flex-wrap gap-2">
          @if($booking->status === 'pending_approval')
            <form method="POST" action="{{ route('admin.meetings.bookings.confirm', $booking->id) }}">@csrf<button class="btn btn-success">{{ __('meetings.confirm_booking') }}</button></form>
          @endif
          <form method="POST" action="{{ route('admin.meetings.bookings.resend', $booking->id) }}">@csrf<button class="btn btn-light">{{ __('meetings.resend_confirmation') }}</button></form>
        </div>
        <div class="card-footer">
          <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <span class="small">
              {{ __('meetings.host_calendar') }}:
              <span class="badge {{ ['synced' => 'bg-success', 'failed' => 'bg-danger'][$booking->calendar_sync_status] ?? 'bg-secondary' }}">{{ __('meetings.sync_'.$booking->calendar_sync_status) }}</span>
            </span>
            @if($booking->calendar_sync_status !== 'synced' || $booking->video_link_status === 'failed')
              <form method="POST" action="{{ route('admin.meetings.bookings.calendar-sync', $booking->id) }}">@csrf<button class="btn btn-sm btn-outline-primary">{{ __('meetings.retry_sync') }}</button></form>
            @endif
          </div>
          @if($booking->calendar_sync_error)<div class="text-danger small mt-1">{{ $booking->calendar_sync_error }}</div>@endif
        </div>
      </div>

      @if(count($type->allowedFormats()) > 1 || ! $isOnline)
        <div class="card">
          <div class="card-header"><div class="card-title">{{ __('meetings.change_format') }}</div></div>
          <div class="card-body">
            <form method="POST" action="{{ route('admin.meetings.bookings.format', $booking->id) }}">
              @csrf
              <label class="form-label">{{ __('meetings.format') }}</label>
              <select name="format" class="form-select mb-2">
                @foreach($type->allowedFormats() as $option)
                  <option value="{{ $option->value }}" @selected(old('format', $booking->format->value) === $option->value)>{{ $option->label() }}</option>
                @endforeach
              </select>
              @if($type->allows(\App\Enums\MeetingFormat::InPerson))
                <label class="form-label">{{ __('meetings.office') }}</label>
                <select name="office_location_id" class="form-select mb-2">
                  <option value="">{{ __('meetings.type_office') }}</option>
                  @foreach($offices as $office)
                    <option value="{{ $office->id }}" @selected((int) old('office_location_id', $booking->office_location_id) === $office->id)>{{ $office->name }} — {{ $office->city }}</option>
                  @endforeach
                </select>
              @endif
              <label class="form-label">{{ __('meetings.new_time') }}</label>
              <input type="datetime-local" name="start" class="form-control" value="{{ old('start', $start->format('Y-m-d\TH:i')) }}">
              <div class="form-text">{{ __('meetings.change_format_help', ['timezone' => $timezone]) }}</div>
              {!! $notify('notify-format') !!}
              <button class="btn btn-primary mt-3">{{ __('meetings.change_format') }}</button>
            </form>
          </div>
        </div>
      @endif

      <div class="card">
        <div class="card-header"><div class="card-title">{{ __('meetings.reschedule') }}</div></div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.meetings.bookings.reschedule', $booking->id) }}">
            @csrf
            <label class="form-label">{{ __('meetings.new_time') }} ({{ $timezone }})</label>
            <input type="datetime-local" name="start" class="form-control" value="{{ $start->format('Y-m-d\TH:i') }}" required>
            {!! $notify('notify-reschedule') !!}
            <button class="btn btn-primary mt-3">{{ __('meetings.reschedule') }}</button>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title">{{ __('meetings.cancel_booking') }}</div></div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.meetings.bookings.cancel', $booking->id) }}" onsubmit="return confirm(@js(__('meetings.confirm_cancel_booking')))">
            @csrf
            <label class="form-label">{{ __('meetings.cancel_reason') }}</label>
            <textarea name="reason" class="form-control" rows="2" maxlength="500"></textarea>
            <div class="form-text">{{ __('meetings.cancel_reason_help') }}</div>
            {!! $notify('notify-cancel') !!}
            <button class="btn btn-danger mt-3">{{ __('meetings.cancel_booking') }}</button>
          </form>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
