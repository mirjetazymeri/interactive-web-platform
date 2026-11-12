@php
  $layoutDefaults = config('settings.layout', []);
  $getLayout = function (string $key, $fallback = null) use ($settings, $layoutDefaults) {
      $default = $layoutDefaults[$key]['default'] ?? $fallback;
      return old("layout.$key", $settings["layout.$key"] ?? $default);
  };

  $preview = [
      'max_width'          => (int) $getLayout('max_width', 1200),
      'container_padding'  => (int) $getLayout('container_padding', 24),
      'gutter_width'       => (int) $getLayout('gutter_width', 24),
      'base_font_size'     => (int) $getLayout('base_font_size', 16),
      'line_height_normal' => (float) $getLayout('line_height_normal', 1.5),
      'border_radius_md'   => (int) $getLayout('border_radius_md', 8),
      'card_border_radius' => (int) $getLayout('card_border_radius', 12),
      'card_padding'       => (int) $getLayout('card_padding', 24),
      'button_border_radius' => (int) $getLayout('button_border_radius', 6),
      'button_padding_x'   => (int) $getLayout('button_padding_x', 20),
      'button_padding_y'   => (int) $getLayout('button_padding_y', 12),
      'spacing_unit'       => (int) $getLayout('spacing_unit', 8),
      'header_height'      => (int) $getLayout('header_height', 80),
      'footer_height'      => (int) $getLayout('footer_height', 100),
      'header_shadow'      => (string) $getLayout('header_shadow', 'light'),
  ];

  $shadowMap = [
      'none' => 'none',
      'light' => '0 6px 16px rgba(15, 23, 42, .08)',
      'medium' => '0 10px 28px rgba(15, 23, 42, .14)',
      'heavy' => '0 18px 40px rgba(15, 23, 42, .2)',
  ];

  $headerShadow = $shadowMap[$preview['header_shadow']] ?? $shadowMap['light'];
@endphp

<div class="mt-4 pt-3 border-top" id="layout-preview-root">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <h6 class="mb-0 fw-semibold">
      <i class="ri-slideshow-line me-1 text-primary"></i>
      Layout Preview
    </h6>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-primary-transparent text-primary">Dummy Data</span>
      <a href="{{ route('admin.settings.layout-preview') }}"
         target="_blank"
         rel="noopener"
         id="open-layout-preview-tab"
         class="btn btn-sm btn-outline-primary">
        <i class="ri-external-link-line me-1"></i>Open Full Preview
      </a>
    </div>
  </div>
  <p class="text-muted small mb-3">Preview updates as you change layout inputs in this tab.</p>

  <div id="layout-preview-canvas"
       class="border rounded-3 bg-white overflow-hidden"
       style="--lp-max-width: {{ max(320, $preview['max_width']) }}px;
              --lp-container-padding: {{ max(0, $preview['container_padding']) }}px;
              --lp-gutter-width: {{ max(0, $preview['gutter_width']) }}px;
              --lp-font-size: {{ max(12, $preview['base_font_size']) }}px;
              --lp-line-height: {{ max(1, $preview['line_height_normal']) }};
              --lp-radius: {{ max(0, $preview['border_radius_md']) }}px;
              --lp-card-radius: {{ max(0, $preview['card_border_radius']) }}px;
              --lp-card-padding: {{ max(8, $preview['card_padding']) }}px;
              --lp-btn-radius: {{ max(0, $preview['button_border_radius']) }}px;
              --lp-btn-px: {{ max(8, $preview['button_padding_x']) }}px;
              --lp-btn-py: {{ max(4, $preview['button_padding_y']) }}px;
              --lp-spacing: {{ max(2, $preview['spacing_unit']) }}px;
              --lp-header-height: {{ max(48, $preview['header_height']) }}px;
              --lp-footer-height: {{ max(48, $preview['footer_height']) }}px;
              --lp-header-shadow: {{ $headerShadow }};">

    <div class="lp-shell mx-auto">
      <header class="lp-header d-flex align-items-center justify-content-between">
        <strong>Brand</strong>
        <small class="text-muted">Header</small>
      </header>

      <main class="lp-main">
        <section class="lp-hero mb-3">
          <h6 class="mb-1">Modern Landing Section</h6>
          <p class="mb-0 text-muted">This is dummy text to visualize your spacing, width, and typography scale.</p>
        </section>

        <div class="lp-grid">
          <article class="lp-card">
            <h6 class="mb-1">Card One</h6>
            <p class="mb-2 text-muted">Reusable card surface with your configured radius and padding.</p>
            <button type="button" class="btn btn-primary btn-sm lp-btn">Primary Action</button>
          </article>
          <article class="lp-card">
            <h6 class="mb-1">Card Two</h6>
            <p class="mb-2 text-muted">Grid gutter and container padding are reflected here.</p>
            <button type="button" class="btn btn-outline-primary btn-sm lp-btn">Secondary Action</button>
          </article>
        </div>
      </main>

      <footer class="lp-footer d-flex align-items-center justify-content-center text-muted">
        Footer Area
      </footer>
    </div>
  </div>

  <div class="small text-muted mt-2" id="layout-preview-stats">
    max_width: <strong>{{ $preview['max_width'] }}px</strong> |
    spacing_unit: <strong>{{ $preview['spacing_unit'] }}px</strong> |
    base_font_size: <strong>{{ $preview['base_font_size'] }}px</strong>
  </div>
