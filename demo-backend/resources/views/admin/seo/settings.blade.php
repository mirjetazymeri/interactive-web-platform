@extends('admin.settings.center')
@php
  $settingsActive = 'seo';
  $field = fn (string $key) => 'seo['.$key.']';
  $error = fn (string $key) => $errors->first("seo.$key");
  $defaultLocale = $locales->firstWhere('is_default', true)?->code ?? $locales->first()?->code ?? config('app.locale');
  $site = old('seo.site_name', $get('seo.site_name')) ?: $get('general.app_name', config('app.name'));
  $ogImage = $get('seo.og_image');
@endphp
@section('title', __('seo.settings.title'))
@section('settings-panel')

<header class="ax-panel-head">
  <span class="ax-panel-head__icon" aria-hidden="true"><i class="ri-search-eye-line"></i></span>
  <div>
    <h2 class="ax-panel-head__title">{{ __('seo.settings.title') }}</h2>
    <p class="ax-panel-head__desc">{{ __('seo.settings.intro') }}</p>
  </div>
</header>

@if($errors->any())
  <div class="alert alert-danger ax-errors" role="alert"><ul class="mb-0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
@endif

<div class="ax-status-grid">
  <div class="ax-status">
    <div class="ax-status__label">{{ __('seo.status.site_url') }}</div>
    @if($siteUrl)<span class="badge bg-success">{{ __('seo.status.configured') }}</span> <code>{{ $siteUrl }}</code>
    @else<span class="badge bg-warning text-dark">{{ __('seo.status.missing') }}</span> <span class="small">{{ __('seo.status.site_url_missing') }}</span>@endif
  </div>
  <div class="ax-status">
    <div class="ax-status__label">{{ __('seo.status.indexing') }}</div>
    @if($get('seo.allow_indexing', true))<span class="badge bg-success">{{ __('seo.status.allowed') }}</span>
    @else<span class="badge bg-secondary">{{ __('seo.status.blocked') }}</span>@endif
    <div class="small text-muted mt-1">{{ __('seo.status.indexing_env') }}</div>
  </div>
  <div class="ax-status">
    <div class="ax-status__label">{{ __('seo.status.languages') }}</div>
    @foreach($locales as $locale)<span class="badge {{ $locale->is_default ? 'bg-primary' : 'bg-light text-dark' }} me-1">{{ $locale->name }} ({{ $locale->code }})</span>@endforeach
    <div class="small mt-1"><a href="{{ url('admin/locales') }}">{{ __('seo.status.manage_languages') }}</a></div>
  </div>
</div>

