@php
  $location = $location ?? null;
  $translation = fn ($locale, $field) => old("translations.$locale.$field", optional($location?->translations->firstWhere('locale', $locale))->content[$field] ?? '');
@endphp

<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">{{ __('meetings.office_name') }} *</label>
    <input name="name" class="form-control" value="{{ old('name', $location->name ?? '') }}" required maxlength="255">
    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-3">
    <label class="form-label">{{ __('meetings.position') }}</label>
    <input type="number" min="0" name="position" class="form-control" value="{{ old('position', $location->position ?? 0) }}">
    @error('position')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-3 d-flex flex-column justify-content-end gap-1">
    <div class="form-check">
      <input type="hidden" name="active" value="0">
      <input type="checkbox" class="form-check-input" id="office-active" name="active" value="1" @checked(old('active', $location->active ?? true))>
      <label class="form-check-label" for="office-active">{{ __('meetings.active') }}</label>
    </div>
    <div class="form-check">
      <input type="hidden" name="is_default" value="0">
      <input type="checkbox" class="form-check-input" id="office-default" name="is_default" value="1" @checked(old('is_default', $location->is_default ?? false))>
      <label class="form-check-label" for="office-default">{{ __('meetings.is_default') }}</label>
    </div>
  </div>

  <div class="col-12"><h2 class="fs-15 mb-0 mt-2">{{ __('meetings.address') }}</h2></div>
  <div class="col-md-6">
    <label class="form-label">{{ __('meetings.address_line1') }} *</label>
    <input name="address_line1" class="form-control" value="{{ old('address_line1', $location->address_line1 ?? '') }}" required maxlength="255" autocomplete="off">
    @error('address_line1')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label class="form-label">{{ __('meetings.address_line2') }}</label>
    <input name="address_line2" class="form-control" value="{{ old('address_line2', $location->address_line2 ?? '') }}" maxlength="255" autocomplete="off">
    @error('address_line2')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-3">
    <label class="form-label">{{ __('meetings.postcode') }}</label>
    <input name="postcode" class="form-control" value="{{ old('postcode', $location->postcode ?? '') }}" maxlength="20">
    @error('postcode')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4">
    <label class="form-label">{{ __('meetings.city') }} *</label>
    <input name="city" class="form-control" value="{{ old('city', $location->city ?? '') }}" required maxlength="255">
    @error('city')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-5">
    <label class="form-label">{{ __('meetings.country') }} *</label>
    <select name="country_id" class="form-select" required>
      <option value="">{{ __('meetings.choose_country') }}</option>
      @foreach($countries as $country)
        <option value="{{ $country->id }}" @selected((int) old('country_id', $location->country_id ?? 0) === $country->id)>{{ $country->name }}</option>
      @endforeach
    </select>
    @error('country_id')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
  <div class="col-12">
    <label class="form-label">{{ __('meetings.maps_url') }}</label>
    <input type="url" name="maps_url" class="form-control" value="{{ old('maps_url', $location->maps_url ?? '') }}" maxlength="2048" placeholder="https://">
    <div class="form-text">{{ __('meetings.maps_url_help') }}</div>
    @error('maps_url')<div class="text-danger small">{{ $message }}</div>@enderror
    @if($location)
      <a href="{{ $location->mapsLink() }}" target="_blank" rel="noopener" class="small">{{ __('meetings.map_preview') }}</a>
    @endif
  </div>
  <div class="col-12">
    <label class="form-label">{{ __('meetings.arrival_instructions') }}</label>
    <textarea name="arrival_instructions" class="form-control" rows="3" maxlength="5000">{{ old('arrival_instructions', $location->arrival_instructions ?? '') }}</textarea>
    <div class="form-text">{{ __('meetings.arrival_instructions_help') }}</div>
    @error('arrival_instructions')<div class="text-danger small">{{ $message }}</div>@enderror
  </div>
</div>

@if($translationLocales)
  <h2 class="fs-15 mt-4 mb-2">{{ __('meetings.translations') }}</h2>
  <p class="text-muted small mb-2">{{ __('meetings.translation_help') }}</p>
  <ul class="nav nav-tabs mb-0 border-bottom-0" role="tablist">
    @foreach($translationLocales as $locale)
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#office-translation-{{ $locale }}" type="button" role="tab">
          {{ config("locales.$locale.name") ?? strtoupper($locale) }}
        </button>
      </li>
    @endforeach
  </ul>
  <div class="tab-content border rounded-bottom p-3">
    @foreach($translationLocales as $locale)
      <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="office-translation-{{ $locale }}" role="tabpanel">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">{{ __('meetings.office_name') }}</label>
            <input name="translations[{{ $locale }}][name]" class="form-control" value="{{ $translation($locale, 'name') }}" maxlength="255">
            @error("translations.$locale.name")<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label">{{ __('meetings.arrival_instructions') }}</label>
            <textarea name="translations[{{ $locale }}][arrival_instructions]" class="form-control" rows="3" maxlength="5000">{{ $translation($locale, 'arrival_instructions') }}</textarea>
            @error("translations.$locale.arrival_instructions")<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
    @endforeach
  </div>
@endif
