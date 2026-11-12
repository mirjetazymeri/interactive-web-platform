@extends('layouts.master')

@section('content')
<div class="page-header-breadcrumb mb-3 d-flex align-items-center justify-content-between">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.media.index') }}">Media Library</a></li>
                <li class="breadcrumb-item active">New Folder</li>
            </ol>
        </nav>
        <h1 class="page-title fw-medium fs-18 mb-0">Create Gallery Folder</h1>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Folder Details</div></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.media.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-medium">Folder Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. Product Banners" autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Slug</label>
                        <input type="text" name="slug" id="slugInput" value="{{ old('slug') }}"
                               class="form-control @error('slug') is-invalid @enderror"
                               placeholder="auto-generated from name">
                        <div class="form-text">Leave blank to auto-generate. Used in URLs.</div>
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Optional description of what this folder contains">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                               class="form-control" min="0" style="max-width:140px">
                        <div class="form-text">Lower numbers appear first on the index.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create Folder</button>
                        <a href="{{ route('admin.media.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script>
(function () {
    const nameInput = document.querySelector('[name="name"]');
    const slugInput = document.getElementById('slugInput');
    let slugTouched = slugInput.value !== '';

    slugInput.addEventListener('input', () => { slugTouched = true; });

    nameInput.addEventListener('input', () => {
        if (slugTouched) return;
        slugInput.value = nameInput.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-');
    });
})();
</script>
@endsection
