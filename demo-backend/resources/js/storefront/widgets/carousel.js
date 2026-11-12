export function initCarousels() {
    document.querySelectorAll('[data-sf-carousel]').forEach((root) => {
        const viewport = root.querySelector('[data-sf-carousel-viewport]');
        const slides = [...root.querySelectorAll('[data-sf-carousel-slide]')];
        const dotsWrap = root.querySelector('[data-sf-carousel-dots]');
        if (!viewport || slides.length < 2) return;

        slides.forEach((_, i) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'sf-carousel__dot' + (i === 0 ? ' is-active' : '');
            dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
            dot.addEventListener('click', () => {
                slides[i].scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' });
            });
            dotsWrap?.appendChild(dot);
        });

        const dots = dotsWrap ? [...dotsWrap.querySelectorAll('.sf-carousel__dot')] : [];
        viewport.addEventListener('scroll', () => {
            const index = slides.findIndex((s) => {
                const r = s.getBoundingClientRect();
                const vr = viewport.getBoundingClientRect();
                return r.left >= vr.left - 20 && r.left <= vr.left + 40;
            });
            dots.forEach((d, i) => d.classList.toggle('is-active', i === Math.max(0, index)));
        }, { passive: true });
    });
}
