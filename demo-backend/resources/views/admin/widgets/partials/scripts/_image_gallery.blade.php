{{-- Widget form: image_gallery type — item add/remove/sortable/image-preview/translation sync --}}

@section('scripts')
@parent
<script>
(function () {
  'use strict';

  var addBtn    = document.getElementById('addGalleryItem');
  var container = document.getElementById('galleryItems');
  var emptyHint = document.getElementById('galleryEmptyHint');
  var countEl   = document.getElementById('galleryItemCount');
  if (!addBtn || !container) return;

  var counter = container.querySelectorAll('.gallery-item-card').length;

  function updateCount() {
    var n = container.querySelectorAll('.gallery-item-card').length;
    if (countEl) countEl.textContent = n;
    if (emptyHint) emptyHint.classList.toggle('d-none', n > 0);
  }
  updateCount();

  // ── Drag-to-reorder ──────────────────────────────────────────────
  if (typeof Sortable !== 'undefined') {
    Sortable.create(container, {
      handle: '.gallery-drag-handle',
      animation: 150,
      ghostClass: 'slide-sortable-ghost',
    });
  }

  // ── Live image preview on file change ────────────────────────────
  container.addEventListener('change', function (e) {
    var fileInput = e.target.closest('.gallery-file-input');
    if (!fileInput || !fileInput.files || !fileInput.files[0]) return;
    var reader = new FileReader();
    reader.onload = function (ev) {
      var wrap        = fileInput.closest('.gallery-thumb-wrap');
      if (!wrap) return;
      var img         = wrap.querySelector('.gallery-img-preview');
      var placeholder = wrap.querySelector('.gallery-img-placeholder');
      if (img) { img.src = ev.target.result; img.classList.remove('d-none'); }
      if (placeholder) placeholder.style.display = 'none';
      var card = fileInput.closest('.gallery-item-card');
      var keepInput = card ? card.querySelector('input[name$="[keep_image]"]') : null;
      if (keepInput) keepInput.remove();
    };
    reader.readAsDataURL(fileInput.files[0]);
  });

  // ── Translation item-card helpers ────────────────────────────────
  function buildTranslationItemCard(localeIndex, itemIndex) {
    var isShared = localeIndex === '__shared__';
    var prefix   = isShared
      ? 'unified_translation[items][' + itemIndex + ']'
      : 'translations[' + localeIndex + '][items][' + itemIndex + ']';
    var card = document.createElement('div');
    card.className = 'card mb-2 gallery-translation-item-card border-0 shadow-sm';
    card.setAttribute('data-item-index', itemIndex);
    card.innerHTML =
      '<div class="card-body py-2 px-3">' +
        '<div class="d-flex justify-content-between align-items-center mb-2">' +
          '<span class="badge bg-secondary-transparent text-secondary fw-semibold">Image ' + (itemIndex + 1) + '</span>' +
          '<small class="text-muted">Alt &amp; caption only — image stays in main config.</small>' +
        '</div>' +
        '<div class="row g-2">' +
          '<div class="col-md-6"><input type="text" name="' + prefix + '[alt]" class="form-control form-control-sm" placeholder="Alt text"></div>' +
          '<div class="col-md-6"><input type="text" name="' + prefix + '[caption]" class="form-control form-control-sm" placeholder="Caption"></div>' +
        '</div>' +
      '</div>';
    return card;
  }

  function syncTranslationEmpty() {
    document.querySelectorAll('[data-gallery-translation-container]').forEach(function (tc) {
      var cards = tc.querySelectorAll('.gallery-translation-item-card').length;
      var empty = tc.querySelector('.gallery-translation-empty');
      if (cards === 0 && !empty) {
        var el = document.createElement('div');
        el.className = 'text-muted small gallery-translation-empty';
        el.innerHTML = '<i class="ri-add-circle-line me-1"></i>Add images in main config first, then translate captions here.';
        tc.appendChild(el);
      }
      if (cards > 0 && empty) empty.remove();
    });
  }

  function addTranslationCards(itemIndex) {
    document.querySelectorAll('[data-gallery-translation-container]').forEach(function (tc) {
      var li    = tc.getAttribute('data-locale-index');
      var empty = tc.querySelector('.gallery-translation-empty');
      if (empty) empty.remove();
      tc.appendChild(buildTranslationItemCard(li, itemIndex));
    });
  }

  function removeTranslationCards(itemIndex) {
    document.querySelectorAll('[data-gallery-translation-container]').forEach(function (tc) {
      var card = tc.querySelector('[data-item-index="' + itemIndex + '"]');
      if (card) card.remove();
    });
    syncTranslationEmpty();
  }

  syncTranslationEmpty();

  // ── Add item ─────────────────────────────────────────────────────
  addBtn.addEventListener('click', function () {
    var k    = counter++;
    var card = document.createElement('div');
    card.className = 'card mb-2 gallery-item-card shadow-sm';
    card.setAttribute('data-item-index', k);
    card.innerHTML =
      '<div class="card-body py-2 px-3">' +
        '<div class="row g-3 align-items-center">' +
          '<div class="col-auto d-flex align-items-center gap-2">' +
            '<span class="gallery-drag-handle text-muted" style="cursor:grab"><i class="ri-draggable fs-18"></i></span>' +
            '<span class="badge bg-secondary-transparent text-secondary">#' + (k + 1) + '</span>' +
          '</div>' +
          '<div class="col-auto">' +
            '<div class="gallery-thumb-wrap rounded overflow-hidden border" style="width:72px;height:72px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;">' +
              '<img class="gallery-img-preview d-none img-fluid w-100 h-100" style="object-fit:cover" alt="">' +
              '<span class="gallery-img-placeholder text-muted"><i class="ri-image-add-line fs-20"></i></span>' +
            '</div>' +
            '<input type="file" name="config[items][' + k + '][image_file]" class="form-control form-control-sm gallery-file-input mt-1" accept="image/*" style="max-width:72px;font-size:0.65rem;">' +
          '</div>' +
          '<div class="col">' +
            '<div class="row g-2">' +
              '<div class="col-md-4"><input type="text" name="config[items][' + k + '][alt]" class="form-control form-control-sm" placeholder="Alt text (SEO)"><div class="form-text fs-11">Alt text</div></div>' +
              '<div class="col-md-4"><input type="text" name="config[items][' + k + '][caption]" class="form-control form-control-sm" placeholder="Caption (optional)"><div class="form-text fs-11">Caption</div></div>' +
              '<div class="col-md-4"><input type="text" name="config[items][' + k + '][link]" class="form-control form-control-sm" placeholder="Link URL (optional)"><div class="form-text fs-11">On-click link</div></div>' +
            '</div>' +
          '</div>' +
          '<div class="col-auto">' +
            '<button type="button" class="btn btn-xs btn-outline-danger js-remove-gallery-item" data-item-index="' + k + '"><i class="ri-delete-bin-line"></i></button>' +
          '</div>' +
        '</div>' +
      '</div>';
    container.appendChild(card);
    addTranslationCards(k);
    updateCount();
  });

  // ── Remove item ──────────────────────────────────────────────────
  document.addEventListener('click', function (e) {
    var removeBtn = e.target.closest('.js-remove-gallery-item');
    if (!removeBtn) return;
    var idx      = removeBtn.getAttribute('data-item-index');
    var itemCard = removeBtn.closest('.gallery-item-card');
    if (itemCard) itemCard.remove();
    removeTranslationCards(idx);
    updateCount();
  });

})();
</script>
@endsection
