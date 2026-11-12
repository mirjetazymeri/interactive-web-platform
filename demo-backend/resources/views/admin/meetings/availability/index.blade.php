@extends('layouts.master')
@section('content')
@php
  $scopeName = function (?int $typeId, ?\App\Enums\MeetingFormat $f) use ($types) {
      $typeName = $typeId ? ($types->firstWhere('id', $typeId)?->name ?? '') : __('meetings.scope_global');
      return $typeName.' · '.($f ? $f->label() : __('meetings.all_formats'));
  };
  $scopeFormats = $type ? $type->allowedFormats() : \App\Enums\MeetingFormat::cases();
  $scopeLink = fn ($f) => route('admin.meetings.availability.index', array_filter(['type' => $type?->id, 'format' => $f?->value]));
  $oldHours = old('hours');
  $hoursFor = fn ($weekday) => is_array($oldHours) ? array_values($oldHours[$weekday] ?? []) : ($hours[$weekday] ?? []);
  $kind = old('kind', 'closed_day');
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="fs-18 mb-1">{{ __('meetings.availability') }}</h1>
    <div class="text-muted small">{{ __('meetings.current_channel') }}: {{ auth()->user()->currentChannel?->name ?? 'N/A' }} · {{ __('meetings.timezone') }}: {{ $timezone }}</div>
  </div>
</div>

@include('admin.meetings._alerts')

<div class="card">
  <div class="card-header"><div class="card-title">{{ __('meetings.weekly_hours') }}</div></div>
  <div class="card-body">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end mb-3">
      <div>
        <label class="form-label">{{ __('meetings.scope') }}</label>
        <select name="type" class="form-select" onchange="this.form.submit()">
          <option value="">{{ __('meetings.scope_global') }}</option>
          @foreach($types as $option)
            <option value="{{ $option->id }}" @selected($type?->id === $option->id)>{{ $option->name }}</option>
          @endforeach
        </select>
      </div>
      <noscript><button class="btn btn-secondary">{{ __('meetings.show') }}</button></noscript>
    </form>

    <ul class="nav nav-pills mb-3">
      @foreach([null, ...$scopeFormats] as $option)
        <li class="nav-item">
          <a class="nav-link {{ $format === $option ? 'active' : '' }}" href="{{ $scopeLink($option) }}">{{ $option ? $option->label() : __('meetings.all_formats') }}</a>
        </li>
      @endforeach
    </ul>

    <p class="text-muted small mb-2">{{ __('meetings.weekly_hours_help', ['scope' => $scopeName($type?->id, $format), 'timezone' => $timezone]) }}</p>
    @if($type)
      <ul class="small mb-3">
        @foreach($effective as $formatValue => $scope)
          @php($f = \App\Enums\MeetingFormat::from($formatValue))
          <li>{{ $scope ? __('meetings.uses_scope', ['format' => $f->label(), 'scope' => $scopeName($scope[0], $scope[1])]) : __('meetings.no_hours_apply', ['format' => $f->label()]) }}</li>
        @endforeach
      </ul>
    @endif

    @error('format')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @error('meeting_type_id')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @error('hours')<div class="alert alert-danger">{{ $message }}</div>@enderror

    <form method="POST" action="{{ route('admin.meetings.availability.update') }}" data-hours-form>
      @csrf @method('PUT')
      <input type="hidden" name="meeting_type_id" value="{{ $type?->id }}">
      <input type="hidden" name="format" value="{{ $format?->value }}">

      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <tbody>
          @foreach($weekdays as $weekday => $dayName)
            <tr>
              <th class="text-nowrap" style="width: 1%;">{{ $dayName }}</th>
              <td>
                <div class="d-flex flex-wrap gap-2" data-hours-day="{{ $weekday }}">
                  @foreach($hoursFor($weekday) as $i => $range)
                    @include('admin.meetings.availability._range', ['weekday' => $weekday, 'index' => $i, 'range' => $range])
                  @endforeach
                  <span class="text-muted small align-self-center" data-hours-closed @if($hoursFor($weekday)) hidden @endif>{{ __('meetings.closed') }}</span>
                </div>
                @error("hours.$weekday")<div class="text-danger small">{{ $message }}</div>@enderror
                @foreach($errors->get("hours.$weekday.*") as $messages)
                  @foreach($messages as $message)<div class="text-danger small">{{ $message }}</div>@endforeach
                @endforeach
              </td>
              <td class="text-end" style="width: 1%;">
                <button type="button" class="btn btn-sm btn-light text-nowrap" data-hours-add="{{ $weekday }}">{{ __('meetings.add_hours') }}</button>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      <button class="btn btn-primary mt-3">{{ __('meetings.save_hours') }}</button>
    </form>
  </div>
