{{-- Widget form: faq type — add/remove/sort items + media toggle (icon OR image) --}}

@section('scripts')
@parent
<script>
(function () {
  'use strict';

  var addBtn = document.getElementById('addFaqItem');
  var container = document.getElementById('faqItems');
  var emptyHint = document.getElementById('faqEmptyHint');
  var countEl = document.getElementById('faqItemCount');
  if (!addBtn || !container) return;

  var counter = container.querySelectorAll('.faq-item-card').length;

  function updateCount() {
    var n = container.querySelectorAll('.faq-item-card').length;
    if (countEl) countEl.textContent = n;
    if (emptyHint) emptyHint.classList.toggle('d-none', n > 0);
  }
  updateCount();

  if (typeof Sortable !== 'undefined') {
    Sortable.create(container, {
      handle: '.faq-drag-handle',
      animation: 150,
      ghostClass: 'slide-sortable-ghost',
    });
  }

  container.addEventListener('change', function (e) {
    var radio = e.target.closest('.faq-media-radio');
    if (radio) {
      var card = radio.closest('.faq-item-card');
      var chosen = radio.value;
      card.querySelectorAll('.faq-media-panel').forEach(function (panel) {
        panel.style.display = panel.getAttribute('data-panel') === chosen ? '' : 'none';
      });
      return;
    }

    var iconInput = e.target.closest('.faq-icon-input');
    if (iconInput && iconInput.files && iconInput.files[0]) {
      var reader = new FileReader();
      reader.onload = function (ev) {
        var wrap = iconInput.closest('.faq-icon-wrap');
        if (!wrap) return;
        var img = wrap.querySelector('.faq-icon-preview');
        var placeholder = wrap.querySelector('.faq-icon-placeholder');
        if (img) { img.src = ev.target.result; img.classList.remove('d-none'); }
        if (placeholder) placeholder.style.display = 'none';
        var keepInput = iconInput.closest('.faq-item-card').querySelector('input[name$="[keep_icon]"]');
        if (keepInput) keepInput.remove();
      };
      reader.readAsDataURL(iconInput.files[0]);
      return;
    }

    var imageInput = e.target.closest('.faq-image-input');
    if (imageInput && imageInput.files && imageInput.files[0]) {
      var reader2 = new FileReader();
      reader2.onload = function (ev) {
        var wrap2 = imageInput.closest('.faq-image-wrap');
        if (!wrap2) return;
        var img2 = wrap2.querySelector('.faq-image-preview');
        var placeholder2 = wrap2.querySelector('.faq-image-placeholder');
        if (img2) { img2.src = ev.target.result; img2.classList.remove('d-none'); }
        if (placeholder2) placeholder2.style.display = 'none';
        var keepInput2 = imageInput.closest('.faq-item-card').querySelector('input[name$="[keep_image]"]');
        if (keepInput2) keepInput2.remove();
      };
      reader2.readAsDataURL(imageInput.files[0]);
    }
  });

  function buildMediaHTML(k) {
    return (
      '<div class="btn-group btn-group-sm mb-2" role="group">' +
        '<input type="radio" name="config[items][' + k + '][media_type]" value="icon" class="btn-check faq-media-radio" id="faq-mt-icon-' + k + '" checked>' +
        '<label class="btn btn-outline-secondary btn-xs" for="faq-mt-icon-' + k + '">Icon</label>' +
        '<input type="radio" name="config[items][' + k + '][media_type]" value="image" class="btn-check faq-media-radio" id="faq-mt-image-' + k + '">' +
        '<label class="btn btn-outline-secondary btn-xs" for="faq-mt-image-' + k + '">Image</label>' +
      '</div>' +
      '<div class="faq-media-panel" data-panel="icon">' +
        '<div class="faq-icon-wrap rounded border" style="width:40px;height:40px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;">' +
          '<img class="faq-icon-preview d-none img-fluid w-100 h-100" style="object-fit:contain" alt="">' +
          '<span class="faq-icon-placeholder text-muted"><i class="ri-image-line fs-18"></i></span>' +
        '</div>' +
        '<input type="file" name="config[items][' + k + '][icon_file]" class="form-control form-control-sm faq-icon-input mt-1" accept="image/*" style="max-width:72px;font-size:0.65rem;">' +
      '</div>' +
      '<div class="faq-media-panel" data-panel="image" style="display:none">' +
        '<div class="faq-image-wrap rounded border" style="width:72px;height:48px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;">' +
          '<img class="faq-image-preview d-none img-fluid w-100 h-100" style="object-fit:cover" alt="">' +
          '<span class="faq-image-placeholder text-muted"><i class="ri-image-add-line fs-18"></i></span>' +
        '</div>' +
        '<input type="file" name="config[items][' + k + '][image_file]" class="form-control form-control-sm faq-image-input mt-1" accept="image/*" style="max-width:72px;font-size:0.65rem;">' +
      '</div>'
    );
  }

  addBtn.addEventListener('click', function () {
    var k = counter++;
    var card = document.createElement('div');
    card.className = 'card mb-2 faq-item-card shadow-sm';
    card.setAttribute('data-item-index', k);
    card.innerHTML =
      '<div class="card-body py-2 px-3">' +
        '<div class="row g-3 align-items-start">' +
          '<div class="col-auto mt-2 d-flex align-items-center gap-2"><span class="faq-drag-handle text-muted" style="cursor:grab"><i class="ri-draggable"></i></span><span class="badge bg-secondary-transparent text-secondary">#' + (k + 1) + '</span></div>' +
          '<div class="col-auto">' + buildMediaHTML(k) + '</div>' +
          '<div class="col-md-4"><input type="text" name="config[items][' + k + '][question]" class="form-control form-control-sm" placeholder="Question"></div>' +
          '<div class="col-md-7"><textarea name="config[items][' + k + '][answer]" rows="2" class="form-control form-control-sm" placeholder="Answer"></textarea></div>' +
          '<div class="col-auto mt-1"><button type="button" class="btn btn-xs btn-outline-danger js-remove-faq-item" data-item-index="' + k + '"><i class="ri-delete-bin-line"></i></button></div>' +
        '</div>' +
      '</div>';
    container.appendChild(card);
    updateCount();
  });

  document.addEventListener('click', function (e) {
    var removeBtn = e.target.closest('.js-remove-faq-item');
    if (!removeBtn) return;
    var card = removeBtn.closest('.faq-item-card');
    if (card) card.remove();
    updateCount();
  });
})();
</script>
@endsection
