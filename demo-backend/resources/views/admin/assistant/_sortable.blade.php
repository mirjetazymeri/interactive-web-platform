{{-- Drag-to-reorder for a table body, as on the widgets list: posts {ordering: [ids]} to $url. --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080" id="{{ $tbody }}Toast">
  <div class="toast align-items-center border-0 shadow" role="status" aria-live="polite"><div class="d-flex"><div class="toast-body"></div></div></div>
</div>
@section('scripts')
@parent
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
(function () {
  var tbody = document.getElementById(@js($tbody));
  if (!tbody || tbody.querySelectorAll('tr[data-id]').length < 2) return;
  var toastEl = document.querySelector('#' + @js($tbody) + 'Toast .toast');
  function showToast(variant, message) {
    toastEl.className = 'toast align-items-center text-bg-' + variant + ' border-0 shadow';
    toastEl.querySelector('.toast-body').textContent = message;
    bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 2500 }).show();
  }
  Sortable.create(tbody, {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function () {
      var ordering = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function (tr) { return parseInt(tr.dataset.id, 10); });
      fetch(@js($url), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
        body: JSON.stringify({ ordering: ordering })
      })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
        .then(function () { showToast('success', @js(__('assistant.order_saved'))); })
        .catch(function () { showToast('danger', @js(__('assistant.order_error'))); });
    }
  });
})();
</script>
@endsection
