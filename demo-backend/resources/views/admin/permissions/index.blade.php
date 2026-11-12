@extends('layouts.master')

@section('content')
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Permissions</h1>
        <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">Create Permission</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card custom-card">
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Permission Name</th>
                    <th>Module</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permissions as $permission)
                    <tr>
                        <td>{{ $permission->name }}</td>
                        <td>{{ $permission->module->name ?? '-' }}</td>
                        <td>
                            <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" class="d-inline" onsubmit="return confirm('Delete this permission?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">No permissions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
