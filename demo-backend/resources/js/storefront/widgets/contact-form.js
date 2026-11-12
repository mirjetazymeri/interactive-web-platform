export function initContactForms() {
    document.querySelectorAll('[data-sf-contact-form]').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const endpoint = form.dataset.endpoint;
            const channel = form.dataset.channel || '';
            const status = form.querySelector('[data-sf-form-status]');
            const submit = form.querySelector('[type="submit"]');

            if (!endpoint) return;

            submit.disabled = true;
            status.className = 'sf-form__status';
            status.textContent = '';

            const body = Object.fromEntries(new FormData(form).entries());

            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        ...(channel ? { 'X-Channel': channel } : {}),
                    },
                    body: JSON.stringify(body),
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    let msg = data.message || 'Could not send message. Please try again.';
                    if (data.errors) {
                        msg = Object.values(data.errors).flat().join(' ');
                    }
                    throw new Error(msg);
                }

                status.textContent = form.dataset.success || data.message || 'Message sent successfully.';
                status.classList.add('is-success');
                form.reset();
            } catch (err) {
                status.textContent = err.message || 'Something went wrong.';
                status.classList.add('is-error');
            } finally {
                submit.disabled = false;
            }
        });
    });
}