<form id="seo-settings-form" method="POST" action="{{ route('admin.seo.settings.update') }}" enctype="multipart/form-data" data-ax-dirty-form>
  @csrf @method('PUT')

  <div class="ax-section">
    <div class="ax-section__head"><h3 class="ax-section__title">{{ __('seo.settings.defaults') }}</h3></div>
    <div class="ax-section__body ax-section__body--stack">
      <div class="row g-3">
        <div class="col-xxl-7">
          <div class="row g-3">
            <div class="col-12 col-xxl-6">
              <label class="form-label" for="seo-site-name">{{ __('seo.settings.site_name') }}</label>
              <input id="seo-site-name" name="{{ $field('site_name') }}" class="form-control @if($error('site_name')) is-invalid @endif" maxlength="100" value="{{ old('seo.site_name', $get('seo.site_name')) }}" placeholder="{{ $get('general.app_name') }}">
              <div class="form-text">{{ __('seo.settings.site_name_help') }}</div>
            </div>
            <div class="col-sm-4 col-xxl-2">
              <label class="form-label" for="seo-separator">{{ __('seo.settings.title_separator') }}</label>
              <select id="seo-separator" name="{{ $field('title_separator') }}" class="form-select">
                @foreach($separators as $separator)<option value="{{ $separator }}" @selected(old('seo.title_separator', $get('seo.title_separator', '|')) === $separator)>{{ $separator }}</option>@endforeach
              </select>
            </div>
            <div class="col-sm-8 col-xxl-4">
              <label class="form-label" for="seo-template">{{ __('seo.settings.title_template') }}</label>
              <input id="seo-template" name="{{ $field('title_template') }}" class="form-control @if($error('title_template')) is-invalid @endif" maxlength="120" placeholder="{title} {separator} {site}" value="{{ old('seo.title_template', $get('seo.title_template')) }}">
              <div class="form-text">{{ __('seo.settings.title_template_help') }}</div>
            </div>
            <div class="col-12">
              <label class="form-label" for="seo-meta-title">{{ __('seo.settings.meta_title') }} <span class="badge bg-light text-dark">{{ strtoupper($defaultLocale) }}</span></label>
              <input id="seo-meta-title" name="{{ $field('meta_title') }}" class="form-control @if($error('meta_title')) is-invalid @endif" maxlength="255" data-seo-count="60" value="{{ old('seo.meta_title', $get('seo.meta_title')) }}">
              <div class="form-text">{{ __('seo.settings.meta_title_help') }}</div>
            </div>
            <div class="col-12">
              <label class="form-label" for="seo-meta-description">{{ __('seo.settings.meta_description') }} <span class="badge bg-light text-dark">{{ strtoupper($defaultLocale) }}</span></label>
              <textarea id="seo-meta-description" name="{{ $field('meta_description') }}" rows="3" class="form-control @if($error('meta_description')) is-invalid @endif" maxlength="1000" data-seo-count="160">{{ old('seo.meta_description', $get('seo.meta_description')) }}</textarea>
              <div class="form-text">{{ __('seo.settings.meta_description_help') }}</div>
            </div>
          </div>
        </div>
        <div class="col-xxl-5">
          @include('admin.seo._preview', [
            'titleSource' => '#seo-meta-title', 'titleFallback' => '#seo-site-name',
            'descriptionSource' => '#seo-meta-description', 'url' => ($siteUrl ?: 'https://www.example.com').'/'.$defaultLocale,
            // Follows the site name, separator and template fields above as they are edited.
            'previewSite' => $get('general.app_name', config('app.name')), 'siteSource' => '#seo-site-name',
            'separatorSource' => '#seo-separator', 'templateSource' => '#seo-template',
          ])
        </div>
      </div>
    </div>
  </div>

  @if($locales->count() > 1)
  <div class="ax-section">
    <div class="ax-section__head"><h3 class="ax-section__title">{{ __('seo.settings.languages') }}</h3></div>
    <div class="ax-section__body ax-section__body--stack">
      <p class="text-muted small">{{ __('seo.settings.languages_help') }}</p>
      @foreach($locales->where('code', '!=', $defaultLocale) as $locale)
        @php($code = $locale->code)
        <fieldset class="ax-fieldset">
          <legend class="fs-6 float-none w-auto px-2 mb-0">{{ $locale->name }} ({{ $code }})</legend>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="seo-{{ $code }}-title">{{ __('seo.settings.meta_title') }}</label>
              <input id="seo-{{ $code }}-title" name="localized[{{ $code }}][meta_title]" class="form-control" maxlength="255" data-seo-count="60" value="{{ old("localized.$code.meta_title", $localized[$code]['meta_title'] ?? '') }}" placeholder="{{ $get('seo.meta_title') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="seo-{{ $code }}-description">{{ __('seo.settings.meta_description') }}</label>
              <textarea id="seo-{{ $code }}-description" name="localized[{{ $code }}][meta_description]" rows="2" class="form-control" maxlength="1000" data-seo-count="160" placeholder="{{ $get('seo.meta_description') }}">{{ old("localized.$code.meta_description", $localized[$code]['meta_description'] ?? '') }}</textarea>
            </div>
            <div class="col-12">
              <label class="form-label" for="seo-{{ $code }}-organization">{{ __('seo.settings.organization_description') }}</label>
              <textarea id="seo-{{ $code }}-organization" name="localized[{{ $code }}][organization_description]" rows="2" class="form-control" maxlength="1000">{{ old("localized.$code.organization_description", $localized[$code]['organization_description'] ?? '') }}</textarea>
            </div>
          </div>
        </fieldset>
      @endforeach
    </div>
  </div>
  @endif

  <div class="ax-section">
    <div class="ax-section__head"><h3 class="ax-section__title">{{ __('seo.settings.organization') }}</h3></div>
    <div class="ax-section__body ax-section__body--stack">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="seo-organization-name">{{ __('seo.settings.organization_name') }}</label>
          <input id="seo-organization-name" name="{{ $field('organization_name') }}" class="form-control" maxlength="255" value="{{ old('seo.organization_name', $get('seo.organization_name')) }}" placeholder="{{ $site }}">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="seo-site-url">{{ __('seo.settings.site_url') }}</label>
          <input id="seo-site-url" type="url" name="{{ $field('site_url') }}" class="form-control @if($error('site_url')) is-invalid @endif" maxlength="255" placeholder="https://www.example.com" value="{{ old('seo.site_url', $get('seo.site_url')) }}">
          <div class="form-text">{{ __('seo.settings.site_url_help') }}</div>
        </div>
        <div class="col-12">
          <label class="form-label" for="seo-organization-description">{{ __('seo.settings.organization_description') }} <span class="badge bg-light text-dark">{{ strtoupper($defaultLocale) }}</span></label>
          <textarea id="seo-organization-description" name="{{ $field('organization_description') }}" rows="3" class="form-control" maxlength="1000">{{ old('seo.organization_description', $get('seo.organization_description')) }}</textarea>
          <div class="form-text">{{ __('seo.settings.organization_description_help') }}</div>
        </div>
        <div class="col-12">
          <div class="alert alert-light border small mb-0">
            <strong>{{ __('seo.settings.reused') }}</strong>
            {{ __('seo.settings.reused_help') }}
            <a href="{{ route('admin.settings.index') }}">{{ __('seo.settings.open_settings') }}</a>
            <ul class="mb-0 mt-2">
              <li>{{ __('seo.settings.logo') }}: @if($get('general.logo'))<span class="text-success">{{ __('seo.status.configured') }}</span>@else<span class="text-warning">{{ __('seo.status.missing') }}</span>@endif</li>
              <li>{{ __('seo.settings.contact') }}: {{ collect([$get('footer.contact_email') ?: $get('store.email'), $get('footer.contact_phone') ?: $get('store.phone')])->filter()->implode(' · ') ?: __('seo.status.missing') }}</li>
              <li>{{ __('seo.settings.address') }}: {{ trim(preg_replace('/\s+/', ' ', (string) $get('footer.address'))) ?: __('seo.status.missing') }}</li>
              <li>{{ __('seo.settings.social') }}: {{ collect(['facebook', 'instagram', 'linkedin', 'twitter', 'youtube', 'tiktok', 'pinterest'])->filter(fn ($network) => filled($get("social.$network")))->map(fn ($network) => ucfirst($network))->implode(', ') ?: __('seo.status.missing') }}</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="ax-section">
    <div class="ax-section__head"><h3 class="ax-section__title">{{ __('seo.settings.sharing') }}</h3></div>
    <div class="ax-section__body ax-section__body--stack">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="seo-og-image">{{ __('seo.settings.og_image') }}</label>
          @if($ogImage)
            <div class="d-flex align-items-center gap-2 mb-2">
              <img src="{{ asset('storage/'.$ogImage) }}" alt="" style="max-height:63px;max-width:120px" class="border rounded">
              <label class="small"><input type="checkbox" class="form-check-input me-1" name="og_image_remove" value="1">{{ __('seo.page.og_image_remove') }}</label>
            </div>
          @endif
          <input id="seo-og-image" type="file" name="og_image_file" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control @error('og_image_file') is-invalid @enderror">
          <div class="form-text">{{ __('seo.settings.og_image_help') }}</div>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="seo-twitter">{{ __('seo.settings.twitter_handle') }}</label>
          <input id="seo-twitter" name="{{ $field('twitter_handle') }}" class="form-control @if($error('twitter_handle')) is-invalid @endif" maxlength="16" placeholder="@demo" value="{{ old('seo.twitter_handle', $get('seo.twitter_handle')) }}">
        </div>
      </div>
    </div>
  </div>

  <div class="ax-section">
    <div class="ax-section__head"><h3 class="ax-section__title">{{ __('seo.settings.indexing') }}</h3></div>
    <div class="ax-section__body ax-section__body--stack">
      <input type="hidden" name="{{ $field('allow_indexing') }}" value="0">
      <div class="form-check form-switch mb-1">
        <input type="checkbox" id="seo-indexing" name="{{ $field('allow_indexing') }}" value="1" class="form-check-input" @checked(old('seo.allow_indexing', $get('seo.allow_indexing', true)))>
        <label class="form-check-label" for="seo-indexing">{{ __('seo.settings.allow_indexing') }}</label>
      </div>
      <div class="form-text mb-3">{{ __('seo.settings.allow_indexing_help') }}</div>

      <div class="row g-3">
        @foreach(['google_site_verification' => 'google_verification', 'bing_site_verification' => 'bing_verification', 'facebook_domain_verification' => 'facebook_verification'] as $key => $label)
          <div class="col-md-4">
            <label class="form-label" for="seo-{{ $key }}">{{ __("seo.settings.$label") }}</label>
            <input id="seo-{{ $key }}" name="{{ $field($key) }}" class="form-control @if($error($key)) is-invalid @endif" maxlength="300" value="{{ old("seo.$key", $get("seo.$key")) }}" autocomplete="off">
            <div class="form-text">{{ __('seo.settings.verification_help') }}</div>
          </div>
        @endforeach
      </div>
    </div>
  </div>


  @include('admin.settings._savebar', ['label' => __('seo.save')])
</form>
@include('admin.seo._preview_script')
@endsection
