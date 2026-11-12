{{-- Features type config block --}}
@php $featureItems = $cfg['items'] ?? []; @endphp
<div id="cfg-features" class="cfg d-none">

  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="ri-apps-2-line text-secondary fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">Features Options</span>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-5">
      <label class="form-label">Heading</label>
      <input type="text" name="config[heading]" class="form-control"
             value="{{ $cfg['heading'] ?? '' }}" placeholder="Why choose us">
    </div>
    <div class="col-md-5">
      <label class="form-label">Subheading</label>
      <input type="text" name="config[subheading]" class="form-control"
             value="{{ $cfg['subheading'] ?? '' }}" placeholder="Short intro for this section">
    </div>
    <div class="col-md-2">
      <label class="form-label">Columns</label>
      <input type="number" name="config[columns]" min="1" max="4" class="form-control"
             value="{{ $cfg['columns'] ?? 3 }}">
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-2">
      <span class="fw-semibold">Feature Items</span>
      <span class="badge bg-secondary rounded-pill" id="featureItemCount">{{ count($featureItems) }}</span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addFeatureItem">
      <i class="ri-add-line me-1"></i> Add Feature
    </button>
  </div>

  <div class="text-muted small mb-3">
    <i class="ri-drag-move-line me-1"></i> Drag items to reorder.
  </div>

  <div id="featureItems">
    @foreach($featureItems as $k => $item)
      @php
        $mediaType = !empty($item['icon']) ? 'icon' : (!empty($item['image']) ? 'image' : 'icon');
      @endphp
      <div class="card mb-2 feature-item-card shadow-sm" data-item-index="{{ $k }}">
        <div class="card-body py-2 px-3">
          <div class="row g-3 align-items-start">

            {{-- Drag + index --}}
            <div class="col-auto d-flex align-items-center gap-2 pt-2">
              <span class="feature-drag-handle text-muted" style="cursor:grab" title="Drag to reorder">
                <i class="ri-draggable fs-18"></i>
              </span>
              <span class="badge bg-secondary-transparent text-secondary">#{{ $k + 1 }}</span>
            </div>

            {{-- Media toggle + upload panels --}}
            <div class="col-auto">
              <div class="btn-group btn-group-sm mb-2" role="group">
                <input type="radio" name="config[items][{{ $k }}][media_type]" value="icon"
                       class="btn-check feature-media-radio" id="feat-mt-icon-{{ $k }}"
                       @checked($mediaType === 'icon')>
                <label class="btn btn-outline-secondary btn-xs" for="feat-mt-icon-{{ $k }}">Icon</label>
                <input type="radio" name="config[items][{{ $k }}][media_type]" value="image"
                       class="btn-check feature-media-radio" id="feat-mt-image-{{ $k }}"
                       @checked($mediaType === 'image')>
                <label class="btn btn-outline-secondary btn-xs" for="feat-mt-image-{{ $k }}">Image</label>
              </div>

              {{-- Icon panel --}}
              <div class="feature-media-panel" data-panel="icon" @style(['display:none' => $mediaType !== 'icon'])>
                <div class="feature-icon-wrap rounded border"
                     style="width:48px;height:48px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                  @if(!empty($item['icon']))
                    <img src="{{ $mediaUrl($item['icon']) }}"
                         class="feature-icon-preview img-fluid w-100 h-100" style="object-fit:contain" alt="">
                    <input type="hidden" name="config[items][{{ $k }}][keep_icon]" value="1">
                    <input type="hidden" name="config[items][{{ $k }}][existing_icon]" value="{{ $item['icon'] }}">
                  @else
                    <img class="feature-icon-preview d-none img-fluid w-100 h-100" style="object-fit:contain" alt="">
                    <span class="feature-icon-placeholder text-muted"><i class="ri-image-line fs-18"></i></span>
                  @endif
                </div>
                <input type="file" name="config[items][{{ $k }}][icon_file]"
                       class="form-control form-control-sm feature-icon-input mt-1" accept="image/*"
                       style="max-width:72px;font-size:0.65rem;">
              </div>

              {{-- Image panel --}}
              <div class="feature-media-panel" data-panel="image" @style(['display:none' => $mediaType !== 'image'])>
                <div class="feature-image-wrap rounded border"
                     style="width:72px;height:54px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                  @if(!empty($item['image']))
                    <img src="{{ $mediaUrl($item['image']) }}"
                         class="feature-image-preview img-fluid w-100 h-100" style="object-fit:cover" alt="">
                    <input type="hidden" name="config[items][{{ $k }}][keep_image]" value="1">
                    <input type="hidden" name="config[items][{{ $k }}][existing_image]" value="{{ $item['image'] }}">
                  @else
                    <img class="feature-image-preview d-none img-fluid w-100 h-100" style="object-fit:cover" alt="">
                    <span class="feature-image-placeholder text-muted"><i class="ri-image-add-line fs-20"></i></span>
                  @endif
                </div>
                <input type="file" name="config[items][{{ $k }}][image_file]"
                       class="form-control form-control-sm feature-image-input mt-1" accept="image/*"
                       style="max-width:72px;font-size:0.65rem;">
              </div>
            </div>

            {{-- Title + Description --}}
            <div class="col">
              <div class="row g-2">
                <div class="col-md-5">
                  <input type="text" name="config[items][{{ $k }}][title]" class="form-control form-control-sm"
                         value="{{ $item['title'] ?? '' }}" placeholder="Title">
                  <div class="form-text fs-11">Title <span class="badge bg-info-subtle text-info">translatable</span></div>
                </div>
                <div class="col-md-7">
                  <input type="text" name="config[items][{{ $k }}][description]" class="form-control form-control-sm"
                         value="{{ $item['description'] ?? '' }}" placeholder="Description">
                  <div class="form-text fs-11">Description <span class="badge bg-info-subtle text-info">translatable</span></div>
                </div>
              </div>
            </div>

            {{-- Remove --}}
            <div class="col-auto pt-2">
              <button type="button" class="btn btn-xs btn-outline-danger js-remove-feature-item"
                      data-item-index="{{ $k }}" title="Remove">
                <i class="ri-delete-bin-line"></i>
              </button>
            </div>

          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div id="featureEmptyHint" class="text-center py-4 text-muted border rounded {{ count($featureItems) ? 'd-none' : '' }}">
    <i class="ri-add-circle-line fs-28 d-block mb-1 opacity-50"></i>
    No feature items yet. Click <strong>Add Feature</strong>.
  </div>

</div>