</div>


<style>
  #layout-preview-canvas {
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
  }

  #layout-preview-canvas .lp-shell {
    max-width: var(--lp-max-width);
    font-size: var(--lp-font-size);
    line-height: var(--lp-line-height);
    padding: var(--lp-container-padding);
  }

  #layout-preview-canvas .lp-header {
    height: var(--lp-header-height);
    border-radius: var(--lp-radius);
    background: #ffffff;
    padding: 0 calc(var(--lp-spacing) * 1.5);
    box-shadow: var(--lp-header-shadow);
    border: 1px solid #e2e8f0;
    margin-bottom: calc(var(--lp-spacing) * 2);
  }

  #layout-preview-canvas .lp-main {
    margin-bottom: calc(var(--lp-spacing) * 2);
  }

  #layout-preview-canvas .lp-hero {
    background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%);
    border: 1px solid #e2e8f0;
    border-radius: var(--lp-radius);
    padding: calc(var(--lp-spacing) * 2);
  }

  #layout-preview-canvas .lp-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--lp-gutter-width);
  }

  #layout-preview-canvas .lp-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: var(--lp-card-radius);
    padding: var(--lp-card-padding);
  }

  #layout-preview-canvas .lp-btn {
    border-radius: var(--lp-btn-radius);
    padding: var(--lp-btn-py) var(--lp-btn-px);
    line-height: 1.2;
  }

  #layout-preview-canvas .lp-footer {
    height: var(--lp-footer-height);
    border-radius: var(--lp-radius);
    border: 1px dashed #cbd5e1;
    background: #f8fafc;
  }

  @media (max-width: 768px) {
    #layout-preview-canvas .lp-grid {
      grid-template-columns: 1fr;
    }
  }
</style>


