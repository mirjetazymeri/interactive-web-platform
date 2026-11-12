<div class="mb-3">
    <label>Code</label>
    <input type="text" name="code" class="form-control"
           value="{{ old('code', $locale->code ?? '') }}" required>
</div>

<div class="mb-3">
    <label>Name</label>
    <input type="text" name="name" class="form-control"
           value="{{ old('name', $locale->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label>Flag</label>
    <input type="text" name="flag" class="form-control"
           value="{{ old('flag', $locale->flag ?? '') }}">
</div>

<div class="form-check mb-2">
    <input type="checkbox" name="is_active" value="1"
           class="form-check-input"
           {{ old('is_active', $locale->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label">Active</label>
</div>

<div class="form-check mb-3">
    <input type="checkbox" name="is_default" value="1"
           class="form-check-input"
           {{ old('is_default', $locale->is_default ?? false) ? 'checked' : '' }}>
    <label class="form-check-label">Default</label>
</div>
