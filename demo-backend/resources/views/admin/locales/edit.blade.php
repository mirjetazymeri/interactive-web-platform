@extends('layouts.master')

@section('content')
<div class="container">
    <h4>Edit Locale</h4>

    <form method="POST" action="{{ route('admin.locales.update', $locale) }}">
        @csrf
        @method('PUT')
        @include('admin.locales._form')
        <button class="btn btn-success">Update</button>
    </form>
</div>
@endsection
