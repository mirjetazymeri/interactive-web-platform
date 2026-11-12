@extends('layouts.master')

@section('content')
<h1 class="h3 mb-3">Edit Employee</h1>

<form method="post" action="{{ route('admin.employees.update', $employee) }}" enctype="multipart/form-data" class="card card-body">
    @csrf @method('PUT')
    @include('admin.employees._form', ['employee' => $employee])
    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Update</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.employees.show', $employee) }}">Back</a>
    </div>
</form>
@endsection
