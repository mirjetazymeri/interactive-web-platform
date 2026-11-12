@php
  $subscriber = $subscriber ?? null;
  $statusValue = old('status', $subscriber->status ?? 'subscribed');
@endphp

<div class="row g-3">
  <div class="col-md-5">
    <label class="form-label">Email *</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $subscriber->email ?? '') }}" required>
    @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $subscriber->name ?? '') }}">
    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-3">
    <label class="form-label">Status *</label>
    <select name="status" class="form-select">
      <option value="subscribed" @selected($statusValue === 'subscribed')>Subscribed</option>
      <option value="unsubscribed" @selected($statusValue === 'unsubscribed')>Unsubscribed</option>
    </select>
    @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
</div>

@if($subscriber)
  <div class="row g-3 mt-1">
    <div class="col-md-4">
      <label class="form-label">Source</label>
      <input type="text" class="form-control" value="{{ $subscriber->source ?: 'N/A' }}" disabled>
    </div>
    <div class="col-md-4">
      <label class="form-label">Subscribed At</label>
      <input type="text" class="form-control" value="{{ $subscriber->subscribed_at?->format('Y-m-d H:i') ?: 'N/A' }}" disabled>
    </div>
    <div class="col-md-4">
      <label class="form-label">Unsubscribed At</label>
      <input type="text" class="form-control" value="{{ $subscriber->unsubscribed_at?->format('Y-m-d H:i') ?: 'N/A' }}" disabled>
    </div>
  </div>
@endif
