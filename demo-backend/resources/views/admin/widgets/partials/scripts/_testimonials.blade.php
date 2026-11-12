{{-- Widget form: testimonials type — add/remove/sort items + media toggle (logo OR photo) --}}

@section('scripts')
@parent
<script>
(function () {
  'use strict';

  var addBtn    = document.getElementById('addTestimonialItem');
  var container = document.getElementById('testimonialItems');
  var emptyHint = document.getElementById('testimonialEmptyHint');
  var countEl   = document.getElementById('testimonialItemCount');
  if (!addBtn || !container) return;

  var counter = container.querySelectorAll('.testimonial-item-card').length;

  function updateCount() {
    var n = container.querySelectorAll('.testimonial-item-card').length;
    if (countEl) countEl.textContent = n;
    if (emptyHint) emptyHint.classList.toggle('d-none', n > 0);
  }
  updateCount();

  // ── Drag-to-reorder ──────────────────────────────────────────────
  if (typeof Sortable !== 'undefined') {
    Sortable.create(container, {
      handle: '.testimonial-drag-handle',
      animation: 150,
      ghostClass: 'slide-sortable-ghost',
    });
  }

  // ── Media type radio toggle ──────────────────────────────────────
  container.addEventListener('change', function (e) {
    var radio = e.target.closest('.testimonial-media-radio');
    if (radio) {
      var card   = radio.closest('.testimonial-item-card');
      var chosen = radio.value;
      card.querySelectorAll('.testimonial-media-panel').forEach(function (panel) {
        panel.style.display = panel.getAttribute('data-panel') === chosen ? '' : 'none';
      });
      return;
    }

    // ── Logo file preview ──────────────────────────────────────────
    var logoInput = e.target.closest('.testimonial-logo-input');
    if (logoInput && logoInput.files && logoInput.files[0]) {
      var reader = new FileReader();
      reader.onload = function (ev) {
        var wrap = logoInput.closest('.testimonial-logo-wrap');
        if (!wrap) return;
        var img  = wrap.querySelector('.testimonial-logo-preview');
        var ph   = wrap.querySelector('.testimonial-logo-placeholder');
        if (img) { img.src = ev.target.result; img.classList.remove('d-none'); }
        if (ph)  ph.style.display = 'none';
        var keepEl = logoInput.closest('.testimonial-item-card').querySelector('input[name$="[keep_logo]"]');
        if (keepEl) keepEl.remove();
      };
      reader.readAsDataURL(logoInput.files[0]);
      return;
    }

    // ── Photo file preview ─────────────────────────────────────────
    var imageInput = e.target.closest('.testimonial-image-input');
    if (imageInput && imageInput.files && imageInput.files[0]) {
      var reader2 = new FileReader();
      reader2.onload = function (ev) {
        var wrap2 = imageInput.closest('.testimonial-image-wrap');
        if (!wrap2) return;
        var img2  = wrap2.querySelector('.testimonial-image-preview');
        var ph2   = wrap2.querySelector('.testimonial-image-placeholder');
        if (img2) { img2.src = ev.target.result; img2.classList.remove('d-none'); }
        if (ph2)  ph2.style.display = 'none';
        var keepEl2 = imageInput.closest('.testimonial-item-card').querySelector('input[name$="[keep_image]"]');
        if (keepEl2) keepEl2.remove();
      };
      reader2.readAsDataURL(imageInput.files[0]);
    }
  });

  // ── Build media toggle HTML ──────────────────────────────────────
  function buildMediaHTML(k) {
    return (
      '<div class="btn-group btn-group-sm mb-2" role="group">' +
        '<input type="radio" name="config[items][' + k + '][media_type]" value="logo" class="btn-check testimonial-media-radio" id="test-mt-logo-' + k + '">' +
        '<label class="btn btn-outline-secondary btn-xs" for="test-mt-logo-' + k + '">Logo</label>' +
        '<input type="radio" name="config[items][' + k + '][media_type]" value="image" class="btn-check testimonial-media-radio" id="test-mt-image-' + k + '" checked>' +
        '<label class="btn btn-outline-secondary btn-xs" for="test-mt-image-' + k + '">Photo</label>' +
      '</div>' +
      '<div class="testimonial-media-panel" data-panel="logo" style="display:none">' +
        '<div class="testimonial-logo-wrap rounded border" style="width:72px;height:36px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;">' +
          '<img class="testimonial-logo-preview d-none img-fluid w-100 h-100" style="object-fit:contain" alt="">' +
          '<span class="testimonial-logo-placeholder text-muted"><i class="ri-building-line fs-18"></i></span>' +
        '</div>' +
        '<input type="file" name="config[items][' + k + '][logo_file]" class="form-control form-control-sm testimonial-logo-input mt-1" accept="image/*" style="max-width:72px;font-size:0.65rem;">' +
      '</div>' +
      '<div class="testimonial-media-panel" data-panel="image">' +
        '<div class="testimonial-image-wrap rounded-circle border" style="width:48px;height:48px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;">' +
          '<img class="testimonial-image-preview d-none img-fluid w-100 h-100" style="object-fit:cover" alt="">' +
          '<span class="testimonial-image-placeholder text-muted"><i class="ri-user-line fs-20"></i></span>' +
        '</div>' +
        '<input type="file" name="config[items][' + k + '][image_file]" class="form-control form-control-sm testimonial-image-input mt-1" accept="image/*" style="max-width:72px;font-size:0.65rem;">' +
      '</div>'
    );
  }

  // ── Add item ─────────────────────────────────────────────────────
  addBtn.addEventListener('click', function () {
    var k    = counter++;
    var card = document.createElement('div');
    card.className = 'card mb-2 testimonial-item-card shadow-sm';
    card.setAttribute('data-item-index', k);
    card.innerHTML =
      '<div class="card-body py-2 px-3">' +
        '<div class="row g-3 align-items-start">' +
          '<div class="col-auto d-flex align-items-center gap-2 pt-2">' +
            '<span class="testimonial-drag-handle text-muted" style="cursor:grab" title="Drag to reorder"><i class="ri-draggable fs-18"></i></span>' +
            '<span class="badge bg-secondary-transparent text-secondary">#' + (k + 1) + '</span>' +
          '</div>' +
          '<div class="col-auto">' + buildMediaHTML(k) + '</div>' +
          '<div class="col">' +
            '<div class="row g-2">' +
              '<div class="col-12">' +
                '<textarea name="config[items][' + k + '][quote]" rows="2" class="form-control form-control-sm" placeholder="Client quote"></textarea>' +
                '<div class="form-text fs-11">Quote</div>' +
              '</div>' +
              '<div class="col-md-4">' +
                '<input type="text" name="config[items][' + k + '][name]" class="form-control form-control-sm" placeholder="Name">' +
                '<div class="form-text fs-11">Name</div>' +
              '</div>' +
              '<div class="col-md-4">' +
                '<input type="text" name="config[items][' + k + '][role]" class="form-control form-control-sm" placeholder="Role or company">' +
                '<div class="form-text fs-11">Role</div>' +
              '</div>' +
              '<div class="col-md-4">' +
                '<input type="number" min="1" max="5" name="config[items][' + k + '][rating]" class="form-control form-control-sm" value="5" placeholder="Rating">' +
                '<div class="form-text fs-11">Rating (1–5)</div>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div class="col-auto pt-2">' +
            '<button type="button" class="btn btn-xs btn-outline-danger js-remove-testimonial-item" data-item-index="' + k + '" title="Remove"><i class="ri-delete-bin-line"></i></button>' +
          '</div>' +
        '</div>' +
      '</div>';
    container.appendChild(card);
    updateCount();
  });

  // ── Remove item ──────────────────────────────────────────────────
  document.addEventListener('click', function (e) {
    var removeBtn = e.target.closest('.js-remove-testimonial-item');
    if (!removeBtn) return;
    var card = removeBtn.closest('.testimonial-item-card');
    if (card) card.remove();
    updateCount();
  });
})();
</script>
@endsection
