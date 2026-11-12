@php
  $heroWorldBase = $type === 'hero-world' ? (array) ($widgetConfig ?? []) : [];
  $heroWorldText = fn ($key) => old("translations.$index.$key", $translationConfig[$key] ?? '');
@endphp
<div class="col-12 widget-translation-type {{ $type === 'hero-world' ? '' : 'd-none' }}" data-widget-translation-type="hero-world">
  <div class="border rounded p-3 bg-light-subtle">
    <div class="fw-semibold mb-2 fs-13 text-muted text-uppercase"><i class="ri-earth-line me-1"></i> {{ __('hero_world.localized') }}</div>
    <p class="text-muted fs-13">{{ __('hero_world.translation_help') }}</p>
    <div class="row g-2">
      <div class="col-md-6">
        <label class="form-label fs-12 text-muted w-100">{{ __('hero_world.title') }}
          <input name="translations[{{ $index }}][headline]" class="form-control form-control-sm" maxlength="255"
                 value="{{ $heroWorldText('headline') }}" placeholder="{{ $heroWorldBase['headline'] ?? '' }}">
        </label>
      </div>
      <div class="col-md-6">
        <label class="form-label fs-12 text-muted w-100">{{ __('hero_world.button_label') }}
          <input name="translations[{{ $index }}][cta_text]" class="form-control form-control-sm" maxlength="120"
                 value="{{ $heroWorldText('cta_text') }}" placeholder="{{ $heroWorldBase['cta_text'] ?? '' }}">
        </label>
      </div>
      <div class="col-12">
        <label class="form-label fs-12 text-muted w-100">{{ __('hero_world.subtitle') }}
          <textarea name="translations[{{ $index }}][subtitle]" class="form-control form-control-sm" rows="2" maxlength="1000"
                    placeholder="{{ $heroWorldBase['subtitle'] ?? '' }}">{{ $heroWorldText('subtitle') }}</textarea>
        </label>
      </div>
      <div class="col-md-5">
        <label class="form-label fs-12 text-muted w-100">{{ __('hero_world.seo') }} — {{ __('hero_world.meta_title') }}
          <input name="translations[{{ $index }}][seo_title]" class="form-control form-control-sm" maxlength="70"
                 value="{{ $heroWorldText('seo_title') }}" placeholder="{{ $heroWorldBase['seo_title'] ?? '' }}">
        </label>
      </div>
      <div class="col-md-7">
        <label class="form-label fs-12 text-muted w-100">{{ __('hero_world.seo') }} — {{ __('hero_world.meta_description') }}
          <textarea name="translations[{{ $index }}][seo_description]" class="form-control form-control-sm" rows="2" maxlength="320"
                    placeholder="{{ $heroWorldBase['seo_description'] ?? '' }}">{{ $heroWorldText('seo_description') }}</textarea>
        </label>
      </div>
    </div>

    <div class="fw-semibold mt-3 mb-2 fs-13">{{ __('hero_world.services') }}</div>
    @forelse(($heroWorldBase['services'] ?? []) as $service)
      @continue(empty($service['id']))
      @php $serviceId = $service['id']; @endphp
      <fieldset class="border rounded p-2 mb-2">
        <legend class="fs-13 float-none w-auto px-1 mb-0">{{ $service['title'] ?? __('hero_world.service') }}</legend>
        <div class="row g-2">
          <div class="col-md-5">
            <label class="form-label fs-12 text-muted w-100">{{ __('hero_world.service_title') }}
              <input class="form-control form-control-sm" maxlength="80" name="translations[{{ $index }}][services][{{ $serviceId }}][title]"
                     value="{{ old("translations.$index.services.$serviceId.title", $translationConfig['services'][$serviceId]['title'] ?? '') }}" placeholder="{{ $service['title'] ?? '' }}">
            </label>
          </div>
          <div class="col-md-7">
            <label class="form-label fs-12 text-muted w-100">{{ __('hero_world.service_description') }}
              <input class="form-control form-control-sm" maxlength="300" name="translations[{{ $index }}][services][{{ $serviceId }}][description]"
                     value="{{ old("translations.$index.services.$serviceId.description", $translationConfig['services'][$serviceId]['description'] ?? '') }}" placeholder="{{ $service['description'] ?? '' }}">
            </label>
          </div>
        </div>
      </fieldset>
    @empty
      <p class="text-muted small mb-0">{{ __('hero_world.translation_empty') }}</p>
    @endforelse
  </div>
</div>
