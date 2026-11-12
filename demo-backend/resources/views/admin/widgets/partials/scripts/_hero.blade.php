{{-- Widget form: hero type — live background / overlay preview --}}

@section('scripts')
@parent
<script>
(function () {
  'use strict';

  var bgColorInput    = document.getElementById('heroBgColor');
  var overlayColor    = document.getElementById('heroOverlayColor');
  var overlayInput    = document.getElementById('heroOverlayInput');
  var overlayDisplay  = document.getElementById('heroOverlayDisplay');
  var bgPreview       = document.getElementById('heroBgPreview');
  var overlayEl       = document.getElementById('heroBgPreviewOverlay');
  var bgFileInput     = document.getElementById('heroBgImageFile');
  var videoFileInput  = document.getElementById('heroBgVideoFile');
  var selectedMedia   = document.getElementById('heroSelectedMedia');
  var removeCb        = document.getElementById('removeBgImage');
  var imageObjectUrl;

  function applyPreview() {
    if (!bgPreview || !overlayEl) return;
    if (bgColorInput) bgPreview.style.backgroundColor = bgColorInput.value;
    if (overlayColor) overlayEl.style.background = overlayColor.value;
    if (overlayInput) {
      var opacity = parseInt(overlayInput.value, 10) / 100;
      overlayEl.style.opacity = opacity;
      if (overlayDisplay) overlayDisplay.textContent = overlayInput.value + '%';
    }
  }

  if (bgColorInput)  bgColorInput.addEventListener('input',  applyPreview);
  if (overlayColor)  overlayColor.addEventListener('input',  applyPreview);
  if (overlayInput)  overlayInput.addEventListener('input',  applyPreview);

  // Show selected background image in preview strip
  if (bgFileInput) {
    bgFileInput.addEventListener('change', function () {
      if (!bgFileInput.files || !bgFileInput.files[0]) return;
      if (videoFileInput) videoFileInput.value = '';
      if (removeCb) removeCb.checked = false;
      if (selectedMedia) selectedMedia.textContent = 'Selected image: ' + bgFileInput.files[0].name;
      if (imageObjectUrl) URL.revokeObjectURL(imageObjectUrl);
      imageObjectUrl = URL.createObjectURL(bgFileInput.files[0]);
      if (bgPreview) {
          bgPreview.style.backgroundImage = 'url(' + imageObjectUrl + ')';
          bgPreview.style.backgroundSize  = 'cover';
          bgPreview.style.backgroundPosition = 'center';
      }
    });
  }

  if (videoFileInput) {
    videoFileInput.addEventListener('change', function () {
      if (!videoFileInput.files || !videoFileInput.files[0]) return;
      if (bgFileInput) bgFileInput.value = '';
      if (removeCb) removeCb.checked = false;
      if (bgPreview) bgPreview.style.backgroundImage = '';
      if (selectedMedia) selectedMedia.textContent = 'Selected video: ' + videoFileInput.files[0].name;
    });
  }

  // Clear image preview when "Remove image" checked
  if (removeCb && bgPreview) {
    removeCb.addEventListener('change', function () {
      if (removeCb.checked) {
        if (bgFileInput) bgFileInput.value = '';
        if (videoFileInput) videoFileInput.value = '';
        if (selectedMedia) selectedMedia.textContent = 'Media will be removed on save.';
        bgPreview.style.backgroundImage = '';
      }
    });
  }

  applyPreview();

})();
</script>
@endsection
