{{-- Widget form: pricing type — add/remove/sort items --}}

@section('scripts')
@parent
<script>
(function () {
  'use strict';

  var addBtn = document.getElementById('addPricingItem');
  var container = document.getElementById('pricingItems');
  var emptyHint = document.getElementById('pricingEmptyHint');
  var countEl = document.getElementById('pricingItemCount');
  if (!addBtn || !container) return;

  var counter = container.querySelectorAll('.pricing-item-card').length;

  function updateCount() {
    var n = container.querySelectorAll('.pricing-item-card').length;
    if (countEl) countEl.textContent = n;
    if (emptyHint) emptyHint.classList.toggle('d-none', n > 0);
  }
  updateCount();

  if (typeof Sortable !== 'undefined') {
    Sortable.create(container, {
      handle: '.pricing-drag-handle',
      animation: 150,
      ghostClass: 'slide-sortable-ghost',
    });
  }

  addBtn.addEventListener('click', function () {
    var k = counter++;
    var card = document.createElement('div');
    card.className = 'card mb-2 pricing-item-card shadow-sm';
    card.setAttribute('data-item-index', k);
    card.innerHTML =
      '<div class="card-body py-2 px-3">' +
        '<div class="row g-2 align-items-start">' +
          '<div class="col-auto mt-2"><span class="pricing-drag-handle text-muted" style="cursor:grab"><i class="ri-draggable"></i></span></div>' +
          '<div class="col-md-2"><input type="text" name="config[items][' + k + '][name]" class="form-control form-control-sm" placeholder="Plan name"></div>' +
          '<div class="col-md-1"><input type="text" name="config[items][' + k + '][price]" class="form-control form-control-sm" placeholder="29"></div>' +
          '<div class="col-md-2"><input type="text" name="config[items][' + k + '][period]" class="form-control form-control-sm" placeholder="/month" value="/month"></div>' +
          '<div class="col-md-2"><input type="text" name="config[items][' + k + '][badge]" class="form-control form-control-sm" placeholder="Most Popular"></div>' +
          '<div class="col-md-4"><input type="text" name="config[items][' + k + '][description]" class="form-control form-control-sm" placeholder="Short plan description"></div>' +
          '<div class="col-auto mt-1"><button type="button" class="btn btn-xs btn-outline-danger js-remove-pricing-item" data-item-index="' + k + '"><i class="ri-delete-bin-line"></i></button></div>' +
        '</div>' +
        '<div class="row g-2 mt-1">' +
          '<div class="col-md-6"><textarea name="config[items][' + k + '][features]" rows="2" class="form-control form-control-sm" placeholder="Feature list (one per line)"></textarea></div>' +
          '<div class="col-md-2"><input type="text" name="config[items][' + k + '][cta_text]" class="form-control form-control-sm" placeholder="CTA text" value="Get started"></div>' +
          '<div class="col-md-3"><input type="text" name="config[items][' + k + '][cta_link]" class="form-control form-control-sm" placeholder="CTA link"></div>' +
          '<div class="col-md-1 d-flex align-items-center"><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="config[items][' + k + '][is_popular]" value="1" id="pricingPopular' + k + '"><label class="form-check-label small" for="pricingPopular' + k + '">Popular</label></div></div>' +
        '</div>' +
      '</div>';
    container.appendChild(card);
    updateCount();
  });

  document.addEventListener('click', function (e) {
    var removeBtn = e.target.closest('.js-remove-pricing-item');
    if (!removeBtn) return;
    var card = removeBtn.closest('.pricing-item-card');
    if (card) card.remove();
    updateCount();
  });
})();
</script>
@endsection
