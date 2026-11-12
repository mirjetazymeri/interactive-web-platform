@extends('layouts.master')
@section('content')
@php
  $checkbox = fn ($name, $checked, $label, $help = null) => compact('name', 'checked', 'label', 'help');
  $provider = old('provider', $online['provider']);
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="fs-18 mb-1">{{ __('meetings.settings') }}</h1>
    <div class="text-muted small">{{ __('meetings.current_channel') }}: {{ auth()->user()->currentChannel?->name ?? 'N/A' }}</div>
  </div>
</div>

@include('admin.meetings._alerts')

<div class="card">
  <div class="card-header"><div class="card-title">{{ __('meetings.host_calendar') }}</div></div>
  <div class="card-body">
    @if(! $connection)
      <p class="mb-2">{{ __('meetings.calendar_intro') }}</p>
      <p class="text-muted small mb-3">{{ __('meetings.calendar_redirect_help') }} <code>{{ $redirectUri }}</code></p>
      <a href="{{ route('admin.meetings.calendar.connect') }}" class="btn btn-primary"><i class="ri-microsoft-line me-1"></i>{{ __('meetings.calendar_connect') }}</a>
    @else
      <p class="mb-2">
        <span class="badge {{ $connection->isConnected() ? 'bg-success' : 'bg-danger' }}">{{ __('meetings.calendar_status_'.$connection->status) }}</span>
        {{ $connection->account_email }}
      </p>
      @if($connection->last_error)<div class="alert alert-warning py-2">{{ $connection->last_error }}</div>@endif

      @if($calendarsError)
        <div class="alert alert-warning py-2">{{ $calendarsError }}</div>
      @elseif($calendars)
        <form method="POST" action="{{ route('admin.meetings.calendar.select') }}" class="d-flex flex-wrap gap-2 align-items-end mb-3">
          @csrf @method('PUT')
          <div>
            <label class="form-label">{{ __('meetings.calendar_host') }}</label>
            <select name="calendar_id" class="form-select">
              @foreach($calendars as $calendar)
                <option value="{{ $calendar['id'] }}" @selected($connection->calendar_id === $calendar['id'] || ($connection->calendar_id === \App\Services\Meetings\Calendar\MicrosoftCalendarGateway::DEFAULT_CALENDAR && $calendar['primary']))>{{ $calendar['name'] }}</option>
              @endforeach
            </select>
          </div>
          <button class="btn btn-secondary">{{ __('meetings.save') }}</button>
        </form>
        <p class="text-muted small">{{ __('meetings.calendar_host_help') }}</p>
      @endif

      <div class="d-flex gap-2">
        @if(! $connection->isConnected())
          <a href="{{ route('admin.meetings.calendar.connect') }}" class="btn btn-primary">{{ __('meetings.calendar_reconnect') }}</a>
        @endif
        <form method="POST" action="{{ route('admin.meetings.calendar.disconnect') }}" onsubmit="return confirm(@js(__('meetings.calendar_confirm_disconnect')))">
          @csrf @method('DELETE')
          <button class="btn btn-outline-danger">{{ __('meetings.calendar_disconnect') }}</button>
        </form>
      </div>
    @endif
  </div>
</div>

