{{-- Testimonials type config block --}}
@php $testimonialItems = $cfg['items'] ?? []; @endphp
<div id="cfg-testimonials" class="cfg d-none">

  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="ri-double-quotes-l text-secondary fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">Testimonials Options</span>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <label class="form-label">Heading</label>
      <input type="text" name="config[heading]" class="form-control"
             value="{{ $cfg['heading'] ?? '' }}" placeholder="What clients say">
    </div>
    <div class="col-md-4">
      <label class="form-label">Subheading</label>
      <input type="text" name="config[subheading]" class="form-control"
             value="{{ $cfg['subheading'] ?? '' }}" placeholder="Social proof section intro">
    </div>
    <div class="col-md-2">
      <label class="form-label">Columns</label>
      <input type="number" name="config[columns]" min="1" max="3" class="form-control"
             value="{{ $cfg['columns'] ?? 2 }}">
    </div>
    <div class="col-md-2 d-flex align-items-end pb-1">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="config[show_rating]" value="1" id="showTestimonialRating"
               @checked($cfg['show_rating'] ?? true)>
        <label class="form-check-label" for="showTestimonialRating">Show rating</label>
      </div>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-2">
      <span class="fw-semibold">Testimonials</span>
      <span class="badge bg-secondary rounded-pill" id="testimonialItemCount">{{ count($testimonialItems) }}</span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addTestimonialItem">
      <i class="ri-add-line me-1"></i> Add Testimonial
    </button>
  </div>

  <div class="text-muted small mb-3">
    <i class="ri-drag-move-line me-1"></i> Drag items to reorder.
  </div>

  <div id="testimonialItems">
    @foreach($testimonialItems as $k => $item)
      @php
        $mediaType = !empty($item['logo']) ? 'logo' : (!empty($item['image']) ? 'image' : 'image');
      @endphp
      <div class="card mb-2 testimonial-item-card shadow-sm" data-item-index="{{ $k }}">
        <div class="card-body py-2 px-3">
          <div class="row g-3 align-items-start">

            {{-- Drag + index --}}
            <div class="col-auto d-flex align-items-center gap-2 pt-2">
              <span class="testimonial-drag-handle text-muted" style="cursor:grab" title="Drag to reorder">
                <i class="ri-draggable fs-18"></i>
              </span>
              <span class="badge bg-secondary-transparent text-secondary">#{{ $k + 1 }}</span>
            </div>

            {{-- Media toggle + upload panels --}}
            <div class="col-auto">
              <div class="btn-group btn-group-sm mb-2" role="group">
                <input type="radio" name="config[items][{{ $k }}][media_type]" value="logo"
                       class="btn-check testimonial-media-radio" id="test-mt-logo-{{ $k }}"
                       @checked($mediaType === 'logo')>
                <label class="btn btn-outline-secondary btn-xs" for="test-mt-logo-{{ $k }}">Logo</label>
                <input type="radio" name="config[items][{{ $k }}][media_type]" value="image"
                       class="btn-check testimonial-media-radio" id="test-mt-image-{{ $k }}"
                       @checked($mediaType === 'image')>
                <label class="btn btn-outline-secondary btn-xs" for="test-mt-image-{{ $k }}">Photo</label>
              </div>

              {{-- Logo panel --}}
              <div class="testimonial-media-panel" data-panel="logo" @style(['display:none' => $mediaType !== 'logo'])>
                <div class="testimonial-logo-wrap rounded border"
                     style="width:72px;height:36px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                  @if(!empty($item['logo']))
                    <img src="{{ $mediaUrl($item['logo']) }}"
                         class="testimonial-logo-preview img-fluid w-100 h-100" style="object-fit:contain" alt="">
                    <input type="hidden" name="config[items][{{ $k }}][keep_logo]" value="1">
                    <input type="hidden" name="config[items][{{ $k }}][existing_logo]" value="{{ $item['logo'] }}">
                  @else
                    <img class="testimonial-logo-preview d-none img-fluid w-100 h-100" style="object-fit:contain" alt="">
                    <span class="testimonial-logo-placeholder text-muted"><i class="ri-building-line fs-18"></i></span>
                  @endif
                </div>
                <input type="file" name="config[items][{{ $k }}][logo_file]"
                       class="form-control form-control-sm testimonial-logo-input mt-1" accept="image/*"
                       style="max-width:72px;font-size:0.65rem;">
              </div>

              {{-- Photo panel --}}
              <div class="testimonial-media-panel" data-panel="image" @style(['display:none' => $mediaType !== 'image'])>
                <div class="testimonial-image-wrap rounded-circle border"
                     style="width:48px;height:48px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                  @if(!empty($item['image']))
                    <img src="{{ $mediaUrl($item['image']) }}"
                         class="testimonial-image-preview img-fluid w-100 h-100" style="object-fit:cover" alt="">
                    <input type="hidden" name="config[items][{{ $k }}][keep_image]" value="1">
                    <input type="hidden" name="config[items][{{ $k }}][existing_image]" value="{{ $item['image'] }}">
                  @else
                    <img class="testimonial-image-preview d-none img-fluid w-100 h-100" style="object-fit:cover" alt="">
                    <span class="testimonial-image-placeholder text-muted"><i class="ri-user-line fs-20"></i></span>
                  @endif
                </div>
                <input type="file" name="config[items][{{ $k }}][image_file]"
                       class="form-control form-control-sm testimonial-image-input mt-1" accept="image/*"
                       style="max-width:72px;font-size:0.65rem;">
              </div>
            </div>

            {{-- Quote + meta --}}
            <div class="col">
              <div class="row g-2">
                <div class="col-12">
                  <textarea name="config[items][{{ $k }}][quote]" rows="2" class="form-control form-control-sm"
                            placeholder="Client quote">{{ $item['quote'] ?? '' }}</textarea>
                  <div class="form-text fs-11">Quote <span class="badge bg-info-subtle text-info">translatable</span></div>
                </div>
                <div class="col-md-4">
                  <input type="text" name="config[items][{{ $k }}][name]" class="form-control form-control-sm"
                         value="{{ $item['name'] ?? '' }}" placeholder="Name">
                  <div class="form-text fs-11">Name <span class="badge bg-info-subtle text-info">translatable</span></div>
                </div>
                <div class="col-md-4">
                  <input type="text" name="config[items][{{ $k }}][role]" class="form-control form-control-sm"
                         value="{{ $item['role'] ?? '' }}" placeholder="Role or company">
                  <div class="form-text fs-11">Role <span class="badge bg-info-subtle text-info">translatable</span></div>
                </div>
                <div class="col-md-4">
                  <input type="number" min="1" max="5" name="config[items][{{ $k }}][rating]" class="form-control form-control-sm"
                         value="{{ $item['rating'] ?? 5 }}" placeholder="Rating">
                  <div class="form-text fs-11">Rating (1–5)</div>
                </div>
              </div>
            </div>

            {{-- Remove --}}
            <div class="col-auto pt-2">
              <button type="button" class="btn btn-xs btn-outline-danger js-remove-testimonial-item"
                      data-item-index="{{ $k }}" title="Remove">
                <i class="ri-delete-bin-line"></i>
              </button>
            </div>

          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div id="testimonialEmptyHint" class="text-center py-4 text-muted border rounded {{ count($testimonialItems) ? 'd-none' : '' }}">
    <i class="ri-chat-quote-line fs-28 d-block mb-1 opacity-50"></i>
    No testimonials yet. Click <strong>Add Testimonial</strong>.
  </div>

</div>
