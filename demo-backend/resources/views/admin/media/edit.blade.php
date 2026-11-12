@extends('layouts.master')

@section('content')
<div class="page-header-breadcrumb mb-3 d-flex align-items-center justify-content-between">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.media.index') }}">Media Library</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
        <h1 class="page-title fw-medium fs-18 mb-0">Edit Folder &mdash; {{ $folder->name }}</h1>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Folder Details</div></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.media.update', $folder->slug) }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-medium">Folder Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $folder->name) }}"
                               class="form-control @error('name') is-invalid @enderror" autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $folder->slug) }}"
                               class="form-control @error('slug') is-invalid @enderror">
                        <div class="form-text">Changing the slug will break any existing direct links.</div>
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $folder->description) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $folder->sort_order) }}"
                               class="form-control" min="0" style="max-width:140px">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="{{ route('admin.media.show', $folder->slug) }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card border-danger mt-3">
            <div class="card-header bg-danger bg-opacity-10">
                <div class="card-title text-danger">Danger Zone</div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Deleting this folder permanently removes all {{ $folder->getMedia('images')->count() }} images inside it.</p>
                <form method="POST" action="{{ route('admin.media.destroy', $folder->slug) }}"
                      onsubmit="return confirm('Delete folder &quot;{{ addslashes($folder->name) }}&quot; and ALL its images? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fe fe-trash-2 me-1"></i> Delete Folder
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
