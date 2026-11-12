{{-- Widget form: html type — live preview pane + character count --}}

@section('scripts')
@parent
<script>
(function () {
  'use strict';

  var toggleBtn   = document.getElementById('htmlPreviewToggle');
  var previewPane = document.getElementById('htmlPreviewPane');
  var previewFr   = document.getElementById('htmlPreviewFrame');
  var htmlEditor  = document.getElementById('htmlCodeEditor');
  var cssEditor   = document.querySelector('[name="config[css]"]');
  var refreshBtn  = document.getElementById('htmlRefreshPreview');
  var charCount   = document.getElementById('htmlCharCount');

  // ── Char count ───────────────────────────────────────────────────
  function updateCharCount() {
    if (!htmlEditor || !charCount) return;
    charCount.textContent = htmlEditor.value.length.toLocaleString() + ' chars';
  }
  if (htmlEditor) {
    htmlEditor.addEventListener('input', updateCharCount);
    updateCharCount();
  }

  // ── Render into sandboxed iframe ─────────────────────────────────
  function renderPreview() {
    if (!previewFr || !htmlEditor) return;
    var html = htmlEditor.value;
    var css  = cssEditor ? cssEditor.value : '';
    previewFr.srcdoc = '<!doctype html><html><head><meta charset="utf-8"><style>' + css + '</style></head><body style="margin:0;padding:12px">' + html + '</body></html>';
  }

  // ── Toggle visibility ────────────────────────────────────────────
  var previewVisible = false;
  if (toggleBtn && previewPane) {
    toggleBtn.addEventListener('click', function () {
      previewVisible = !previewVisible;
      previewPane.style.display = previewVisible ? '' : 'none';
      toggleBtn.innerHTML = previewVisible
        ? '<i class="ri-eye-off-line me-1"></i> Hide Preview'
        : '<i class="ri-eye-line me-1"></i> Show Preview';
      if (previewVisible) renderPreview();
    });
  }

  if (refreshBtn) refreshBtn.addEventListener('click', renderPreview);

})();
</script>
@endsection
