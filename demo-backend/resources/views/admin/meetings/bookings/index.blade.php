@extends('layouts.master')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="fs-18 mb-1">{{ __('meetings.bookings') }}</h1>
    <div class="text-muted small">{{ __('meetings.current_channel') }}: {{ auth()->user()->currentChannel?->name ?? 'N/A' }} · {{ __('meetings.times_in', ['timezone' => $timezone]) }}</div>
  </div>
</div>

@include('admin.meetings._alerts')

<form method="GET" class="mb-3 d-flex gap-2 flex-wrap align-items-center">
  <input class="form-control form-control-sm" style="max-width: 220px;" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('meetings.search_placeholder') }}">
  <select name="when" class="form-select form-select-sm" style="max-width: 150px;">
    @foreach(['upcoming', 'past', 'all'] as $option)
      <option value="{{ $option }}" @selected($filters['when'] === $option)>{{ __('meetings.when_'.$option) }}</option>
    @endforeach
  </select>
  <select name="format" class="form-select form-select-sm" style="max-width: 160px;">
    <option value="">{{ __('meetings.all_formats') }}</option>
    @foreach(\App\Enums\MeetingFormat::cases() as $option)
      <option value="{{ $option->value }}" @selected(($filters['format'] ?? '') === $option->value)>{{ $option->label() }}</option>
    @endforeach
  </select>
  <select name="status" class="form-select form-select-sm" style="max-width: 170px;">
    <option value="">{{ __('meetings.any_status') }}</option>
    @foreach(\App\Http\Controllers\Admin\Meetings\BookingController::STATUSES as $option)
      <option value="{{ $option }}" @selected(($filters['status'] ?? '') === $option)>{{ __('meetings.status_'.$option) }}</option>
    @endforeach
  </select>
  <select name="type" class="form-select form-select-sm" style="max-width: 200px;">
    <option value="">{{ __('meetings.all_types') }}</option>
    @foreach($types as $option)
      <option value="{{ $option->id }}" @selected((int) ($filters['type'] ?? 0) === $option->id)>{{ $option->name }}</option>
    @endforeach
  </select>
  <input type="date" name="from" class="form-control form-control-sm" style="max-width: 150px;" value="{{ $filters['from'] ?? '' }}" aria-label="{{ __('meetings.from') }}">
  <input type="date" name="to" class="form-control form-control-sm" style="max-width: 150px;" value="{{ $filters['to'] ?? '' }}" aria-label="{{ __('meetings.to') }}">
  <button class="btn btn-sm btn-secondary">{{ __('meetings.filter') }}</button>
  <a href="{{ route('admin.meetings.bookings.index') }}" class="btn btn-sm btn-light">{{ __('meetings.reset') }}</a>
</form>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead>
          <tr>
            <th>{{ __('meetings.mail.when') }}</th>
            <th>{{ __('meetings.visitor') }}</th>
            <th>{{ __('meetings.mail.meeting') }}</th>
            <th>{{ __('meetings.format') }}</th>
            <th>{{ __('meetings.status') }}</th>
            <th>{{ __('meetings.location_column') }}</th>
            <th class="text-end">{{ __('meetings.actions') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($bookings as $booking)
          @php($start = $booking->starts_at->copy()->setTimezone($timezone)->locale(app()->getLocale()))
          <tr>
            <td class="text-nowrap">{{ $start->isoFormat('ddd, ll') }}<br><span class="text-muted small">{{ $start->isoFormat('LT') }}–{{ $booking->ends_at->copy()->setTimezone($timezone)->isoFormat('LT') }}</span></td>
            <td>
              <div class="fw-semibold">{{ $booking->name }}</div>
              <div class="text-muted small">{{ $booking->email }}@if($booking->company) · {{ $booking->company }}@endif</div>
            </td>
            <td>{{ $booking->meetingType?->name }}</td>
            <td>@include('admin.meetings.bookings._format-badge', ['format' => $booking->format])</td>
            <td>@include('admin.meetings.bookings._status-badge', ['status' => $booking->status])</td>
            <td class="small">
              @if($booking->format === \App\Enums\MeetingFormat::Online)
                @if($booking->video_link_status === 'ready')
                  <span class="text-success"><i class="ri-checkbox-circle-line"></i> {{ __('meetings.link_status_ready') }}</span>
                @else
                  <span class="text-warning"><i class="ri-error-warning-line"></i> {{ __('meetings.link_status_pending') }}</span>
                @endif
              @else
                {{ $booking->location_snapshot['name'] ?? '' }}
              @endif
            </td>
            <td class="text-end text-nowrap">
              @if($booking->calendar_sync_status === 'failed')<i class="ri-calendar-close-line text-danger me-1" title="{{ __('meetings.sync_failed') }}"></i>@endif
              <a href="{{ route('admin.meetings.bookings.show', $booking->id) }}" class="btn btn-sm btn-info">{{ __('meetings.open') }}</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center py-4">{{ __('meetings.no_bookings') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($bookings->hasPages())<div class="card-footer">{{ $bookings->links() }}</div>@endif
</div>
@endsection
