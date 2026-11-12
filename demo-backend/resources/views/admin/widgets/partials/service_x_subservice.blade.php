<div class="border rounded p-3 mb-2 service-x-sub">
  <input type="hidden" data-sub-id name="config[services][{{ $serviceIndex }}][subservices][{{ $subIndex }}][id]" value="{{ $subservice['id'] ?? '' }}">
  <div class="row g-2">
    <div class="col-md-6">
      <label class="form-label">Title (required)
        <input class="form-control" name="config[services][{{ $serviceIndex }}][subservices][{{ $subIndex }}][title]" value="{{ $subservice['title'] ?? '' }}" maxlength="255">
      </label>
    </div>
    <div class="col-md-6">
      <label class="form-label">Description
        <textarea class="form-control" name="config[services][{{ $serviceIndex }}][subservices][{{ $subIndex }}][description]" maxlength="10000">{{ $subservice['description'] ?? '' }}</textarea>
      </label>
    </div>
    <div class="col-md-3">
      <label class="form-label">Order
        <input class="form-control" type="number" min="0" name="config[services][{{ $serviceIndex }}][subservices][{{ $subIndex }}][position]" value="{{ $subservice['position'] ?? 1 }}">
      </label>
    </div>
    <div class="col-md-3">
      <label class="form-label">Status
        <select class="form-select" name="config[services][{{ $serviceIndex }}][subservices][{{ $subIndex }}][active]">
          <option value="1" @selected($subservice['active'] ?? true)>Active</option>
          <option value="0" @selected(!($subservice['active'] ?? true))>Inactive</option>
        </select>
      </label>
    </div>
    <div class="col-md-3"><button type="button" class="btn btn-outline-danger" data-sub-remove>Remove subservice</button></div>
  </div>
</div>
