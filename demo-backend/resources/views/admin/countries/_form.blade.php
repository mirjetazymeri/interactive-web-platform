<div class="row g-3">
  <div class="col-md-3">
    <label class="form-label">Code (ISO2) *</label>
    <input name="code" class="form-control" maxlength="2" value="{{ old('code', $country->code ?? '') }}" required>
    @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label class="form-label">Name *</label>
    <input name="name" class="form-control" value="{{ old('name', $country->name ?? '') }}" required>
    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
</div>
