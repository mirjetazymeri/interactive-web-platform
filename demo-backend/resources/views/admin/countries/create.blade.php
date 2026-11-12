@extends('layouts.master')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="fs-18 mb-0">Create Country</h1>
  <a class="btn btn-light" href="{{ route('admin.countries.index') }}">Back</a>
</div>

<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.countries.store') }}">
      @csrf
      @include('admin.countries._form')
      <button class="btn btn-primary mt-3">Create</button>
    </form>
  </div>
</div>
@endsection
