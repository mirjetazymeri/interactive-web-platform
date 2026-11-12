/**
 * Admin shell behaviour: collapsible sidebar (desktop), navigation drawer (mobile),
 * collapsed-rail flyouts and tooltips, theme-aware logo contrast and toasts.
 * No dependencies; markup lives in layouts/components/admin-sidebar|admin-topbar.
 */
(function () {
    'use strict';

    const html = document.documentElement;
    const sidebar = document.getElementById('ax-sidebar');
    if (!sidebar) return;

    const DESKTOP = window.matchMedia('(min-width: 992px)');
    const STORAGE_KEY = 'ax-sidebar';
    const toggles = document.querySelectorAll('[data-ax-sidebar-toggle]');
    const collapseBtn = sidebar.querySelector('[data-ax-collapse]');

    const store = {
        get() { try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; } },
        set(value) { try { localStorage.setItem(STORAGE_KEY, value); } catch (e) { /* private mode */ } },
    };

    const isCollapsed = () => html.classList.contains('ax-collapsed');
    const isDrawerOpen = () => html.classList.contains('ax-drawer-open');

    function syncControls() {
        const expanded = DESKTOP.matches ? !isCollapsed() : isDrawerOpen();
        toggles.forEach((btn) => btn.setAttribute('aria-expanded', String(expanded)));
        if (collapseBtn) {
            collapseBtn.setAttribute('aria-pressed', String(isCollapsed()));
            collapseBtn.querySelector('.ax-nav__label').textContent = isCollapsed() ? 'Expand sidebar' : 'Collapse sidebar';
        }
    }

    function setCollapsed(collapsed) {
        html.classList.toggle('ax-collapsed', collapsed);
        store.set(collapsed ? 'collapsed' : 'expanded');
        closeFlyouts();
        hideTip();
        syncControls();
    }

    let lastFocus = null;
    function openDrawer() {
        lastFocus = document.activeElement;
        html.classList.add('ax-drawer-open');
        syncControls();
        const target = sidebar.querySelector('.ax-nav__link.is-active, .ax-nav__sublink.is-active, .ax-nav__link');
        target && target.focus({ preventScroll: false });
    }
    function closeDrawer() {
        if (!isDrawerOpen()) return;
        html.classList.remove('ax-drawer-open');
        syncControls();
        lastFocus && lastFocus.focus && lastFocus.focus();
    }

    toggles.forEach((btn) => btn.addEventListener('click', () => {
        if (DESKTOP.matches) setCollapsed(!isCollapsed());
        else (isDrawerOpen() ? closeDrawer() : openDrawer());
    }));
    collapseBtn && collapseBtn.addEventListener('click', () => setCollapsed(!isCollapsed()));
    document.querySelectorAll('[data-ax-drawer-close]').forEach((el) => el.addEventListener('click', closeDrawer));

    DESKTOP.addEventListener('change', () => {
        html.classList.remove('ax-drawer-open');
        closeFlyouts();
        syncControls();
    });

    /* ── Groups ─────────────────────────────────────────── */
    const groups = sidebar.querySelectorAll('[data-ax-group]');
    const railMode = () => DESKTOP.matches && isCollapsed();

    function closeFlyouts(except) {
        groups.forEach((g) => { if (g !== except) g.classList.remove('is-flyout'); });
    }
    function openFlyout(group) {
        closeFlyouts(group);
        const rect = group.getBoundingClientRect();
        const sub = group.querySelector('.ax-nav__sub');
        group.classList.add('is-flyout');
        // Keep the panel inside the viewport.
        const maxTop = window.innerHeight - sub.offsetHeight - 8;
        sub.style.setProperty('--ax-fly-top', Math.max(8, Math.min(rect.top, maxTop)) + 'px');
    }

    groups.forEach((group) => {
        const button = group.querySelector(':scope > .ax-nav__link');

        button.addEventListener('click', () => {
            if (railMode()) {
                group.classList.contains('is-flyout') ? closeFlyouts() : openFlyout(group);
                return;
            }
            const open = !group.classList.contains('is-open');
            group.classList.toggle('is-open', open);
            button.setAttribute('aria-expanded', String(open));
        });

        let leaveTimer;
        group.addEventListener('mouseenter', () => {
            if (!railMode()) return;
            clearTimeout(leaveTimer);
            hideTip();
            openFlyout(group);
        });
        group.addEventListener('mouseleave', () => {
            if (!railMode()) return;
            leaveTimer = setTimeout(() => group.classList.remove('is-flyout'), 160);
        });
        group.addEventListener('focusout', (event) => {
            if (railMode() && !group.contains(event.relatedTarget)) group.classList.remove('is-flyout');
        });
    });

    document.addEventListener('click', (event) => {
        if (railMode() && !event.target.closest('[data-ax-group]')) closeFlyouts();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const flyout = sidebar.querySelector('.is-flyout');
        if (flyout) {
            closeFlyouts();
            flyout.querySelector(':scope > .ax-nav__link').focus();
        } else if (isDrawerOpen()) {
            closeDrawer();
        }
    });

    /* ── Tooltips for the collapsed rail ────────────────── */
    const tip = document.createElement('div');
    tip.className = 'ax-tip';
    tip.setAttribute('role', 'tooltip');
    tip.id = 'ax-tip';
    document.body.appendChild(tip);

    function showTip(el) {
        if (!railMode() || el.closest('.is-flyout')) return;
        const rect = el.getBoundingClientRect();
        tip.textContent = el.getAttribute('data-ax-tip');
        tip.style.top = rect.top + rect.height / 2 + 'px';
        tip.style.left = rect.right + 10 + 'px';
        tip.classList.add('is-visible');
        el.setAttribute('aria-describedby', 'ax-tip');
    }
    function hideTip() {
        tip.classList.remove('is-visible');
        sidebar.querySelectorAll('[aria-describedby="ax-tip"]').forEach((el) => el.removeAttribute('aria-describedby'));
    }

    sidebar.querySelectorAll('[data-ax-tip]').forEach((el) => {
        if (el.closest('[data-ax-group]') && el.matches('[data-ax-group] > .ax-nav__link')) return; // groups show a flyout instead
        el.addEventListener('mouseenter', () => showTip(el));
        el.addEventListener('focus', () => showTip(el));
        el.addEventListener('mouseleave', hideTip);
        el.addEventListener('blur', hideTip);
    });
    sidebar.querySelector('.ax-nav').addEventListener('scroll', () => { hideTip(); closeFlyouts(); }, { passive: true });

    syncControls();

    /* ── Logo contrast ──────────────────────────────────── */
    // An uploaded logo may be white (made for dark backgrounds) or black. Measure its
    // brightness once so CSS can give it a contrasting tile in the theme where it would vanish.
    function measureTone(img, holder) {
        try {
            const size = 48;
            const canvas = document.createElement('canvas');
            const ratio = img.naturalWidth / img.naturalHeight || 1;
            canvas.width = ratio >= 1 ? size : Math.max(1, Math.round(size * ratio));
            canvas.height = ratio >= 1 ? Math.max(1, Math.round(size / ratio)) : size;
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            const data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
            let weight = 0;
            let luminance = 0;
            for (let i = 0; i < data.length; i += 4) {
                const alpha = data[i + 3] / 255;
                if (alpha < 0.1) continue;
                luminance += alpha * (0.2126 * data[i] + 0.7152 * data[i + 1] + 0.0722 * data[i + 2]) / 255;
                weight += alpha;
            }
            if (!weight) return;
            const average = luminance / weight;
            holder.dataset.tone = average > 0.78 ? 'light' : average < 0.22 ? 'dark' : 'mid';
        } catch (e) {
            /* cross-origin or decode failure: leave the logo as is */
        }
    }
    // Re-measures whenever the image changes (e.g. a new upload preview).
    window.axWatchLogo = function (holder) {
        const img = holder.querySelector('img');
        if (!img || img.dataset.axWatched) return;
        img.dataset.axWatched = '1';
        img.addEventListener('load', () => { delete holder.dataset.tone; measureTone(img, holder); });
        img.addEventListener('error', () => holder.classList.add('is-broken'));
        if (img.complete && img.naturalWidth) measureTone(img, holder);
    };
    document.querySelectorAll('[data-ax-logo]').forEach((holder) => window.axWatchLogo(holder));

    /* ── Forms with a save bar: unsaved-change tracking ── */
    // <form data-ax-dirty-form> containing the admin.settings._savebar partial.
    document.querySelectorAll('form[data-ax-dirty-form]').forEach((form) => {
        const savebar = form.querySelector('[data-ax-savebar]');
        const status = form.querySelector('[data-ax-save-status]');
        const discard = form.querySelector('[data-ax-discard]');
        const saveBtn = form.querySelector('[data-ax-save]');
        const failedStatus = status ? status.textContent : '';
        const hasErrors = !!document.querySelector('.ax-errors');
        let submitting = false;
        let dirty = false;

        const snapshot = () => {
            const parts = [];
            new FormData(form).forEach((value, key) => {
                if (key === '_token' || key === '_method') return;
                parts.push(key + '=' + (value instanceof File ? value.name + ':' + value.size : value));
            });
            return parts.join('&');
        };
        const initial = snapshot();

        const refresh = () => {
            dirty = snapshot() !== initial;
            if (!savebar) return;
            savebar.classList.toggle('is-dirty', dirty);
            status.textContent = dirty ? 'Unsaved changes' : (hasErrors ? failedStatus : 'All changes saved');
            if (discard) discard.hidden = !dirty;
        };
        form.addEventListener('input', refresh);
        form.addEventListener('change', refresh);

        discard && discard.addEventListener('click', () => {
            if (!window.confirm('Discard all unsaved changes?')) return;
            submitting = true;
            window.location.reload();
        });

        window.addEventListener('beforeunload', (event) => {
            if (dirty && !submitting) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        form.addEventListener('submit', (event) => {
            if (submitting) { event.preventDefault(); return; } // no double submit
            submitting = true;
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.setAttribute('aria-busy', 'true');
                saveBtn.innerHTML = '<span class="ax-spinner me-2" aria-hidden="true"></span><span>Saving…</span>';
            }
            if (status) status.textContent = 'Saving…';
        });
        // Coming back via the browser's back/forward cache must not leave the button disabled.
        window.addEventListener('pageshow', (event) => { if (event.persisted) window.location.reload(); });
    });

    /* ── Toasts ─────────────────────────────────────────── */
    const toastIcons = { success: 'ri-checkbox-circle-fill', danger: 'ri-error-warning-fill', warning: 'ri-alert-fill', info: 'ri-information-fill' };
    window.axToast = function (message, type = 'info', timeout = 5000) {
        const region = document.getElementById('ax-toasts');
        if (!region) return;
        const toast = document.createElement('div');
        toast.className = 'ax-toast ax-toast--' + type;
        toast.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        const icon = document.createElement('i');
        icon.className = (toastIcons[type] || toastIcons.info) + ' ax-toast__icon';
        icon.setAttribute('aria-hidden', 'true');
        const text = document.createElement('div');
        text.className = 'ax-toast__text';
        text.textContent = message;
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'ax-toast__close';
        close.setAttribute('aria-label', 'Dismiss');
        close.innerHTML = '<i class="ri-close-line" aria-hidden="true"></i>';
        const remove = () => { toast.classList.add('is-leaving'); setTimeout(() => toast.remove(), 200); };
        close.addEventListener('click', remove);
        toast.append(icon, text, close);
        region.appendChild(toast);
        if (timeout) setTimeout(remove, timeout);
    };
})();
