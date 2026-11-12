@extends('layouts.master')

@section('content')

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title fs-18 mb-1">Widgets</h1>
    <p class="text-muted mb-0 fs-13">Manage carousel, video and HTML widgets — drag rows to reorder.</p>
  </div>
  <a class="btn btn-primary" href="{{ route('admin.widgets.create') }}">
    <i class="ri-add-line me-1"></i> New Widget
  </a>
</div>

{{-- Toast --}}
<div id="reorderToast" class="position-fixed bottom-0 end-0 p-3" style="z-index:1080">
  <div class="toast align-items-center text-bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body"><i class="ri-check-line me-1"></i> Order saved.</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<div class="card">
  {{-- Filters --}}
  <div class="card-header border-bottom py-3">
    <div class="row g-2 align-items-center">
      <div class="col-md-4">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-transparent"><i class="ri-search-line"></i></span>
          <input id="widgetSearch" type="search" class="form-control" placeholder="Search by title…">
        </div>
      </div>
      <div class="col-md-2">
        <select id="filterType" class="form-select form-select-sm">
          <option value="">All types</option>
          <option value="carousel">Carousel</option>
          <option value="video">Video</option>
          <option value="html">HTML</option>
          <option value="hero">Hero</option>
          <option value="hero-world">{{ __('hero_world.name') }}</option>
          <option value="meeting_booking">{{ __('meetings.widget_name') }}</option>
          <option value="footer_cta">{{ __('footer_cta.name') }}</option>
          <option value="separator">Separator</option>
          <option value="contact_form">Contact Form</option>
          <option value="image_gallery">Image Gallery</option>
          <option value="map">Map</option>
          <option value="features">Features</option>
          <option value="faq">FAQ</option>
          <option value="testimonials">Testimonials</option>
          <option value="pricing">Pricing</option>
        </select>
      </div>
      <div class="col-md-2">
        <select id="filterStatus" class="form-select form-select-sm">
          <option value="">All statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <div class="col-md-4 text-end">
        <span id="filteredCount" class="text-muted small"></span>
      </div>
    </div>
  </div>

  <div class="card-body p-0">
    <table class="table table-hover align-middle mb-0" id="widgetsTable">
      <thead class="table-light">
        <tr>
          <th style="width:36px" class="text-center"></th>
          <th style="width:50px" class="text-muted fw-normal">#</th>
          <th>Title</th>
          <th style="width:130px">Type</th>
          <th style="width:110px">Layout</th>
          <th style="width:100px">Status</th>
          <th style="width:140px" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="widgetSortable">
        @forelse($widgets as $w)
          @php
            $typeColors = ['carousel' => 'primary', 'video' => 'purple', 'html' => 'warning', 'hero' => 'success', 'separator' => 'info', 'contact_form' => 'danger', 'image_gallery' => 'teal', 'map' => 'danger', 'features' => 'primary', 'faq' => 'warning', 'testimonials' => 'success', 'pricing' => 'info', 'hero-world' => 'success', 'meeting_booking' => 'info', 'footer_cta' => 'warning'];
            $typeIcons  = ['carousel' => 'ri-image-line', 'video' => 'ri-video-line', 'html' => 'ri-code-line', 'hero' => 'ri-layout-top-line', 'separator' => 'ri-separator', 'contact_form' => 'ri-mail-send-line', 'image_gallery' => 'ri-gallery-line', 'map' => 'ri-map-pin-2-line', 'features' => 'ri-apps-2-line', 'faq' => 'ri-question-answer-line', 'testimonials' => 'ri-chat-quote-line', 'pricing' => 'ri-price-tag-3-line', 'hero-world' => 'ri-earth-line', 'meeting_booking' => 'ri-calendar-event-line', 'footer_cta' => 'ri-megaphone-line'];
            $tColor = $typeColors[$w->type] ?? 'secondary';
            $tIcon  = $typeIcons[$w->type]  ?? 'ri-layout-line';
          @endphp
          <tr data-id="{{ $w->id }}"
              data-type="{{ $w->type }}"
              data-status="{{ $w->status }}"
              data-title="{{ strtolower($w->title ?? '') }}">
            <td class="drag-handle text-center text-muted" style="cursor:grab" title="Drag to reorder">
              <i class="ri-draggable fs-16"></i>
            </td>
            <td class="text-muted small">{{ $w->id }}</td>
            <td>
              <span class="fw-semibold">{{ $w->title ?? '—' }}</span>
              @if(data_get($w->config,'section_label'))
                <br><small class="text-muted">{{ data_get($w->config,'section_label') }}</small>
              @endif
            </td>
            <td>
              <span class="badge bg-{{ $tColor }}-transparent text-{{ $tColor }} fw-semibold border border-{{ $tColor }} border-opacity-25">
                <i class="{{ $tIcon }} me-1"></i>{{ ['hero-world' => __('hero_world.name'), 'meeting_booking' => __('meetings.widget_name'), 'footer_cta' => __('footer_cta.name')][$w->type] ?? ucfirst($w->type) }}
              </span>
            </td>
            <td class="text-muted small">{{ data_get($w->config, 'layout', '—') }}</td>
            <td>
              @if($w->status === 'active')
                <span class="badge bg-success-transparent text-success fw-semibold"><i class="ri-checkbox-circle-line me-1"></i>Active</span>
              @else
                <span class="badge bg-secondary-transparent text-secondary fw-semibold"><i class="ri-pause-circle-line me-1"></i>Inactive</span>
              @endif
            </td>
            <td class="text-end">
              <a href="{{ route('admin.widgets.edit',$w->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                <i class="ri-edit-line"></i>
              </a>
              <form action="{{ route('admin.widgets.destroy',$w->id) }}" method="POST" class="d-inline"
                    onsubmit="return confirm('Delete widget "{{ addslashes($w->title ?? 'this widget') }}"?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="ri-delete-bin-line"></i></button>
              </form>
            </td>
          </tr>
        @empty
          <tr id="emptyRow">
            <td colspan="7" class="text-center py-5">
              <div class="text-muted">
                <i class="ri-layout-3-line fs-40 d-block mb-2 opacity-50"></i>
                <strong>No widgets yet.</strong><br>
                <small>Create your first widget to get started.</small>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    {{-- No-filter-results row (hidden by default) --}}
    <div id="noFilterResults" class="text-center py-4 d-none text-muted">
      <i class="ri-search-line fs-24 d-block mb-1 opacity-50"></i>
      No widgets match your filters.
    </div>
  </div>

  @if($widgets->hasPages())
  <div class="card-footer">
    {{ $widgets->links() }}
  </div>
  @endif