<script>
  (function () {
    const root = document.getElementById('layout-preview-root');
    const canvas = document.getElementById('layout-preview-canvas');
    const stats = document.getElementById('layout-preview-stats');

    if (!root || !canvas) {
      return;
    }

    const map = {
      max_width: '--lp-max-width',
      container_padding: '--lp-container-padding',
      gutter_width: '--lp-gutter-width',
      base_font_size: '--lp-font-size',
      line_height_normal: '--lp-line-height',
      border_radius_md: '--lp-radius',
      card_border_radius: '--lp-card-radius',
      card_padding: '--lp-card-padding',
      button_border_radius: '--lp-btn-radius',
      button_padding_x: '--lp-btn-px',
      button_padding_y: '--lp-btn-py',
      spacing_unit: '--lp-spacing',
      header_height: '--lp-header-height',
      footer_height: '--lp-footer-height',
    };

    const shadowMap = {
      none: 'none',
      light: '0 6px 16px rgba(15, 23, 42, .08)',
      medium: '0 10px 28px rgba(15, 23, 42, .14)',
      heavy: '0 18px 40px rgba(15, 23, 42, .2)',
    };

    const unitFields = new Set([
      'max_width', 'container_padding', 'gutter_width', 'base_font_size',
      'border_radius_md', 'card_border_radius', 'card_padding', 'button_border_radius',
      'button_padding_x', 'button_padding_y', 'spacing_unit', 'header_height', 'footer_height',
    ]);

    const minClamp = {
      max_width: 320,
      container_padding: 0,
      gutter_width: 0,
      base_font_size: 12,
      line_height_normal: 1,
      border_radius_md: 0,
      card_border_radius: 0,
      card_padding: 8,
      button_border_radius: 0,
      button_padding_x: 8,
      button_padding_y: 4,
      spacing_unit: 2,
      header_height: 48,
      footer_height: 48,
    };

    const getInput = (key) => document.querySelector('[name="layout[' + key + ']"]');

    const applyKey = (key) => {
      const input = getInput(key);
      if (!input) {
        return;
      }

      const rawValue = input.value;
      const value = rawValue === '' ? 0 : Number(rawValue);
      const clamped = Math.max(minClamp[key] ?? 0, Number.isNaN(value) ? 0 : value);
      const cssVar = map[key];

      if (!cssVar) {
        return;
      }

      canvas.style.setProperty(cssVar, unitFields.has(key) ? clamped + 'px' : String(clamped));
    };

    const applyShadow = () => {
      const input = getInput('header_shadow');
      const key = input ? input.value : 'light';
      canvas.style.setProperty('--lp-header-shadow', shadowMap[key] || shadowMap.light);
    };

    const refreshStats = () => {
      if (!stats) {
        return;
      }

      const maxWidth = getInput('max_width')?.value || '1200';
      const spacing = getInput('spacing_unit')?.value || '8';
      const fontSize = getInput('base_font_size')?.value || '16';

      stats.innerHTML =
        'max_width: <strong>' + maxWidth + 'px</strong> | ' +
        'spacing_unit: <strong>' + spacing + 'px</strong> | ' +
        'base_font_size: <strong>' + fontSize + 'px</strong>';
    };

    const watched = [
      'max_width', 'container_padding', 'gutter_width', 'base_font_size', 'line_height_normal',
      'border_radius_md', 'card_border_radius', 'card_padding', 'button_border_radius',
      'button_padding_x', 'button_padding_y', 'spacing_unit', 'header_height', 'footer_height',
      'header_shadow',
    ];

    const refreshAll = () => {
      watched.forEach((key) => {
        if (key === 'header_shadow') {
          return;
        }
        applyKey(key);
      });
      applyShadow();
      refreshStats();
    };

    watched.forEach((key) => {
      const input = getInput(key);
      if (!input) {
        return;
      }
      input.addEventListener('input', refreshAll);
      input.addEventListener('change', refreshAll);
    });

    const openPreviewBtn = document.getElementById('open-layout-preview-tab');
    const settingsForm = document.getElementById('settings-form');

    const buildPreviewUrl = () => {
      if (!openPreviewBtn) {
        return null;
      }

      const baseUrl = openPreviewBtn.dataset.baseHref || openPreviewBtn.getAttribute('href');
      const url = new URL(baseUrl, window.location.origin);
      const formData = settingsForm ? new FormData(settingsForm) : null;

      if (!formData) {
        return url;
      }

      for (const [name, value] of formData.entries()) {
        if (!name.startsWith('layout[')) {
          continue;
        }
        url.searchParams.set(name, String(value));
      }

      return url;
    };

    const syncPreviewLink = () => {
      if (!openPreviewBtn) {
        return;
      }

      if (!openPreviewBtn.dataset.baseHref) {
        openPreviewBtn.dataset.baseHref = openPreviewBtn.getAttribute('href') || '';
      }

      const url = buildPreviewUrl();
      if (url) {
        openPreviewBtn.setAttribute('href', url.toString());
      }
    };

    if (openPreviewBtn) {
      openPreviewBtn.addEventListener('click', function () {
        syncPreviewLink();
      });
    }

    if (settingsForm) {
      settingsForm.addEventListener('input', syncPreviewLink);
      settingsForm.addEventListener('change', syncPreviewLink);
    }

    refreshAll();
    syncPreviewLink();
  })();
</script>

