@extends('layouts.master')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="fs-18 mb-1">Newsletter Subscribers</h1>
    <div class="text-muted small">Current channel: {{ auth()->user()->currentChannel?->name ?? 'N/A' }}</div>
  </div>
  <a href="{{ route('admin.newsletters.create') }}" class="btn btn-primary">Add Subscriber</a>
</div>

<form method="GET" class="mb-3 d-flex gap-2 flex-wrap">
  <input class="form-control form-control-sm" name="q" value="{{ $q ?? '' }}" placeholder="Email or name">
  <select name="status" class="form-select form-select-sm" style="max-width: 180px;">
    <option value="">Any status</option>
    <option value="subscribed" @selected(($status ?? '') === 'subscribed')>Subscribed</option>
    <option value="unsubscribed" @selected(($status ?? '') === 'unsubscribed')>Unsubscribed</option>
  </select>
  <button class="btn btn-sm btn-secondary">Filter</button>
  <a href="{{ route('admin.newsletters.index') }}" class="btn btn-sm btn-light">Reset</a>
</form>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>Email</th>
          <th>Name</th>
          <th>Status</th>
          <th>Source</th>
          <th>Subscribed</th>
          <th>Unsubscribed</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($subscribers as $subscriber)
        <tr>
          <td class="fw-semibold">{{ $subscriber->email }}</td>
          <td>{{ $subscriber->name ?: 'N/A' }}</td>
          <td>
            {!! $subscriber->status === 'subscribed'
              ? '<span class="badge bg-success">Subscribed</span>'
              : '<span class="badge bg-secondary">Unsubscribed</span>' !!}
          </td>
          <td>{{ $subscriber->source ?: 'N/A' }}</td>
          <td>{{ $subscriber->subscribed_at?->format('Y-m-d H:i') ?: 'N/A' }}</td>
          <td>{{ $subscriber->unsubscribed_at?->format('Y-m-d H:i') ?: 'N/A' }}</td>
          <td class="text-end">
            <a href="{{ route('admin.newsletters.edit', $subscriber->id) }}" class="btn btn-sm btn-info">Edit</a>
            <form method="POST" action="{{ route('admin.newsletters.delete', $subscriber->id) }}" class="d-inline" onsubmit="return confirm('Delete this subscriber?')">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-center py-4">No subscribers found.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if($subscribers->hasPages())<div class="card-footer">{{ $subscribers->links() }}</div>@endif
</div>
@endsection
