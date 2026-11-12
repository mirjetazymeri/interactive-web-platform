@php
  $translation = fn ($locale) => old("translations.$locale.name", optional($category->translations?->firstWhere('locale', $locale))->content['name'] ?? '');
@endphp
<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label" for="category-name">{{ __('assistant.category_name') }} *</label>
    <input id="category-name" name="name" class="form-control" required maxlength="120" value="{{ old('name', $category->name) }}">
  </div>
  <div class="col-md-2">
    <label class="form-label" for="category-position">{{ __('assistant.order') }}</label>
    <input type="number" min="0" id="category-position" name="position" class="form-control" value="{{ old('position', $category->position ?? 0) }}">
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <div class="form-check mb-2">
      <input type="hidden" name="active" value="0">
      <input type="checkbox" class="form-check-input" id="category-active" name="active" value="1" @checked(old('active', $category->active ?? true))>
      <label class="form-check-label" for="category-active">{{ __('assistant.active') }}</label>
    </div>
  </div>
  @foreach($translationLocales as $locale)
    <div class="col-md-6">
      <label class="form-label">{{ __('assistant.category_name') }} — {{ config("locales.$locale.name") ?? strtoupper($locale) }}</label>
      <input name="translations[{{ $locale }}][name]" class="form-control" maxlength="120" value="{{ $translation($locale) }}">
    </div>
  @endforeach
</div>
