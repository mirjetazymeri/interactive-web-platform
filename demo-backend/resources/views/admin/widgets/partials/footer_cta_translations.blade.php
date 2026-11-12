@php
  $footerCtaBase = $type === 'footer_cta' ? (array) ($widgetConfig ?? []) : [];
@endphp
<div class="col-12 widget-translation-type {{ $type === 'footer_cta' ? '' : 'd-none' }}" data-widget-translation-type="footer_cta">
  <div class="border rounded p-3 bg-light-subtle">
    <div class="fw-semibold mb-2 fs-13 text-muted text-uppercase"><i class="ri-megaphone-line me-1"></i> {{ __('footer_cta.content') }}</div>
    <p class="text-muted fs-13">{{ __('footer_cta.translation_help') }}</p>
    <div class="row g-2">
      @foreach(\App\Services\Widgets\Types\FooterCtaWidgetTypeService::TEXT_FIELDS as $field)
        @php
          $long = in_array($field, \App\Services\Widgets\Types\FooterCtaWidgetTypeService::LONG_FIELDS, true);
          $value = old("translations.$index.$field", $translationConfig[$field] ?? '');
        @endphp
        <div class="{{ $long ? 'col-12' : 'col-md-6' }}">
          <label class="form-label fs-12 text-muted w-100">{{ __('footer_cta.field.'.$field) }}
            @if($long)
              <textarea name="translations[{{ $index }}][{{ $field }}]" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="{{ $footerCtaBase[$field] ?? '' }}">{{ $value }}</textarea>
            @else
              <input name="translations[{{ $index }}][{{ $field }}]" class="form-control form-control-sm" maxlength="255" value="{{ $value }}" placeholder="{{ $footerCtaBase[$field] ?? '' }}">
            @endif
          </label>
        </div>
      @endforeach
    </div>
  </div>
</div>