</div>

<template id="hours-range-template">
  @include('admin.meetings.availability._range', ['weekday' => '__DAY__', 'index' => '__INDEX__', 'range' => ['start' => '', 'end' => '']])
</template>

<div class="card">
  <div class="card-header"><div class="card-title">{{ __('meetings.exceptions') }}</div></div>
  <div class="card-body">
    <form method="POST" action="{{ route('admin.meetings.availability.exceptions.store') }}" class="row g-3 align-items-end mb-4">
      @csrf
      <div class="col-md-2">
        <label class="form-label">{{ __('meetings.date') }} *</label>
        <input type="date" name="date" class="form-control" value="{{ old('date') }}" required>
        @error('date')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('meetings.exception_kind') }} *</label>
        <select name="kind" class="form-select" data-exception-kind>
          @foreach(\App\Http\Requests\Admin\Meetings\AvailabilityExceptionRequest::KINDS as $option)
            <option value="{{ $option }}" @selected($kind === $option)>{{ __('meetings.kind_'.$option) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-1" data-exception-times>
        <label class="form-label">{{ __('meetings.from') }}</label>
        <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}">
        @error('start_time')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-1" data-exception-times>
        <label class="form-label">{{ __('meetings.to') }}</label>
        <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}">
        @error('end_time')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-2">
        <label class="form-label">{{ __('meetings.applies_to') }}</label>
        <select name="meeting_type_id" class="form-select">
          <option value="">{{ __('meetings.all_types') }}</option>
          @foreach($types as $option)
            <option value="{{ $option->id }}" @selected((int) old('meeting_type_id') === $option->id)>{{ $option->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">{{ __('meetings.format') }}</label>
        <select name="format" class="form-select">
          <option value="">{{ __('meetings.all_formats') }}</option>
          @foreach(\App\Enums\MeetingFormat::cases() as $option)
            <option value="{{ $option->value }}" @selected(old('format') === $option->value)>{{ $option->label() }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-1">
        <button class="btn btn-primary w-100">{{ __('meetings.add') }}</button>
      </div>
      <div class="col-12">
        <input name="note" class="form-control" maxlength="255" placeholder="{{ __('meetings.note') }}" value="{{ old('note') }}">
        @error('note')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    </form>

    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>{{ __('meetings.date') }}</th>
          <th>{{ __('meetings.exception_kind') }}</th>
          <th>{{ __('meetings.applies_to') }}</th>
          <th>{{ __('meetings.note') }}</th>
          <th class="text-end">{{ __('meetings.actions') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($exceptions as $exception)
        <tr>
          <td class="text-nowrap">{{ $exception->date->locale(app()->getLocale())->isoFormat('ddd, LL') }}</td>
          <td>
            @if(! $exception->start_time)
              <span class="badge bg-danger-transparent text-danger">{{ __('meetings.kind_closed_day') }}</span>
            @else
              <span class="badge {{ $exception->is_closed ? 'bg-danger-transparent text-danger' : 'bg-success-transparent text-success' }}">{{ __('meetings.kind_'.($exception->is_closed ? 'closed_hours' : 'open_hours')) }}</span>
              {{ substr($exception->start_time, 0, 5) }}–{{ substr($exception->end_time, 0, 5) }}
            @endif
          </td>
          <td>{{ $scopeName($exception->meeting_type_id, $exception->format) }}</td>
          <td>{{ $exception->note }}</td>
          <td class="text-end">
            <form method="POST" action="{{ route('admin.meetings.availability.exceptions.destroy', $exception->id) }}" class="d-inline" onsubmit="return confirm(@js(__('meetings.confirm_delete_exception')))">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">{{ __('meetings.delete') }}</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-center py-4">{{ __('meetings.no_exceptions') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">{{ __('meetings.preview') }}</div></div>
  <div class="card-body">
    @if($types->isEmpty())
      <p class="text-muted mb-0">{{ __('meetings.no_types') }}</p>
    @else
      <form method="GET" class="d-flex flex-wrap gap-2 align-items-end mb-3">
        <input type="hidden" name="type" value="{{ $type?->id }}">
        <input type="hidden" name="format" value="{{ $format?->value }}">
        <div>
          <label class="form-label">{{ __('meetings.meeting_types') }}</label>
          <select name="preview_type" class="form-select">
            @foreach($types as $option)
              <option value="{{ $option->id }}" @selected($preview['type']?->id === $option->id)>{{ $option->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="form-label">{{ __('meetings.format') }}</label>
          <select name="preview_format" class="form-select">
            @foreach(\App\Enums\MeetingFormat::cases() as $option)
              <option value="{{ $option->value }}" @selected($preview['format'] === $option)>{{ $option->label() }}</option>
            @endforeach
          </select>
        </div>
        <button class="btn btn-secondary">{{ __('meetings.show_preview') }}</button>
      </form>

      @if($preview['error'])
        <div class="alert alert-warning">{{ $preview['error'] }}</div>
      @elseif($preview['type'])
        <p class="text-muted small">{{ __('meetings.preview_help', ['type' => $preview['type']->name, 'format' => $preview['format']->label(), 'timezone' => $timezone]) }}</p>
        @forelse($preview['days'] as $date => $times)
          <div class="mb-2">
            <div class="fw-semibold small">{{ \Carbon\CarbonImmutable::parse($date)->locale(app()->getLocale())->isoFormat('dddd, LL') }}</div>
            <div class="d-flex flex-wrap gap-1">
              @foreach($times as $time)<span class="badge bg-light text-body border">{{ $time }}</span>@endforeach
            </div>
          </div>
        @empty
          <p class="text-muted mb-0">{{ __('meetings.preview_empty') }}</p>
        @endforelse
      @endif
    @endif
  </div>
</div>
@endsection

@section('scripts')
<script>
  (function () {
    // Weekly hours: add and remove time ranges per day.
    const template = document.getElementById('hours-range-template');
    const syncClosed = (day) => { day.querySelector('[data-hours-closed]').hidden = !!day.querySelector('[data-hours-range]'); };
    document.querySelectorAll('[data-hours-add]').forEach((button) => button.addEventListener('click', () => {
      const day = document.querySelector('[data-hours-day="' + button.dataset.hoursAdd + '"]');
      const html = template.innerHTML.replaceAll('__DAY__', button.dataset.hoursAdd).replaceAll('__INDEX__', Date.now());
      day.querySelector('[data-hours-closed]').insertAdjacentHTML('beforebegin', html);
      syncClosed(day);
      day.querySelector('[data-hours-range]:last-of-type input')?.focus();
    }));
    document.addEventListener('click', (event) => {
      const remove = event.target.closest('[data-hours-remove]');
      if (!remove) return;
      const day = remove.closest('[data-hours-day]');
      remove.closest('[data-hours-range]').remove();
      syncClosed(day);
    });

    // Exceptions: times only matter for partial closures and extra hours.
    const kind = document.querySelector('[data-exception-kind]');
    const syncKind = () => document.querySelectorAll('[data-exception-times]').forEach((el) => el.classList.toggle('d-none', kind.value === 'closed_day'));
    kind.addEventListener('change', syncKind);
    syncKind();
  })();
</script>
@endsection
