@extends('layouts.master')
@section('content')
@include('admin.assistant._header', ['title' => __('assistant.edit_question'), 'action' => ['href' => route('admin.assistant.questions.index'), 'label' => __('assistant.back'), 'class' => 'btn-light']])
<div class="card"><div class="card-body">
  <form method="POST" action="{{ route('admin.assistant.questions.update', $intent->id) }}">
    @csrf @method('PUT')
    @include('admin.assistant.questions._form')
    <button class="btn btn-primary mt-3">{{ __('assistant.save') }}</button>
  </form>
</div></div>
@endsection
