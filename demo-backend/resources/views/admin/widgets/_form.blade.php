@php
  $editing = isset($widget);
  $widgetRef = $widget ?? null;
  $widgetChannels = $editing && $widgetRef ? $widgetRef->channels->pluck('id')->toArray() : [];
  $widgetTranslations = $editing && $widgetRef ? $widgetRef->translations : collect();
  $widgetType = $editing && $widgetRef ? $widgetRef->type : 'carousel';
  $widgetConfig = $editing && $widgetRef ? ($widgetRef->config ?? []) : [];

  $val = fn($k, $d = null) => old($k, $editing ? data_get($widgetRef, $k) : $d);
  $type = old('type', $widgetType);
  $cfg  = old('config', $widgetConfig);

  $selectedChannels = old('channels',
      $editing
      ? $widgetChannels
      : [auth()->user()->current_channel_id]
  );

  $configuredLocales = array_keys(config('locales', []));
  if (empty($configuredLocales)) {
      $configuredLocales = [config('app.locale')];
  }

  $existingLocales = $editing ? $widgetTranslations->pluck('locale')->all() : [];
  $locales = array_values(array_unique(array_merge($configuredLocales, $existingLocales)));
  $localeName = fn($locale) => config("locales.$locale.name") ?? strtoupper($locale);
  $baseSlides = $cfg['slides'] ?? [];
    $normalizeMediaPath = function (?string $path): string {
      if (empty($path)) {
        return '';
      }

      $raw = trim((string) $path);

      if (filter_var($raw, FILTER_VALIDATE_URL)) {
        $raw = (string) (parse_url($raw, PHP_URL_PATH) ?? '');
      }

      $normalized = ltrim($raw, '/');

      if (\Illuminate\Support\Str::startsWith($normalized, 'storage/')) {
        $normalized = substr($normalized, strlen('storage/'));
      }

      return $normalized;
    };

    $mediaUrl = function (?string $path) use ($normalizeMediaPath): string {
      if (empty($path)) {
        return '';
      }

      $normalized = $normalizeMediaPath($path);
      if ($normalized === '') {
        return '';
      }

      return \Illuminate\Support\Facades\Storage::url($normalized);
    };

    $mediaExists = function (?string $path) use ($normalizeMediaPath): bool {
      $normalized = $normalizeMediaPath($path);
      return $normalized !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($normalized);
    };

  $types = $types ?? [];
  $allowedTypes = ['carousel', 'video', 'html', 'hero', 'separator', 'contact_form', 'image_gallery', 'map', 'features', 'faq', 'testimonials', 'pricing', 'service_x', 'projects', 'hero-world', 'meeting_booking', 'footer_cta'];
  $types = array_filter(
      $types,
      fn ($label, $key) => in_array($key, $allowedTypes, true),
      ARRAY_FILTER_USE_BOTH
  );
  if (!array_key_exists($type, $types)) {
      $type = 'carousel';
  }
  $layouts = $layouts ?? [];

  $typeMetaMap = [
      'footer_cta' => ['icon' => 'ri-megaphone-line', 'color' => 'warning', 'desc' => __('footer_cta.description'), 'label' => __('footer_cta.name')],
      'meeting_booking' => ['icon' => 'ri-calendar-event-line', 'color' => 'info', 'desc' => __('meetings.widget_description'), 'label' => __('meetings.widget_name')],
      'hero-world' => ['icon' => 'ri-earth-line', 'color' => 'success', 'desc' => __('hero_world.widget_description'), 'label' => __('hero_world.name')],
      'projects' => ['icon' => 'ri-briefcase-line', 'color' => 'primary', 'desc' => __('projects.widget_description')],
      'service_x' => ['icon' => 'ri-stack-line', 'color' => 'primary', 'desc' => 'Shared services with a separate presentation on each page'],
      'carousel'  => ['icon' => 'ri-image-2-line',      'color' => 'primary',   'desc' => 'Image slideshow with headlines & CTAs'],
      'video'     => ['icon' => 'ri-video-line',         'color' => 'purple',    'desc' => 'Embed a YouTube or Vimeo video'],
      'html'      => ['icon' => 'ri-code-s-slash-line',  'color' => 'warning',   'desc' => 'Custom HTML / CSS / JS block'],
      'hero'      => ['icon' => 'ri-layout-top-line',    'color' => 'success',   'desc' => 'Full-width hero banner with CTA'],
      'separator'    => ['icon' => 'ri-separator',          'color' => 'info',      'desc' => 'Visual divider between content sections'],
      'contact_form' => ['icon' => 'ri-mail-send-line',     'color' => 'danger',    'desc' => 'Embeddable contact form (name, email, subject, message)'],
      'image_gallery' => ['icon' => 'ri-gallery-line',      'color' => 'teal',      'desc' => 'Responsive image grid with lightbox and captions'],
      'map'          => ['icon' => 'ri-map-pin-2-line',     'color' => 'danger',    'desc' => 'Coordinates map with embed URL fallback'],
        'features'     => ['icon' => 'ri-apps-2-line',        'color' => 'primary',   'desc' => 'Feature cards with icon, title, and description'],
        'faq'          => ['icon' => 'ri-question-answer-line','color' => 'warning',   'desc' => 'Frequently asked questions section'],
        'testimonials' => ['icon' => 'ri-chat-quote-line',    'color' => 'success',   'desc' => 'Customer quotes with author and rating'],
          'pricing'      => ['icon' => 'ri-price-tag-3-line',   'color' => 'info',      'desc' => 'Pricing plans with CTA and feature list'],
  ];
