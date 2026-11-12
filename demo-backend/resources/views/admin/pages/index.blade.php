@extends('layouts.master')

@section('content')
<div class="page-header-breadcrumb mb-3 d-flex align-center justify-content-between">
    <h1 class="page-title fw-medium fs-18 mb-0">Pages</h1>
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">New Page</a>
</div>

<div class="card custom-card">
    <div class="card-header">
        <div class="card-title">Page Tree</div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="pagesTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:44%;">Title / Path</th>
                        <th>Status</th>
                        <th>Published</th>
                        <th class="text-center">Widgets</th>
                        <th class="text-end" style="width:170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @php
                    $render = function($nodes, $level = 0, $parentId = null) use (&$render) {
                        foreach ($nodes as $p) {
                            $indent = $level * 20;
                            $widgetsCount = $p->relationLoaded('pageWidgets')
                                ? $p->pageWidgets->count()
                                : $p->pageWidgets()->count();
                @endphp
                    <tr class="page-row"
                        data-id="{{ $p->id }}"
                        data-parent-id="{{ $parentId ?? 'root' }}"
                        draggable="true">
                        <td>
                            <div class="d-flex align-items-center">
                                <span title="Drag to reorder siblings"
                                      class="tree-dot me-2"
                                      style="margin-left: {{ $indent }}px; cursor:grab;">⋮⋮</span>
                                <div>
                                    <div class="fw-semibold">
                                        <a href="{{ route('admin.pages.edit', $p->id) }}" class="text-decoration-none">
                                            {{ $p->title ?? '—' }}
                                        </a>
                                    </div>
                                    <div class="text-muted small">
                                        /{{ $p->path ?? $p->slug }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($p->status === 'published')
                                <span class="badge bg-success">Published</span>
                            @elseif($p->status === 'draft')
                                <span class="badge bg-secondary">Draft</span>
                            @else
                                <span class="badge bg-light text-dark">{{ ucfirst($p->status) }}</span>
                            @endif
                        </td>
                        <td class="text-muted">
                            {{ optional($p->published_at)->format('Y-m-d H:i') ?? '—' }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info-subtle text-info">{{ $widgetsCount }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.pages.edit', $p->id) }}" class="btn btn-sm btn-outline-primary">
                                Edit
                            </a>
                            <form action="{{ route('admin.pages.destroy', $p->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete this page?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @php
                            // children (one level; keep UI snappy)
                            if ($p->relationLoaded('children')) {
                                if ($p->children->count()) { $render($p->children, $level + 1, $p->id); }
                            } else {
                                $kids = $p->children()->with('children')->get();
                                if ($kids->count()) { $render($kids, $level + 1, $p->id); }
                            }
                        }
                    };
                    // controller passed $pages as roots (with children eager loaded)
                    $render($pages, 0, null);
                @endphp
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('styles')
@parent
<style>
.tree-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--tblr-primary, #3b82f6);
    display:inline-flex; align-items:center; justify-content:center;
    font-size: 14px; color:#888; line-height: 1; user-select:none;
}
.page-row.drag-over { outline: 2px dashed #3b82f6; }
</style>
@endsection

@section('scripts')
@parent
<script>
(function(){
  const table = document.getElementById('pagesTable');
  if (!table) return;

  let dragSrc = null;

  function siblingsOf(el) {
    if (!el) return [];
    const pid = el.dataset.parentId || 'root';
    return Array.from(table.querySelectorAll('.page-row'))
      .filter(r => (r.dataset.parentId || 'root') === pid);
  }

  function saveOrderFor(el) {
    const pid = el.dataset.parentId || 'root';
    const group = siblingsOf(el);
    const ids = group.map(r => r.dataset.id);
    fetch('{{ route('admin.pages.reorder') }}', {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'},
      body: JSON.stringify({
        parent_id: pid === 'root' ? null : parseInt(pid,10),
        ordering: ids.map(id => parseInt(id,10))
      })
    }).catch(()=>{});
  }

  table.addEventListener('dragstart', e => {
    const row = e.target.closest('.page-row');
    if (!row) return;
    dragSrc = row;
    e.dataTransfer.effectAllowed = 'move';
    // Required in Firefox to enable dropping
    e.dataTransfer.setData('text/plain', '');
  });

  table.addEventListener('dragend', () => {
    dragSrc = null;
    table.querySelectorAll('.page-row.drag-over').forEach(r => r.classList.remove('drag-over'));
  });

  table.addEventListener('dragover', e => {
    const row = e.target.closest('.page-row');
    if (!row || !dragSrc) return;

    // only allow over same parent (sibling reordering)
    if ((row.dataset.parentId||'root') !== (dragSrc.dataset.parentId||'root')) return;

    e.preventDefault(); // allow drop
    row.classList.add('drag-over');
    e.dataTransfer.dropEffect = 'move';
  });

  table.addEventListener('dragleave', e => {
    const row = e.target.closest('.page-row');
    row?.classList.remove('drag-over');
  });

  table.addEventListener('drop', e => {
    const row = e.target.closest('.page-row');
    if (!row || !dragSrc) return;
    row.classList.remove('drag-over');

    const sameParent = (row.dataset.parentId||'root') === (dragSrc.dataset.parentId||'root');
    if (!sameParent || row === dragSrc) return;

    // If dragSrc is before the drop target (dragging down), insert after target.
    // Otherwise (dragging up), insert before target.
    const dragBeforeDropTarget =
      !!(dragSrc.compareDocumentPosition(row) & Node.DOCUMENT_POSITION_FOLLOWING);

    if (dragBeforeDropTarget) {
      row.parentNode.insertBefore(dragSrc, row.nextElementSibling);
    } else {
      row.parentNode.insertBefore(dragSrc, row);
    }

    saveOrderFor(row);
  });
})();
</script>

@endsection
