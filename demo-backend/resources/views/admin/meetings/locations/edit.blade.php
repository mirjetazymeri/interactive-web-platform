@extends('layouts.master')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="fs-18 mb-0">{{ __('meetings.edit_location') }}</h1>
  <a href="{{ route('admin.meetings.locations.index') }}" class="btn btn-light">{{ __('meetings.back') }}</a>
</div>
@include('admin.meetings._alerts')
<div class="card"><div class="card-body">
  <form method="POST" action="{{ route('admin.meetings.locations.update', $location->id) }}">
    @csrf @method('PUT')
    @include('admin.meetings.locations._form')
    <button class="btn btn-primary mt-3">{{ __('meetings.save') }}</button>
  </form>
</div></div>
@endsection
