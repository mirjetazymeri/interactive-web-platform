@section('scripts')
@parent
<script>
(() => {
 const root = document.getElementById('projectItems'); if (!root) return;
 let sequence = 0;
 const identity = prefix => prefix + '-' + (globalThis.crypto?.randomUUID?.() || Date.now().toString(36) + '-' + (++sequence) + '-' + Math.random().toString(36).slice(2));
 const renumber = (positions = false) => {
   [...root.children].forEach((item, index) => {
     item.querySelectorAll('[name]').forEach(field => field.name = field.name.replace(/config\[projects\]\[[^\]]+\]/, `config[projects][${index}]`));
     if (positions) item.querySelector('[data-project-position]').value = index;
     [...item.querySelector('.project-images').children].forEach((image, imageIndex) => {
       image.querySelectorAll('[name]').forEach(field => field.name = field.name.replace(/\[images\]\[[^\]]+\]/, `[images][${imageIndex}]`));
       image.querySelector('[data-image-number]').textContent = imageIndex + 1;
     });
   });
 };
 document.getElementById('addProjectItem').addEventListener('click', () => {
   if (root.children.length >= 100) return;
   const fragment = document.getElementById('projectItemTemplate').content.cloneNode(true);
   fragment.querySelector('[data-project-id]').value = identity('prj');
   fragment.querySelectorAll('[data-image-id]').forEach(field => field.value = identity('img'));
   root.append(fragment); renumber(true);
 });
 root.addEventListener('click', event => {
   const item = event.target.closest('.project-item'); if (!item) return;
   const image = event.target.closest('.project-image');
   if (event.target.closest('[data-project-remove]')) item.remove();
   if (event.target.closest('[data-project-up]') && item.previousElementSibling) root.insertBefore(item, item.previousElementSibling);
   if (event.target.closest('[data-project-down]') && item.nextElementSibling) root.insertBefore(item.nextElementSibling, item);
   if (event.target.closest('[data-image-up]') && image.previousElementSibling) image.parentNode.insertBefore(image, image.previousElementSibling);
   if (event.target.closest('[data-image-down]') && image.nextElementSibling) image.parentNode.insertBefore(image.nextElementSibling, image);
   if (event.target.closest('[data-image-remove]')) {
     image.querySelector('[data-image-removal]').value = '1';
     image.querySelector('input[type=file]').value = ''; image.querySelector('img')?.remove();
   }
   if (event.target.closest('[data-video-remove]')) {
     const video = event.target.closest('.project-video');
     video.querySelector('[data-video-removal]').value = '1';
     video.querySelector('input[type=file]').value = ''; video.querySelector('video')?.remove();
   }
   if (event.target.closest('[data-technology-add]')) {
     const list = item.querySelector('.project-technologies');
     if (list.children.length < 30) {
       const input = document.createElement('input'); input.className = 'form-control mb-1'; input.maxLength = 100;
       input.name = item.querySelector('[data-project-id]').name.replace(/\[id\]$/, '[technologies][]');
       input.setAttribute('aria-label', @json(__('projects.technologies'))); list.append(input);
     }
   }
   renumber(!!event.target.closest('[data-project-up], [data-project-down], [data-project-remove]'));
 });
 root.addEventListener('change', event => {
   if (event.target.matches('.project-image input[type=file]')) event.target.closest('.project-image').querySelector('[data-image-removal]').value = '0';
   if (event.target.matches('.project-video input[type=file]')) event.target.closest('.project-video').querySelector('[data-video-removal]').value = '0';
 });
 document.getElementById('widgetForm').addEventListener('submit', () => renumber());
})();
</script>
@endsection
