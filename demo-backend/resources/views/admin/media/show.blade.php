@extends('layouts.master')

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
<style>
/* ── Dropzone ── */
#mediaDropzone {
    border: 2px dashed #94a3b8;
    border-radius: 12px;
    padding: 36px 20px;
    text-align: center;
    cursor: pointer;
    background: #f8fafc;
    transition: border-color .2s, background .2s;
    min-height: 120px;
}
#mediaDropzone:hover,
#mediaDropzone.dz-drag-hover { border-color: #6366f1; background: #eef2ff; }
#mediaDropzone .dz-message { margin: 0; }
#mediaDropzone .dz-preview { margin: 6px; }
#mediaDropzone .dz-preview .dz-image img { border-radius: 8px; }

/* ── Gallery grid ── */
#galleryGrid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}
.media-item {
    position: relative;
    background: #f1f5f9;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid transparent;
    cursor: grab;
    transition: border-color .15s, box-shadow .15s;
    aspect-ratio: 1/1;
}
.media-item:hover  { border-color: #6366f1; box-shadow: 0 4px 18px rgba(99,102,241,.2); }
.media-item.dragging { opacity: .5; border-style: dashed; }
.media-item img { width:100%; height:100%; object-fit:cover; display:block; }

/* info overlay */
.media-item .overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.55);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    opacity: 0;
    transition: opacity .2s;
}
.media-item:hover .overlay { opacity: 1; }
.media-item .overlay button { font-size: .75rem; }

/* bottom label */
.media-item .label {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    background: rgba(0,0,0,.6);
    color: #fff;
    font-size: .65rem;
    padding: 2px 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Detail panel sidebar */
#detailPanel {
    min-height: 300px;
    display: none;
}
#detailPanel.visible { display: block; }
</style>
@endsection

