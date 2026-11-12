@extends('layouts.master')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="fs-18 mb-0">Add Newsletter Subscriber</h1>
  <a href="{{ route('admin.newsletters.index') }}" class="btn btn-light">Back</a>
</div>
<div class="card"><div class="card-body">
  <form method="POST" action="{{ route('admin.newsletters.store') }}">
    @csrf
    @include('admin.newsletters._form')
    <button class="btn btn-primary mt-3">Create</button>
  </form>
</div></div>
@endsection
