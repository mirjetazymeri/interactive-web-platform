@php
  $editing = isset($page);

  $rawFooterSections = setting('footer.sections', "Company\nCustomer Care\nLegal\nSocial");
  if (is_array($rawFooterSections)) {
    $footerSections = collect($rawFooterSections)
      ->map(fn ($item) => trim((string) $item))
      ->filter()
      ->values();
  } else {
    $rawFooterSections = trim((string) $rawFooterSections);
    $decoded = $rawFooterSections !== '' ? json_decode($rawFooterSections, true) : null;

    if (is_array($decoded)) {
      $footerSections = collect($decoded)
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->values();
    } else {
      $footerSections = collect(preg_split('/\r\n|\r|\n|,/', $rawFooterSections ?: ''))
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->values();
    }
  }

  $selectedParentId = old('parent_id',
      $editing ? $page->parent_id : request('parent_id')
  );

  $selectedChannels = old('channels',
      $editing
        ? $page->channels->pluck('id')->toArray()
        : [auth()->user()->current_channel_id]
  );

  $configuredLocales = array_keys(config('locales', []));
  if (empty($configuredLocales)) {
      $configuredLocales = [config('app.locale')];
  }

  $existingLocales = $editing ? $page->translations->pluck('locale')->all() : [];
  $locales = array_values(array_unique(array_merge($configuredLocales, $existingLocales)));

  $localeName = fn($locale) => config("locales.$locale.name") ?? strtoupper($locale);

  $pageTree = $pageTree ?? \App\Models\Page::with('children')
      ->orderBy('title')
      ->get()
      ->whereNull('parent_id');

  // prevent selecting self/descendants
  $excludeIds = [];
  if ($editing) {
      $walk = function($node) use (&$walk, &$excludeIds) {
          $excludeIds[] = $node->id;
          foreach ($node->children as $ch) $walk($ch);
      };
      $walk($page->loadMissing('children'));
  }

  $renderOptions = function($nodes, $level = 0) use (&$renderOptions, $excludeIds, $selectedParentId) {
      foreach ($nodes as $n) {
          if (in_array($n->id, $excludeIds, true)) continue;

          echo '<option value="'.$n->id.'"'
            .($selectedParentId == $n->id ? ' selected' : '')
            .'>'
            .str_repeat('— ', $level)
            .e($n->title ?: $n->slug)
            .'</option>';

          if ($n->children->count()) {
              $renderOptions($n->children, $level + 1);
          }
      }
  };
@endphp

<ul class="nav nav-tabs mb-3" id="pageEditorTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="tab-content" data-bs-toggle="tab" data-bs-target="#pane-content" type="button" role="tab">
      {{ __('translations.admin.pages.tabs.content') }}
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="tab-navigation" data-bs-toggle="tab" data-bs-target="#pane-navigation" type="button" role="tab">
      {{ __('translations.admin.pages.tabs.navigation') }}
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="tab-seo" data-bs-toggle="tab" data-bs-target="#pane-seo" type="button" role="tab">
      {{ __('translations.admin.pages.tabs.seo') }}
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="tab-translations" data-bs-toggle="tab" data-bs-target="#pane-translations" type="button" role="tab">
      {{ __('translations.admin.pages.tabs.translations') }}
    </button>
  </li>
</ul>