@endphp

{{-- ══════════════════════════════════════════════════
     SECTION 1 — Basic Info
     ══════════════════════════════════════════════════ --}}
<div class="section-label d-flex align-items-center gap-2 mb-3">
  <span class="badge bg-primary rounded-pill">1</span>
  <span class="fw-semibold">Basic Info</span>
</div>

<div class="row g-3 mb-4">

  {{-- Title --}}
  <div class="col-md-5">
    <label class="form-label">Title</label>
    <input name="title" class="form-control @error('title') is-invalid @enderror"
           value="{{ $val('title') }}" placeholder="E.g. Homepage Hero Carousel">
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  {{-- Status --}}
  <div class="col-md-3">
    <label class="form-label">Status <span class="text-danger">*</span></label>
    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
      @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $s => $sl)
        <option value="{{ $s }}" @selected($val('status','active') === $s)>{{ $sl }}</option>
      @endforeach
    </select>
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  {{-- Channels --}}
  <div class="col-md-4">
    <label class="form-label">Channels</label>
    <select name="channels[]" class="form-select select2" multiple>
      @foreach($channels as $channel)
        <option value="{{ $channel->id }}" @selected(in_array($channel->id, $selectedChannels))>
          {{ $channel->name }}
        </option>
      @endforeach
    </select>
    <div class="form-text">Empty = visible on all channels</div>
  </div>

</div>

{{-- ══════════════════════════════════════════════════
     SECTION 2 — Type Selector (Create Only)
     ══════════════════════════════════════════════════ --}}
