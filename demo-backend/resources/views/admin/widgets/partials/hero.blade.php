{{-- Hero Banner type config block --}}
@php
  $heroMedia = app(\App\Services\Widgets\HeroMediaService::class)->representation($widgetConfig ?? []);
  $heroBgExists = ($heroMedia['type'] ?? null) === 'image';
  $heroBgUrl = $heroBgExists ? $heroMedia['url'] : '';
@endphp
<div id="cfg-hero" class="cfg d-none">

  {{-- ── Background ───────────────────────────────────────────────── --}}
  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="ri-image-fill text-success fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">Background</span>
  </div>

  <div class="row g-3 mb-4">
    {{-- Background image --}}
    <div class="col-md-6">
      <label class="form-label">Background Media</label>
      @if($editing && $heroMedia)
        <div class="mb-2 position-relative d-inline-block">
          <div class="small fw-semibold mb-1">Current media: {{ ucfirst($heroMedia['type']) }}</div>
          @if($heroBgExists)
          <img src="{{ $heroBgUrl }}" alt="Hero background"
               class="rounded border" style="max-height:90px; max-width:240px; object-fit:cover;">
          @else
            <video src="{{ $heroMedia['url'] }}" controls preload="metadata" class="rounded border" style="max-height:140px; max-width:240px;"></video>
          @endif
          <label class="form-check d-block mt-1">
            <input type="checkbox" class="form-check-input" name="config[remove_media]" value="1"
                   id="removeBgImage" @checked(old('config.remove_media', false))>
            <span class="form-check-label text-danger small">Remove media</span>
          </label>
        </div>
      @endif
      <label for="heroBgImageFile" class="form-label small">Upload image (up to 5 MiB)</label>
      <input type="file" name="config[bg_image_file]" id="heroBgImageFile"
             class="form-control" accept="image/*">
      <div class="form-text">Recommended: 1920×600 px or wider. JPG/WebP preferred.</div>
      <label for="heroBgVideoFile" class="form-label small mt-2">Or upload video</label>
      <input type="file" name="config[bg_video_file]" id="heroBgVideoFile" class="form-control"
             accept=".mp4,.webm,.mov,video/mp4,video/webm,video/quicktime">
      <div class="form-text">MP4, WebM or MOV, up to {{ config('widgets.hero_video_max_kb') }} KiB. Uploading replaces the current media. MOV and some codecs may need conversion before browser playback.</div>
      <div id="heroSelectedMedia" class="form-text" aria-live="polite"></div>
    </div>

    {{-- Fallback bg colour --}}
    <div class="col-md-3">
      <label class="form-label">Fallback BG Colour</label>
      <input type="color" name="config[bg_color]" class="form-control form-control-color w-100"
             value="{{ $cfg['bg_color'] ?? '#1a1a2e' }}" id="heroBgColor">
      <div class="form-text">Shown if no background media is rendered.</div>
    </div>

    {{-- Overlay colour + opacity --}}
    <div class="col-md-2">
      <label class="form-label">Overlay Colour</label>
      <input type="color" name="config[hero_overlay_color]" class="form-control form-control-color w-100"
             value="{{ $cfg['hero_overlay_color'] ?? '#000000' }}" id="heroOverlayColor">
    </div>
    <div class="col-md-1">
      <label class="form-label">Opacity&nbsp;<span id="heroOverlayDisplay">{{ $cfg['hero_overlay_opacity'] ?? 40 }}%</span></label>
      <input type="range" name="config[hero_overlay_opacity]" class="form-range"
             id="heroOverlayInput" min="0" max="100"
             value="{{ $cfg['hero_overlay_opacity'] ?? 40 }}">
    </div>
  </div>

  {{-- Live preview strip --}}
  <div id="heroBgPreview"
       class="rounded border mb-4 d-flex align-items-center justify-content-center position-relative overflow-hidden"
      style="height:120px; background-color:{{ $cfg['bg_color'] ?? '#1a1a2e' }}; transition: background-color .3s;{{ $heroBgExists ? " background-image:url('{$heroBgUrl}'); background-size:cover; background-position:center;" : '' }}">
    <div id="heroBgPreviewOverlay"
         class="position-absolute top-0 start-0 w-100 h-100"
         style="background:{{ $cfg['hero_overlay_color'] ?? '#000000' }}; opacity:{{ ($cfg['hero_overlay_opacity'] ?? 40)/100 }};"></div>
    <span class="position-relative text-white fw-semibold fs-13 opacity-75">Background preview</span>
  </div>

  {{-- ── Layout ────────────────────────────────────────────────────── --}}
  <div class="d-flex align-items-center gap-2 mb-3 mt-2">
    <i class="ri-layout-top-line text-success fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">Layout</span>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <label class="form-label">Min Height</label>
      <select name="config[hero_min_height]" class="form-select">
        @foreach(['300px'=>'300 px','400px'=>'400 px','500px'=>'500 px','60vh'=>'60 vh','70vh'=>'70 vh','80vh'=>'80 vh','100vh'=>'Full screen'] as $v => $l)
          <option value="{{ $v }}" @selected(($cfg['hero_min_height'] ?? '400px') === $v)>{{ $l }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Text Alignment</label>
      <select name="config[hero_text_align]" class="form-select">
        @foreach(['left'=>'Left','center'=>'Center','right'=>'Right'] as $v => $l)
          <option value="{{ $v }}" @selected(($cfg['hero_text_align'] ?? 'center') === $v)>{{ $l }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Content Width</label>
      <select name="config[hero_content_width]" class="form-select">
        @foreach(['full'=>'Full width','wide'=>'Wide (col-10)','medium'=>'Medium (col-8)','narrow'=>'Narrow (col-6)'] as $v => $l)
          <option value="{{ $v }}" @selected(($cfg['hero_content_width'] ?? 'medium') === $v)>{{ $l }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-3 d-flex align-items-end gap-3 pb-1">
      <div class="form-check form-switch">
        <input type="hidden" name="config[hero_fullscreen]" value="0">
        <input class="form-check-input" type="checkbox" id="heroFullscreen"
               name="config[hero_fullscreen]" value="1"
               @checked($cfg['hero_fullscreen'] ?? false)>
        <label class="form-check-label" for="heroFullscreen">Fullscreen mode</label>
      </div>
    </div>
  </div>

  {{-- ── Content ───────────────────────────────────────────────────── --}}
  <div class="d-flex align-items-center gap-2 mb-3 mt-2">
    <i class="ri-text text-success fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">Default Content <small class="text-muted text-lowercase">(overridden per locale in Section 5)</small></span>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <label class="form-label">Headline</label>
      <input type="text" name="config[headline]" class="form-control"
             value="{{ $cfg['headline'] ?? '' }}" placeholder="e.g. Welcome to Our Store">
    </div>
    <div class="col-md-6">
      <label class="form-label">Subtitle</label>
      <input type="text" name="config[subtitle]" class="form-control"
             value="{{ $cfg['subtitle'] ?? '' }}" placeholder="A short supporting line">
    </div>

    <div class="col-md-4">
      <label class="form-label">CTA Button Text</label>
      <input type="text" name="config[cta_text]" class="form-control"
             value="{{ $cfg['cta_text'] ?? '' }}" placeholder="e.g. Shop Now">
    </div>
    <div class="col-md-4">
      <label class="form-label">CTA Button Link</label>
      <input type="url" name="config[cta_link]" class="form-control"
             value="{{ $cfg['cta_link'] ?? '' }}" placeholder="https://...">
    </div>
    <div class="col-md-2">
      <label class="form-label">CTA Style</label>
      <select name="config[cta_style]" class="form-select">
        @foreach(['primary'=>'Primary','secondary'=>'Secondary','outline-light'=>'Outline Light','outline-dark'=>'Outline Dark','custom'=>'Custom colour'] as $v => $l)
          <option value="{{ $v }}" @selected(($cfg['cta_style'] ?? 'primary') === $v)>{{ $l }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-1">
      <label class="form-label">Text</label>
      <input type="color" name="config[cta_color]" class="form-control form-control-color w-100"
             value="{{ $cfg['cta_color'] ?? '#ffffff' }}">
    </div>
    <div class="col-md-1">
      <label class="form-label">BG</label>
      <input type="color" name="config[cta_bg]" class="form-control form-control-color w-100"
             value="{{ $cfg['cta_bg'] ?? '#0d6efd' }}">
    </div>

    <div class="col-12">
      <label class="form-label" for="heroRotatingLabels">Globe labels</label>
      <textarea name="config[rotating_labels]" id="heroRotatingLabels" class="form-control" rows="4" maxlength="2000"
                placeholder="Your Project Manager&#10;Your Data Engineer&#10;Your Software Developer">{{ old('config.rotating_labels', implode("\n", (array) ($cfg['rotating_labels'] ?? []))) }}</textarea>
      <div class="form-text">One label per line, up to {{ \App\Services\Widgets\Types\HeroWidgetTypeService::MAX_LABELS }}. When filled, the website shows the animated globe hero with these labels instead of the background media. Leave empty to keep the media hero.</div>
    </div>
  </div>

</div>
