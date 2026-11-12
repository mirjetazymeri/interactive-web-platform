{{-- Widget form: carousel type — slide add/remove/sortable/image-preview/translation sync --}}

@section('scripts')
@parent
<script>
(function () {
  'use strict';

  var addBtn    = document.getElementById('addCarouselSlide');
  var container = document.getElementById('carouselSlides');
  var emptyHint = document.getElementById('carouselEmptyHint');
  var countEl   = document.getElementById('slideCount');
  if (!addBtn || !container) return;

  var counter = container.querySelectorAll('.carousel-slide-card').length;

  function updateCount() {
    var n = container.querySelectorAll('.carousel-slide-card').length;
    if (countEl) countEl.textContent = n;
    if (emptyHint) emptyHint.classList.toggle('d-none', n > 0);
  }
  updateCount();

  // ── Drag-to-reorder ──────────────────────────────────────────────
  if (typeof Sortable !== 'undefined') {
    Sortable.create(container, {
      handle: '.slide-drag-handle',
      animation: 150,
      ghostClass: 'slide-sortable-ghost',
    });
  }

  // ── Live image preview on file change ────────────────────────────
  container.addEventListener('change', function (e) {
    var fileInput = e.target.closest('.slide-file-input');
    if (!fileInput || !fileInput.files || !fileInput.files[0]) return;
    var reader = new FileReader();
    reader.onload = function (ev) {
      var wrap        = fileInput.closest('.slide-image-wrap');
      if (!wrap) return;
      var img         = wrap.querySelector('.slide-img-preview');
      var placeholder = wrap.querySelector('.slide-img-placeholder');
      if (img) { img.src = ev.target.result; img.classList.remove('d-none'); }
      if (placeholder) placeholder.style.display = 'none';
      var keepInput = fileInput.closest('.carousel-slide-card').querySelector('input[name$="[keep_image]"]');
      if (keepInput) keepInput.remove();
    };
    reader.readAsDataURL(fileInput.files[0]);
  });

  // ── Collapse / expand toggle ─────────────────────────────────────
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-toggle-slide');
    if (!btn) return;
    var body = btn.closest('.carousel-slide-card').querySelector('.slide-body');
    if (!body) return;
    var collapsed = body.style.display === 'none';
    body.style.display = collapsed ? '' : 'none';
    var icon = btn.querySelector('i');
    if (icon) icon.className = collapsed ? 'ri-arrow-down-s-line' : 'ri-arrow-up-s-line';
  });

  // ── Translation slide-card helpers ───────────────────────────────
  function buildTranslationSlideCard(localeIndex, slideIndex) {
    var isShared = localeIndex === '__shared__';
    var prefix   = isShared
      ? 'unified_translation[slides][' + slideIndex + ']'
      : 'translations[' + localeIndex + '][slides][' + slideIndex + ']';
    var card = document.createElement('div');
    card.className = 'card mb-3 carousel-translation-slide-card border-0 shadow-sm';
    card.setAttribute('data-slide-index', slideIndex);
    card.innerHTML =
      '<div class="card-body py-2 px-3">' +
        '<div class="d-flex justify-content-between align-items-center mb-2">' +
          '<span class="badge bg-secondary-transparent text-secondary fw-semibold">Slide ' + (slideIndex + 1) + '</span>' +
          '<small class="text-muted">Text only, image and style stay in main config.</small>' +
        '</div>' +
        '<div class="row g-2">' +
          '<div class="col-md-6"><input type="text" name="' + prefix + '[alt]" class="form-control form-control-sm" placeholder="Alt text"></div>' +
          '<div class="col-md-6"><input type="text" name="' + prefix + '[headline]" class="form-control form-control-sm" placeholder="Headline"></div>' +
          '<div class="col-md-6"><input type="text" name="' + prefix + '[subtitle]" class="form-control form-control-sm" placeholder="Subtitle"></div>' +
          '<div class="col-md-3"><input type="text" name="' + prefix + '[cta_text]" class="form-control form-control-sm" placeholder="CTA text"></div>' +
          '<div class="col-md-3"><input type="text" name="' + prefix + '[cta_link]" class="form-control form-control-sm" placeholder="CTA link"></div>' +
        '</div>' +
      '</div>';
    return card;
  }

  function syncTranslationEmpty() {
    document.querySelectorAll('[data-carousel-translation-container]').forEach(function (tc) {
      var cards = tc.querySelectorAll('.carousel-translation-slide-card').length;
      var empty = tc.querySelector('.carousel-translation-empty');
      if (cards === 0 && !empty) {
        var el = document.createElement('div');
        el.className = 'text-muted small carousel-translation-empty';
        el.innerHTML = '<i class="ri-add-circle-line me-1"></i>Add slides in main config first, then translate here.';
        tc.appendChild(el);
      }
      if (cards > 0 && empty) empty.remove();
    });
  }

  function addTranslationCards(slideIndex) {
    document.querySelectorAll('[data-carousel-translation-container]').forEach(function (tc) {
      var li    = tc.getAttribute('data-locale-index');
      var empty = tc.querySelector('.carousel-translation-empty');
      if (empty) empty.remove();
      tc.appendChild(buildTranslationSlideCard(li, slideIndex));
    });
  }

  function removeTranslationCards(slideIndex) {
    document.querySelectorAll('[data-carousel-translation-container]').forEach(function (tc) {
      var card = tc.querySelector('[data-slide-index="' + slideIndex + '"]');
      if (card) card.remove();
    });
    syncTranslationEmpty();
  }

  syncTranslationEmpty();

  // ── Add slide ────────────────────────────────────────────────────
  addBtn.addEventListener('click', function () {
    var k    = counter++;
    var card = document.createElement('div');
    card.className = 'card mb-3 carousel-slide-card shadow-sm';
    card.setAttribute('data-slide-index', k);
    card.innerHTML =
      '<div class="card-header bg-light py-2 px-3 d-flex align-items-center gap-2">' +
        '<span class="slide-drag-handle text-muted me-1" style="cursor:grab"><i class="ri-draggable"></i></span>' +
        '<span class="badge bg-primary-transparent text-primary fw-semibold">Slide ' + (k + 1) + '</span>' +
        '<div class="ms-auto d-flex gap-2">' +
          '<button type="button" class="btn btn-xs btn-light js-toggle-slide"><i class="ri-arrow-down-s-line"></i></button>' +
          '<button type="button" class="btn btn-xs btn-outline-danger js-remove-carousel-slide" data-slide-index="' + k + '"><i class="ri-delete-bin-line"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="card-body slide-body">' +
        '<div class="row g-3">' +
          '<div class="col-md-3">' +
            '<label class="form-label fs-12 text-muted text-uppercase">Image</label>' +
            '<div class="slide-image-wrap rounded overflow-hidden border mb-2" style="height:100px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;">' +
              '<img class="slide-img-preview d-none img-fluid w-100 h-100" style="object-fit:cover" alt="">' +
              '<span class="text-muted small slide-img-placeholder"><i class="ri-image-add-line fs-24 d-block mb-1"></i>No image</span>' +
            '</div>' +
            '<input type="file" name="config[slides][' + k + '][image_file]" class="form-control form-control-sm slide-file-input" accept="image/*">' +
          '</div>' +
          '<div class="col-md-9">' +
            '<div class="row g-2">' +
              '<div class="col-md-6"><input type="text" name="config[slides][' + k + '][alt]" class="form-control form-control-sm" placeholder="Alt text (SEO)"><div class="form-text">Alt / SEO</div></div>' +
              '<div class="col-md-6"><select name="config[slides][' + k + '][text_position]" class="form-select form-select-sm"><option value="left">Text Left</option><option value="center" selected>Text Center</option><option value="right">Text Right</option></select></div>' +
              '<div class="col-md-6"><input type="text" name="config[slides][' + k + '][headline]" class="form-control form-control-sm" placeholder="Headline"></div>' +
              '<div class="col-md-6"><input type="text" name="config[slides][' + k + '][subtitle]" class="form-control form-control-sm" placeholder="Subtitle"></div>' +
              '<div class="col-md-4"><input type="text" name="config[slides][' + k + '][cta_text]" class="form-control form-control-sm" placeholder="CTA label"></div>' +
              '<div class="col-md-4"><input type="text" name="config[slides][' + k + '][cta_link]" class="form-control form-control-sm" placeholder="CTA URL"></div>' +
              '<div class="col-md-2"><label class="form-label fs-11 text-muted mb-1">CTA text color</label><input type="color" name="config[slides][' + k + '][cta_color]" class="form-control form-control-sm form-control-color w-100" value="#ffffff"></div>' +
              '<div class="col-md-2"><label class="form-label fs-11 text-muted mb-1">CTA bg color</label><input type="color" name="config[slides][' + k + '][cta_bg]" class="form-control form-control-sm form-control-color w-100" value="#0d6efd"></div>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>';
    container.appendChild(card);
    addTranslationCards(k);
    updateCount();
  });

  // ── Remove slide ─────────────────────────────────────────────────
  document.addEventListener('click', function (e) {
    var removeBtn = e.target.closest('.js-remove-carousel-slide');
    if (!removeBtn) return;
    var idx       = removeBtn.getAttribute('data-slide-index');
    var slideCard = removeBtn.closest('.carousel-slide-card');
    if (slideCard) slideCard.remove();
    removeTranslationCards(idx);
    updateCount();
  });

})();
</script>
@endsection
