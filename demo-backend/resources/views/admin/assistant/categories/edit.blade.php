@extends('layouts.master')
@section('content')
@include('admin.assistant._header', ['title' => __('assistant.edit_category'), 'action' => ['href' => route('admin.assistant.categories.index'), 'label' => __('assistant.back'), 'class' => 'btn-light']])
<div class="card"><div class="card-body">
  <form method="POST" action="{{ route('admin.assistant.categories.update', $category->id) }}">
    @csrf @method('PUT')
    @include('admin.assistant.categories._form')
    <button class="btn btn-primary mt-3">{{ __('assistant.save') }}</button>
  </form>
</div></div>
@endsection
