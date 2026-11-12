@extends('layouts.master')

@section('content')

@php
  $typeColors = ['carousel' => 'primary', 'video' => 'purple', 'html' => 'warning', 'map' => 'danger'];
  $typeIcons  = ['carousel' => 'ri-image-line', 'video' => 'ri-video-line', 'html' => 'ri-code-line', 'map' => 'ri-map-pin-2-line'];
  $tColor = $typeColors[$widget->type] ?? 'secondary';
  $tIcon  = $typeIcons[$widget->type]  ?? 'ri-layout-line';
@endphp

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-1 fs-13">
        <li class="breadcrumb-item"><a href="{{ route('admin.widgets.index') }}" class="text-muted">Widgets</a></li>
        <li class="breadcrumb-item active">Edit</li>
      </ol>
    </nav>
    <div class="d-flex align-items-center gap-2">
      <h1 class="page-title fs-18 mb-0"><i class="{{ $tIcon }} me-2 text-{{ $tColor }}"></i>{{ $widget->title ?: 'Widget #'.$widget->id }}</h1>
      <span class="badge bg-{{ $tColor }}-transparent text-{{ $tColor }} border border-{{ $tColor }} border-opacity-25 fw-semibold">
        {{ ucfirst($widget->type) }}
      </span>
      @if($widget->status === 'active')
        <span class="badge bg-success-transparent text-success fw-semibold">Active</span>
      @else
        <span class="badge bg-secondary-transparent text-secondary fw-semibold">Inactive</span>
      @endif
    </div>
  </div>
  <a href="{{ route('admin.widgets.index') }}" class="btn btn-light">
    <i class="ri-arrow-left-line me-1"></i> Back
  </a>
</div>

@if($errors->any())
  <div class="alert alert-danger alert-dismissible mb-4" role="alert">
    <i class="ri-error-warning-line me-2"></i>
    <strong>Please fix the following errors:</strong>
    <ul class="mb-0 mt-1">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<form method="POST" action="{{ route('admin.widgets.update',$widget->id) }}" enctype="multipart/form-data" id="widgetForm">
  @csrf @method('PUT')

  <div class="row g-4">

    {{-- ── Main form ── --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom">
          <div class="card-title mb-0">Widget Configuration</div>
        </div>
        <div class="card-body">
          @include('admin.widgets._form', ['widget' => $widget, 'types' => $types, 'layouts' => $layouts])
        </div>
      </div>
    </div>

  </div>

  {{-- ── Publish Controls (Bottom Section) ── --}}
  <div class="row g-4 mt-2">
    <div class="col-12">
      <div class="card">
        {{-- Header --}}
        <div class="card-header border-bottom">
          <div class="card-title mb-0"><i class="ri-shield-check-line me-2"></i>Publish</div>
        </div>

        {{-- Body: Status & Primary Actions --}}
        <div class="card-body">
          <div class="row g-3">
            {{-- Last Updated Info --}}
            <div class="col-md-6">
              <div class="p-3 bg-light rounded">
                <p class="text-muted fs-12 mb-1"><i class="ri-time-line me-1"></i>Last updated</p>
                <p class="fw-semibold mb-0 fs-13">{{ $widget->updated_at ? $widget->updated_at->diffForHumans() : '—' }}</p>
                <p class="text-muted fs-11 mb-0 mt-1">ID: {{ $widget->id }}</p>
              </div>
            </div>

            {{-- Primary Actions (Save/Cancel) --}}
            <div class="col-md-6">
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-lg btn-primary fw-semibold">
                  <i class="ri-save-line me-1"></i> Save Changes
                </button>
                <a href="{{ route('admin.widgets.index') }}" class="btn btn-outline-secondary">
                  Cancel
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

@endsection

@section('styles')
@parent
<style>
  .badge.bg-purple-transparent { background: rgba(111,66,193,.12); }
  .text-purple { color: #6f42c1 !important; }
  .border-purple { border-color: #6f42c1 !important; }
</style>
@endsection

@section('scripts')
@parent
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Scroll to error alert if validation errors exist
    const errorAlert = document.querySelector('.alert-danger');
    if (errorAlert) {
      errorAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
</script>
@endsection
