@extends('layouts.master')

@section('content')

<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Create Access Level</h1>
        <a href="{{ route('admin.access_levels.index') }}" class="btn btn-secondary">Back to Access Levels</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.access_levels.store') }}">
    @csrf

    <div class="row">
        <div class="col-xl-6">

            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Access Level Details</div>
                </div>
                <div class="card-body">

                    <div class="form-group mb-3">
                        <label for="name">Name</label>
                        <input type="text" name="name" id="name"
                            class="form-control"
                            value="{{ old('name') }}" required>
                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="description">Description</label>
                        <textarea name="description" id="description"
                            class="form-control" rows="3">{{ old('description') }}</textarea>
                        @error('description') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="permissions">Permissions</label>
                        <select name="permissions[]" id="permissions" class="form-control" multiple>
                            @foreach($permissions as $permission)
                                <option value="{{ $permission->id }}"
                                    {{ collect(old('permissions'))->contains($permission->id) ? 'selected' : '' }}>
                                    {{ $permission->module->name ?? 'No Module' }} - {{ $permission->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('permissions') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                </div>
            </div>

        </div>

        <div class="col-12 text-end mt-3">
            <button type="submit" class="btn btn-primary">Create Access Level</button>
        </div>
    </div>
</form>

@endsection
