@extends('layouts.master')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="fs-18 mb-0">Edit Country</h1>
  <a class="btn btn-light" href="{{ route('admin.countries.index') }}">Back</a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.countries.update', $country->id) }}">
      @csrf @method('PUT')
      @include('admin.countries._form')
      <button class="btn btn-primary mt-3">Save</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Cities</div></div>
  <div class="card-body">
    <form method="POST" action="{{ route('admin.countries.cities.store', $country->id) }}" class="row g-2 mb-3">
      @csrf
      <div class="col-md-6">
        <input name="name" class="form-control" placeholder="New city name" required>
      </div>
      <div class="col-md-2">
        <button class="btn btn-success w-100">Add City</button>
      </div>
    </form>

    @error('name')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">ID</th>
            <th>Name</th>
            <th class="text-end" style="width: 260px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        @forelse($country->cities as $city)
          <tr>
            <td>{{ $city->id }}</td>
            <td>
              <form method="POST" action="{{ route('admin.countries.cities.update', [$country->id, $city->id]) }}" class="d-flex gap-2">
                @csrf @method('PUT')
                <input name="name" class="form-control form-control-sm" value="{{ $city->name }}" required>
                <button class="btn btn-sm btn-info">Save</button>
              </form>
            </td>
            <td class="text-end">
              <form method="POST" action="{{ route('admin.countries.cities.delete', [$country->id, $city->id]) }}" class="d-inline" onsubmit="return confirm('Delete city?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="3" class="text-center py-4">No cities yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
