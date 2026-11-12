<div class="card card-body mb-3 service-x-item">
  <input type="hidden" data-service-id name="config[services][{{ $serviceIndex }}][id]" value="{{ $service['id'] ?? '' }}">
  <div class="row g-3">
    @foreach(['name' => 'Name (required)', 'title' => 'Title (required)', 'subtitle' => 'Introduction', 'image_alt' => 'Image alternative text'] as $field => $label)
      <div class="col-md-6">
        <label class="form-label">{{ $label }}
          @if($field === 'subtitle')
            <textarea class="form-control" name="config[services][{{ $serviceIndex }}][{{ $field }}]" maxlength="10000">{{ $service[$field] ?? '' }}</textarea>
          @else
            <input class="form-control" name="config[services][{{ $serviceIndex }}][{{ $field }}]" value="{{ $service[$field] ?? '' }}" maxlength="255">
          @endif
        </label>
      </div>
    @endforeach
    <div class="col-md-6">
      @if(!empty($service['image']))
        <img src="{{ $mediaUrl($service['image']) }}" alt="" class="img-thumbnail mb-2" style="max-height:120px">
      @endif
      <label class="form-label">Main image (optional, maximum 5 MB)
        <input class="form-control" type="file" accept="image/jpeg,image/png,image/webp,image/gif" name="config[services][{{ $serviceIndex }}][image_file]">
      </label>
      <label class="d-block"><input type="checkbox" value="1" name="config[services][{{ $serviceIndex }}][remove_image]" @checked($service['remove_image'] ?? false)> Remove image</label>
    </div>
    <div class="col-md-3">
      <label class="form-label">Order
        <input class="form-control" type="number" min="0" name="config[services][{{ $serviceIndex }}][position]" value="{{ $service['position'] ?? 1 }}">
      </label>
    </div>
    <div class="col-md-3">
      <label class="form-label">Status
        <select class="form-select" name="config[services][{{ $serviceIndex }}][active]">
          <option value="1" @selected($service['active'] ?? true)>Active</option>
          <option value="0" @selected(!($service['active'] ?? true))>Inactive</option>
        </select>
      </label>
      <button type="button" class="btn btn-outline-danger" data-service-remove>Remove service</button>
    </div>
  </div>
  @if(!empty($service['id']) && isset($assistantPhrases))
    @php
      // This service and its subservices, by the references assistant phrases link to.
      $refs = [$service['id'], ...collect($service['subservices'] ?? [])->pluck('id')->filter()->map(fn ($id) => $service['id'].'/'.$id)->all()];
      $linked = $assistantPhrases->filter(fn ($phrase) => array_intersect($refs, (array) $phrase->service_refs) !== []);
    @endphp
    <details class="mt-3 border rounded p-2 bg-light" data-assistant-knowledge>
      <summary class="small fw-semibold">{{ __('assistant.knowledge_section', ['count' => $linked->sum(fn ($phrase) => count($phrase->terms ?? []))]) }}</summary>
      <p class="small text-muted my-2">{{ __('assistant.knowledge_section_help') }}</p>
      @forelse($linked as $phrase)
        <div class="mb-1">
          @if($phrase->capability && $phrase->capability !== 'confirmed')
            <span class="badge bg-warning text-dark me-1">{{ __('assistant.capability_'.$phrase->capability) }}</span>
          @endif
          @foreach($phrase->terms ?? [] as $term)
            <span class="badge bg-white text-dark border me-1">{{ $term }}</span>
          @endforeach
          @unless($phrase->active)<span class="badge bg-secondary">{{ __('assistant.inactive') }}</span>@endunless
        </div>
      @empty
        <div class="small text-muted">{{ __('assistant.knowledge_section_empty') }}</div>
      @endforelse
      <a class="small" href="{{ route('admin.assistant.synonyms.index', ['service' => $service['id']]) }}">{{ __('assistant.knowledge_section_manage') }}</a>
    </details>
  @endif
  <h6 class="mt-3">Subservices</h6>
  <div class="service-x-subs">
    @foreach(($service['subservices'] ?? []) as $subIndex => $subservice)
      @include('admin.widgets.partials.service_x_subservice')
    @endforeach
  </div>
  <button type="button" class="btn btn-sm btn-outline-primary align-self-start" data-sub-add>Add subservice</button>
</div>
