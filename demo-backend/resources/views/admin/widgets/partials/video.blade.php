{{-- Video: URL + autoplay/muted toggles --}}
<div id="cfg-video" class="cfg d-none">
  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label">Video URL</label>
      <div class="input-group">
        <span class="input-group-text"><i class="ri-video-line"></i></span>
        <input type="url" id="videoUrlInput" name="config[video_url]" class="form-control"
               value="{{ $cfg['video_url'] ?? '' }}"
               placeholder="https://youtube.com/watch?v=… or https://vimeo.com/…">
        <button type="button" class="btn btn-outline-secondary" id="videoPreviewBtn"><i class="ri-eye-line me-1"></i>Preview</button>
      </div>
      <div class="form-text">Supports YouTube and Vimeo URLs.</div>
    </div>
    <div class="col-md-4">
      <label class="form-label">Aspect Ratio</label>
      <select name="config[aspect_ratio]" class="form-select">
        @foreach(['16:9'=>'16:9 (Widescreen)','4:3'=>'4:3 (Standard)','1:1'=>'1:1 (Square)'] as $ar => $arl)
          <option value="{{ $ar }}" @selected(($cfg['aspect_ratio'] ?? '16:9') === $ar)>{{ $arl }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-12 d-flex gap-4">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="videoAutoplay" name="config[autoplay]" value="1"
               @checked($cfg['autoplay'] ?? false)>
        <label class="form-check-label" for="videoAutoplay">Autoplay</label>
      </div>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="videoMuted" name="config[muted]" value="1"
               @checked($cfg['muted'] ?? false)>
        <label class="form-check-label" for="videoMuted">Muted</label>
      </div>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="videoLoop" name="config[loop]" value="1"
               @checked($cfg['loop'] ?? false)>
        <label class="form-check-label" for="videoLoop">Loop</label>
      </div>
    </div>
    <div class="col-12" id="videoPreviewBox" style="display:none">
      <div class="ratio ratio-16x9 rounded overflow-hidden border bg-dark" style="max-width:560px">
        <iframe id="videoPreviewFrame" src="" frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
      </div>
      <div class="form-text mt-1"><i class="ri-information-line me-1"></i>Preview only — embed settings (autoplay/muted/loop) apply on the frontend.</div>
    </div>
  </div>
</div>
