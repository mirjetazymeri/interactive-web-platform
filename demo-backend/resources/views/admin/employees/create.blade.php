@extends('layouts.master')

@section('content')
<h1 class="h3 mb-3">Add Employee</h1>

<form method="post" action="{{ route('admin.employees.store') }}" enctype="multipart/form-data" class="card card-body">
    @csrf
    @include('admin.employees._form', ['employee' => $employee])
    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Save</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.employees.index') }}">Cancel</a>
    </div>
</form>
@endsection
