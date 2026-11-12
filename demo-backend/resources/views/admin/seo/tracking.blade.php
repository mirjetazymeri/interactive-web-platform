@extends('admin.settings.center')
@php
  $settingsActive = 'tracking';
  $checked = fn (string $key, bool $default = false) => (bool) old($key, $get($key, $default));
  $badges = ['disabled' => 'bg-light text-dark', 'invalid' => 'bg-danger', 'managed' => 'bg-info', 'paused' => 'bg-warning text-dark', 'ready' => 'bg-success'];
@endphp
@section('title', __('seo.tracking.title'))
@section('settings-panel')

<header class="ax-panel-head">
  <span class="ax-panel-head__icon" aria-hidden="true"><i class="ri-line-chart-line"></i></span>
  <div>
    <h2 class="ax-panel-head__title">{{ __('seo.tracking.title') }}</h2>
    <p class="ax-panel-head__desc">{{ __('seo.tracking.intro') }}</p>
  </div>
</header>

@if($errors->any())
  <div class="alert alert-danger ax-errors" role="alert"><ul class="mb-0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
@endif

<div class="alert alert-info d-flex gap-2 align-items-start">
  <i class="ri-shield-check-line" aria-hidden="true"></i><span>{{ __('seo.tracking.environment_note') }}</span>
</div>

