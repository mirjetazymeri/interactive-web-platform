<div class="col-12 widget-translation-type {{ $type === 'service_x' ? '' : 'd-none' }}" data-widget-translation-type="service_x">
  <label class="form-label">Localized section index or label
    <input class="form-control" name="translations[{{ $index }}][section_index]" value="{{ old("translations.$index.section_index", $translationConfig['section_index'] ?? '') }}">
  </label>
  <p class="text-muted">Blank fields use the base content. Save newly added services to make them available here.</p>
  @foreach(($cfg['services'] ?? []) as $service)
    @continue(empty($service['id']))
    @php $serviceId = $service['id']; @endphp
    <fieldset class="border rounded p-3 mb-3">
      <legend class="fs-6">{{ $service['name'] ?? 'Service' }}</legend>
      @foreach(['name', 'title', 'subtitle', 'image_alt'] as $field)
        <label class="form-label d-block">{{ ucfirst(str_replace('_', ' ', $field)) }}
          <textarea class="form-control" name="translations[{{ $index }}][services][{{ $serviceId }}][{{ $field }}]">{{ old("translations.$index.services.$serviceId.$field", $translationConfig['services'][$serviceId][$field] ?? '') }}</textarea>
        </label>
      @endforeach
      @foreach(($service['subservices'] ?? []) as $subservice)
        @continue(empty($subservice['id']))
        @php $subId = $subservice['id']; @endphp
        <h6>{{ $subservice['title'] ?? 'Subservice' }}</h6>
        @foreach(['title', 'description'] as $field)
          <label class="form-label d-block">{{ ucfirst($field) }}
            <textarea class="form-control" name="translations[{{ $index }}][services][{{ $serviceId }}][subservices][{{ $subId }}][{{ $field }}]">{{ old("translations.$index.services.$serviceId.subservices.$subId.$field", $translationConfig['services'][$serviceId]['subservices'][$subId][$field] ?? '') }}</textarea>
          </label>
        @endforeach
      @endforeach
    </fieldset>
  @endforeach
</div>
