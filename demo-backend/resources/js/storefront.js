import { initCarousels } from './storefront/widgets/carousel.js';
import { initGalleryLightbox } from './storefront/widgets/gallery.js';
import { initContactForms } from './storefront/widgets/contact-form.js';
import './storefront/widgets/hero.js';
import './storefront/widgets/video.js';
import './storefront/widgets/map.js';
import './storefront/widgets/faq.js';
import './storefront/widgets/features.js';
import './storefront/widgets/testimonials.js';
import './storefront/widgets/pricing.js';
import './storefront/widgets/separator.js';
import './storefront/widgets/html.js';

document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
    initCarousels();
    initGalleryLightbox();
    initContactForms();
});

function initMobileNav() {
    const toggle = document.querySelector('[data-sf-nav-toggle]');
    const drawer = document.querySelector('[data-sf-nav-drawer]');
    if (!toggle || !drawer) return;

    const open = () => {
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };
    const close = () => {
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    toggle.addEventListener('click', open);
    drawer.querySelector('[data-sf-nav-close]')?.addEventListener('click', close);
    drawer.querySelector('[data-sf-nav-backdrop]')?.addEventListener('click', close);
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
}

