@section('scripts')
@parent
<script>
(() => {
  const root = document.getElementById('heroWorldServices');
  if (!root) return;
  const empty = document.querySelector('[data-hero-world-empty]');
  let sequence = 0;
  const identity = () => 'svc-' + (globalThis.crypto?.randomUUID?.() || Date.now().toString(36) + '-' + (++sequence) + '-' + Math.random().toString(36).slice(2));
  // Field indexes and sort order always follow the visible list order.
  const renumber = () => {
    const items = root.querySelectorAll('[data-hero-world-item]');
    items.forEach((item, i) => {
      item.querySelectorAll('[name]').forEach(field => {
        field.name = field.name.replace(/config\[services\]\[[^\]]+\]/, `config[services][${i}]`);
      });
      item.querySelector('[data-service-position]').value = i + 1;
      item.querySelector('[data-service-position-display]').value = i + 1;
      item.querySelector('[data-hero-world-number]').textContent = i + 1;
      item.querySelector('[data-hero-world-up]').disabled = i === 0;
      item.querySelector('[data-hero-world-down]').disabled = i === items.length - 1;
    });
    empty?.classList.toggle('d-none', items.length > 0);
  };
  document.getElementById('addHeroWorldService').addEventListener('click', () => {
    const fragment = document.getElementById('heroWorldServiceTemplate').content.cloneNode(true);
    fragment.querySelector('[data-service-id]').value = identity();
    const item = fragment.querySelector('[data-hero-world-item]');
    root.append(fragment);
    renumber();
    item.querySelector('input:not([type=hidden])')?.focus();
  });
  root.addEventListener('click', event => {
    const item = event.target.closest('[data-hero-world-item]');
    if (!item) return;
    if (event.target.closest('[data-hero-world-remove]')) item.remove();
    else if (event.target.closest('[data-hero-world-up]') && item.previousElementSibling) item.previousElementSibling.before(item);
    else if (event.target.closest('[data-hero-world-down]') && item.nextElementSibling) item.nextElementSibling.after(item);
    else return;
    renumber();
  });
  if (window.Sortable) Sortable.create(root, { handle: '[data-hero-world-handle]', animation: 150, onEnd: renumber });
  document.getElementById('widgetForm')?.addEventListener('submit', renumber);
  renumber();
})();
</script>
@endsection
