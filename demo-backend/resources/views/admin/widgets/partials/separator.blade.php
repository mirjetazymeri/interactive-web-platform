{{-- Separator type config block --}}
<div id="cfg-separator" class="cfg d-none">

  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="ri-separator text-secondary fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">Separator Options</span>
  </div>

  <div class="row g-3 mb-4">

    {{-- Style --}}
    <div class="col-md-3">
      <label class="form-label">Style</label>
      <select name="config[style]" id="separatorStyle" class="form-select">
        @foreach(['line' => 'Solid Line', 'dashed' => 'Dashed', 'dotted' => 'Dotted', 'double' => 'Double', 'none' => 'Invisible (spacing only)'] as $sv => $sl)
          <option value="{{ $sv }}" @selected(($cfg['style'] ?? 'line') === $sv)>{{ $sl }}</option>
        @endforeach
      </select>
    </div>

    {{-- Thickness --}}
    <div class="col-md-2">
      <label class="form-label">Thickness (px)</label>
      <input type="number" name="config[thickness]" class="form-control"
             min="1" max="20" value="{{ $cfg['thickness'] ?? 1 }}">
    </div>

    {{-- Color --}}
    <div class="col-md-2">
      <label class="form-label">Color</label>
      <input type="color" name="config[color]" class="form-control form-control-color w-100"
             value="{{ $cfg['color'] ?? '#dee2e6' }}">
    </div>

    {{-- Width --}}
    <div class="col-md-2">
      <label class="form-label">Width</label>
      <input type="text" name="config[width]" class="form-control"
             value="{{ $cfg['width'] ?? '100%' }}" placeholder="100%, 80%, 600px…">
      <div class="form-text">CSS width value.</div>
    </div>

    {{-- Alignment --}}
    <div class="col-md-3">
      <label class="form-label">Alignment</label>
      <select name="config[alignment]" class="form-select">
        @foreach(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $av => $al)
          <option value="{{ $av }}" @selected(($cfg['alignment'] ?? 'center') === $av)>{{ $al }}</option>
        @endforeach
      </select>
    </div>

  </div>

  {{-- Spacing --}}
  <div class="row g-3 mb-4">

    <div class="col-md-3">
      <label class="form-label">Spacing Top (px)</label>
      <input type="number" name="config[spacing_top]" class="form-control"
             min="0" max="200" value="{{ $cfg['spacing_top'] ?? 24 }}">
    </div>

    <div class="col-md-3">
      <label class="form-label">Spacing Bottom (px)</label>
      <input type="number" name="config[spacing_bottom]" class="form-control"
             min="0" max="200" value="{{ $cfg['spacing_bottom'] ?? 24 }}">
    </div>

  </div>

  {{-- Optional label --}}
  <div class="d-flex align-items-center gap-2 mb-3 mt-2">
    <i class="ri-text fs-16 text-muted"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">Optional Label</span>
  </div>

  <div class="row g-3">

    <div class="col-md-5">
      <label class="form-label">Label Text</label>
      <input type="text" name="config[label]" class="form-control"
             value="{{ $cfg['label'] ?? '' }}" placeholder="e.g. OR, Section Title">
      <div class="form-text">Leave empty for a plain line.</div>
    </div>

    <div class="col-md-2">
      <label class="form-label">Label Background</label>
      <input type="color" name="config[label_bg]" class="form-control form-control-color w-100"
             value="{{ $cfg['label_bg'] ?? '#ffffff' }}">
    </div>

    <div class="col-md-2">
      <label class="form-label">Label Text Color</label>
      <input type="color" name="config[label_color]" class="form-control form-control-color w-100"
             value="{{ $cfg['label_color'] ?? '#6c757d' }}">
    </div>

  </div>

  {{-- Live preview --}}
  <div class="mt-4 p-3 border rounded bg-light" id="separatorPreviewWrap">
    <div class="text-muted small mb-2 fw-semibold">Preview</div>
    <div id="separatorPreview"
         style="padding-top:{{ $cfg['spacing_top'] ?? 24 }}px; padding-bottom:{{ $cfg['spacing_bottom'] ?? 24 }}px;">
      <div style="
        display: flex;
        align-items: center;
        gap: 12px;
        justify-content: {{ ($cfg['alignment'] ?? 'center') === 'left' ? 'flex-start' : (($cfg['alignment'] ?? 'center') === 'right' ? 'flex-end' : 'center') }};
      ">
        @if(!empty($cfg['label']))
          <div style="flex:1; max-width:40%; height:{{ $cfg['thickness'] ?? 1 }}px;
            background:{{ $cfg['color'] ?? '#dee2e6' }};
            border-style:{{ $cfg['style'] ?? 'solid' }};
            border-color:{{ $cfg['color'] ?? '#dee2e6' }};
            border-top-width:{{ $cfg['thickness'] ?? 1 }}px;"></div>
          <span style="font-size:0.85rem; background:{{ $cfg['label_bg'] ?? '#ffffff' }};
            color:{{ $cfg['label_color'] ?? '#6c757d' }}; padding:0 10px; white-space:nowrap;">
            {{ $cfg['label'] }}
          </span>
          <div style="flex:1; max-width:40%; height:{{ $cfg['thickness'] ?? 1 }}px;
            background:{{ $cfg['color'] ?? '#dee2e6' }};
            border-style:{{ $cfg['style'] ?? 'solid' }};
            border-color:{{ $cfg['color'] ?? '#dee2e6' }};
            border-top-width:{{ $cfg['thickness'] ?? 1 }}px;"></div>
        @else
          <div style="
            width:{{ $cfg['width'] ?? '100%' }};
            height:{{ $cfg['thickness'] ?? 1 }}px;
            background:{{ $cfg['color'] ?? '#dee2e6' }};
          "></div>
        @endif
      </div>
    </div>
  </div>

</div>
