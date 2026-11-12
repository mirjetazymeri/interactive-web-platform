{{-- FAQ type config block --}}
@php $faqItems = $cfg['items'] ?? []; @endphp
<div id="cfg-faq" class="cfg d-none">

  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="ri-question-answer-line text-secondary fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">FAQ Options</span>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <label class="form-label">Heading</label>
      <input type="text" name="config[heading]" class="form-control"
             value="{{ $cfg['heading'] ?? '' }}" placeholder="Frequently Asked Questions">
    </div>
    <div class="col-md-4">
      <label class="form-label">Subheading</label>
      <input type="text" name="config[subheading]" class="form-control"
             value="{{ $cfg['subheading'] ?? '' }}" placeholder="Support and common answers">
    </div>
    <div class="col-md-2">
      <label class="form-label">Layout</label>
      <select name="config[layout_mode]" class="form-select">
        <option value="accordion" @selected(($cfg['layout_mode'] ?? 'accordion') === 'accordion')>Accordion</option>
        <option value="stacked" @selected(($cfg['layout_mode'] ?? 'accordion') === 'stacked')>Stacked</option>
      </select>
    </div>
    <div class="col-md-2 d-flex align-items-end pb-1">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="config[allow_multiple_open]" value="1" id="faqMultiOpen"
               @checked($cfg['allow_multiple_open'] ?? false)>
        <label class="form-check-label" for="faqMultiOpen">Allow multi-open</label>
      </div>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-2">
      <span class="fw-semibold">FAQ Items</span>
      <span class="badge bg-secondary rounded-pill" id="faqItemCount">{{ count($faqItems) }}</span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addFaqItem">
      <i class="ri-add-line me-1"></i> Add Question
    </button>
  </div>

  <div id="faqItems">
    @foreach($faqItems as $k => $item)
      @php
        $mediaType = !empty($item['icon']) ? 'icon' : (!empty($item['image']) ? 'image' : 'icon');
      @endphp
      <div class="card mb-2 faq-item-card shadow-sm" data-item-index="{{ $k }}">
        <div class="card-body py-2 px-3">
          <div class="row g-3 align-items-start">
            <div class="col-auto mt-2 d-flex align-items-center gap-2">
              <span class="faq-drag-handle text-muted" style="cursor:grab"><i class="ri-draggable"></i></span>
              <span class="badge bg-secondary-transparent text-secondary">#{{ $k + 1 }}</span>
            </div>
            <div class="col-auto">
              <div class="btn-group btn-group-sm mb-2" role="group">
                <input type="radio" name="config[items][{{ $k }}][media_type]" value="icon"
                       class="btn-check faq-media-radio" id="faq-mt-icon-{{ $k }}"
                       @checked($mediaType === 'icon')>
                <label class="btn btn-outline-secondary btn-xs" for="faq-mt-icon-{{ $k }}">Icon</label>
                <input type="radio" name="config[items][{{ $k }}][media_type]" value="image"
                       class="btn-check faq-media-radio" id="faq-mt-image-{{ $k }}"
                       @checked($mediaType === 'image')>
                <label class="btn btn-outline-secondary btn-xs" for="faq-mt-image-{{ $k }}">Image</label>
              </div>

              <div class="faq-media-panel" data-panel="icon" @style(['display:none' => $mediaType !== 'icon'])>
                <div class="faq-icon-wrap rounded border"
                     style="width:40px;height:40px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                  @if(!empty($item['icon']))
                    <img src="{{ $mediaUrl($item['icon']) }}"
                         class="faq-icon-preview img-fluid w-100 h-100" style="object-fit:contain" alt="">
                    <input type="hidden" name="config[items][{{ $k }}][keep_icon]" value="1">
                    <input type="hidden" name="config[items][{{ $k }}][existing_icon]" value="{{ $item['icon'] }}">
                  @else
                    <img class="faq-icon-preview d-none img-fluid w-100 h-100" style="object-fit:contain" alt="">
                    <span class="faq-icon-placeholder text-muted"><i class="ri-image-line fs-18"></i></span>
                  @endif
                </div>
                <input type="file" name="config[items][{{ $k }}][icon_file]"
                       class="form-control form-control-sm faq-icon-input mt-1" accept="image/*"
                       style="max-width:72px;font-size:0.65rem;">
              </div>

              <div class="faq-media-panel" data-panel="image" @style(['display:none' => $mediaType !== 'image'])>
                <div class="faq-image-wrap rounded border"
                     style="width:72px;height:48px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                  @if(!empty($item['image']))
                    <img src="{{ $mediaUrl($item['image']) }}"
                         class="faq-image-preview img-fluid w-100 h-100" style="object-fit:cover" alt="">
                    <input type="hidden" name="config[items][{{ $k }}][keep_image]" value="1">
                    <input type="hidden" name="config[items][{{ $k }}][existing_image]" value="{{ $item['image'] }}">
                  @else
                    <img class="faq-image-preview d-none img-fluid w-100 h-100" style="object-fit:cover" alt="">
                    <span class="faq-image-placeholder text-muted"><i class="ri-image-add-line fs-18"></i></span>
                  @endif
                </div>
                <input type="file" name="config[items][{{ $k }}][image_file]"
                       class="form-control form-control-sm faq-image-input mt-1" accept="image/*"
                       style="max-width:72px;font-size:0.65rem;">
              </div>
            </div>
            <div class="col-md-4">
              <input type="text" name="config[items][{{ $k }}][question]" class="form-control form-control-sm"
                     value="{{ $item['question'] ?? '' }}" placeholder="Question">
            </div>
            <div class="col-md-7">
              <textarea name="config[items][{{ $k }}][answer]" rows="2" class="form-control form-control-sm"
                        placeholder="Answer">{{ $item['answer'] ?? '' }}</textarea>
            </div>
            <div class="col-auto mt-1">
              <button type="button" class="btn btn-xs btn-outline-danger js-remove-faq-item" data-item-index="{{ $k }}">
                <i class="ri-delete-bin-line"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div id="faqEmptyHint" class="text-center py-4 text-muted border rounded {{ count($faqItems) ? 'd-none' : '' }}">
    <i class="ri-question-line fs-28 d-block mb-1 opacity-50"></i>
    No FAQs yet. Click <strong>Add Question</strong>.
  </div>

</div>
