@extends('layouts.master')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="fs-18 mb-0">Countries</h1>
  <a href="{{ route('admin.countries.create') }}" class="btn btn-primary">Add Country</a>
</div>

<form method="GET" class="mb-3 d-flex gap-2 flex-wrap">
  <input class="form-control form-control-sm" name="q" value="{{ $q ?? '' }}" placeholder="Search code/name...">
  <button class="btn btn-sm btn-secondary">Filter</button>
  <a href="{{ route('admin.countries.index') }}" class="btn btn-sm btn-light">Reset</a>
</form>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>Code</th>
          <th>Name</th>
          <th>Cities</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($countries as $country)
        <tr>
          <td class="fw-semibold">{{ strtoupper($country->code) }}</td>
          <td>{{ $country->name }}</td>
          <td>{{ $country->cities_count }}</td>
          <td class="text-end">
            <a href="{{ route('admin.countries.edit', $country->id) }}" class="btn btn-sm btn-info">Edit</a>
            <form method="POST" action="{{ route('admin.countries.delete', $country->id) }}" class="d-inline" onsubmit="return confirm('Delete country and all its cities?')">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="text-center py-4">No countries found.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if($countries->hasPages())<div class="card-footer">{{ $countries->links() }}</div>@endif
</div>
@endsection
