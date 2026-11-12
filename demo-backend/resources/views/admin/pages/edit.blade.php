{{-- resources/views/admin/pages/edit.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <h1 class="fs-18 mb-0">{{ __('translations.admin.pages.edit_title') }}</h1>
  <a href="{{ route('admin.pages.index') }}" class="btn btn-light">{{ __('translations.admin.common.back') }}</a>
</div>

@if($errors->any())
  <div class="alert alert-danger alert-dismissible mb-3" role="alert">
    <strong>{{ __('translations.admin.common.error') }}</strong>
    <ul class="mb-0 mt-1">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<div class="card mb-3">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.pages.update',$page->id) }}" enctype="multipart/form-data">
      @csrf @method('PUT')
      @include('admin.pages._form', [
            'page' => $page,
            'pageTree' => $pageTree ?? null,
            'channels' => $channels
        ])
      <button class="btn btn-primary mt-3">{{ __('translations.admin.common.save') }}</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div class="card-title mb-0">{{ __('translations.admin.pages.widgets.title') }}</div>
    <span class="badge bg-light text-muted border">{{ $widgets->count() }} {{ __('translations.admin.pages.widgets.widget') }}</span>
  </div>

  <div class="card-body border-bottom">
    <div class="row g-2 mb-3">
      <div class="col-md-6">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-transparent"><i class="ri-search-line"></i></span>
          <input type="search" id="widgetAttachSearch" class="form-control" placeholder="Search widgets by type or title...">
        </div>
      </div>
      <div class="col-md-6 text-md-end">
        <small class="text-muted">Pick a widget and click <strong>{{ __('translations.admin.pages.widgets.attach') }}</strong></small>
      </div>
    </div>

    <div class="row g-2" id="widgetAttachGrid" style="max-height: 260px; overflow-y: auto;">
      @forelse($widgets as $w)
        @php $widgetSearch = strtolower(trim($w->type . ' ' . ($w->title ?? ''))); @endphp
        <div class="col-xl-4 col-lg-6 widget-attach-item" data-widget-search="{{ $widgetSearch }}">
          <div class="border rounded p-2 d-flex justify-content-between align-items-start gap-2 h-100">
            <div class="min-w-0">
              <div class="fw-semibold text-capitalize">{{ $w->type === 'hero-world' ? __('hero_world.name') : $w->type }}</div>
              <div class="small text-muted text-truncate">{{ $w->title ?: 'Untitled widget' }}</div>
            </div>
            <form method="POST" action="{{ route('admin.pages.widgets.attach',$page->id) }}" class="flex-shrink-0">
              @csrf
              <input type="hidden" name="widget_id" value="{{ $w->id }}">
              @if($w->type === 'service_x')
                <label class="form-label">Presentation
                  <select class="form-select form-select-sm" name="config[display_mode]">
                    <option value="slider">Slider</option>
                    <option value="stacked">Stacked</option>
                  </select>
                </label>
              @endif
              <button class="btn btn-sm btn-primary">{{ __('translations.admin.pages.widgets.attach') }}</button>
            </form>
          </div>
        </div>
      @empty
        <div class="col-12 text-center text-muted py-3">No active widgets available to attach.</div>
      @endforelse
    </div>

    <div id="widgetAttachEmpty" class="text-center text-muted small py-3 d-none">
      No widgets match your search.
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle" id="widgetsTable">
        <thead>
          <tr><th style="width:40px;"></th><th>{{ __('translations.admin.pages.widgets.widget') }}</th><th>{{ __('translations.admin.pages.widgets.active') }}</th><th>{{ __('translations.admin.pages.widgets.window') }}</th><th></th></tr>
        </thead>
        <tbody id="pwRows">
        @forelse($page->pageWidgets as $pw)
          <tr data-id="{{ $pw->id }}">
            <td class="text-muted" style="cursor:grab">⋮⋮</td>
            <td>{{ $pw->widget->type === 'hero-world' ? __('hero_world.name') : $pw->widget->type }} @if($pw->widget->title) <span class="text-muted">— {{ $pw->widget->title }}</span>@endif</td>
            <td>
              <div class="form-check form-switch m-0">
                <input class="form-check-input toggle-active" type="checkbox" @checked($pw->active)>
              </div>
            </td>
            <td class="text-muted">
              @if($pw->starts_at || $pw->ends_at)
                {{ optional($pw->starts_at)->toDateTimeString() }} — {{ optional($pw->ends_at)->toDateTimeString() }}
              @else — @endif
            </td>
            <td class="text-end">
              @if($pw->widget->type === 'service_x')
                <form action="{{ route('admin.pages.widgets.config', [$page->id, $pw->id]) }}" method="POST" class="d-inline-flex gap-2 mb-2">
                  @csrf @method('PATCH')
                  <label class="form-label">Presentation
                    <select class="form-select form-select-sm" name="config[display_mode]">
                      <option value="slider" @selected(($pw->config['display_mode'] ?? 'slider') === 'slider')>Slider</option>
                      <option value="stacked" @selected(($pw->config['display_mode'] ?? 'slider') === 'stacked')>Stacked</option>
                    </select>
                  </label>
                  <button class="btn btn-sm btn-outline-primary">Save</button>
                </form>
              @endif
              <form action="{{ route('admin.pages.widgets.detach',[$page->id,$pw->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('translations.admin.pages.widgets.confirm_remove') }}')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">{{ __('translations.admin.pages.widgets.detach') }}</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center p-3">{{ __('translations.admin.pages.widgets.empty') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
(() => {
  const attachSearch = document.getElementById('widgetAttachSearch');
  const attachGrid = document.getElementById('widgetAttachGrid');
  const attachEmpty = document.getElementById('widgetAttachEmpty');

  if (attachSearch && attachGrid) {
    const filterWidgets = () => {
      const q = (attachSearch.value || '').toLowerCase().trim();
      const items = Array.from(attachGrid.querySelectorAll('.widget-attach-item'));
      let visible = 0;

      items.forEach((item) => {
        const haystack = item.dataset.widgetSearch || '';
        const show = q === '' || haystack.includes(q);
        item.classList.toggle('d-none', !show);
        if (show) visible += 1;
      });

      if (attachEmpty) attachEmpty.classList.toggle('d-none', visible > 0);
    };

    attachSearch.addEventListener('input', filterWidgets);
    filterWidgets();
  }

  // simple drag reorder with HTML5 API for widgets
  const tbody = document.getElementById('pwRows');
  if (!tbody) return;

  let dragSrc;
  tbody.querySelectorAll('tr').forEach(tr => {
    tr.draggable = true;
    tr.addEventListener('dragstart', e => { dragSrc = tr; e.dataTransfer.effectAllowed='move'; });
    tr.addEventListener('dragover', e => { e.preventDefault(); e.dataTransfer.dropEffect='move'; });
    tr.addEventListener('drop', e => {
      e.preventDefault();
      if (!dragSrc || dragSrc===tr) return;
      tr.parentNode.insertBefore(dragSrc, tr.nextSibling);
      saveOrder();
    });
  });

  function saveOrder() {
    const ids = Array.from(tbody.querySelectorAll('tr')).map(tr => tr.dataset.id);
    fetch('{{ route('admin.pages.widgets.reorder',$page->id) }}', {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}','Content-Type':'application/json'},
      body: JSON.stringify({ordering: ids})
    }).catch(()=>{});
  }

  tbody.addEventListener('change', async (e) => {
    if (!e.target.classList.contains('toggle-active')) return;
    const tr = e.target.closest('tr');
    const id = tr.dataset.id;
    await fetch(`{{ url('/admin/pages/'.$page->id.'/widgets') }}/${id}/toggle`, {
      method: 'POST',
      headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},
      body: JSON.stringify({active: e.target.checked ? 1 : 0})
    });
  });
})();
</script>
@endsection
