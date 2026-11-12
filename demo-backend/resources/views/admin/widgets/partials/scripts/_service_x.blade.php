@section('scripts')
@parent
<script>
(() => {
  const root = document.getElementById('serviceXItems');
  if (!root) return;
  let sequence = 0;
  const identity = prefix => prefix + '-' + (globalThis.crypto?.randomUUID?.() || Date.now().toString(36) + '-' + (++sequence) + '-' + Math.random().toString(36).slice(2));
  const renumber = () => {
    root.querySelectorAll('.service-x-item').forEach((service, i) => {
      service.querySelectorAll('[name]').forEach(field => {
        field.name = field.name.replace(/config\[services\]\[[^\]]+\]/, `config[services][${i}]`);
      });
      service.querySelectorAll('.service-x-sub').forEach((sub, j) => {
        sub.querySelectorAll('[name]').forEach(field => {
          field.name = field.name.replace(/\[subservices\]\[[^\]]+\]/, `[subservices][${j}]`);
        });
      });
    });
  };
  document.getElementById('addServiceX').addEventListener('click', () => {
    const fragment = document.getElementById('serviceXTemplate').content.cloneNode(true);
    fragment.querySelector('[data-service-id]').value = identity('svc');
    root.append(fragment);
    renumber();
  });
  root.addEventListener('click', event => {
    if (event.target.closest('[data-service-remove]')) event.target.closest('.service-x-item').remove();
    if (event.target.closest('[data-sub-remove]')) event.target.closest('.service-x-sub').remove();
    if (event.target.closest('[data-sub-add]')) {
      const fragment = document.getElementById('serviceXSubTemplate').content.cloneNode(true);
      fragment.querySelector('[data-sub-id]').value = identity('sub');
      event.target.closest('.service-x-item').querySelector('.service-x-subs').append(fragment);
    }
    renumber();
  });
  document.getElementById('widgetForm').addEventListener('submit', renumber);
})();
</script>
@endsection
