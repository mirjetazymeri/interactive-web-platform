@php
  $field = fn ($name) => "config[services][$serviceIndex][$name]";
  $fieldError = fn ($name) => is_int($serviceIndex) ? $errors->first("config.services.$serviceIndex.$name") : null;
  $serviceActive = filter_var($service['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
@endphp
<div class="card card-body mb-3 hero-world-item" data-hero-world-item>
  <input type="hidden" data-service-id name="{{ $field('id') }}" value="{{ $service['id'] ?? '' }}">
  <input type="hidden" data-service-position name="{{ $field('position') }}" value="{{ $service['position'] ?? (is_int($serviceIndex) ? $serviceIndex + 1 : 1) }}">
  <div class="d-flex align-items-center gap-2 mb-3">
    <span class="text-muted" data-hero-world-handle style="cursor:grab" title="{{ __('hero_world.drag') }}" aria-hidden="true">⋮⋮</span>
    <span class="fw-semibold">{{ __('hero_world.service') }} <span data-hero-world-number>{{ is_int($serviceIndex) ? $serviceIndex + 1 : '' }}</span></span>
    @if(! $serviceActive)<span class="badge bg-secondary-transparent text-secondary">{{ __('hero_world.inactive') }}</span>@endif
    <div class="ms-auto btn-group btn-group-sm">
      <button type="button" class="btn btn-outline-secondary" data-hero-world-up aria-label="{{ __('hero_world.move_up') }}" title="{{ __('hero_world.move_up') }}"><i class="ri-arrow-up-line"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-hero-world-down aria-label="{{ __('hero_world.move_down') }}" title="{{ __('hero_world.move_down') }}"><i class="ri-arrow-down-line"></i></button>
      <button type="button" class="btn btn-outline-danger" data-hero-world-remove><i class="ri-delete-bin-line me-1"></i>{{ __('hero_world.remove_service') }}</button>
    </div>
  </div>
  <div class="row g-3">
    <div class="col-md-5">
      <label class="form-label w-100">{{ __('hero_world.service_title') }} <span class="text-danger">*</span>
        <input class="form-control @if($fieldError('title')) is-invalid @endif" name="{{ $field('title') }}" value="{{ $service['title'] ?? '' }}" maxlength="80">
      </label>
      @if($fieldError('title'))<div class="invalid-feedback d-block">{{ $fieldError('title') }}</div>@endif
    </div>
    <div class="col-md-7">
      <label class="form-label w-100">{{ __('hero_world.service_description') }}
        <input class="form-control @if($fieldError('description')) is-invalid @endif" name="{{ $field('description') }}" value="{{ $service['description'] ?? '' }}" maxlength="300">
      </label>
      @if($fieldError('description'))<div class="invalid-feedback d-block">{{ $fieldError('description') }}</div>@endif
    </div>
    <div class="col-md-3">
      <label class="form-label w-100">{{ __('hero_world.icon') }}
        <select class="form-select @if($fieldError('icon')) is-invalid @endif" name="{{ $field('icon') }}">
          <option value="">—</option>
          @foreach(\App\Services\Widgets\Types\HeroWorldWidgetTypeService::ICONS as $icon)
            <option value="{{ $icon }}" @selected(($service['icon'] ?? '') === $icon)>{{ __("hero_world.icons.$icon") }}</option>
          @endforeach
        </select>
      </label>
    </div>
    <div class="col-md-5">
      <span class="form-label d-block">{{ __('hero_world.position') }}</span>
      <div class="input-group">
        <input class="form-control @if($fieldError('lat')) is-invalid @endif" type="number" step="any" min="-90" max="90" name="{{ $field('lat') }}" value="{{ $service['lat'] ?? '' }}" aria-label="{{ __('hero_world.latitude') }}" placeholder="{{ __('hero_world.latitude') }}">
        <input class="form-control @if($fieldError('lng')) is-invalid @endif" type="number" step="any" min="-180" max="180" name="{{ $field('lng') }}" value="{{ $service['lng'] ?? '' }}" aria-label="{{ __('hero_world.longitude') }}" placeholder="{{ __('hero_world.longitude') }}">
      </div>
      <div class="form-text">{{ __('hero_world.position_help') }}</div>
      @foreach(['lat', 'lng'] as $coordinate)
        @if($fieldError($coordinate))<div class="invalid-feedback d-block">{{ $fieldError($coordinate) }}</div>@endif
      @endforeach
    </div>
    <div class="col-md-2">
      <label class="form-label w-100">{{ __('hero_world.status') }}
        <select class="form-select" name="{{ $field('active') }}">
          <option value="1" @selected($serviceActive)>{{ __('hero_world.active') }}</option>
          <option value="0" @selected(! $serviceActive)>{{ __('hero_world.inactive') }}</option>
        </select>
      </label>
    </div>
    <div class="col-md-2">
      <label class="form-label w-100">{{ __('hero_world.sort_order') }}
        <input class="form-control" type="number" min="0" readonly tabindex="-1" data-service-position-display value="{{ $service['position'] ?? (is_int($serviceIndex) ? $serviceIndex + 1 : 1) }}">
      </label>
    </div>
  </div>
</div>
