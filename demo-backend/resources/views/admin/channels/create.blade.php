@extends('layouts.master')

@section('content')

<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Create Channel</h1>
        <a href="{{ route('admin.channels.index') }}" class="btn btn-secondary">Back</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.channels.create') }}" class="card card-body">
    @csrf

    @include('admin.channels._form', ['channel' => new \App\Models\Channel()])

    <div class="mt-3">
        <button class="btn btn-primary">Create</button>
    </div>
</form>

@endsection
