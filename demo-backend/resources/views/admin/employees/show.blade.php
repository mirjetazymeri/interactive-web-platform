@extends('layouts.master')

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">{{ $employee->full_name }}</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="{{ route('admin.employees.edit', $employee) }}">Edit</a>
        <a class="btn btn-outline-secondary" href="{{ route('admin.employees.index') }}">Back</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                @if ($employee->avatar_path && file_exists(public_path('storage/' . $employee->avatar_path)))
                    <img src="{{ asset('storage/' . $employee->avatar_path) }}" alt="Avatar" class="img-fluid rounded">
                @else
                    <div class="text-muted">No avatar</div>
                @endif
            </div>
            <div class="col-md-9">
                <dl class="row">
                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">{{ $employee->email }}</dd>

                    <dt class="col-sm-3">Phone</dt>
                    <dd class="col-sm-9">{{ $employee->phone }}</dd>

                    <dt class="col-sm-3">Department</dt>
                    <dd class="col-sm-9">{{ $employee->department }}</dd>

                    <dt class="col-sm-3">Position</dt>
                    <dd class="col-sm-9">{{ $employee->position }}</dd>

                    <dt class="col-sm-3">Hired at</dt>
                    <dd class="col-sm-9">{{ optional($employee->hired_at)->format('Y-m-d') }}</dd>

                    <dt class="col-sm-3">Salary</dt>
                    <dd class="col-sm-9">{{ number_format($employee->salary, 2) }}</dd>

                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-{{ $employee->status === 'active' ? 'success' : ($employee->status === 'inactive' ? 'secondary' : 'danger') }}">
                            {{ ucfirst($employee->status) }}
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
