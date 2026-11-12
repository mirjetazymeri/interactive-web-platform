{{-- Carousel: per-slide config + global settings --}}
@php $slides = $cfg['slides'] ?? []; @endphp
<div id="cfg-carousel" class="cfg d-none">

  {{-- ── Global carousel settings ── --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <label class="form-label">Transition</label>
      <select name="config[carousel_transition]" class="form-select">
        @foreach(['slide'=>'Slide','fade'=>'Fade'] as $tv => $tl)
          <option value="{{ $tv }}" @selected(($cfg['carousel_transition'] ?? 'slide') === $tv)>{{ $tl }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Auto-play Interval (ms)</label>
      <input type="number" name="config[carousel_interval]" class="form-control"
             value="{{ $cfg['carousel_interval'] ?? 5000 }}" min="0" step="500" placeholder="5000">
      <div class="form-text">0 = disabled</div>
    </div>
    <div class="col-md-3">
      <label class="form-label">Overlay Opacity <small class="text-muted">(0–100)</small></label>
      <div class="input-group">
        <input type="number" name="config[overlay_opacity]" class="form-control"
               value="{{ $cfg['overlay_opacity'] ?? 40 }}" min="0" max="100" step="5" id="overlayOpacityInput">
        <span class="input-group-text" id="overlayOpacityDisplay">{{ $cfg['overlay_opacity'] ?? 40 }}%</span>
      </div>
    </div>
    <div class="col-md-3 d-flex align-items-end pb-1">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="carouselLoop" name="config[carousel_loop]" value="1"
               @checked($cfg['carousel_loop'] ?? true)>
        <label class="form-check-label" for="carouselLoop">Loop Slides</label>
      </div>
    </div>
  </div>

  {{-- ── Slide list header ── --}}
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-2">
      <span class="fw-semibold">Slides</span>
      <span class="badge bg-secondary rounded-pill" id="slideCount">{{ count($slides) }}</span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addCarouselSlide">
      <i class="ri-add-line me-1"></i> Add Slide
    </button>
  </div>

  <div class="text-muted small mb-3">
    <i class="ri-drag-move-line me-1"></i> Drag slides to reorder.
  </div>

  {{-- ── Existing slides ── --}}
  <div id="carouselSlides">
    @foreach($slides as $k => $slide)
      <div class="card mb-3 carousel-slide-card shadow-sm" data-slide-index="{{ $k }}">
        {{-- Slide header --}}
        <div class="card-header bg-light py-2 px-3 d-flex align-items-center gap-2">
          <span class="slide-drag-handle text-muted me-1" style="cursor:grab" title="Drag to reorder">
            <i class="ri-draggable"></i>
          </span>
          <span class="badge bg-primary-transparent text-primary fw-semibold">Slide {{ $k + 1 }}</span>
          @if(!empty($slide['headline']))
            <span class="text-muted small ms-1 text-truncate" style="max-width:200px">{{ $slide['headline'] }}</span>
          @endif
          <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-xs btn-light js-toggle-slide" title="Expand/Collapse">
              <i class="ri-arrow-down-s-line"></i>
            </button>
            <button type="button" class="btn btn-xs btn-outline-danger js-remove-carousel-slide" data-slide-index="{{ $k }}" title="Remove slide">
              <i class="ri-delete-bin-line"></i>
            </button>
          </div>
        </div>

        {{-- Slide body --}}
        <div class="card-body slide-body">
          <div class="row g-3">
            {{-- Image column --}}
            <div class="col-md-3">
              <label class="form-label fs-12 text-muted text-uppercase">Image</label>
              <div class="slide-image-wrap rounded overflow-hidden border mb-2"
                   style="height:100px; background:#f8f9fa; display:flex; align-items:center; justify-content:center;">
                @if(!empty($slide['image']))
                  <img src="{{ $mediaUrl($slide['image']) }}"
                       class="slide-img-preview img-fluid w-100 h-100"
                       style="object-fit:cover" alt="{{ $slide['alt'] ?? '' }}">
                  <input type="hidden" name="config[slides][{{ $k }}][keep_image]" value="1">
                  <input type="hidden" name="config[slides][{{ $k }}][existing_image]" value="{{ $slide['image'] }}">
                @else
                  <img class="slide-img-preview d-none img-fluid w-100 h-100" style="object-fit:cover" alt="">
                  <span class="text-muted small slide-img-placeholder"><i class="ri-image-add-line fs-24 d-block mb-1"></i>No image</span>
                @endif
              </div>
              <input type="file" name="config[slides][{{ $k }}][image_file]"
                     class="form-control form-control-sm slide-file-input" accept="image/*">
            </div>

            {{-- Content column --}}
            <div class="col-md-9">
              <div class="row g-2">
                <div class="col-md-6">
                  <input type="text" name="config[slides][{{ $k }}][alt]" class="form-control form-control-sm"
                         value="{{ $slide['alt'] ?? '' }}" placeholder="Alt text (SEO)">
                  <div class="form-text">Alt / SEO</div>
                </div>
                <div class="col-md-6">
                  <select name="config[slides][{{ $k }}][text_position]" class="form-select form-select-sm">
                    @foreach(['left'=>'Text Left','center'=>'Text Center','right'=>'Text Right'] as $pv => $pl)
                      <option value="{{ $pv }}" @selected(($slide['text_position'] ?? 'center') === $pv)>{{ $pl }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <input type="text" name="config[slides][{{ $k }}][headline]" class="form-control form-control-sm"
                         value="{{ $slide['headline'] ?? '' }}" placeholder="Headline">
                </div>
                <div class="col-md-6">
                  <input type="text" name="config[slides][{{ $k }}][subtitle]" class="form-control form-control-sm"
                         value="{{ $slide['subtitle'] ?? '' }}" placeholder="Subtitle">
                </div>
                <div class="col-md-4">
                  <input type="text" name="config[slides][{{ $k }}][cta_text]" class="form-control form-control-sm"
                         value="{{ $slide['cta_text'] ?? '' }}" placeholder="CTA label">
                </div>
                <div class="col-md-4">
                  <input type="text" name="config[slides][{{ $k }}][cta_link]" class="form-control form-control-sm"
                         value="{{ $slide['cta_link'] ?? '' }}" placeholder="CTA URL (https://…)">
                </div>
                <div class="col-md-2">
                  <label class="form-label fs-11 text-muted mb-1">CTA text color</label>
                  <input type="color" name="config[slides][{{ $k }}][cta_color]"
                         class="form-control form-control-sm form-control-color w-100"
                         value="{{ $slide['cta_color'] ?? '#ffffff' }}" title="CTA text color">
                </div>
                <div class="col-md-2">
                  <label class="form-label fs-11 text-muted mb-1">CTA bg color</label>
                  <input type="color" name="config[slides][{{ $k }}][cta_bg]"
                         class="form-control form-control-sm form-control-color w-100"
                         value="{{ $slide['cta_bg'] ?? '#0d6efd' }}" title="CTA background">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  @if(count($slides) === 0)
    <div id="carouselEmptyHint" class="text-center py-4 text-muted border rounded">
      <i class="ri-image-add-line fs-32 d-block mb-1 opacity-50"></i>
      No slides yet. Click <strong>Add Slide</strong> to get started.
    </div>
  @else
    <div id="carouselEmptyHint" class="text-center py-4 text-muted border rounded d-none">
      <i class="ri-image-add-line fs-32 d-block mb-1 opacity-50"></i>
      No slides yet. Click <strong>Add Slide</strong> to get started.
    </div>
  @endif

</div>
