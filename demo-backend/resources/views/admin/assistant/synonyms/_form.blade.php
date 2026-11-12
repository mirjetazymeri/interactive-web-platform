@php
  $terms = old('terms') !== null ? (is_array(old('terms')) ? implode("\n", old('terms')) : old('terms')) : implode("\n", (array) ($synonym->terms ?? []));
  $refs = (array) old('service_refs', $synonym->service_refs ?? []);
  // Linked services that are no longer published: kept on the record, ignored by the assistant.
  $missing = array_diff($refs, array_keys($services));
@endphp
<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label" for="synonym-terms">{{ __('assistant.terms') }} *</label>
    <textarea id="synonym-terms" name="terms" class="form-control @error('terms') is-invalid @enderror" rows="4" required>{{ $terms }}</textarea>
    @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text">{{ __('assistant.terms_help') }}</div>
  </div>
  <div class="col-md-3">
    <label class="form-label" for="synonym-locale">{{ __('assistant.language') }}</label>
    <select id="synonym-locale" name="locale" class="form-select">
      <option value="">{{ __('assistant.all_languages') }}</option>
      @foreach($locales as $locale)
        <option value="{{ $locale }}" @selected(old('locale', $synonym->locale) === $locale)>{{ config("locales.$locale.name") ?? strtoupper($locale) }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-3 d-flex align-items-end">
    <div class="form-check mb-2">
      <input type="hidden" name="active" value="0">
      <input type="checkbox" class="form-check-input" id="synonym-active" name="active" value="1" @checked(old('active', $synonym->active ?? true))>
      <label class="form-check-label" for="synonym-active">{{ __('assistant.active') }}</label>
    </div>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="synonym-services">{{ __('assistant.linked_services') }}</label>
    <select id="synonym-services" name="service_refs[]" class="form-select @error('service_refs.*') is-invalid @enderror" multiple size="8">
      @foreach($services as $ref => $label)
        <option value="{{ $ref }}" @selected(in_array($ref, $refs, true))>{{ str_contains($ref, '/') ? '— ' : '' }}{{ $label }}</option>
      @endforeach
    </select>
    @error('service_refs.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text">{{ __('assistant.linked_services_help') }}</div>
    @if($missing)
      <div class="form-text text-warning">{{ implode(', ', $missing) }}</div>
    @endif
  </div>
  <div class="col-md-6">
    <label class="form-label" for="synonym-capability">{{ __('assistant.capability') }}</label>
    <select id="synonym-capability" name="capability" class="form-select @error('capability') is-invalid @enderror">
      <option value="">{{ __('assistant.capability_none') }}</option>
      @foreach(\App\Models\AssistantSynonym::CAPABILITIES as $capability)
        <option value="{{ $capability }}" @selected(old('capability', $synonym->capability) === $capability)>{{ __('assistant.capability_'.$capability) }}</option>
      @endforeach
    </select>
    <div class="form-text">{{ __('assistant.capability_help') }}</div>
  </div>
</div>
