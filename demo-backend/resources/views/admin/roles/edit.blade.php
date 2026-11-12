@extends('layouts.master')

@section('content')
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Edit Role</h1>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Back to Roles</a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.roles.edit', $role->id) }}">
        @csrf
        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Role Details</div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="role-name">Role Name</label>
                            <input type="text" name="name" class="form-control" id="role-name"
                                   value="{{ old('name', $role->name) }}" required>
                            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 text-end mt-3">
                <button type="submit" class="btn btn-primary">Update Role</button>
            </div>
        </div>
    </form>
@endsection
