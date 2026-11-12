@extends('layouts.master')

@section('content')

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-1 fs-13">
        <li class="breadcrumb-item"><a href="{{ route('admin.widgets.index') }}" class="text-muted">Widgets</a></li>
        <li class="breadcrumb-item active">New Widget</li>
      </ol>
    </nav>
    <h1 class="page-title fs-18 mb-0"><i class="ri-add-circle-line me-2 text-primary"></i>New Widget</h1>
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

<form method="POST" action="{{ route('admin.widgets.store') }}" enctype="multipart/form-data" id="widgetForm">
  @csrf

  <div class="row g-4">

    {{-- ── Main form ── --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom">
          <div class="card-title mb-0">Widget Configuration</div>
        </div>
        <div class="card-body">
          @include('admin.widgets._form', ['types' => $types, 'layouts' => $layouts])
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

        {{-- Body: Instructions & Primary Actions --}}
        <div class="card-body">
          <div class="row g-3">
            {{-- Instructions Box --}}
            <div class="col-md-6">
              <div class="p-3 bg-light rounded">
                <p class="text-muted fs-12 mb-2"><i class="ri-information-line me-1"></i>Before Creating</p>
                <ul class="text-muted fs-12 mb-0 ps-3">
                  <li>Review all your widget settings</li>
                  <li>Required fields marked with <strong>*</strong></li>
                </ul>
              </div>
            </div>

            {{-- Primary Actions (Save/Cancel) --}}
            <div class="col-md-6">
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-lg btn-primary fw-semibold">
                  <i class="ri-save-line me-1"></i> Create Widget
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
