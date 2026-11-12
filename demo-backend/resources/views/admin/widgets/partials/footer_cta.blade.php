@php
  // Other widget types share field names like config[text]; only show this type's own values.
  $footerCtaCfg = $type === 'footer_cta' ? (array) $cfg : [];
  $footerCtaValue = fn ($field) => old("config.$field", $footerCtaCfg[$field] ?? '');
@endphp
{{-- Footer call to action config block --}}
<div id="cfg-footer_cta" class="cfg d-none">
  <div class="d-flex align-items-center gap-2 mb-2">
    <i class="ri-megaphone-line text-secondary fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">{{ __('footer_cta.content') }}</span>
  </div>
  <p class="text-muted fs-13">{{ __('footer_cta.help') }}</p>
  <div class="row g-3">
    @foreach(\App\Services\Widgets\Types\FooterCtaWidgetTypeService::TEXT_FIELDS as $field)
      @php($long = in_array($field, \App\Services\Widgets\Types\FooterCtaWidgetTypeService::LONG_FIELDS, true))
      <div class="{{ $long ? 'col-12' : 'col-md-6' }}">
        <label class="form-label">{{ __('footer_cta.field.'.$field) }}@if($field === 'heading') <span class="text-danger">*</span>@endif</label>
        @if($long)
          <textarea name="config[{{ $field }}]" class="form-control" rows="2" maxlength="500">{{ $footerCtaValue($field) }}</textarea>
        @else
          <input name="config[{{ $field }}]" class="form-control" maxlength="255" value="{{ $footerCtaValue($field) }}">
        @endif
        @error("config.$field")<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    @endforeach
    <div class="col-md-6">
      <label class="form-label">{{ __('footer_cta.field.button_link') }}</label>
      <input name="config[button_link]" class="form-control" maxlength="1000" value="{{ $footerCtaValue('button_link') }}" placeholder="/contact">
      <div class="form-text">{{ __('footer_cta.button_link_help') }}</div>
      @error('config.button_link')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 d-flex align-items-center">
      <div class="form-check form-switch mt-md-3">
        <input type="hidden" name="config[show_email]" value="0">
        <input type="checkbox" class="form-check-input" id="footer-cta-show-email" name="config[show_email]" value="1"
               @checked(old('config.show_email', $footerCtaCfg['show_email'] ?? true))>
        <label class="form-check-label" for="footer-cta-show-email">{{ __('footer_cta.field.show_email') }}</label>
        <div class="form-text">{{ __('footer_cta.show_email_help') }}</div>
      </div>
    </div>
  </div>
</div>
