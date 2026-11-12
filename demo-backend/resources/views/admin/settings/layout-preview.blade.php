@extends('layouts.master')

@section('content')
@php
  $layoutDefaults = config('settings.layout', []);
  $getLayout = function (string $key, $fallback = null) use ($settings, $layoutDefaults) {
      $default = $layoutDefaults[$key]['default'] ?? $fallback;
      return $settings["layout.$key"] ?? $default;
  };

  $preview = [
      'max_width'            => (int) $getLayout('max_width', 1200),
      'container_padding'    => (int) $getLayout('container_padding', 24),
      'gutter_width'         => (int) $getLayout('gutter_width', 24),
      'base_font_size'       => (int) $getLayout('base_font_size', 16),
      'line_height_normal'   => (float) $getLayout('line_height_normal', 1.5),
      'border_radius_md'     => (int) $getLayout('border_radius_md', 8),
      'card_border_radius'   => (int) $getLayout('card_border_radius', 12),
      'card_padding'         => (int) $getLayout('card_padding', 24),
      'button_border_radius' => (int) $getLayout('button_border_radius', 6),
      'button_padding_x'     => (int) $getLayout('button_padding_x', 20),
      'button_padding_y'     => (int) $getLayout('button_padding_y', 12),
      'spacing_unit'         => (int) $getLayout('spacing_unit', 8),
      'header_height'        => (int) $getLayout('header_height', 80),
      'footer_height'        => (int) $getLayout('footer_height', 100),
      'header_shadow'        => (string) $getLayout('header_shadow', 'light'),
  ];

  $shadowMap = [
      'none' => 'none',
      'light' => '0 6px 16px rgba(15, 23, 42, .08)',
      'medium' => '0 10px 28px rgba(15, 23, 42, .14)',
      'heavy' => '0 18px 40px rgba(15, 23, 42, .2)',
  ];

  $headerShadow = $shadowMap[$preview['header_shadow']] ?? $shadowMap['light'];
@endphp

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h1 class="page-title fw-medium fs-18 mb-0">Layout Preview</h1>
    <p class="text-muted small mb-0">Full-page dummy preview based on current layout values.</p>
  </div>
  <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-primary">
    <i class="ri-arrow-left-line me-1"></i>Back To Settings
  </a>
</div>

<div class="card custom-card">
  <div class="card-body p-3 p-lg-4">
    <div id="layout-full-preview-canvas"
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
            <p class="mb-0 text-muted">This full preview uses dummy content to show your layout scale, spacing, and component rhythm.</p>
          </section>

          <div class="lp-grid mb-3">
            <article class="lp-card">
              <h6 class="mb-1">Card One</h6>
              <p class="mb-2 text-muted">Card radius and padding are using your layout settings.</p>
              <button type="button" class="btn btn-primary btn-sm lp-btn">Primary Action</button>
            </article>
            <article class="lp-card">
              <h6 class="mb-1">Card Two</h6>
              <p class="mb-2 text-muted">Grid gutter, typography, and button rhythm are reflected here.</p>
              <button type="button" class="btn btn-outline-primary btn-sm lp-btn">Secondary Action</button>
            </article>
          </div>

          <div class="lp-card">
            <h6 class="mb-1">Long Content Block</h6>
            <p class="mb-0 text-muted">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque sed sem at est efficitur porta. Integer fringilla nisl in ligula faucibus, vel volutpat nisl volutpat.</p>
          </div>
        </main>

        <footer class="lp-footer d-flex align-items-center justify-content-center text-muted">
          Footer Area
        </footer>
      </div>
    </div>

    <div class="small text-muted mt-3">
      max_width: <strong>{{ $preview['max_width'] }}px</strong> |
      spacing_unit: <strong>{{ $preview['spacing_unit'] }}px</strong> |
      base_font_size: <strong>{{ $preview['base_font_size'] }}px</strong>
    </div>
  </div>
</div>
@endsection

@section('styles')
<style>
  #layout-full-preview-canvas {
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
  }

  #layout-full-preview-canvas .lp-shell {
    max-width: var(--lp-max-width);
    font-size: var(--lp-font-size);
    line-height: var(--lp-line-height);
    padding: var(--lp-container-padding);
  }

  #layout-full-preview-canvas .lp-header {
    height: var(--lp-header-height);
    border-radius: var(--lp-radius);
    background: #ffffff;
    padding: 0 calc(var(--lp-spacing) * 1.5);
    box-shadow: var(--lp-header-shadow);
    border: 1px solid #e2e8f0;
    margin-bottom: calc(var(--lp-spacing) * 2);
  }

  #layout-full-preview-canvas .lp-main {
    margin-bottom: calc(var(--lp-spacing) * 2);
  }

  #layout-full-preview-canvas .lp-hero {
    background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%);
    border: 1px solid #e2e8f0;
    border-radius: var(--lp-radius);
    padding: calc(var(--lp-spacing) * 2);
  }

  #layout-full-preview-canvas .lp-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--lp-gutter-width);
  }

  #layout-full-preview-canvas .lp-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: var(--lp-card-radius);
    padding: var(--lp-card-padding);
  }

  #layout-full-preview-canvas .lp-btn {
    border-radius: var(--lp-btn-radius);
    padding: var(--lp-btn-py) var(--lp-btn-px);
    line-height: 1.2;
  }

  #layout-full-preview-canvas .lp-footer {
    height: var(--lp-footer-height);
    border-radius: var(--lp-radius);
    border: 1px dashed #cbd5e1;
    background: #f8fafc;
  }

  @media (max-width: 768px) {
    #layout-full-preview-canvas .lp-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
@endsection
