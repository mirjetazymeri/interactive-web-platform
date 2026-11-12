{{-- Custom HTML: HTML / CSS / JS editors --}}
<div id="cfg-html" class="cfg d-none">
  <div class="mb-2">
    <div class="d-flex align-items-center justify-content-between mb-1">
      <label class="form-label mb-0">HTML</label>
      <small class="text-muted" id="htmlCharCount"></small>
    </div>
    <textarea id="htmlCodeEditor" name="config[html]" class="form-control font-monospace" rows="10"
              spellcheck="false"
              placeholder="<div class=&quot;my-widget&quot;>&#10;  <h2>Heading</h2>&#10;  <p>Content here…</p>&#10;</div>">{{ $cfg['html'] ?? '' }}</textarea>
  </div>
  {{-- Preview toggle --}}
  <div class="d-flex align-items-center justify-content-between mb-2">
    <span></span>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="htmlPreviewToggle">
      <i class="ri-eye-line me-1"></i> Show Preview
    </button>
  </div>
  <div class="row g-2">
    <div class="col-md-6">
      <label class="form-label">CSS</label>
      <textarea name="config[css]" class="form-control font-monospace" rows="5"
                spellcheck="false"
                placeholder=".my-widget { padding: 20px; }">{{ $cfg['css'] ?? '' }}</textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label">JS <small class="text-muted">(runs on frontend only)</small></label>
      <textarea name="config[js]" class="form-control font-monospace" rows="5"
                spellcheck="false"
                placeholder="// Optional JS">{{ $cfg['js'] ?? '' }}</textarea>
    </div>
  </div>
  {{-- Live preview pane --}}
  <div id="htmlPreviewPane" class="mt-3" style="display:none">
    <div class="d-flex align-items-center gap-2 mb-1">
      <span class="fw-semibold fs-13">Live Preview</span>
      <span class="badge bg-warning-transparent text-warning fw-normal fs-11">CSS applied — JS not executed</span>
      <button type="button" class="btn btn-xs btn-light ms-auto" id="htmlRefreshPreview">
        <i class="ri-refresh-line"></i>
      </button>
    </div>
    <div class="border rounded overflow-hidden" style="height:300px; background:#fff;">
      <iframe id="htmlPreviewFrame" style="width:100%; height:100%; border:none;"
              sandbox="allow-same-origin"></iframe>
    </div>
  </div>
  <div class="form-check form-switch mt-3">
    <input class="form-check-input" type="checkbox" id="enableHtmlPreview"
           name="config[enable_preview]" value="1" @checked($cfg['enable_preview'] ?? false)>
    <label class="form-check-label" for="enableHtmlPreview">Enable frontend preview mode</label>
  </div>
</div>