</div>

@endsection

@section('styles')
@parent
<style>
  .sortable-ghost { opacity: .35; background: var(--bs-primary-bg-subtle, #e7f3ff) !important; }
  .drag-handle { transition: color .15s; }
  .drag-handle:hover { color: var(--bs-primary) !important; }
  .drag-handle:active { cursor: grabbing; }
  #widgetsTable tbody tr { transition: background .15s; }
  .badge.bg-purple-transparent { background: rgba(111,66,193,.12); }
  .text-purple { color: #6f42c1 !important; }
  .border-purple { border-color: #6f42c1 !important; }
</style>
@endsection

@section('scripts')
@parent
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
(function(){
  // ── Toast helper ───────────────────────────────────────────
  function showToast(variant, msg) {
    var toastEl = document.querySelector('#reorderToast .toast');
    toastEl.className = 'toast align-items-center text-bg-' + variant + ' border-0 shadow';
    toastEl.querySelector('.toast-body').innerHTML = msg;
    bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 2500 }).show();
  }

  // ── Client-side filter ──────────────────────────────────────
  var searchInput  = document.getElementById('widgetSearch');
  var filterType   = document.getElementById('filterType');
  var filterStatus = document.getElementById('filterStatus');
  var countEl      = document.getElementById('filteredCount');
  var noResults    = document.getElementById('noFilterResults');

  function applyFilters() {
    var q      = (searchInput.value || '').toLowerCase().trim();
    var type   = filterType.value;
    var status = filterStatus.value;
    var rows   = document.querySelectorAll('#widgetSortable tr[data-id]');
    var visible = 0;

    rows.forEach(function(tr) {
      var match = true;
      if (q      && !tr.dataset.title.includes(q))      match = false;
      if (type   && tr.dataset.type   !== type)          match = false;
      if (status && tr.dataset.status !== status)        match = false;
      tr.style.display = match ? '' : 'none';
      if (match) visible++;
    });

    if (rows.length) {
      countEl.textContent = visible + ' of ' + rows.length + ' widget' + (rows.length !== 1 ? 's' : '');
      noResults.classList.toggle('d-none', visible > 0);
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', applyFilters);
    filterType.addEventListener('change', applyFilters);
    filterStatus.addEventListener('change', applyFilters);
    applyFilters();
  }

  // ── Sortable drag-to-reorder ────────────────────────────────
  var tbody = document.getElementById('widgetSortable');
  if (!tbody || !tbody.querySelector('tr[data-id]')) return;

  Sortable.create(tbody, {
    handle: '.drag-handle',
    animation: 150,
    ghostClass: 'sortable-ghost',
    onEnd: function() {
      var ordering = Array.from(tbody.querySelectorAll('tr[data-id]'))
                          .map(function(tr){ return parseInt(tr.dataset.id); });
      if (!ordering.length) return;

      fetch('{{ route("admin.widgets.reorder") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ ordering: ordering })
      })
      .then(function(r){ return r.ok ? r.json() : Promise.reject(r); })
      .then(function(){ showToast('success', '<i class="ri-check-line me-1"></i> Order saved.'); })
      .catch(function(){ showToast('danger', '<i class="ri-error-warning-line me-1"></i> Error saving order.'); });
    }
  });
})();
</script>
@endsection