<div class="tab-content" id="pageEditorTabContent">
  <div class="tab-pane fade show active" id="pane-content" role="tabpanel" aria-labelledby="tab-content">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">{{ __('translations.admin.pages.fields.title') }} *</label>
        <input type="text" name="title" class="form-control" required value="{{ old('title', $page->title ?? '') }}">
      </div>

      <div class="col-md-6">
        <label class="form-label">{{ __('translations.admin.pages.fields.slug') }}</label>
        <input type="text" name="slug" class="form-control" value="{{ old('slug', $page->slug ?? '') }}">
      </div>

      <div class="col-md-4">
        <label class="form-label">{{ __('translations.admin.pages.fields.parent_page') }}</label>
        <select name="parent_id" class="form-select">
          <option value="">{{ __('translations.admin.pages.fields.root') }}</option>
          @php $renderOptions($pageTree); @endphp
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">{{ __('translations.admin.pages.fields.status') }} *</label>
        <select name="status" class="form-select">
          @foreach(['draft','published','archived'] as $s)
            <option value="{{ $s }}" @selected(old('status',$page->status ?? 'draft') === $s)>
              {{ __('translations.admin.pages.statuses.' . $s) }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">{{ __('translations.admin.pages.fields.layout') }}</label>
        <input type="text" name="layout" class="form-control" value="{{ old('layout', $page->layout ?? '') }}">
      </div>

      <div class="col-md-3">
        <label class="form-label">{{ __('translations.admin.pages.fields.publish_at') }}</label>
        <input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', optional($page->published_at ?? null)->format('Y-m-d\TH:i')) }}">
      </div>

      <div class="col-md-6">
        <label class="form-label">{{ __('translations.admin.pages.fields.channels') }}</label>
        <select name="channels[]" class="form-select" multiple>
          @foreach($channels as $channel)
            <option value="{{ $channel->id }}" @selected(in_array($channel->id, $selectedChannels))>
              {{ $channel->name }}
            </option>
          @endforeach
        </select>
        <div class="form-text">{{ __('translations.admin.pages.hints.channels_fallback') }}</div>
      </div>

      <div class="col-12">
        <label class="form-label">{{ __('translations.admin.pages.fields.content') }}</label>
        <textarea name="content" rows="8" class="form-control">{{ old('content', $page->content ?? '') }}</textarea>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="pane-navigation" role="tabpanel" aria-labelledby="tab-navigation">
    <div class="row g-3">
      <div class="col-md-3">
        <div class="form-check form-switch mt-4">
          <input type="checkbox" name="show_in_header" value="1" class="form-check-input" @checked(old('show_in_header', $page->show_in_header ?? false))>
          <label class="form-check-label">{{ __('translations.admin.pages.fields.show_in_header') }}</label>
        </div>
      </div>

      <div class="col-md-3">
        <label class="form-label">{{ __('translations.admin.pages.fields.header_position') }}</label>
        <input type="number" name="header_position" class="form-control" value="{{ old('header_position', $page->header_position ?? '') }}">
      </div>

      <div class="col-md-3">
        <div class="form-check form-switch mt-4">
          <input type="checkbox" name="show_in_footer" value="1" class="form-check-input" @checked(old('show_in_footer', $page->show_in_footer ?? false))>
          <label class="form-check-label">{{ __('translations.admin.pages.fields.show_in_footer') }}</label>
        </div>
      </div>

      <div class="col-md-3">
        <label class="form-label">{{ __('translations.admin.pages.fields.footer_position') }}</label>
        <input type="number" name="footer_position" class="form-control" value="{{ old('footer_position', $page->footer_position ?? '') }}">
      </div>

      <div class="col-md-6">
        <label class="form-label">{{ __('translations.admin.pages.fields.footer_section') }}</label>
        <select name="footer_section" class="form-select">
          <option value="">{{ __('translations.admin.pages.fields.auto_unassigned') }}</option>
          @foreach($footerSections as $section)
            <option value="{{ $section }}" @selected(old('footer_section', $page->footer_section ?? '') === $section)>
              {{ $section }}
            </option>
          @endforeach
        </select>
        <div class="form-text">{{ __('translations.admin.pages.hints.footer_section') }}</div>
      </div>

      <div class="col-md-6">
        <label class="form-label">{{ __('translations.admin.pages.fields.navigation_label') }}</label>
        <input type="text" name="nav_label" class="form-control" value="{{ old('nav_label', $page->nav_label ?? '') }}">
      </div>

      <div class="col-md-3">
        <div class="form-check form-switch mt-4">
          <input type="checkbox" name="nav_new_tab" value="1" class="form-check-input" @checked(old('nav_new_tab', $page->nav_new_tab ?? false))>
          <label class="form-check-label">{{ __('translations.admin.pages.fields.open_in_new_tab') }}</label>
        </div>
      </div>
    </div>
  </div>

  @php
    $seoPreview = app(\App\Services\Seo\SeoService::class)->previewDefaults();
    $seoSite = $seoPreview['base'];
    $seoPath = $editing && $page->exists ? $page->path : '…';
    $seoDefaultLocale = $seoPreview['locale'];
    $seoUrl = $seoSite.'/'.$seoDefaultLocale.($seoPath === 'home' ? '' : '/'.$seoPath);
  @endphp
  <div class="tab-pane fade" id="pane-seo" role="tabpanel" aria-labelledby="tab-seo">
    <p class="text-muted small">{{ __('seo.page.intro') }}</p>
    <div class="row g-3">
      <div class="col-lg-7">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="seo-meta-title">{{ __('translations.admin.pages.fields.meta_title') }}</label>
            <input type="text" id="seo-meta-title" name="meta_title" class="form-control @error('meta_title') is-invalid @enderror" maxlength="255" data-seo-count="60" value="{{ old('meta_title', $page->meta_title ?? '') }}">
            @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">{{ __('seo.page.meta_title_help') }}</div>
          </div>
          <div class="col-12">
            <label class="form-label" for="seo-meta-description">{{ __('translations.admin.pages.fields.meta_description') }}</label>
            <textarea id="seo-meta-description" name="meta_description" rows="3" class="form-control @error('meta_description') is-invalid @enderror" maxlength="1000" data-seo-count="160">{{ old('meta_description', $page->meta_description ?? '') }}</textarea>
            @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">{{ __('seo.page.meta_description_help') }}</div>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        @include('admin.seo._preview', [
          'titleSource' => '[name=meta_title]', 'titleFallback' => '[name=title]',
          'descriptionSource' => '[name=meta_description]', 'url' => $seoUrl,
        ])
      </div>

      <div class="col-md-6">
        <label class="form-label" for="seo-og-title">{{ __('seo.page.og_title') }}</label>
        <input type="text" id="seo-og-title" name="og_title" class="form-control @error('og_title') is-invalid @enderror" maxlength="255" data-seo-count="70" value="{{ old('og_title', $page->og_title ?? '') }}">
        @error('og_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">{{ __('seo.page.og_title_help') }}</div>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="seo-og-description">{{ __('seo.page.og_description') }}</label>
        <textarea id="seo-og-description" name="og_description" rows="2" class="form-control @error('og_description') is-invalid @enderror" maxlength="1000" data-seo-count="200">{{ old('og_description', $page->og_description ?? '') }}</textarea>
        @error('og_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label" for="seo-og-image">{{ __('seo.page.og_image') }}</label>
        @if(!empty($page->og_image))
          <div class="d-flex align-items-center gap-2 mb-2">
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($page->og_image) }}" alt="" style="max-height:63px;max-width:120px" class="border rounded">
            <label class="form-check-label small"><input type="checkbox" class="form-check-input me-1" name="og_image_remove" value="1">{{ __('seo.page.og_image_remove') }}</label>
          </div>
        @endif
        <input type="file" id="seo-og-image" name="og_image_file" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control @error('og_image_file') is-invalid @enderror">
        @error('og_image_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">{{ __('seo.page.og_image_help') }}</div>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="seo-canonical">{{ __('seo.page.canonical_url') }}</label>
        <input type="text" id="seo-canonical" name="canonical_url" class="form-control @error('canonical_url') is-invalid @enderror" maxlength="2048" placeholder="/services" value="{{ old('canonical_url', $page->canonical_url ?? '') }}">
        @error('canonical_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">{{ __('seo.page.canonical_help') }}</div>
      </div>

      <div class="col-md-3">
        <input type="hidden" name="robots_index" value="0">
        <div class="form-check form-switch mt-2">
          <input type="checkbox" id="seo-robots-index" name="robots_index" value="1" class="form-check-input" @checked(old('robots_index', $page->robots_index ?? true))>
          <label class="form-check-label" for="seo-robots-index">{{ __('seo.page.robots_index') }}</label>
        </div>
        <div class="form-text">{{ __('seo.page.robots_index_help') }}</div>
      </div>
      <div class="col-md-3">
        <input type="hidden" name="robots_follow" value="0">
        <div class="form-check form-switch mt-2">
          <input type="checkbox" id="seo-robots-follow" name="robots_follow" value="1" class="form-check-input" @checked(old('robots_follow', $page->robots_follow ?? true))>
          <label class="form-check-label" for="seo-robots-follow">{{ __('seo.page.robots_follow') }}</label>
        </div>
        <div class="form-text">{{ __('seo.page.robots_follow_help') }}</div>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="seo-schema-type">{{ __('seo.page.schema_type') }}</label>
        <select id="seo-schema-type" name="schema_type" class="form-select @error('schema_type') is-invalid @enderror">
          <option value="">{{ __('seo.page.schema_auto') }}</option>
          @foreach(\App\Services\Seo\SeoService::SCHEMA_TYPES as $schemaType)
            <option value="{{ $schemaType }}" @selected(old('schema_type', $page->schema_type ?? '') === $schemaType)>{{ __('seo.schema_types.'.$schemaType) }}</option>
          @endforeach
        </select>
        @error('schema_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">{{ __('seo.page.schema_help') }}</div>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="pane-translations" role="tabpanel" aria-labelledby="tab-translations">
    <div class="alert alert-info py-2 px-3 small mb-3">
      {{ __('translations.admin.pages.hints.translations') }}
    </div>

    <ul class="nav nav-tabs mb-3" id="pageTranslationTabs" role="tablist">
      @foreach($locales as $index => $locale)
        <li class="nav-item" role="presentation">
          <button class="nav-link {{ $index === 0 ? 'active' : '' }}" id="translation-tab-{{ $locale }}" data-bs-toggle="tab" data-bs-target="#translation-pane-{{ $locale }}" type="button" role="tab">
            {{ $localeName($locale) }}
          </button>
        </li>
      @endforeach
    </ul>

    <div class="tab-content border rounded p-3" id="pageTranslationTabContent">
      @foreach($locales as $index => $locale)
        @php
          $translation = $editing ? $page->translations->firstWhere('locale', $locale) : null;
        @endphp

        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="translation-pane-{{ $locale }}" role="tabpanel" aria-labelledby="translation-tab-{{ $locale }}">
          <input type="hidden" name="translations[{{ $index }}][locale]" value="{{ $locale }}">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">{{ __('translations.admin.pages.fields.title_override') }}</label>
              <input type="text" name="translations[{{ $index }}][title]" class="form-control" value="{{ old("translations.$index.title", $translation->title ?? '') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">{{ __('translations.admin.pages.fields.navigation_label') }}</label>
              <input type="text" name="translations[{{ $index }}][nav_label]" class="form-control" value="{{ old("translations.$index.nav_label", $translation->nav_label ?? '') }}">
            </div>

            <div class="col-12">
              <label class="form-label">{{ __('translations.admin.pages.fields.content_override') }}</label>
              <textarea name="translations[{{ $index }}][content]" rows="4" class="form-control">{{ old("translations.$index.content", $translation->content ?? '') }}</textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label">{{ __('translations.admin.pages.fields.meta_title') }}</label>
              <input type="text" name="translations[{{ $index }}][meta_title]" class="form-control" maxlength="255" data-seo-count="60" value="{{ old("translations.$index.meta_title", $translation->meta_title ?? '') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">{{ __('translations.admin.pages.fields.meta_description') }}</label>
              <input type="text" name="translations[{{ $index }}][meta_description]" class="form-control" maxlength="1000" data-seo-count="160" value="{{ old("translations.$index.meta_description", $translation->meta_description ?? '') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">{{ __('seo.page.og_title') }}</label>
              <input type="text" name="translations[{{ $index }}][og_title]" class="form-control" maxlength="255" value="{{ old("translations.$index.og_title", $translation->og_title ?? '') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">{{ __('seo.page.og_description') }}</label>
              <input type="text" name="translations[{{ $index }}][og_description]" class="form-control" maxlength="1000" value="{{ old("translations.$index.og_description", $translation->og_description ?? '') }}">
            </div>

            <div class="col-12">
              @include('admin.seo._preview', [
                'scope' => '.tab-pane',
                'titleSource' => "[name=\"translations[$index][meta_title]\"]", 'titleFallback' => "[name=\"translations[$index][title]\"]",
                'descriptionSource' => "[name=\"translations[$index][meta_description]\"]",
                'url' => $seoSite.'/'.$locale.($seoPath === 'home' ? '' : '/'.$seoPath),
              ])
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>

</div>

@include('admin.seo._preview_script')