@section('content')
<div class="page-header-breadcrumb mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.media.index') }}">Media Library</a></li>
                <li class="breadcrumb-item active">{{ $folder->name }}</li>
            </ol>
        </nav>
        <h1 class="page-title fw-medium fs-18 mb-0">
            <i class="fe fe-folder-open me-1 text-warning"></i>{{ $folder->name }}
            <span class="badge bg-secondary fw-normal ms-1 fs-12">{{ $media->count() }} {{ Str::plural('image', $media->count()) }}</span>
        </h1>
        @if($folder->description)
            <p class="text-muted mb-0 mt-1">{{ $folder->description }}</p>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.media.edit', $folder->slug) }}" class="btn btn-light btn-sm">
            <i class="fe fe-settings me-1"></i> Folder Settings
        </a>
        <button id="btnSelectMode" class="btn btn-outline-secondary btn-sm">
            <i class="fe fe-check-square me-1"></i> Select
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3">
    {{-- LEFT: Dropzone + Gallery --}}
    <div class="col-lg-8 col-xl-9">

        {{-- Dropzone upload --}}
        <div class="card custom-card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="card-title mb-0">Upload Images</div>
                <small class="text-muted">JPG, PNG, GIF, WEBP, SVG &bull; max 10 MB each</small>
            </div>
            <div class="card-body">
                <div id="mediaDropzone" class="dropzone">
                    <div class="dz-message">
                        <i class="fe fe-upload-cloud d-block mb-2" style="font-size:2.5rem; color:#94a3b8;"></i>
                        <span class="fw-medium text-muted">Drag & drop images here, or click to browse</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bulk action bar (hidden until selection mode) --}}
        <div id="bulkBar" class="alert alert-info d-none align-items-center justify-content-between mb-3">
            <span id="bulkCount">0 selected</span>
            <div class="d-flex gap-2">
                <button id="btnBulkDelete" class="btn btn-danger btn-sm">
                    <i class="fe fe-trash-2 me-1"></i> Delete Selected
                </button>
                <button id="btnCancelSelect" class="btn btn-light btn-sm">Cancel</button>
            </div>
        </div>

        {{-- Gallery grid --}}
        <div class="card custom-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="card-title mb-0">Images</div>
                <small class="text-muted">Drag to reorder</small>
            </div>
            <div class="card-body">
                @if($media->isEmpty())
                    <div class="text-center text-muted py-5" id="emptyState">
                        <i class="fe fe-image d-block mb-2" style="font-size:2.5rem;"></i>
                        No images yet. Upload some above.
                    </div>
                @endif
                <div id="galleryGrid">
                    @foreach($media as $item)
                    <div class="media-item" data-id="{{ $item->id }}" draggable="true">
                        <img src="{{ $item->hasGeneratedConversion('thumb') ? $item->getUrl('thumb') : $item->getUrl() }}"
                             alt="{{ $item->getCustomProperty('alt', $item->name) }}"
                             loading="lazy">
                        <div class="overlay">
                            <button class="btn btn-sm btn-light btnView" data-id="{{ $item->id }}"
                                    data-name="{{ $item->name }}"
                                    data-url="{{ $item->getUrl() }}"
                                    data-thumb="{{ $item->hasGeneratedConversion('thumb') ? $item->getUrl('thumb') : $item->getUrl() }}"
                                    data-medium="{{ $item->hasGeneratedConversion('medium') ? $item->getUrl('medium') : $item->getUrl() }}"
                                    data-size="{{ number_format($item->size / 1024, 1) }} KB"
                                    data-mime="{{ $item->mime_type }}"
                                    data-alt="{{ $item->getCustomProperty('alt', '') }}"
                                    data-caption="{{ $item->getCustomProperty('caption', '') }}"
                                    title="View details">
                                <i class="fe fe-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btnDelete" data-id="{{ $item->id }}" title="Delete">
                                <i class="fe fe-trash-2"></i>
                            </button>
                        </div>
                        <div class="label" title="{{ $item->name }}">{{ $item->name }}</div>
                        {{-- selection checkbox (hidden by default) --}}
                        <input type="checkbox" class="form-check-input position-absolute top-0 start-0 m-1 select-cb d-none"
                               value="{{ $item->id }}" style="z-index:10; width:1.2rem; height:1.2rem;">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- RIGHT: Detail panel --}}
    <div class="col-lg-4 col-xl-3">
        <div class="card custom-card" id="detailPanel">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="card-title mb-0">Image Details</div>
                <button class="btn-close btn-sm" id="btnCloseDetail"></button>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <a id="dpLink" href="#" target="_blank">
                        <img id="dpThumb" src="" alt="" class="img-fluid rounded" style="max-height:200px; object-fit:contain;">
                    </a>
                </div>

                <dl class="row small mb-3">
                    <dt class="col-5 text-muted">File</dt>
                    <dd class="col-7 text-truncate mb-0" id="dpFileName">—</dd>
                    <dt class="col-5 text-muted">Size</dt>
                    <dd class="col-7 mb-0" id="dpSize">—</dd>
                    <dt class="col-5 text-muted">Type</dt>
                    <dd class="col-7 mb-0" id="dpMime">—</dd>
                </dl>

                <form id="detailForm">
                    <input type="hidden" id="dpId" value="">

                    <div class="mb-2">
                        <label class="form-label small fw-medium mb-1">Name</label>
                        <input type="text" id="dpName" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-medium mb-1">Alt Text</label>
                        <input type="text" id="dpAlt" class="form-control form-control-sm" placeholder="Describe the image">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium mb-1">Caption</label>
                        <textarea id="dpCaption" class="form-control form-control-sm" rows="2" placeholder="Optional caption"></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Save</button>
                        <button type="button" id="dpCopyUrl" class="btn btn-outline-secondary btn-sm" title="Copy URL">
                            <i class="fe fe-copy"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm btnDelete" id="dpDeleteBtn" data-id="">
                            <i class="fe fe-trash-2"></i>
                        </button>
                    </div>

                    <div id="dpSaveMsg" class="mt-2 text-success small d-none">Saved!</div>
                </form>

                <div class="mt-3 pt-2 border-top">
                    <label class="form-label small fw-medium mb-1">Direct URL</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="dpUrl" class="form-control form-control-sm" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="dpCopyUrlInline" title="Copy">
                            <i class="fe fe-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Move to folder panel (shows when detail panel is visible) --}}
        <div class="card custom-card mt-3" id="movePanel" style="display:none;">
            <div class="card-header"><div class="card-title mb-0">Move to Folder</div></div>
            <div class="card-body">
                <select id="moveFolderSelect" class="form-select form-select-sm mb-2">
                    <option value="">— Select folder —</option>
                    @foreach(\App\Models\MediaFolder::orderBy('name')->get() as $f)
                        @if($f->id !== $folder->id)
                            <option value="{{ $f->slug }}">{{ $f->name }}</option>
                        @endif
                    @endforeach
                </select>
                <button id="btnMove" class="btn btn-sm btn-secondary w-100">Move Image</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
<script>
Dropzone.autoDiscover = false;

const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
const UPLOAD_URL  = "{{ route('admin.media.upload', $folder->slug) }}";
const DELETE_URL  = "{{ url('admin/media/items') }}";
const UPDATE_URL  = "{{ url('admin/media/items') }}";
const REORDER_URL = "{{ route('admin.media.reorder', $folder->slug) }}";
const MOVE_URL    = "{{ url('admin/media/items') }}";

