@extends('layouts.master')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="fs-18 mb-1">{{ __('meetings.office_locations') }}</h1>
    <div class="text-muted small">{{ __('meetings.current_channel') }}: {{ auth()->user()->currentChannel?->name ?? 'N/A' }}</div>
  </div>
  <a href="{{ route('admin.meetings.locations.create') }}" class="btn btn-primary">{{ __('meetings.add_location') }}</a>
</div>

@include('admin.meetings._alerts')

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>{{ __('meetings.office_name') }}</th>
          <th>{{ __('meetings.address') }}</th>
          <th>{{ __('meetings.status') }}</th>
          <th>{{ __('meetings.position') }}</th>
          <th class="text-end">{{ __('meetings.actions') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($locations as $location)
        <tr>
          <td class="fw-semibold">
            {{ $location->name }}
            @if($location->is_default)<span class="badge bg-primary-transparent text-primary ms-1">{{ __('meetings.default_badge') }}</span>@endif
          </td>
          <td>
            {{ $location->fullAddress() }}
            <a href="{{ $location->mapsLink() }}" target="_blank" rel="noopener" class="ms-1 small">{{ __('meetings.map_preview') }}</a>
          </td>
          <td>
            @if($location->active)
              <span class="badge bg-success">{{ __('meetings.active') }}</span>
            @else
              <span class="badge bg-secondary">{{ __('meetings.inactive') }}</span>
            @endif
          </td>
          <td>{{ $location->position }}</td>
          <td class="text-end">
            <a href="{{ route('admin.meetings.locations.edit', $location->id) }}" class="btn btn-sm btn-info">{{ __('meetings.edit') }}</a>
            <form method="POST" action="{{ route('admin.meetings.locations.destroy', $location->id) }}" class="d-inline" onsubmit="return confirm(@js(__('meetings.confirm_delete_location')))">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">{{ __('meetings.delete') }}</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-center py-4">{{ __('meetings.no_locations') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
