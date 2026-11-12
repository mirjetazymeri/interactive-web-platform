{{-- Widget form: CDN libraries + type-card selector, panel sync, form submit, overlay opacity --}}

@section('scripts')
@parent
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script>
(function () {
  'use strict';

  // ── 1. Select2 ────────────────────────────────────────────────────
  if (typeof $.fn !== 'undefined' && $.fn.select2) {
    $('.select2').select2({ width: '100%', placeholder: 'Select channels' });
  }

  // ── 2. Visual type-card selector ─────────────────────────────────
  var typeInput = document.getElementById('widgetType');
  if (!typeInput) return;

  var typeColorMap = {
    carousel: 'primary',
    video: 'purple',
    html: 'warning',
    hero: 'success',
    separator: 'info',
    contact_form: 'danger',
    image_gallery: 'teal',
    map: 'danger',
    features: 'primary',
    faq: 'warning',
    testimonials: 'success',
    pricing: 'info',
    'hero-world': 'success',
    meeting_booking: 'info',
    footer_cta: 'warning'
  };

  function selectTypeCard(value) {
    document.querySelectorAll('.type-card').forEach(function (card) {
      var active = card.dataset.type === value;
      card.classList.toggle('type-card--active', active);
      var color = typeColorMap[card.dataset.type] || 'secondary';
      card.classList.toggle('border-' + color, active);
      var check = card.querySelector('.type-check');
      if (check) check.classList.toggle('invisible', !active);
    });
    typeInput.value = value;
    syncTypePanels(value);
  }

  document.querySelectorAll('.type-card').forEach(function (card) {
    card.addEventListener('click', function () { selectTypeCard(card.dataset.type); });
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); selectTypeCard(card.dataset.type); }
    });
  });

  // ── 3. Type panel sync ───────────────────────────────────────────
  // Fields of hidden panels must not take part in validation: types share config
  // keys (e.g. a Hero World "/contact" pre-fills the Hero panel's URL input), and an
  // invalid hidden field would silently block the browser's submit. Only fields this
  // code disabled are re-enabled.
  function setPanelFields(panel, enabled) {
    panel.querySelectorAll('input, select, textarea, button').forEach(function (field) {
      if (!enabled && !field.disabled) { field.disabled = true; field.setAttribute('data-panel-disabled', '1'); }
      if (enabled && field.hasAttribute('data-panel-disabled')) { field.disabled = false; field.removeAttribute('data-panel-disabled'); }
    });
  }

  function syncTypePanels(type) {
    document.querySelectorAll('.cfg, .widget-translation-type').forEach(function (el) {
      el.classList.add('d-none');
      setPanelFields(el, false);
    });

    var layoutField = document.getElementById('widgetLayoutField');
    var layoutSelect = document.getElementById('widgetLayoutSelect');
    var layoutEnabled = type === 'carousel';
    if (layoutField) {
      layoutField.classList.toggle('d-none', !layoutEnabled);
    }
    if (layoutSelect && !layoutEnabled) {
      layoutSelect.value = '1x1';
    }

    var panel = document.getElementById('cfg-' + type);
    if (panel) { panel.classList.remove('d-none'); setPanelFields(panel, true); }
    document.querySelectorAll('[data-widget-translation-type="' + type + '"]').forEach(function (el) {
      el.classList.remove('d-none');
      setPanelFields(el, true);
    });
  }

  selectTypeCard(typeInput.value || 'carousel');

  // ── 4. Overlay opacity live display ─────────────────────────────
  var opacityInput   = document.getElementById('overlayOpacityInput');
  var opacityDisplay = document.getElementById('overlayOpacityDisplay');
  if (opacityInput && opacityDisplay) {
    opacityInput.addEventListener('input', function () {
      opacityDisplay.textContent = opacityInput.value + '%';
    });
  }

  // ── 5. Form submit: fan-out unified create translations + disable hidden panels ──
  var form              = document.getElementById('widgetForm');
  var unifiedCreateRoot = document.getElementById('createUnifiedTranslation');

  function appendGeneratedInput(formEl, name, value) {
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value == null ? '' : String(value);
    input.setAttribute('data-generated-unified-translation', '1');
    formEl.appendChild(input);
  }

  function fanOutUnifiedCreateTranslations(formEl) {
    if (!unifiedCreateRoot) return;

    formEl.querySelectorAll('[data-generated-unified-translation="1"]').forEach(function (el) {
      el.remove();
    });

    var locales = [];
    try {
      locales = JSON.parse(unifiedCreateRoot.getAttribute('data-locales') || '[]');
    } catch (e) {
      locales = [];
    }

    if (!Array.isArray(locales) || locales.length === 0) return;

    function getUnifiedValue(selector) {
      var el = unifiedCreateRoot.querySelector(selector);
      return el ? el.value : '';
    }

    var title        = getUnifiedValue('[name="unified_translation[title]"]');
    var sectionLabel = getUnifiedValue('[name="unified_translation[section_label]"]');
    var activeType   = typeInput.value;

    locales.forEach(function (locale, localeIndex) {
      appendGeneratedInput(formEl, 'translations[' + localeIndex + '][locale]', locale);
      appendGeneratedInput(formEl, 'translations[' + localeIndex + '][title]', title);
      appendGeneratedInput(formEl, 'translations[' + localeIndex + '][section_label]', sectionLabel);

      if (activeType === 'video') {
        var videoUrlInput = formEl.querySelector('[name="config[video_url]"]');
        appendGeneratedInput(formEl, 'translations[' + localeIndex + '][video_url]', videoUrlInput ? videoUrlInput.value : '');
      }

      if (activeType === 'html') {
        var htmlInput = formEl.querySelector('[name="config[html]"]');
        var cssInput  = formEl.querySelector('[name="config[css]"]');
        var jsInput   = formEl.querySelector('[name="config[js]"]');
        appendGeneratedInput(formEl, 'translations[' + localeIndex + '][html]', htmlInput ? htmlInput.value : '');
        appendGeneratedInput(formEl, 'translations[' + localeIndex + '][css]',  cssInput  ? cssInput.value  : '');
        appendGeneratedInput(formEl, 'translations[' + localeIndex + '][js]',   jsInput   ? jsInput.value   : '');
      }

      if (activeType === 'hero') {
        var heroHeadline = formEl.querySelector('[name="config[headline]"]');
        var heroSubtitle = formEl.querySelector('[name="config[subtitle]"]');
        var heroCtaText  = formEl.querySelector('[name="config[cta_text]"]');
        var heroCtaLink  = formEl.querySelector('[name="config[cta_link]"]');
        var heroLabels   = formEl.querySelector('[name="config[rotating_labels]"]');
        appendGeneratedInput(formEl, 'translations[' + localeIndex + '][headline]', heroHeadline ? heroHeadline.value : '');
        appendGeneratedInput(formEl, 'translations[' + localeIndex + '][subtitle]', heroSubtitle ? heroSubtitle.value : '');
        appendGeneratedInput(formEl, 'translations[' + localeIndex + '][cta_text]',  heroCtaText  ? heroCtaText.value  : '');
        appendGeneratedInput(formEl, 'translations[' + localeIndex + '][cta_link]',  heroCtaLink  ? heroCtaLink.value  : '');
        appendGeneratedInput(formEl, 'translations[' + localeIndex + '][rotating_labels]', heroLabels ? heroLabels.value : '');
      }

      if (activeType === 'carousel') {
        formEl.querySelectorAll('[name^="config[slides]"]').forEach(function (field) {
          var name  = field.getAttribute('name') || '';
          var match = name.match(/^config\[slides\]\[(\d+)\]\[(alt|headline|subtitle|cta_text|cta_link)\]$/);
          if (!match) return;
          appendGeneratedInput(
            formEl,
            'translations[' + localeIndex + '][slides][' + match[1] + '][' + match[2] + ']',
            field.value || ''
          );
        });
      }
    });
  }

  if (form) {
    form.addEventListener('submit', function () {
      fanOutUnifiedCreateTranslations(form);
      document.querySelectorAll('.cfg.d-none, .widget-translation-type.d-none').forEach(function (panel) {
        panel.querySelectorAll('input, select, textarea').forEach(function (inp) { inp.disabled = true; });
      });
    });
  }

})();
</script>
@endsection
