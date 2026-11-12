{{-- Widget form: video type — live YouTube / Vimeo embed preview --}}

@section('scripts')
@parent
<script>
(function () {
  'use strict';

  var urlInput   = document.getElementById('videoUrlInput');
  var previewBtn = document.getElementById('videoPreviewBtn');
  var previewBox = document.getElementById('videoPreviewBox');
  var previewFr  = document.getElementById('videoPreviewFrame');
  if (!urlInput || !previewBtn || !previewBox || !previewFr) return;

  function toEmbedUrl(url) {
    url = (url || '').trim();
    var ytMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
    if (ytMatch) return 'https://www.youtube.com/embed/' + ytMatch[1] + '?rel=0';
    var vmMatch = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
    if (vmMatch) return 'https://player.vimeo.com/video/' + vmMatch[1];
    return null;
  }

  previewBtn.addEventListener('click', function () {
    var embedUrl = toEmbedUrl(urlInput.value);
    if (!embedUrl) {
      previewBox.style.display = 'none';
      alert('Could not parse video URL. Supported: YouTube and Vimeo.');
      return;
    }
    previewFr.src = embedUrl;
    previewBox.style.display = '';
  });

  // Auto-preview when editing an existing widget with a saved URL
  if (urlInput.value) {
    var em = toEmbedUrl(urlInput.value);
    if (em) { previewFr.src = em; previewBox.style.display = ''; }
  }

})();
</script>
@endsection
