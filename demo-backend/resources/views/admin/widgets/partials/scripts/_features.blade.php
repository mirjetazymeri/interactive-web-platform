{{-- Widget form: features type â€” add/remove/sort items + media toggle (icon OR image) --}}

@section('scripts')
@parent
<script>
(function () {
  'use strict';

  var addBtn    = document.getElementById('addFeatureItem');
  var container = document.getElementById('featureItems');
  var emptyHint = document.getElementById('featureEmptyHint');
  var countEl   = document.getElementById('featureItemCount');
  if (!addBtn || !container) return;

  var counter = container.querySelectorAll('.feature-item-card').length;

  function updateCount() {
    var n = container.querySelectorAll('.feature-item-card').length;
    if (countEl) countEl.textContent = n;
    if (emptyHint) emptyHint.classList.toggle('d-none', n > 0);
  }
  updateCount();

  // â”€â”€ Drag-to-reorder â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (typeof Sortable !== 'undefined') {
    Sortable.create(container, {
      handle: '.feature-drag-handle',
      animation: 150,
      ghostClass: 'slide-sortable-ghost',
    });
  }

  // â”€â”€ Media type radio toggle â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  container.addEventListener('change', function (e) {
    var radio = e.target.closest('.feature-media-radio');
    if (radio) {
      var card   = radio.closest('.feature-item-card');
      var chosen = radio.value;
      card.querySelectorAll('.feature-media-panel').forEach(function (panel) {
        panel.style.display = panel.getAttribute('data-panel') === chosen ? '' : 'none';
      });
      return;
    }

    // â”€â”€ Icon file preview â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    var iconInput = e.target.closest('.feature-icon-input');
    if (iconInput && iconInput.files && iconInput.files[0]) {
      var reader = new FileReader();
      reader.onload = function (ev) {
        var wrap = iconInput.closest('.feature-icon-wrap');
        if (!wrap) return;
        var img  = wrap.querySelector('.feature-icon-preview');
        var ph   = wrap.querySelector('.feature-icon-placeholder');
        if (img) { img.src = ev.target.result; img.classList.remove('d-none'); }
        if (ph)  ph.style.display = 'none';
        var keepEl = iconInput.closest('.feature-item-card').querySelector('input[name$="[keep_icon]"]');
        if (keepEl) keepEl.remove();
      };
      reader.readAsDataURL(iconInput.files[0]);
      return;
    }

    // â”€â”€ Image file preview â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    var imageInput = e.target.closest('.feature-image-input');
    if (imageInput && imageInput.files && imageInput.files[0]) {
      var reader2 = new FileReader();
      reader2.onload = function (ev) {
        var wrap2 = imageInput.closest('.feature-image-wrap');
        if (!wrap2) return;
        var img2  = wrap2.querySelector('.feature-image-preview');
        var ph2   = wrap2.querySelector('.feature-image-placeholder');
        if (img2) { img2.src = ev.target.result; img2.classList.remove('d-none'); }
        if (ph2)  ph2.style.display = 'none';
        var keepEl2 = imageInput.closest('.feature-item-card').querySelector('input[name$="[keep_image]"]');
        if (keepEl2) keepEl2.remove();
      };
      reader2.readAsDataURL(imageInput.files[0]);
    }
  });

  // â”€â”€ Build media toggle HTML â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  function buildMediaHTML(k) {
    return (
      '<div class="btn-group btn-group-sm mb-2" role="group">' +
        '<input type="radio" name="config[items][' + k + '][media_type]" value="icon" class="btn-check feature-media-radio" id="feat-mt-icon-' + k + '" checked>' +
        '<label class="btn btn-outline-secondary btn-xs" for="feat-mt-icon-' + k + '">Icon</label>' +
        '<input type="radio" name="config[items][' + k + '][media_type]" value="image" class="btn-check feature-media-radio" id="feat-mt-image-' + k + '">' +
        '<label class="btn btn-outline-secondary btn-xs" for="feat-mt-image-' + k + '">Image</label>' +
      '</div>' +
      '<div class="feature-media-panel" data-panel="icon">' +
        '<div class="feature-icon-wrap rounded border" style="width:48px;height:48px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;">' +
          '<img class="feature-icon-preview d-none img-fluid w-100 h-100" style="object-fit:contain" alt="">' +
          '<span class="feature-icon-placeholder text-muted"><i class="ri-image-line fs-18"></i></span>' +
        '</div>' +
        '<input type="file" name="config[items][' + k + '][icon_file]" class="form-control form-control-sm feature-icon-input mt-1" accept="image/*" style="max-width:72px;font-size:0.65rem;">' +
      '</div>' +
      '<div class="feature-media-panel" data-panel="image" style="display:none">' +
        '<div class="feature-image-wrap rounded border" style="width:72px;height:54px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;">' +
          '<img class="feature-image-preview d-none img-fluid w-100 h-100" style="object-fit:cover" alt="">' +
          '<span class="feature-image-placeholder text-muted"><i class="ri-image-add-line fs-20"></i></span>' +
        '</div>' +
        '<input type="file" name="config[items][' + k + '][image_file]" class="form-control form-control-sm feature-image-input mt-1" accept="image/*" style="max-width:72px;font-size:0.65rem;">' +
      '</div>'
    );
  }

  // â”€â”€ Add item â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  addBtn.addEventListener('click', function () {
    var k    = counter++;
    var card = document.createElement('div');
    card.className = 'card mb-2 feature-item-card shadow-sm';
    card.setAttribute('data-item-index', k);
    card.innerHTML =
      '<div class="card-body py-2 px-3">' +
        '<div class="row g-3 align-items-start">' +
          '<div class="col-auto d-flex align-items-center gap-2 pt-2">' +
            '<span class="feature-drag-handle text-muted" style="cursor:grab" title="Drag to reorder"><i class="ri-draggable fs-18"></i></span>' +
            '<span class="badge bg-secondary-transparent text-secondary">#' + (k + 1) + '</span>' +
          '</div>' +
          '<div class="col-auto">' + buildMediaHTML(k) + '</div>' +
          '<div class="col">' +
            '<div class="row g-2">' +
              '<div class="col-md-5">' +
                '<input type="text" name="config[items][' + k + '][title]" class="form-control form-control-sm" placeholder="Title">' +
                '<div class="form-text fs-11">Title</div>' +
              '</div>' +
              '<div class="col-md-7">' +
                '<input type="text" name="config[items][' + k + '][description]" class="form-control form-control-sm" placeholder="Description">' +
                '<div class="form-text fs-11">Description</div>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div class="col-auto pt-2">' +
            '<button type="button" class="btn btn-xs btn-outline-danger js-remove-feature-item" data-item-index="' + k + '" title="Remove"><i class="ri-delete-bin-line"></i></button>' +
          '</div>' +
        '</div>' +
      '</div>';
    container.appendChild(card);
    updateCount();
  });

  // â”€â”€ Remove item â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  document.addEventListener('click', function (e) {
    var removeBtn = e.target.closest('.js-remove-feature-item');
    if (!removeBtn) return;
    var card = removeBtn.closest('.feature-item-card');
    if (card) card.remove();
    updateCount();
  });
})();
</script>
@endsection
