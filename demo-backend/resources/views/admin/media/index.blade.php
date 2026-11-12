@extends('layouts.master')

@section('content')
<div class="page-header-breadcrumb mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-title fw-medium fs-18 mb-0">Media Library</h1>
        <p class="text-muted mb-0 mt-1">Organise images into galleries for use across the site.</p>
    </div>
    <a href="{{ route('admin.media.create') }}" class="btn btn-primary">
        <i class="fe fe-folder-plus me-1"></i> New Folder
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($folders->isEmpty())
    <div class="card custom-card">
        <div class="card-body text-center py-5">
            <i class="fe fe-image fs-1 text-muted d-block mb-3"></i>
            <h5 class="text-muted">No folders yet</h5>
            <p class="text-muted mb-4">Create your first folder to start organising images.</p>
            <a href="{{ route('admin.media.create') }}" class="btn btn-primary">Create Folder</a>
        </div>
    </div>
@else
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3">
        @foreach($folders as $folder)
            @php
                $cover = $folder->getFirstMedia('images');
                $coverUrl = $cover ? $cover->getUrl('thumb') : null;
                $count = $folder->media_count;
            @endphp
            <div class="col">
                <div class="card custom-card h-100 folder-card">
                    <a href="{{ route('admin.media.show', $folder->slug) }}" class="position-relative d-block overflow-hidden" style="height:160px; background:#f1f5f9;">
                        @if($coverUrl)
                            <img src="{{ $coverUrl }}" alt="{{ $folder->name }}" class="w-100 h-100 object-fit-cover">
                        @else
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i class="fe fe-image text-muted" style="font-size:3rem;"></i>
                            </div>
                        @endif
                        <span class="badge bg-dark position-absolute bottom-0 end-0 m-2">{{ $count }} {{ Str::plural('image', $count) }}</span>
                    </a>

                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <a href="{{ route('admin.media.show', $folder->slug) }}" class="fw-medium text-dark text-truncate flex-grow-1" title="{{ $folder->name }}">
                                <i class="fe fe-folder me-1 text-warning"></i>{{ $folder->name }}
                            </a>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fe fe-more-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('admin.media.show', $folder->slug) }}"><i class="fe fe-eye me-2"></i>Open</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.media.edit', $folder->slug) }}"><i class="fe fe-edit-2 me-2"></i>Edit</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('admin.media.destroy', $folder->slug) }}"
                                              onsubmit="return confirm('Delete folder &quot;{{ addslashes($folder->name) }}&quot; and ALL its images? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fe fe-trash-2 me-2"></i>Delete Folder
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        @if($folder->description)
                            <p class="text-muted small mb-0 mt-1 text-truncate">{{ $folder->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection

@section('styles')
<style>
.folder-card { transition: box-shadow .2s; }
.folder-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.12); }
.object-fit-cover { object-fit: cover; }
</style>
@endsection
