@extends('layouts.master')

@section('content')

<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Edit Permission</h1>
        <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">Back to Permissions</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.permissions.update', $permission) }}">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Permission Details</div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label for="permission-name">Permission Name</label>
                        <input type="text" name="name" class="form-control" id="permission-name" value="{{ old('name', $permission->name) }}" required>
                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="module-select">Module</label>
                        <select name="module_id" id="module-select" class="form-control" required>
                            <option value="" disabled>-- Select Module --</option>
                            @foreach($modules as $module)
                                <option value="{{ $module->id }}" {{ (old('module_id', $permission->module_id) == $module->id) ? 'selected' : '' }}>{{ $module->name }}</option>
                            @endforeach
                        </select>
                        @error('module_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12 text-end mt-3">
            <button type="submit" class="btn btn-primary">Update Permission</button>
        </div>
    </div>
</form>

@endsection