@if (!$editing)
  <div class="section-label d-flex align-items-center gap-2 mb-3">
    <span class="badge bg-primary rounded-pill">2</span>
    <span class="fw-semibold">Widget Type <span class="text-danger">*</span></span>
  </div>

  <div class="row g-3 mb-4" id="typeCards">
    @foreach($allowedTypes as $tk)
      @php $tm = $typeMetaMap[$tk] ?? ['icon'=>'ri-layout-line','color'=>'secondary','desc'=>'']; @endphp
      <div class="col-md-4">
        <div class="type-card card border-2 cursor-pointer h-100 {{ $type === $tk ? 'type-card--active border-'.$tm['color'] : 'border-transparent' }}"
             data-type="{{ $tk }}" role="button" tabindex="0"
             style="transition: border-color .2s, box-shadow .2s;">
          <div class="card-body d-flex align-items-start gap-3 py-3 px-3">
            <div class="avatar avatar-md rounded bg-{{ $tm['color'] }}-transparent flex-shrink-0">
              <i class="{{ $tm['icon'] }} fs-20 text-{{ $tm['color'] }}"></i>
            </div>
            <div>
              <div class="fw-semibold text-capitalize">{{ $tm['label'] ?? $tk }}</div>
              <div class="text-muted fs-12">{{ $tm['desc'] }}</div>
            </div>
            <i class="ri-checkbox-circle-fill ms-auto text-{{ $tm['color'] }} type-check {{ $type === $tk ? '' : 'invisible' }}" style="font-size:1.1rem"></i>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  {{-- Type Error Display --}}
  @error('type')
    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
      <i class="ri-error-warning-line me-2"></i>
      {{ $message }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @enderror
@else
  {{-- Editing: Show current type as read-only info --}}
  <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
    <i class="ri-information-line fs-18"></i>
    <div>
      <strong>Widget Type:</strong> <span class="text-capitalize">{{ $typeMetaMap[$type]['label'] ?? $type }}</span>
      <div class="fs-12 text-muted mt-1">Cannot change type after creation. To change type, delete and create a new widget.</div>
    </div>
  </div>
@endif

{{-- Hidden real input (always present) --}}
<input type="hidden" id="widgetType" name="type" value="{{ $type }}" required>

{{-- ══════════════════════════════════════════════════
     SECTION 3 — Display Settings
     ══════════════════════════════════════════════════ --}}
<div class="section-label d-flex align-items-center gap-2 mb-3">
  <span class="badge bg-primary rounded-pill">3</span>
  <span class="fw-semibold">Display Settings</span>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3 {{ $type === 'carousel' ? '' : 'd-none' }}" id="widgetLayoutField">
    <label class="form-label">Layout</label>
    <select name="config[layout]" class="form-select" id="widgetLayoutSelect">
      @foreach($layouts as $lk => $ll)
        <option value="{{ $lk }}" @selected(($cfg['layout'] ?? '1x1') === $lk)>{{ $ll }}</option>
      @endforeach
    </select>
    <div class="form-text">Used for carousel variants only.</div>
  </div>

  <div class="col-md-5">
    <label class="form-label">Section Label <small class="text-muted">(aria-label / SEO)</small></label>
    <input name="config[section_label]" class="form-control"
           value="{{ $cfg['section_label'] ?? '' }}" placeholder="e.g. Homepage Hero, Featured Content">
  </div>

  <div class="col-md-4 d-flex align-items-end gap-4 pb-1">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="showTitle" name="config[show_title]" value="1"
             @checked($cfg['show_title'] ?? true)>
      <label class="form-check-label" for="showTitle">Show Title</label>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════
     SECTION 4 — Type Config
     ══════════════════════════════════════════════════ --}}
<div class="section-label d-flex align-items-center gap-2 mb-3">
  <span class="badge bg-primary rounded-pill">4</span>
  <span class="fw-semibold">Type Configuration</span>
</div>

@include('admin.widgets.partials.carousel')
@include('admin.widgets.partials.video')
@include('admin.widgets.partials.html')
@include('admin.widgets.partials.hero')
@include('admin.widgets.partials.separator')
@include('admin.widgets.partials.contact_form')
@include('admin.widgets.partials.image_gallery')
@include('admin.widgets.partials.map')
@include('admin.widgets.partials.features')
@include('admin.widgets.partials.faq')
@include('admin.widgets.partials.testimonials')
@include('admin.widgets.partials.pricing')
@include('admin.widgets.partials.service_x')
@include('admin.widgets.partials.projects')
@include('admin.widgets.partials.hero_world')
@include('admin.widgets.partials.meeting_booking')
@include('admin.widgets.partials.footer_cta')

{{-- ══════════════════════════════════════════════════
     SECTION 5 — Translations
     ══════════════════════════════════════════════════ --}}
<div class="section-label d-flex align-items-center gap-2 mt-4 mb-3">
  <span class="badge bg-primary rounded-pill">5</span>
  @if($editing)
    <span class="fw-semibold">Translations</span>
    <span class="badge bg-secondary-transparent text-secondary ms-1">{{ count($locales) }} locale{{ count($locales) !== 1 ? 's' : '' }}</span>
  @else
    <span class="fw-semibold">Translations (Apply to All Locales)</span>
    <span class="badge bg-info-transparent text-info ms-1">Unified Input</span>
  @endif
