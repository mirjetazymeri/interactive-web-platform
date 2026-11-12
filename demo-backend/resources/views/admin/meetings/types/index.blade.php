@extends('layouts.master')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="fs-18 mb-1">{{ __('meetings.meeting_types') }}</h1>
    <div class="text-muted small">{{ __('meetings.current_channel') }}: {{ auth()->user()->currentChannel?->name ?? 'N/A' }}</div>
  </div>
  <a href="{{ route('admin.meetings.types.create') }}" class="btn btn-primary">{{ __('meetings.add_type') }}</a>
</div>

@include('admin.meetings._alerts')

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>{{ __('meetings.name') }}</th>
          <th>{{ __('meetings.formats_summary') }}</th>
          <th>{{ __('meetings.duration') }}</th>
          <th>{{ __('meetings.status') }}</th>
          <th>{{ __('meetings.position') }}</th>
          <th class="text-end">{{ __('meetings.actions') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($types as $type)
        <tr>
          <td>
            <div class="fw-semibold">{{ $type->name }}</div>
            <div class="text-muted small">{{ $type->slug }}</div>
          </td>
          <td>
            @foreach($type->allowedFormats() as $format)
              <span class="badge {{ $format === \App\Enums\MeetingFormat::Online ? 'bg-info-transparent text-info' : 'bg-warning-transparent text-warning' }}">
                {{ $format->label() }}@if(count($type->allowedFormats()) > 1 && $type->default_format === $format) · {{ __('meetings.default_badge') }}@endif
              </span>
            @endforeach
          </td>
          <td>
            @foreach($type->allowedFormats() as $format)
              <div class="small">{{ $format->label() }}: {{ $type->settingsFor($format)['duration'] }} {{ __('meetings.minutes') }}</div>
            @endforeach
          </td>
          <td>
            @if($type->active)
              <span class="badge bg-success">{{ __('meetings.active') }}</span>
            @else
              <span class="badge bg-secondary">{{ __('meetings.inactive') }}</span>
            @endif
          </td>
          <td>{{ $type->position }}</td>
          <td class="text-end">
            <a href="{{ route('admin.meetings.types.edit', $type->id) }}" class="btn btn-sm btn-info">{{ __('meetings.edit') }}</a>
            <form method="POST" action="{{ route('admin.meetings.types.destroy', $type->id) }}" class="d-inline" onsubmit="return confirm(@js(__('meetings.confirm_delete_type')))">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">{{ __('meetings.delete') }}</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-center py-4">{{ __('meetings.no_types') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
