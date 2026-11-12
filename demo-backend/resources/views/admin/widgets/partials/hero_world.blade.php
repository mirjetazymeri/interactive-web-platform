@php
  $heroWorldServices = array_values((array) ($type === 'hero-world' ? ($cfg['services'] ?? []) : []));
  $heroWorldError = fn ($key) => $errors->first($key);
@endphp
<div id="cfg-hero-world" class="cfg d-none">
  <h5 class="mb-3">{{ __('hero_world.settings') }}</h5>

  <fieldset class="border rounded p-3 mb-4">
    <legend class="fs-6 fw-semibold float-none w-auto px-2 mb-0">{{ __('hero_world.general') }}</legend>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" for="heroWorldHeadline">{{ __('hero_world.title') }} <span class="text-danger">*</span></label>
        <input id="heroWorldHeadline" class="form-control @if($heroWorldError('config.headline')) is-invalid @endif" name="config[headline]" maxlength="255"
               value="{{ $type === 'hero-world' ? ($cfg['headline'] ?? '') : '' }}">
        @if($heroWorldError('config.headline'))<div class="invalid-feedback">{{ $heroWorldError('config.headline') }}</div>@endif
      </div>
      <div class="col-md-6">
        <label class="form-label" for="heroWorldSubtitle">{{ __('hero_world.subtitle') }}</label>
        <textarea id="heroWorldSubtitle" class="form-control @if($heroWorldError('config.subtitle')) is-invalid @endif" name="config[subtitle]" rows="2" maxlength="1000">{{ $type === 'hero-world' ? ($cfg['subtitle'] ?? '') : '' }}</textarea>
        @if($heroWorldError('config.subtitle'))<div class="invalid-feedback">{{ $heroWorldError('config.subtitle') }}</div>@endif
      </div>
      <div class="col-md-4">
        <label class="form-label" for="heroWorldCtaText">{{ __('hero_world.button_label') }}</label>
        <input id="heroWorldCtaText" class="form-control @if($heroWorldError('config.cta_text')) is-invalid @endif" name="config[cta_text]" maxlength="120"
               value="{{ $type === 'hero-world' ? ($cfg['cta_text'] ?? '') : '' }}">
        @if($heroWorldError('config.cta_text'))<div class="invalid-feedback">{{ $heroWorldError('config.cta_text') }}</div>@endif
      </div>
      <div class="col-md-8">
        <label class="form-label" for="heroWorldCtaLink">{{ __('hero_world.button_url') }}</label>
        <input id="heroWorldCtaLink" class="form-control @if($heroWorldError('config.cta_link')) is-invalid @endif" name="config[cta_link]" maxlength="1000"
               value="{{ $type === 'hero-world' ? ($cfg['cta_link'] ?? '') : '' }}" placeholder="/contact" aria-describedby="heroWorldCtaLinkHelp">
        <div class="form-text" id="heroWorldCtaLinkHelp">{{ __('hero_world.button_url_help') }}</div>
        @if($heroWorldError('config.cta_link'))<div class="invalid-feedback d-block">{{ $heroWorldError('config.cta_link') }}</div>@endif
      </div>
    </div>
  </fieldset>

  <fieldset class="border rounded p-3 mb-4">
    <legend class="fs-6 fw-semibold float-none w-auto px-2 mb-0">{{ __('hero_world.services') }}</legend>
    <p class="text-muted fs-13">{{ __('hero_world.services_help') }}</p>
    @if($heroWorldError('config.services'))<div class="alert alert-danger py-2">{{ $heroWorldError('config.services') }}</div>@endif
    <div id="heroWorldServices" data-hero-world-services>
      @foreach($heroWorldServices as $serviceIndex => $service)
        @include('admin.widgets.partials.hero_world_service')
      @endforeach
    </div>
    <p class="text-muted small {{ $heroWorldServices ? 'd-none' : '' }}" data-hero-world-empty>{{ __('hero_world.no_services') }}</p>
    <button class="btn btn-outline-primary" type="button" id="addHeroWorldService"><i class="ri-add-line me-1"></i>{{ __('hero_world.add_service') }}</button>
    <template id="heroWorldServiceTemplate">
      @include('admin.widgets.partials.hero_world_service', ['serviceIndex' => '__SERVICE__', 'service' => []])
    </template>
  </fieldset>

  <fieldset class="border rounded p-3 mb-2">
    <legend class="fs-6 fw-semibold float-none w-auto px-2 mb-0">{{ __('hero_world.seo') }}</legend>
    <p class="text-muted fs-13">{{ __('hero_world.seo_help') }}</p>
    <div class="row g-3">
      <div class="col-md-5">
        <label class="form-label" for="heroWorldSeoTitle">{{ __('hero_world.meta_title') }}</label>
        <input id="heroWorldSeoTitle" class="form-control @if($heroWorldError('config.seo_title')) is-invalid @endif" name="config[seo_title]" maxlength="70"
               value="{{ $type === 'hero-world' ? ($cfg['seo_title'] ?? '') : '' }}">
        @if($heroWorldError('config.seo_title'))<div class="invalid-feedback">{{ $heroWorldError('config.seo_title') }}</div>@endif
      </div>
      <div class="col-md-7">
        <label class="form-label" for="heroWorldSeoDescription">{{ __('hero_world.meta_description') }}</label>
        <textarea id="heroWorldSeoDescription" class="form-control @if($heroWorldError('config.seo_description')) is-invalid @endif" name="config[seo_description]" rows="2" maxlength="320">{{ $type === 'hero-world' ? ($cfg['seo_description'] ?? '') : '' }}</textarea>
        @if($heroWorldError('config.seo_description'))<div class="invalid-feedback">{{ $heroWorldError('config.seo_description') }}</div>@endif
      </div>
    </div>
  </fieldset>
</div>