<form method="POST" action="{{ route('admin.meetings.settings.update') }}">
  @csrf @method('PUT')

  <div class="card">
    <div class="card-header"><div class="card-title">{{ __('meetings.section_general') }}</div></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">{{ __('meetings.timezone') }} *</label>
          <select name="timezone" class="form-select" required>
            @foreach(\DateTimeZone::listIdentifiers() as $zone)
              <option value="{{ $zone }}" @selected(old('timezone', $timezone) === $zone)>{{ $zone }}</option>
            @endforeach
          </select>
          <div class="form-text">{{ __('meetings.timezone_help') }}</div>
          @error('timezone')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ __('meetings.organizer_name') }}</label>
          <input name="organizer_name" class="form-control" value="{{ old('organizer_name', $organizerName) }}" maxlength="150">
          @error('organizer_name')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ __('meetings.admin_recipients') }}</label>
          <input name="admin_recipients" class="form-control" value="{{ old('admin_recipients', $adminRecipients) }}">
          <div class="form-text">{{ __('meetings.admin_recipients_help') }}</div>
          @error('admin_recipients')<div class="text-danger small">{{ $message }}</div>@enderror
          @error('admin_recipients.*')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        @foreach([
          $checkbox('require_approval', $requireApproval, __('meetings.require_approval')),
          $checkbox('fail_closed_when_calendar_unreachable', $failClosed, __('meetings.fail_closed'), __('meetings.fail_closed_help')),
        ] as $box)
          <div class="col-md-6">
            <div class="form-check">
              <input type="hidden" name="{{ $box['name'] }}" value="0">
              <input type="checkbox" class="form-check-input" id="setting-{{ $box['name'] }}" name="{{ $box['name'] }}" value="1" @checked(old($box['name'], $box['checked']))>
              <label class="form-check-label" for="setting-{{ $box['name'] }}">{{ $box['label'] }}</label>
              @if($box['help'])<div class="form-text">{{ $box['help'] }}</div>@endif
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">{{ __('meetings.section_online_settings') }}</div></div>
    <div class="card-body">
      <div class="alert {{ $calendarConnected ? 'alert-success' : 'alert-warning' }} py-2">
        {{ $calendarConnected ? __('meetings.calendar_connected') : __('meetings.calendar_not_connected') }}
      </div>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">{{ __('meetings.default_provider') }} *</label>
          <select name="provider" class="form-select" required>
            @foreach($providers as $option)
              <option value="{{ $option->value }}" @selected($provider === $option->value)>{{ $option->label() }}</option>
            @endforeach
          </select>
          @error('provider')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        @foreach([
          $checkbox('auto_create', $online['auto_create'], __('meetings.auto_create'), __('meetings.auto_create_help')),
          $checkbox('allow_manual_after_booking', $online['allow_manual_after_booking'], __('meetings.allow_manual'), __('meetings.allow_manual_help')),
        ] as $box)
          <div class="col-md-4">
            <div class="form-check mt-md-4">
              <input type="hidden" name="{{ $box['name'] }}" value="0">
              <input type="checkbox" class="form-check-input" id="setting-{{ $box['name'] }}" name="{{ $box['name'] }}" value="1" @checked(old($box['name'], $box['checked']))>
              <label class="form-check-label" for="setting-{{ $box['name'] }}">{{ $box['label'] }}</label>
              @if($box['help'])<div class="form-text">{{ $box['help'] }}</div>@endif
            </div>
          </div>
        @endforeach

        @foreach($providers as $option)
          <div class="col-md-6">
            <label class="form-label">{{ __('meetings.static_url', ['provider' => $option->label()]) }}</label>
            <input type="url" name="static_urls[{{ $option->value }}]" class="form-control" placeholder="https://" maxlength="2048"
                   value="{{ old('static_urls.'.$option->value, $online['static_urls'][$option->value] ?? '') }}">
            @error('static_urls.'.$option->value)<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
        @endforeach
        <div class="col-12"><div class="form-text mt-0">{{ __('meetings.static_url_help') }}</div></div>

        @foreach($locales as $locale)
          <div class="col-md-6">
            <label class="form-label">{{ __('meetings.joining_instructions') }} ({{ config("locales.$locale.name") ?? strtoupper($locale) }})</label>
            <textarea name="joining_instructions[{{ $locale }}]" class="form-control" rows="3" maxlength="5000">{{ old('joining_instructions.'.$locale, $joiningInstructions[$locale] ?? '') }}</textarea>
            @error('joining_instructions.'.$locale)<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <button class="btn btn-primary">{{ __('meetings.save') }}</button>
</form>
@endsection
