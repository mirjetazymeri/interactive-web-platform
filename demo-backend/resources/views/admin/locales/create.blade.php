@extends('layouts.master')

@section('content')
<div class="container">
    <h4>Create Locale</h4>

    <form method="POST" action="{{ route('admin.locales.store') }}">
        @csrf
        @include('admin.locales._form')
        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection
