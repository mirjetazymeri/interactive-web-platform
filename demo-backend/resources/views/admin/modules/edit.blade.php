@extends('layouts.master')

@section('content')

<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Edit Module</h1>
        <a href="{{ route('admin.modules.index') }}" class="btn btn-secondary">Back to Modules</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.modules.update', $module) }}">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Module Details</div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label for="module-name">Name</label>
                        <input type="text" name="name" class="form-control" id="module-name" value="{{ old('name', $module->name) }}" required>
                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="module-description">Description</label>
                        <textarea name="description" class="form-control" id="module-description" rows="3">{{ old('description', $module->description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 text-end mt-3">
            <button type="submit" class="btn btn-primary">Update Module</button>
        </div>
    </div>
</form>

@endsection
