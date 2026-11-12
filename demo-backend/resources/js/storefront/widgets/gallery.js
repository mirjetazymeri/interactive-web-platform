export function initGalleryLightbox() {
    const lightbox = document.querySelector('[data-sf-lightbox]');
    if (!lightbox) return;

    const img = lightbox.querySelector('img');
    const close = () => lightbox.classList.remove('is-open');

    document.querySelectorAll('[data-sf-gallery-item]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const src = btn.getAttribute('data-src');
            const alt = btn.getAttribute('data-alt') || '';
            if (!src || !img) return;
            img.src = src;
            img.alt = alt;
            lightbox.classList.add('is-open');
        });
    });

    lightbox.querySelector('[data-sf-lightbox-close]')?.addEventListener('click', close);
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) close();
    });
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
}
