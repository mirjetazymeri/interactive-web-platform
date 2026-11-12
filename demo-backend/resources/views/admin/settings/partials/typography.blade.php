@php
  /* Typography for the public site (theme.typography.*). Only the preview below uses the public fonts. */
  $typographySchema = config('settings.theme.typography', []);
  $typography = [];
  foreach ($typographySchema as $key => $field) {
      $value = old("theme.typography.$key", $settings["theme.typography.$key"] ?? $field['default'] ?? null);
      // Never echo an unknown stored font/weight back into the preview's CSS.
      if (($field['type'] ?? null) === 'select' && !in_array((string) $value, $field['options'], true)) {
          $value = $field['default'];
      }
      $typography[$key] = $value;
  }
  $defaults = collect($typographySchema)->map(fn ($field) => $field['default'] ?? '')->all();
  $stack = fn (string $font) => "'{$font}', ".($font === 'IBM Plex Mono' ? 'monospace' : 'sans-serif');
  $numberValue = fn ($value, $fallback) => is_numeric($value) ? $value : $fallback;
@endphp

<div class="mt-4 pt-3 border-top" id="typography-settings" data-defaults='@json($defaults)'>
  <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
      <h6 class="text-uppercase text-muted fs-11 fw-semibold letter-spacing mb-1">
        <i class="ri-font-size me-1"></i>{{ __('typography.title') }}
      </h6>
      <p class="text-muted small mb-0">{{ __('typography.intro') }}</p>
    </div>
    <div class="text-end">
      <button type="button" class="btn btn-outline-secondary btn-sm" data-typography-reset>
        <i class="ri-restart-line me-1"></i>{{ __('typography.reset') }}
      </button>
      <div class="form-text small">{{ __('typography.reset_help') }}</div>
    </div>
  </div>

  <div class="row">
    @foreach (['display_font', 'body_font'] as $key)
      @php $hasError = $errors->has("theme.typography.$key"); @endphp
      <div class="col-md-6 mb-3">
        <label class="form-label fw-medium small mb-1" for="typography-{{ $key }}">{{ __("typography.$key") }}</label>
        <select id="typography-{{ $key }}" name="theme[typography][{{ $key }}]" data-typography="{{ $key }}"
                class="form-select form-select-sm {{ $hasError ? 'is-invalid' : '' }}">
          @foreach ($typographySchema[$key]['options'] as $font)
            <option value="{{ $font }}" @selected($typography[$key] === $font)>{{ $font }}</option>
          @endforeach
        </select>
        <div class="form-text small">{{ __("typography.{$key}_help") }}</div>
        @error("theme.typography.$key")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      </div>
    @endforeach

    @foreach (['display_weight', 'ui_weight', 'body_weight'] as $key)
      @php $hasError = $errors->has("theme.typography.$key"); @endphp
      <div class="col-md-4 mb-3">
        <label class="form-label fw-medium small mb-1" for="typography-{{ $key }}">{{ __("typography.$key") }}</label>
        <select id="typography-{{ $key }}" name="theme[typography][{{ $key }}]" data-typography="{{ $key }}"
                class="form-select form-select-sm {{ $hasError ? 'is-invalid' : '' }}">
          @foreach ($typographySchema[$key]['options'] as $weight)
            <option value="{{ $weight }}" @selected((string) $typography[$key] === $weight)>{{ __("typography.weights.$weight") }}</option>
          @endforeach
        </select>
        @error("theme.typography.$key")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      </div>
    @endforeach

    @foreach (['body_size', 'body_line_height', 'heading_letter_spacing', 'heading_scale'] as $key)
      @php $field = $typographySchema[$key]; $hasError = $errors->has("theme.typography.$key"); @endphp
      <div class="col-md-6 col-xl-3 mb-3">
        <label class="form-label fw-medium small mb-1" for="typography-{{ $key }}">{{ __("typography.$key") }}</label>
        <input type="number" id="typography-{{ $key }}" name="theme[typography][{{ $key }}]" data-typography="{{ $key }}"
               value="{{ $typography[$key] }}" min="{{ $field['min'] }}" max="{{ $field['max'] }}" step="{{ $field['step'] }}"
               @if($key !== 'heading_scale') required @endif
               class="form-control form-control-sm {{ $hasError ? 'is-invalid' : '' }}">
        <div class="form-text small">
          {{ $key === 'heading_scale' ? __('typography.heading_scale_help') : __('typography.range', ['min' => $field['min'], 'max' => $field['max']]) }}
        </div>
        @error("theme.typography.$key")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      </div>
    @endforeach
  </div>

  {{-- Live preview: the only place in the admin that uses the public fonts. --}}
  <div class="card custom-card mb-0 border">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
      <div class="card-title fs-13 mb-0">{{ __('typography.preview') }}</div>
      <span class="text-muted small">{{ __('typography.preview_help') }}</span>
    </div>
    <div class="card-body p-0">
      <div class="typography-preview" data-typography-preview style="
        --tp-display: {{ $stack($typography['display_font']) }}; --tp-body: {{ $stack($typography['body_font']) }};
        --tp-display-weight: {{ $typography['display_weight'] }}; --tp-ui-weight: {{ $typography['ui_weight'] }}; --tp-body-weight: {{ $typography['body_weight'] }};
        --tp-size: {{ $numberValue($typography['body_size'], 16) }}px; --tp-line-height: {{ $numberValue($typography['body_line_height'], 1.6) }};
        --tp-letter-spacing: {{ $numberValue($typography['heading_letter_spacing'], -0.02) }}em; --tp-scale: {{ $numberValue($typography['heading_scale'], 1) }};">
        <nav class="tp-nav" aria-hidden="true">
          @foreach (__('typography.preview_nav') as $item)<span>{{ $item }}</span>@endforeach
        </nav>
        <p class="tp-meta">{{ __('typography.preview_meta') }}</p>
        <p class="tp-heading">{{ __('typography.preview_heading') }}</p>
        <p class="tp-paragraph">{{ __('typography.preview_paragraph') }}</p>
        <div class="tp-row">
          <span class="tp-button">{{ __('typography.preview_button') }} <span aria-hidden="true">↗</span></span>
          <p class="tp-project">{{ __('typography.preview_project') }}</p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .typography-preview { --tp-bg: #050505; --tp-fg: #ffffff; --tp-accent: #fea34d; --tp-accent-fg: #000000;
    display: grid; gap: 18px; padding: clamp(20px, 3vw, 40px); overflow: hidden; border-radius: 0 0 .375rem .375rem;
    background: var(--tp-bg); color: var(--tp-fg); font-family: var(--tp-body); font-weight: var(--tp-body-weight);
    font-size: var(--tp-size); line-height: var(--tp-line-height); }
  .typography-preview p { margin: 0; }
  .typography-preview .tp-nav { display: flex; flex-wrap: wrap; gap: 8px 28px; font-family: var(--tp-display); font-weight: var(--tp-ui-weight); font-size: .875rem; opacity: .8; }
  .typography-preview .tp-meta { font-family: var(--tp-display); font-weight: var(--tp-ui-weight); font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; opacity: .65; }
  .typography-preview .tp-heading { max-width: 18ch; font-family: var(--tp-display); font-weight: var(--tp-display-weight); font-size: calc(clamp(2rem, 4.4vw, 3.75rem) * var(--tp-scale)); line-height: 1.04; letter-spacing: var(--tp-letter-spacing); overflow-wrap: anywhere; }
  .typography-preview .tp-paragraph { max-width: 60ch; opacity: .8; }
  .typography-preview .tp-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px 32px; padding-top: 18px; border-top: 1px solid color-mix(in srgb, var(--tp-fg) 18%, transparent); }
  .typography-preview .tp-button { display: inline-flex; gap: 16px; padding: 12px 24px; border-radius: 100px; background: var(--tp-accent); color: var(--tp-accent-fg); font-family: var(--tp-display); font-weight: var(--tp-ui-weight); font-size: .95rem; line-height: 1.5; }
  .typography-preview .tp-project { font-family: var(--tp-display); font-weight: var(--tp-display-weight); font-size: calc(1.5rem * var(--tp-scale)); letter-spacing: var(--tp-letter-spacing); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const root = document.getElementById('typography-settings');
  if (!root) return;
  const preview = root.querySelector('[data-typography-preview]');
  const defaults = JSON.parse(root.dataset.defaults);
  const input = (key) => root.querySelector(`[data-typography="${key}"]`);
  const form = root.closest('form');
  const stack = (font) => `'${font}', ${font === 'IBM Plex Mono' ? 'monospace' : 'sans-serif'}`;
  const number = (key, fallback) => { const v = parseFloat(input(key).value); return Number.isFinite(v) ? v : fallback; };

  // Load only the two selected families, and only for this preview (no admin UI rule names them).
  function loadFonts(fonts) {
    const href = 'https://fonts.googleapis.com/css2?' + [...new Set(fonts)].map((f) => 'family=' + encodeURIComponent(f).replace(/%20/g, '+') + ':wght@300;400;500;600;700').join('&') + '&display=swap';
    let link = document.getElementById('typography-preview-fonts');
    if (!link) { link = document.createElement('link'); link.id = 'typography-preview-fonts'; link.rel = 'stylesheet'; document.head.appendChild(link); }
    if (link.href !== href) link.href = href;
  }

  // Preview colors follow the (unsaved) theme colors in the same tab.
  function ink(hex) {
    const rgb = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255).map((v) => v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4);
    return rgb[0] * .2126 + rgb[1] * .7152 + rgb[2] * .0722 > .179 ? '#000000' : '#ffffff';
  }
  function color(name) {
    const field = form?.querySelector(`[name="theme[${name}]"]`);
    return field && /^#[0-9a-f]{6}$/i.test(field.value) ? field.value : null;
  }

  function update() {
    const display = input('display_font').value, body = input('body_font').value;
    loadFonts([display, body]);
    const vars = {
      '--tp-display': stack(display), '--tp-body': stack(body),
      '--tp-display-weight': input('display_weight').value, '--tp-ui-weight': input('ui_weight').value, '--tp-body-weight': input('body_weight').value,
      '--tp-size': number('body_size', defaults.body_size) + 'px', '--tp-line-height': number('body_line_height', defaults.body_line_height),
      '--tp-letter-spacing': number('heading_letter_spacing', defaults.heading_letter_spacing) + 'em', '--tp-scale': number('heading_scale', defaults.heading_scale),
    };
    const bg = color('primary_color'), accent = color('accent_color');
    if (bg) Object.assign(vars, { '--tp-bg': bg, '--tp-fg': ink(bg) });
    if (accent) Object.assign(vars, { '--tp-accent': accent, '--tp-accent-fg': ink(accent) });
    for (const [name, value] of Object.entries(vars)) preview.style.setProperty(name, value);
  }

  root.querySelector('[data-typography-reset]').addEventListener('click', function () {
    for (const [key, value] of Object.entries(defaults)) input(key).value = value;
    update();
  });
  root.addEventListener('input', update);
  root.addEventListener('change', update);
  form?.querySelectorAll('[name="theme[primary_color]"],[name="theme[accent_color]"]').forEach((field) => field.addEventListener('input', update));
  update();
});
</script>
