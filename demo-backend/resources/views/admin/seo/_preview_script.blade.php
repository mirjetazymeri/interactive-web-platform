{{-- Live search preview and character guidance for admin.seo._preview; include once per page, outside any <template>. --}}
@once
<script>
(() => {
  // Character guidance only: search engines truncate by pixel width, so nothing is enforced.
  const counter = (input) => {
    if (input.dataset.seoReady) return;
    input.dataset.seoReady = '1';
    const limit = Number(input.dataset.seoCount);
    let note = input.parentElement.querySelector('[data-seo-count-note]');
    if (!note) {
      note = document.createElement('div');
      note.className = 'form-text';
      note.dataset.seoCountNote = '';
      input.insertAdjacentElement('afterend', note);
    }
    const update = () => {
      const length = input.value.trim().length;
      note.textContent = `${length} / ~${limit} ${@json(__('seo.preview.characters'))}`;
      note.classList.toggle('text-warning', length > limit);
    };
    input.addEventListener('input', update);
    update();
  };
  const preview = (box) => {
    if (box.dataset.seoReady) return;
    box.dataset.seoReady = '1';
    const scope = box.closest(box.dataset.scope) || document;
    const pick = (selector) => selector ? scope.querySelector(selector) : null;
    const value = (selector, fallback) => (pick(selector)?.value || '').trim() || (pick(fallback)?.value || '').trim();
    const titleEl = box.querySelector('[data-seo-preview-title]');
    const descriptionEl = box.querySelector('[data-seo-preview-description]');
    // Same rules as the website's pageTitle(): a title naming the site stays as is; a template needs {title}.
    const render = () => {
      const title = value(box.dataset.titleSource, box.dataset.titleFallback).replace(/\s+/g, ' ');
      const site = value(box.dataset.siteSource) || box.dataset.site;
      const typed = value(box.dataset.templateSource);
      const template = (typed || box.dataset.template).includes('{title}') ? (typed || box.dataset.template) : '{title} {separator} {site}';
      const separator = value(box.dataset.separatorSource) || box.dataset.separator;
      const named = !title || title.toLowerCase().includes(site.toLowerCase());
      titleEl.textContent = named ? (title || site) : template.replace('{title}', title).replace('{separator}', separator).replace('{site}', site).replace(/\s+/g, ' ').trim();
      descriptionEl.textContent = value(box.dataset.descriptionSource, box.dataset.descriptionFallback) || @json(__('seo.preview.no_description'));
    };
    [box.dataset.titleSource, box.dataset.titleFallback, box.dataset.descriptionSource, box.dataset.descriptionFallback, box.dataset.siteSource, box.dataset.separatorSource, box.dataset.templateSource]
      .filter(Boolean).forEach((selector) => { pick(selector)?.addEventListener('input', render); pick(selector)?.addEventListener('change', render); });
    render();
  };
  const init = () => {
    document.querySelectorAll('[data-seo-count]').forEach(counter);
    document.querySelectorAll('[data-seo-preview]').forEach(preview);
  };
  const start = () => {
    init();
    // Items added from a <template> (e.g. a new project) get their preview and counters too.
    new MutationObserver(init).observe(document.body, { childList: true, subtree: true });
  };
  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start) : start();
})();
</script>
@endonce
