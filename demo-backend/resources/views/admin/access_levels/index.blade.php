@extends('layouts.master')

@section('content')

<div class="page-header-breadcrumb mb-3 d-flex justify-content-between align-items-center">
    <h1 class="page-title fw-medium fs-18 mb-0">Access Levels</h1>
    <a href="{{ route('admin.access_levels.create') }}" class="btn btn-primary">Create Access Level</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($accessLevels->isEmpty())
    <p>No access levels found.</p>
@else
    <div class="card custom-card">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Permissions</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accessLevels as $level)
                        <tr>
                            <td>{{ $level->name }}</td>
                            <td>{{ $level->description ?? '-' }}</td>
                            <td>
                                @if($level->permissions->isEmpty())
                                    <em>No permissions assigned</em>
                                @else
                                    <ul class="mb-0 ps-3">
                                        @foreach($level->permissions as $permission)
                                            <li>{{ $permission->module->name ?? 'No Module' }}: {{ $permission->name }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.access_levels.edit', $level) }}" class="btn btn-sm btn-warning me-2">Edit</a>
                                <form action="{{ route('admin.access_levels.destroy', $level) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to delete this access level?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