// ── Dropzone ────────────────────────────────────────────────────────────────
const dz = new Dropzone('#mediaDropzone', {
    url: UPLOAD_URL,
    paramName: 'file',
    maxFilesize: 10,
    acceptedFiles: 'image/*',
    addRemoveLinks: true,
    headers: { 'X-CSRF-TOKEN': CSRF },
    success(file, response) {
        addMediaItem(response);
        document.getElementById('emptyState')?.remove();
    },
    error(file, msg) {
        const m = typeof msg === 'object' ? (msg.message || JSON.stringify(msg)) : msg;
        alert('Upload failed: ' + m);
        this.removeFile(file);
    }
});

// ── Build media item DOM ─────────────────────────────────────────────────────
function addMediaItem(item) {
    const grid = document.getElementById('galleryGrid');
    const div = document.createElement('div');
    div.className = 'media-item';
    div.dataset.id = item.id;
    div.setAttribute('draggable', 'true');
    div.innerHTML = `
        <img src="${item.thumb_url}" alt="${item.alt || item.name}" loading="lazy">
        <div class="overlay">
            <button class="btn btn-sm btn-light btnView"
                data-id="${item.id}" data-name="${item.name}" data-url="${item.url}"
                data-thumb="${item.thumb_url}" data-medium="${item.url}"
                data-size="${(item.size/1024).toFixed(1)} KB" data-mime="${item.mime_type}"
                data-alt="${item.alt||''}" data-caption="">
                <i class="fe fe-eye"></i>
            </button>
            <button class="btn btn-sm btn-danger btnDelete" data-id="${item.id}">
                <i class="fe fe-trash-2"></i>
            </button>
        </div>
        <div class="label" title="${item.name}">${item.name}</div>
        <input type="checkbox" class="form-check-input position-absolute top-0 start-0 m-1 select-cb d-none"
               value="${item.id}" style="z-index:10;width:1.2rem;height:1.2rem;">
    `;
    grid.appendChild(div);
    attachDrag(div);
}

// ── Detail panel ─────────────────────────────────────────────────────────────
const panel     = document.getElementById('detailPanel');
const movePanel = document.getElementById('movePanel');

function openDetail(btn) {
    document.getElementById('dpId').value      = btn.dataset.id;
    document.getElementById('dpThumb').src     = btn.dataset.thumb;
    document.getElementById('dpLink').href      = btn.dataset.url;
    document.getElementById('dpUrl').value      = btn.dataset.url;
    document.getElementById('dpFileName').textContent = btn.dataset.name;
    document.getElementById('dpSize').textContent     = btn.dataset.size;
    document.getElementById('dpMime').textContent     = btn.dataset.mime;
    document.getElementById('dpName').value    = btn.dataset.name;
    document.getElementById('dpAlt').value     = btn.dataset.alt  || '';
    document.getElementById('dpCaption').value = btn.dataset.caption || '';
    document.getElementById('dpDeleteBtn').dataset.id = btn.dataset.id;
    document.getElementById('dpSaveMsg').classList.add('d-none');
    panel.classList.add('visible');
    movePanel.style.display = 'block';
}

document.getElementById('btnCloseDetail').addEventListener('click', () => {
    panel.classList.remove('visible');
    movePanel.style.display = 'none';
});

document.addEventListener('click', e => {
    const btn = e.target.closest('.btnView');
    if (btn) openDetail(btn);
});

// ── Save detail form ──────────────────────────────────────────────────────────
document.getElementById('detailForm').addEventListener('submit', async e => {
    e.preventDefault();
    const id      = document.getElementById('dpId').value;
    const payload = {
        name:    document.getElementById('dpName').value,
        alt:     document.getElementById('dpAlt').value,
        caption: document.getElementById('dpCaption').value,
    };
    const res = await fetch(`${UPDATE_URL}/${id}`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload),
    });
    if (res.ok) {
        const data = await res.json();
        // Update label in grid
        const item = document.querySelector(`.media-item[data-id="${id}"]`);
        if (item) item.querySelector('.label').textContent = data.name;
        document.getElementById('dpSaveMsg').classList.remove('d-none');
        setTimeout(() => document.getElementById('dpSaveMsg').classList.add('d-none'), 2000);
    } else {
        alert('Failed to save. Please try again.');
    }
});

// ── Copy URL buttons ──────────────────────────────────────────────────────────
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('URL copied to clipboard!');
    });
}
document.getElementById('dpCopyUrl').addEventListener('click', () => {
    copyToClipboard(document.getElementById('dpLink').href);
});
document.getElementById('dpCopyUrlInline').addEventListener('click', () => {
    copyToClipboard(document.getElementById('dpUrl').value);
});