</div>

@if($editing)
  <ul class="nav nav-tabs mb-0 border-bottom-0" id="widgetTranslationTabs" role="tablist">
    @foreach($locales as $index => $locale)
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $index === 0 ? 'active' : '' }}"
                id="widget-translation-tab-{{ $locale }}"
                data-bs-toggle="tab"
                data-bs-target="#widget-translation-pane-{{ $locale }}"
                type="button" role="tab">
          {{ $localeName($locale) }}
        </button>
      </li>
    @endforeach
  </ul>

  <div class="tab-content border border-top-0 rounded-bottom p-4 mb-2">
    @foreach($locales as $index => $locale)
      @php
        $translation = $widgetTranslations->firstWhere('locale', $locale);
        $translationConfig = (array) ($translation->config ?? []);
      @endphp
      <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
           id="widget-translation-pane-{{ $locale }}"
           role="tabpanel">

        <input type="hidden" name="translations[{{ $index }}][locale]" value="{{ $locale }}">

        <div class="row g-3">
          @include('admin.widgets.partials.service_x_translations')
          @include('admin.widgets.partials.projects_translations')
          @include('admin.widgets.partials.hero_world_translations')
          @include('admin.widgets.partials.meeting_booking_translations')
          @include('admin.widgets.partials.footer_cta_translations')
          <div class="col-md-6">
            <label class="form-label">{{ $type === 'projects' ? __('projects.localized_title') : 'Localized Title' }}</label>
            <input name="translations[{{ $index }}][title]" class="form-control"
                   value="{{ old("translations.$index.title", $translation->title ?? '') }}"
                   placeholder="Title in {{ $localeName($locale) }}">
          </div>

          <div class="col-md-6">
            <label class="form-label">{{ $type === 'projects' ? __('projects.localized_section_label') : 'Localized Section Label' }}</label>
            <input name="translations[{{ $index }}][section_label]" class="form-control"
                   value="{{ old("translations.$index.section_label", $translationConfig['section_label'] ?? '') }}"
                   placeholder="Section label in {{ $localeName($locale) }}">
          </div>

          <div class="col-12 widget-translation-type {{ $type === 'carousel' ? '' : 'd-none' }}"
               data-widget-translation-type="carousel">
            <div class="border rounded p-3 bg-light-subtle"
                 data-carousel-translation-container
                 data-locale-index="{{ $index }}">
              <div class="fw-semibold mb-3 fs-13 text-muted text-uppercase tracking-wide">
                <i class="ri-translate-2 me-1"></i> Localized Carousel Slides
              </div>
              @forelse($baseSlides as $slideIndex => $slide)
                @php $translatedSlide = $translationConfig['slides'][$slideIndex] ?? []; @endphp
                <div class="card mb-3 carousel-translation-slide-card border-0 shadow-sm" data-slide-index="{{ $slideIndex }}">
                  <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="badge bg-secondary-transparent text-secondary fw-semibold">Slide {{ $slideIndex + 1 }}</span>
                      <small class="text-muted"><i class="ri-information-line me-1"></i>Text only, image and style stay in main config.</small>
                    </div>
                    <div class="row g-2">
                      <div class="col-md-6">
                        <input type="text" name="translations[{{ $index }}][slides][{{ $slideIndex }}][alt]" class="form-control form-control-sm"
                               value="{{ old("translations.$index.slides.$slideIndex.alt", $translatedSlide['alt'] ?? '') }}"
                               placeholder="Alt text">
                      </div>
                      <div class="col-md-6">
                        <input type="text" name="translations[{{ $index }}][slides][{{ $slideIndex }}][headline]" class="form-control form-control-sm"
                               value="{{ old("translations.$index.slides.$slideIndex.headline", $translatedSlide['headline'] ?? '') }}"
                               placeholder="Headline">
                      </div>
                      <div class="col-md-6">
                        <input type="text" name="translations[{{ $index }}][slides][{{ $slideIndex }}][subtitle]" class="form-control form-control-sm"
                               value="{{ old("translations.$index.slides.$slideIndex.subtitle", $translatedSlide['subtitle'] ?? '') }}"
                               placeholder="Subtitle">
                      </div>
                      <div class="col-md-3">
                        <input type="text" name="translations[{{ $index }}][slides][{{ $slideIndex }}][cta_text]" class="form-control form-control-sm"
                               value="{{ old("translations.$index.slides.$slideIndex.cta_text", $translatedSlide['cta_text'] ?? '') }}"
                               placeholder="CTA text">
                      </div>
                      <div class="col-md-3">
                        <input type="text" name="translations[{{ $index }}][slides][{{ $slideIndex }}][cta_link]" class="form-control form-control-sm"
                               value="{{ old("translations.$index.slides.$slideIndex.cta_link", $translatedSlide['cta_link'] ?? '') }}"
                               placeholder="CTA link">
                      </div>
                    </div>
                  </div>
                </div>
              @empty
                <div class="text-muted small carousel-translation-empty">
                  <i class="ri-add-circle-line me-1"></i> Add carousel slides in the main config first, then translate them here.
                </div>
              @endforelse
            </div>
          </div>

          <div class="col-12 widget-translation-type {{ $type === 'hero' ? '' : 'd-none' }}"
               data-widget-translation-type="hero">
            <div class="border rounded p-3 bg-light-subtle">
              <div class="fw-semibold mb-3 fs-13 text-muted text-uppercase">
                <i class="ri-layout-top-line me-1"></i> Localized Hero Content
              </div>
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label fs-12 text-muted">Headline</label>
                  <input name="translations[{{ $index }}][headline]" class="form-control form-control-sm"
                         value="{{ old('translations.'.$index.'.headline', $translationConfig['headline'] ?? '') }}"
                         placeholder="Headline in {{ $localeName($locale) }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label fs-12 text-muted">Subtitle</label>
                  <input name="translations[{{ $index }}][subtitle]" class="form-control form-control-sm"
                         value="{{ old('translations.'.$index.'.subtitle', $translationConfig['subtitle'] ?? '') }}"
                         placeholder="Subtitle in {{ $localeName($locale) }}">
                </div>
                <div class="col-md-4">
                  <label class="form-label fs-12 text-muted">CTA Text</label>
                  <input name="translations[{{ $index }}][cta_text]" class="form-control form-control-sm"
                         value="{{ old('translations.'.$index.'.cta_text', $translationConfig['cta_text'] ?? '') }}"
                         placeholder="Button label">
                </div>
                <div class="col-md-8">
                  <label class="form-label fs-12 text-muted">CTA Link</label>
                  <input name="translations[{{ $index }}][cta_link]" class="form-control form-control-sm"
                         value="{{ old('translations.'.$index.'.cta_link', $translationConfig['cta_link'] ?? '') }}"
                         placeholder="https://...">
                </div>
                <div class="col-12">
                  <label class="form-label fs-12 text-muted">Globe labels <span class="text-lowercase">(one per line; empty uses the default list)</span></label>
                  <textarea name="translations[{{ $index }}][rotating_labels]" class="form-control form-control-sm" rows="3" maxlength="2000"
                            placeholder="Globe labels in {{ $localeName($locale) }}">{{ old('translations.'.$index.'.rotating_labels', implode("\n", (array) ($translationConfig['rotating_labels'] ?? []))) }}</textarea>
                </div>
              </div>
            </div>
          </div>

          {{-- Contact form texts; the "Localized Title" above is the form heading. Without these
               fields a save rebuilt the translation and dropped them. --}}
          <div class="col-12 widget-translation-type {{ $type === 'contact_form' ? '' : 'd-none' }}"
               data-widget-translation-type="contact_form">
            <div class="border rounded p-3 bg-light-subtle">
              <div class="fw-semibold mb-3 fs-13 text-muted text-uppercase">
                <i class="ri-mail-send-line me-1"></i> Localized Contact Form Texts
              </div>
              <div class="row g-2">
                <div class="col-12">
                  <label class="form-label fs-12 text-muted">Subtitle</label>
                  <input name="translations[{{ $index }}][subtitle]" class="form-control form-control-sm" maxlength="500"
                         value="{{ old('translations.'.$index.'.subtitle', $translationConfig['subtitle'] ?? '') }}"
                         placeholder="Subtitle in {{ $localeName($locale) }}">
                </div>
                <div class="col-md-4">
                  <label class="form-label fs-12 text-muted">Submit button</label>
                  <input name="translations[{{ $index }}][submit_label]" class="form-control form-control-sm" maxlength="100"
                         value="{{ old('translations.'.$index.'.submit_label', $translationConfig['submit_label'] ?? '') }}"
                         placeholder="Button label">
                </div>
                <div class="col-md-8">
                  <label class="form-label fs-12 text-muted">Success message</label>
                  <input name="translations[{{ $index }}][success_message]" class="form-control form-control-sm" maxlength="500"
                         value="{{ old('translations.'.$index.'.success_message', $translationConfig['success_message'] ?? '') }}"
                         placeholder="Message after sending">
                </div>
                <div class="col-md-6">
                  <label class="form-label fs-12 text-muted">Fixed subject (optional)</label>
                  <input name="translations[{{ $index }}][fixed_subject]" class="form-control form-control-sm" maxlength="255"
                         value="{{ old('translations.'.$index.'.fixed_subject', $translationConfig['fixed_subject'] ?? '') }}">
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 widget-translation-type {{ $type === 'video' ? '' : 'd-none' }}"
               data-widget-translation-type="video">
            <label class="form-label">Localized Video URL</label>
            <input name="translations[{{ $index }}][video_url]" class="form-control"
                   value="{{ old("translations.$index.video_url", $translationConfig['video_url'] ?? '') }}"
                   placeholder="https://youtube.com/watch?v=...">
          </div>

          <div class="col-12 widget-translation-type {{ $type === 'html' ? '' : 'd-none' }}"
               data-widget-translation-type="html">
            <label class="form-label">Localized HTML</label>
            <textarea name="translations[{{ $index }}][html]" rows="5" class="form-control font-monospace">{{ old("translations.$index.html", $translationConfig['html'] ?? '') }}</textarea>
          </div>
          <div class="col-md-6 widget-translation-type {{ $type === 'html' ? '' : 'd-none' }}"
               data-widget-translation-type="html">
            <label class="form-label">Localized CSS</label>
            <textarea name="translations[{{ $index }}][css]" rows="4" class="form-control font-monospace">{{ old("translations.$index.css", $translationConfig['css'] ?? '') }}</textarea>
          </div>
          <div class="col-md-6 widget-translation-type {{ $type === 'html' ? '' : 'd-none' }}"
               data-widget-translation-type="html">
            <label class="form-label">Localized JS</label>
            <textarea name="translations[{{ $index }}][js]" rows="4" class="form-control font-monospace">{{ old("translations.$index.js", $translationConfig['js'] ?? '') }}</textarea>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@else
  <div class="border rounded p-4 mb-2" id="createUnifiedTranslation" data-locales='@json($locales)'>
    <div class="alert alert-info d-flex align-items-start gap-2 py-2 px-3 mb-3">
      <i class="ri-information-line mt-1"></i>
      <div class="fs-13 mb-0">
        Enter values once. They will be copied to all locales when you create the widget.
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Title (all locales)</label>
        <input name="unified_translation[title]" class="form-control"
               value="{{ old('unified_translation.title', old('translations.0.title', '')) }}"
               placeholder="Unified title">
      </div>

      <div class="col-md-6">
        <label class="form-label">Section Label (all locales)</label>
        <input name="unified_translation[section_label]" class="form-control"
               value="{{ old('unified_translation.section_label', old('translations.0.section_label', '')) }}"
               placeholder="Unified section label">
      </div>

      <div class="col-12">
        <div class="alert alert-light border mb-0 fs-13">
          <i class="ri-layout-grid-line me-1 text-muted"></i>
          Type-specific content is configured in <strong>Section 4</strong> and will be applied to all locales automatically on create.
        </div>
      </div>
    </div>
  </div>
@endif

{{-- Styles + Scripts --}}
@include('admin.widgets.partials._scripts')
