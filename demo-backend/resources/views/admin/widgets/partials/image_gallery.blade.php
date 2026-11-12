{{-- Image Gallery type config block --}}
@php $galleryItems = $cfg['items'] ?? []; @endphp
<div id="cfg-image_gallery" class="cfg d-none">

  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="ri-gallery-line text-secondary fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">Gallery Options</span>
  </div>

  {{-- Global settings --}}
  <div class="row g-3 mb-4">

    <div class="col-md-2">
      <label class="form-label">Columns</label>
      <input type="number" name="config[columns]" class="form-control"
             min="1" max="6" value="{{ $cfg['columns'] ?? 3 }}">
    </div>

    <div class="col-md-2">
      <label class="form-label">Gap (px)</label>
      <input type="number" name="config[gap]" class="form-control"
             min="0" max="60" value="{{ $cfg['gap'] ?? 12 }}">
    </div>

    <div class="col-md-3">
      <label class="form-label">Aspect Ratio</label>
      <select name="config[aspect_ratio]" class="form-select">
        @foreach(['square' => 'Square (1:1)', 'landscape' => 'Landscape (4:3)', 'wide' => 'Wide (16:9)', 'portrait' => 'Portrait (3:4)', 'free' => 'Free (auto height)'] as $av => $al)
          <option value="{{ $av }}" @selected(($cfg['aspect_ratio'] ?? 'square') === $av)>{{ $al }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Hover Effect</label>
      <select name="config[hover_effect]" class="form-select">
        @foreach(['none' => 'None', 'zoom' => 'Zoom', 'overlay' => 'Dark overlay', 'caption' => 'Slide-up caption'] as $hv => $hl)
          <option value="{{ $hv }}" @selected(($cfg['hover_effect'] ?? 'zoom') === $hv)>{{ $hl }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-2 d-flex align-items-end pb-1">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="config[lightbox]" value="1" id="galleryLightbox"
               @checked($cfg['lightbox'] ?? true)>
        <label class="form-check-label" for="galleryLightbox">Lightbox</label>
      </div>
    </div>

  </div>

  {{-- Items header --}}
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-2">
      <span class="fw-semibold">Images</span>
      <span class="badge bg-secondary rounded-pill" id="galleryItemCount">{{ count($galleryItems) }}</span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addGalleryItem">
      <i class="ri-add-line me-1"></i> Add Image
    </button>
  </div>

  <div class="text-muted small mb-3">
    <i class="ri-drag-move-line me-1"></i> Drag images to reorder.
  </div>

  {{-- Existing items --}}
  <div id="galleryItems">
    @foreach($galleryItems as $k => $item)
      <div class="card mb-2 gallery-item-card shadow-sm" data-item-index="{{ $k }}">
        <div class="card-body py-2 px-3">
          <div class="row g-3 align-items-center">

            {{-- Drag handle + index --}}
            <div class="col-auto d-flex align-items-center gap-2">
              <span class="gallery-drag-handle text-muted" style="cursor:grab" title="Drag to reorder">
                <i class="ri-draggable fs-18"></i>
              </span>
              <span class="badge bg-secondary-transparent text-secondary">#{{ $k + 1 }}</span>
            </div>

            {{-- Thumbnail --}}
            <div class="col-auto">
              <div class="gallery-thumb-wrap rounded overflow-hidden border"
                   style="width:72px;height:72px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;">
                @if(!empty($item['image']))
                  <img src="{{ $mediaUrl($item['image']) }}"
                       class="gallery-img-preview img-fluid w-100 h-100"
                       style="object-fit:cover" alt="{{ $item['alt'] ?? '' }}">
                  <input type="hidden" name="config[items][{{ $k }}][keep_image]" value="1">
                  <input type="hidden" name="config[items][{{ $k }}][existing_image]" value="{{ $item['image'] }}">
                @else
                  <img class="gallery-img-preview d-none img-fluid w-100 h-100" style="object-fit:cover" alt="">
                  <span class="gallery-img-placeholder text-muted"><i class="ri-image-add-line fs-20"></i></span>
                @endif
              </div>
              <input type="file" name="config[items][{{ $k }}][image_file]"
                     class="form-control form-control-sm gallery-file-input mt-1" accept="image/*"
                     style="max-width:72px;font-size:0.65rem;">
            </div>

            {{-- Fields --}}
            <div class="col">
              <div class="row g-2">
                <div class="col-md-4">
                  <input type="text" name="config[items][{{ $k }}][alt]" class="form-control form-control-sm"
                         value="{{ $item['alt'] ?? '' }}" placeholder="Alt text (SEO)">
                  <div class="form-text fs-11">Alt text</div>
                </div>
                <div class="col-md-4">
                  <input type="text" name="config[items][{{ $k }}][caption]" class="form-control form-control-sm"
                         value="{{ $item['caption'] ?? '' }}" placeholder="Caption (optional)">
                  <div class="form-text fs-11">Caption <span class="badge bg-info-subtle text-info">translatable</span></div>
                </div>
                <div class="col-md-4">
                  <input type="text" name="config[items][{{ $k }}][link]" class="form-control form-control-sm"
                         value="{{ $item['link'] ?? '' }}" placeholder="Link URL (optional)">
                  <div class="form-text fs-11">On-click link</div>
                </div>
              </div>
            </div>

            {{-- Remove --}}
            <div class="col-auto">
              <button type="button" class="btn btn-xs btn-outline-danger js-remove-gallery-item"
                      data-item-index="{{ $k }}" title="Remove image">
                <i class="ri-delete-bin-line"></i>
              </button>
            </div>

          </div>
        </div>
      </div>
    @endforeach
  </div>

  @if(count($galleryItems) === 0)
    <div id="galleryEmptyHint" class="text-center py-4 text-muted border rounded">
      <i class="ri-image-add-line fs-32 d-block mb-1 opacity-50"></i>
      No images yet. Click <strong>Add Image</strong> to get started.
    </div>
  @else
    <div id="galleryEmptyHint" class="text-center py-4 text-muted border rounded d-none">
      <i class="ri-image-add-line fs-32 d-block mb-1 opacity-50"></i>
      No images yet. Click <strong>Add Image</strong> to get started.
    </div>
  @endif

</div>