// ── Delete ───────────────────────────────────────────────────────────────────
document.addEventListener('click', async e => {
    const btn = e.target.closest('.btnDelete');
    if (!btn) return;
    if (!confirm('Delete this image? This cannot be undone.')) return;
    const id = btn.dataset.id;
    const res = await fetch(`${DELETE_URL}/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    if (res.ok) {
        document.querySelector(`.media-item[data-id="${id}"]`)?.remove();
        panel.classList.remove('visible');
        movePanel.style.display = 'none';
        if (!document.querySelector('.media-item')) {
            const grid = document.getElementById('galleryGrid');
            grid.innerHTML = '<p class="text-muted text-center py-4 w-100">No images yet. Upload some above.</p>';
        }
    } else {
        alert('Delete failed.');
    }
});

// ── Move to folder ────────────────────────────────────────────────────────────
document.getElementById('btnMove').addEventListener('click', async () => {
    const slug = document.getElementById('moveFolderSelect').value;
    if (!slug) { alert('Please select a target folder.'); return; }
    const id = document.getElementById('dpId').value;
    const res = await fetch(`${MOVE_URL}/${id}/move`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ folder_slug: slug }),
    });
    if (res.ok) {
        const data = await res.json();
        document.querySelector(`.media-item[data-id="${id}"]`)?.remove();
        panel.classList.remove('visible');
        movePanel.style.display = 'none';
        alert(`Image moved to "${data.folder}".`);
    } else {
        alert('Move failed.');
    }
});

// ── Drag-and-drop reorder ────────────────────────────────────────────────────
const grid = document.getElementById('galleryGrid');
let dragEl = null;

function attachDrag(el) {
    el.addEventListener('dragstart', e => {
        dragEl = el;
        el.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    el.addEventListener('dragend', () => {
        el.classList.remove('dragging');
        dragEl = null;
        saveOrder();
    });
}

grid.addEventListener('dragover', e => {
    e.preventDefault();
    if (!dragEl) return;
    const target = e.target.closest('.media-item');
    if (!target || target === dragEl) return;
    const rect = target.getBoundingClientRect();
    const after = e.clientX > rect.left + rect.width / 2;
    grid.insertBefore(dragEl, after ? target.nextSibling : target);
});

async function saveOrder() {
    const ids = Array.from(grid.querySelectorAll('.media-item')).map(el => el.dataset.id);
    await fetch(REORDER_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids }),
    });
}

// Attach drag to existing items
grid.querySelectorAll('.media-item').forEach(attachDrag);

// ── Selection mode ────────────────────────────────────────────────────────────
const bulkBar       = document.getElementById('bulkBar');
const bulkCount     = document.getElementById('bulkCount');
const btnSelectMode = document.getElementById('btnSelectMode');
const btnCancelSel  = document.getElementById('btnCancelSelect');
let selectMode = false;

btnSelectMode.addEventListener('click', () => {
    selectMode = true;
    bulkBar.classList.remove('d-none');
    bulkBar.classList.add('d-flex');
    grid.querySelectorAll('.select-cb').forEach(cb => cb.classList.remove('d-none'));
    btnSelectMode.classList.add('d-none');
});

btnCancelSel.addEventListener('click', exitSelect);

function exitSelect() {
    selectMode = false;
    bulkBar.classList.add('d-none');
    bulkBar.classList.remove('d-flex');
    grid.querySelectorAll('.select-cb').forEach(cb => {
        cb.classList.add('d-none');
        cb.checked = false;
    });
    btnSelectMode.classList.remove('d-none');
    updateBulkCount();
}

grid.addEventListener('change', e => {
    if (e.target.classList.contains('select-cb')) updateBulkCount();
});

function updateBulkCount() {
    const n = grid.querySelectorAll('.select-cb:checked').length;
    bulkCount.textContent = `${n} selected`;
}

document.getElementById('btnBulkDelete').addEventListener('click', async () => {
    const ids = Array.from(grid.querySelectorAll('.select-cb:checked')).map(cb => cb.value);
    if (!ids.length) { alert('No images selected.'); return; }
    if (!confirm(`Delete ${ids.length} selected image(s)? This cannot be undone.`)) return;

    for (const id of ids) {
        const res = await fetch(`${DELETE_URL}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (res.ok) {
            document.querySelector(`.media-item[data-id="${id}"]`)?.remove();
        }
    }
    exitSelect();
});
</script>
@endsection
