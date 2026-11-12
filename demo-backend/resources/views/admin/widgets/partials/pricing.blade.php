{{-- Pricing type config block --}}
@php $pricingItems = $cfg['items'] ?? []; @endphp
<div id="cfg-pricing" class="cfg d-none">

  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="ri-price-tag-3-line text-secondary fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">Pricing Options</span>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <label class="form-label">Heading</label>
      <input type="text" name="config[heading]" class="form-control"
             value="{{ $cfg['heading'] ?? '' }}" placeholder="Simple pricing for every team">
    </div>
    <div class="col-md-4">
      <label class="form-label">Subheading</label>
      <input type="text" name="config[subheading]" class="form-control"
             value="{{ $cfg['subheading'] ?? '' }}" placeholder="Transparent plans with no hidden fees">
    </div>
    <div class="col-md-1">
      <label class="form-label">Currency</label>
      <input type="text" name="config[currency]" class="form-control" maxlength="6"
             value="{{ $cfg['currency'] ?? '$' }}" placeholder="$">
    </div>
    <div class="col-md-2">
      <label class="form-label">Billing Note</label>
      <input type="text" name="config[billing_note]" class="form-control"
             value="{{ $cfg['billing_note'] ?? 'Billed monthly' }}" placeholder="Billed monthly">
    </div>
    <div class="col-md-1">
      <label class="form-label">Columns</label>
      <input type="number" name="config[columns]" min="1" max="4" class="form-control"
             value="{{ $cfg['columns'] ?? 3 }}">
    </div>
    <div class="col-md-3 d-flex align-items-end pb-1">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="config[highlight_popular]" value="1" id="pricingHighlightPopular"
               @checked($cfg['highlight_popular'] ?? true)>
        <label class="form-check-label" for="pricingHighlightPopular">Highlight popular plan</label>
      </div>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-2">
      <span class="fw-semibold">Pricing Plans</span>
      <span class="badge bg-secondary rounded-pill" id="pricingItemCount">{{ count($pricingItems) }}</span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addPricingItem">
      <i class="ri-add-line me-1"></i> Add Plan
    </button>
  </div>

  <div id="pricingItems">
    @foreach($pricingItems as $k => $item)
      <div class="card mb-2 pricing-item-card shadow-sm" data-item-index="{{ $k }}">
        <div class="card-body py-2 px-3">
          <div class="row g-2 align-items-start">
            <div class="col-auto mt-2">
              <span class="pricing-drag-handle text-muted" style="cursor:grab"><i class="ri-draggable"></i></span>
            </div>
            <div class="col-md-2">
              <input type="text" name="config[items][{{ $k }}][name]" class="form-control form-control-sm"
                     value="{{ $item['name'] ?? '' }}" placeholder="Plan name">
            </div>
            <div class="col-md-1">
              <input type="text" name="config[items][{{ $k }}][price]" class="form-control form-control-sm"
                     value="{{ $item['price'] ?? '' }}" placeholder="29">
            </div>
            <div class="col-md-2">
              <input type="text" name="config[items][{{ $k }}][period]" class="form-control form-control-sm"
                     value="{{ $item['period'] ?? '/month' }}" placeholder="/month">
            </div>
            <div class="col-md-2">
              <input type="text" name="config[items][{{ $k }}][badge]" class="form-control form-control-sm"
                     value="{{ $item['badge'] ?? '' }}" placeholder="Most Popular">
            </div>
            <div class="col-md-4">
              <input type="text" name="config[items][{{ $k }}][description]" class="form-control form-control-sm"
                     value="{{ $item['description'] ?? '' }}" placeholder="Short plan description">
            </div>
            <div class="col-auto mt-1">
              <button type="button" class="btn btn-xs btn-outline-danger js-remove-pricing-item" data-item-index="{{ $k }}">
                <i class="ri-delete-bin-line"></i>
              </button>
            </div>
          </div>

          <div class="row g-2 mt-1">
            <div class="col-md-6">
              <textarea name="config[items][{{ $k }}][features]" rows="2" class="form-control form-control-sm"
                        placeholder="Feature list (one per line)">{{ $item['features'] ?? '' }}</textarea>
            </div>
            <div class="col-md-2">
              <input type="text" name="config[items][{{ $k }}][cta_text]" class="form-control form-control-sm"
                     value="{{ $item['cta_text'] ?? 'Get started' }}" placeholder="CTA text">
            </div>
            <div class="col-md-3">
              <input type="text" name="config[items][{{ $k }}][cta_link]" class="form-control form-control-sm"
                     value="{{ $item['cta_link'] ?? '' }}" placeholder="CTA link">
            </div>
            <div class="col-md-1 d-flex align-items-center">
              <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" name="config[items][{{ $k }}][is_popular]" value="1"
                       id="pricingPopular{{ $k }}" @checked($item['is_popular'] ?? false)>
                <label class="form-check-label small" for="pricingPopular{{ $k }}">Popular</label>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div id="pricingEmptyHint" class="text-center py-4 text-muted border rounded {{ count($pricingItems) ? 'd-none' : '' }}">
    <i class="ri-price-tag-3-line fs-28 d-block mb-1 opacity-50"></i>
    No pricing plans yet. Click <strong>Add Plan</strong>.
  </div>

</div>
