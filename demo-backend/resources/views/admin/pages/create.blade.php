{{-- resources/views/admin/pages/create.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <h1 class="fs-18 mb-0">{{ __('translations.admin.pages.create_title') }}</h1>
  <a href="{{ route('admin.pages.index') }}" class="btn btn-light">{{ __('translations.admin.common.back') }}</a>
</div>

@if($errors->any())
  <div class="alert alert-danger alert-dismissible mb-3" role="alert">
    <strong>{{ __('translations.admin.common.error') }}</strong>
    <ul class="mb-0 mt-1">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.pages.store') }}" enctype="multipart/form-data">
      @csrf
      @include('admin.pages._form')
      <button class="btn btn-primary mt-3">{{ __('translations.admin.common.create') }}</button>
    </form>
  </div>
</div>
@endsection
