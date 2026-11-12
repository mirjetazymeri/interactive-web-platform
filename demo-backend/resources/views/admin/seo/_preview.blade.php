{{--
  Google-style search result preview for a title/description pair.
  $titleSource / $descriptionSource: CSS selectors of the inputs (scoped to $scope, default the form).
  $titleFallback / $descriptionFallback: selectors used when those are empty (e.g. the page title).
  $url: the address shown. The title is built like the website builds it, from the saved site name,
  separator and template ($previewSite / $previewSeparator / $previewTemplate override them), or live
  from fields given as $siteSource / $separatorSource / $templateSource (SEO Settings).
--}}
@php
  $seoDefaults = app(\App\Services\Seo\SeoService::class)->previewDefaults();
@endphp
<div class="seo-preview border rounded p-3 bg-light" data-seo-preview
     data-scope="{{ $scope ?? 'form' }}"
     data-title-source="{{ $titleSource }}" data-title-fallback="{{ $titleFallback ?? '' }}"
     data-description-source="{{ $descriptionSource }}" data-description-fallback="{{ $descriptionFallback ?? '' }}"
     data-site="{{ $previewSite ?? $seoDefaults['site'] }}" data-separator="{{ $previewSeparator ?? $seoDefaults['separator'] }}" data-template="{{ $previewTemplate ?? $seoDefaults['template'] }}"
     data-site-source="{{ $siteSource ?? '' }}" data-separator-source="{{ $separatorSource ?? '' }}" data-template-source="{{ $templateSource ?? '' }}">
  <div class="text-muted small mb-2"><i class="ri-google-line me-1"></i>{{ __('seo.preview.heading') }}</div>
  <div class="small text-truncate" style="color:#202124">{{ $url }}</div>
  <div data-seo-preview-title class="text-truncate" style="color:#1a0dab;font-size:1.15rem;line-height:1.3"></div>
  <div data-seo-preview-description class="small" style="color:#4d5156;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"></div>
  <div class="form-text mt-2 mb-0">{{ __('seo.preview.help') }}</div>
</div>
