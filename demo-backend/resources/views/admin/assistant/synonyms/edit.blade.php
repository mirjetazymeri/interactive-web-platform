@extends('layouts.master')
@section('content')
@include('admin.assistant._header', ['title' => __('assistant.edit_synonyms'), 'action' => ['href' => route('admin.assistant.synonyms.index'), 'label' => __('assistant.back'), 'class' => 'btn-light']])
<div class="card"><div class="card-body">
  <form method="POST" action="{{ route('admin.assistant.synonyms.update', $synonym->id) }}">
    @csrf @method('PUT')
    @include('admin.assistant.synonyms._form')
    <button class="btn btn-primary mt-3">{{ __('assistant.save') }}</button>
  </form>
</div></div>
@endsection