<form id="tracking-form" method="POST" action="{{ route('admin.seo.tracking.update') }}" data-ax-dirty-form>
  @csrf @method('PUT')

  <div class="ax-section">
    <div class="ax-section__head"><h3 class="ax-section__title">{{ __('seo.tracking.master') }}</h3></div>
    <div class="ax-section__body ax-section__body--stack">
      <input type="hidden" name="gdpr[analytics_tracking_enabled]" value="0">
      <div class="form-check form-switch">
        <input type="checkbox" id="tracking-master" name="gdpr[analytics_tracking_enabled]" value="1" class="form-check-input" @checked($checked('gdpr.analytics_tracking_enabled'))>
        <label class="form-check-label fw-semibold" for="tracking-master">{{ __('seo.tracking.master_label') }}</label>
      </div>
      <div class="form-text">{{ __('seo.tracking.master_help') }}</div>
    </div>
  </div>

  <div class="ax-section">
    <div class="ax-section__head"><h3 class="ax-section__title">{{ __('seo.tracking.providers') }}</h3></div>
    <div class="ax-section__body ax-section__body--stack">
      @foreach([
        ['gtm', 'gtm_enabled', 'gtm_id', 'GTM-XXXXXXX'],
        ['ga4', 'ga4_enabled', 'ga4_measurement_id', 'G-XXXXXXXXXX'],
        ['meta_pixel', 'meta_pixel_enabled', 'meta_pixel_id', '123456789012345'],
      ] as [$provider, $enabledKey, $idKey, $placeholder])
        <div class="ax-provider">
          <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div>
              <div class="fw-semibold">{{ __("seo.tracking.$provider.name") }}</div>
              <div class="text-muted small">{{ __("seo.tracking.$provider.help") }}</div>
            </div>
            <span class="badge {{ $badges[$providers[$provider]] }}">{{ __('seo.tracking.status.'.$providers[$provider]) }}</span>
          </div>
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <input type="hidden" name="tracking[{{ $enabledKey }}]" value="0">
              <div class="form-check form-switch">
                <input type="checkbox" id="tracking-{{ $enabledKey }}" name="tracking[{{ $enabledKey }}]" value="1" class="form-check-input" @checked($checked("tracking.$enabledKey"))>
                <label class="form-check-label" for="tracking-{{ $enabledKey }}">{{ __('seo.tracking.enable') }}</label>
              </div>
            </div>
            <div class="col-md-5">
              <label class="form-label" for="tracking-{{ $idKey }}">{{ __("seo.tracking.$provider.id") }}</label>
              <input id="tracking-{{ $idKey }}" name="tracking[{{ $idKey }}]" class="form-control @error("tracking.$idKey") is-invalid @enderror" placeholder="{{ $placeholder }}" maxlength="40" autocomplete="off" spellcheck="false" value="{{ old("tracking.$idKey", $get("tracking.$idKey")) }}">
              @error("tracking.$idKey")<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @if($provider === 'gtm')
              <div class="col-md-4">
                <input type="hidden" name="tracking[gtm_manages_tags]" value="0">
                <div class="form-check form-switch">
                  <input type="checkbox" id="tracking-gtm-manages" name="tracking[gtm_manages_tags]" value="1" class="form-check-input" @checked($checked('tracking.gtm_manages_tags', true))>
                  <label class="form-check-label" for="tracking-gtm-manages">{{ __('seo.tracking.gtm.manages') }}</label>
                </div>
              </div>
              <div class="col-12"><div class="form-text">{{ __('seo.tracking.gtm.manages_help') }}</div></div>
            @endif
          </div>
        </div>
      @endforeach
      <div class="small text-muted">{{ __('seo.tracking.events_help') }}</div>
    </div>
  </div>

  <div class="ax-section">
    <div class="ax-section__head"><h3 class="ax-section__title">{{ __('seo.tracking.consent') }}</h3></div>
    <div class="ax-section__body ax-section__body--stack">
      <div class="row g-3">
        @foreach([
          ['analytics_require_consent', true, 'require_consent'],
          ['cookie_banner_enabled', true, 'banner_enabled'],
          ['show_reject_button', true, 'show_reject'],
        ] as [$key, $default, $label])
          <div class="col-md-4">
            <input type="hidden" name="gdpr[{{ $key }}]" value="0">
            <div class="form-check form-switch">
              <input type="checkbox" id="gdpr-{{ $key }}" name="gdpr[{{ $key }}]" value="1" class="form-check-input" @checked($checked("gdpr.$key", $default))>
              <label class="form-check-label" for="gdpr-{{ $key }}">{{ __("seo.tracking.$label") }}</label>
            </div>
            <div class="form-text">{{ __("seo.tracking.{$label}_help") }}</div>
          </div>
        @endforeach
        <div class="col-md-3">
          <label class="form-label" for="gdpr-version">{{ __('seo.tracking.consent_version') }}</label>
          <input id="gdpr-version" name="gdpr[consent_version]" class="form-control @error('gdpr.consent_version') is-invalid @enderror" maxlength="20" value="{{ old('gdpr.consent_version', $get('gdpr.consent_version', 'v1')) }}">
          <div class="form-text">{{ __('seo.tracking.consent_version_help') }}</div>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="gdpr-expiry">{{ __('seo.tracking.consent_expiry') }}</label>
          <input id="gdpr-expiry" type="number" min="1" max="395" name="gdpr[consent_expiry_days]" class="form-control @error('gdpr.consent_expiry_days') is-invalid @enderror" value="{{ old('gdpr.consent_expiry_days', $get('gdpr.consent_expiry_days', 180)) }}">
        </div>
        <div class="col-md-3">
          <label class="form-label" for="gdpr-privacy">{{ __('seo.tracking.privacy_url') }}</label>
          <input id="gdpr-privacy" name="gdpr[privacy_policy_url]" class="form-control @error('gdpr.privacy_policy_url') is-invalid @enderror" maxlength="2048" value="{{ old('gdpr.privacy_policy_url', $get('gdpr.privacy_policy_url', '/privacy-policy')) }}">
        </div>
        <div class="col-md-3">
          <label class="form-label" for="gdpr-cookies">{{ __('seo.tracking.cookie_url') }}</label>
          <input id="gdpr-cookies" name="gdpr[cookie_policy_url]" class="form-control @error('gdpr.cookie_policy_url') is-invalid @enderror" maxlength="2048" value="{{ old('gdpr.cookie_policy_url', $get('gdpr.cookie_policy_url')) }}">
          <div class="form-text">{{ __('seo.tracking.cookie_url_help') }}</div>
        </div>
      </div>

      <h6 class="mt-4">{{ __('seo.tracking.banner_copy') }}</h6>
      <p class="text-muted small">{{ __('seo.tracking.banner_copy_help') }}</p>
      @foreach($locales as $locale)
        @php($code = $locale->code)
        <fieldset class="ax-fieldset">
          <legend class="fs-6 float-none w-auto px-2 mb-0">{{ $locale->name }} ({{ $code }})</legend>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="copy-{{ $code }}-title">{{ __('seo.tracking.banner_title') }}</label>
              <input id="copy-{{ $code }}-title" name="consent_copy[{{ $code }}][title]" class="form-control" maxlength="120" value="{{ old("consent_copy.$code.title", $copy[$code]['title'] ?? '') }}">
            </div>
            <div class="col-md-8">
              <label class="form-label" for="copy-{{ $code }}-text">{{ __('seo.tracking.banner_text') }}</label>
              <textarea id="copy-{{ $code }}-text" name="consent_copy[{{ $code }}][text]" rows="2" class="form-control" maxlength="600">{{ old("consent_copy.$code.text", $copy[$code]['text'] ?? '') }}</textarea>
            </div>
          </div>
        </fieldset>
      @endforeach
    </div>
  </div>


  @include('admin.settings._savebar', ['label' => __('seo.save')])
</form>
@endsection
