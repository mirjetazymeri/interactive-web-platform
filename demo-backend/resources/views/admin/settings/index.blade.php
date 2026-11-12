@extends('admin.settings.center')

@php
    $centerTabs = \App\Support\SettingsCenter::tabs(\App\Support\SettingsCenter::flatten($schema));

    // Tabs holding a validation error get a marker; the first one opens after a failed save.
    $errorTabs = collect($errors->keys())
        ->map(fn ($key) => strtok($key, '.'))
        ->filter(fn ($tab) => isset($centerTabs[$tab]))
        ->unique()->values()->all();
    $settingsActive = $errorTabs[0] ?? array_key_first($centerTabs);
@endphp

@section('settings-panel')
    @if ($errors->any())
        <div class="alert alert-danger ax-errors" role="alert">
            <div class="d-flex align-items-start gap-2">
                <i class="ri-error-warning-fill fs-16" aria-hidden="true"></i>
                <div>
                    <strong>Nothing was saved. Please fix the following:</strong>
                    <ul>
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form id="settings-form" method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" novalidate
          data-ax-dirty-form data-has-errors="{{ $errors->any() ? '1' : '0' }}">
        @csrf
        @method('PUT')

        @include('admin.settings._form', [
            'settings' => $settings,
            'centerTabs' => $centerTabs,
            'settingsActive' => $settingsActive,
        ])

        @include('admin.settings._savebar', ['label' => 'Save settings'])
    </form>

    <!-- Test Email Selection Modal -->
    <div class="modal fade" id="testEmailModal" tabindex="-1" aria-labelledby="testEmailLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testEmailLabel">Send test email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Choose which template to send to <strong>{{ auth()->user()->email }}</strong>.</p>
                    <div class="d-flex flex-column gap-2">
                        <button type="button" class="ax-option" data-template="welcome">
                            <i class="ri-mail-open-line" aria-hidden="true"></i>
                            <span><span class="ax-option__title">Welcome email</span><span class="ax-option__desc">New user registration</span></span>
                        </button>
                        <button type="button" class="ax-option" data-template="verify-email">
                            <i class="ri-shield-check-line" aria-hidden="true"></i>
                            <span><span class="ax-option__title">Email verification</span><span class="ax-option__desc">Verify email address link</span></span>
                        </button>
                        <button type="button" class="ax-option" data-template="login-alert">
                            <i class="ri-login-box-line" aria-hidden="true"></i>
                            <span><span class="ax-option__title">Login alert</span><span class="ax-option__desc">New sign-in notification</span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var form = document.getElementById('settings-form');
    if (!form) return;

    var panes = Array.prototype.slice.call(document.querySelectorAll('[data-ax-pane]'));
    var tabButtons = Array.prototype.slice.call(document.querySelectorAll('[data-ax-tab]'));
    var select = document.querySelector('[data-ax-settings-select]');
    var STORE_KEY = 'ax-settings-tab';
    var hasErrors = form.dataset.hasErrors === '1';

    /* ── Tabs ─────────────────────────────────────────── */
    function showTab(key, updateHash) {
        var pane = document.querySelector('[data-ax-pane="' + key + '"]');
        if (!pane) return false;
        panes.forEach(function (p) { p.hidden = p !== pane; });
        tabButtons.forEach(function (btn) {
            var active = btn.dataset.axTab === key;
            btn.classList.toggle('is-active', active);
            active ? btn.setAttribute('aria-current', 'true') : btn.removeAttribute('aria-current');
        });
        if (select) {
            Array.prototype.forEach.call(select.options, function (opt) {
                if (opt.dataset.axTabOption === key) opt.selected = true;
            });
        }
        try { sessionStorage.setItem(STORE_KEY, key); } catch (e) {}
        if (updateHash && history.replaceState) history.replaceState(null, '', '#' + key);
        return true;
    }
    window.axSettingsShowTab = function (key, updateHash) {
        if (showTab(key, updateHash)) {
            var title = document.getElementById('settings-title-' + key);
            title && title.setAttribute('tabindex', '-1');
            title && title.focus({ preventScroll: true });
            window.scrollTo({ top: 0 });
        }
    };
    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () { window.axSettingsShowTab(btn.dataset.axTab, true); });
    });

    // A failed save opens the tab with the first error (server-side); otherwise honour the URL, then the last tab used.
    if (!hasErrors) {
        var initial = location.hash.slice(1);
        if (!initial) { try { initial = sessionStorage.getItem(STORE_KEY); } catch (e) {} }
        if (initial) showTab(initial, false);
    }
    window.addEventListener('hashchange', function () { showTab(location.hash.slice(1), false); });

    /* ── Field helpers ────────────────────────────────── */
    form.querySelectorAll('[data-ax-color]').forEach(function (input) {
        var code = input.parentElement.querySelector('[data-ax-color-value]');
        input.addEventListener('input', function () { code.textContent = input.value; });
    });
    form.querySelectorAll('[data-ax-reveal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('aria-controls'));
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Hide value' : 'Show value');
            btn.querySelector('i').className = show ? 'ri-eye-off-line' : 'ri-eye-line';
        });
    });

    /* ── Uploads: instant preview, undo, drag & drop ───── */
    form.querySelectorAll('[data-ax-upload]').forEach(function (box) {
        var input = box.querySelector('[data-ax-upload-input]');
        var preview = box.querySelector('[data-ax-upload-preview]');
        var nameEl = box.querySelector('[data-ax-upload-name]');
        var reset = box.querySelector('[data-ax-upload-reset]');
        var originalHtml = preview.innerHTML;
        var originalName = nameEl.textContent;
        var objectUrl = null;

        function restore() {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            input.value = '';
            preview.innerHTML = originalHtml;
            nameEl.textContent = originalName;
            reset.hidden = true;
            delete preview.dataset.tone;
            var img = preview.querySelector('img');
            img && delete img.dataset.axWatched;
            window.axWatchLogo && window.axWatchLogo(preview);
            form.dispatchEvent(new Event('input', { bubbles: true }));
        }

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) { restore(); return; }
            if (file.size > 2 * 1024 * 1024) {
                window.axToast && window.axToast(file.name + ' is larger than 2 MB.', 'danger');
                restore();
                return;
            }
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = URL.createObjectURL(file);
            preview.innerHTML = '';
            delete preview.dataset.tone;
            var img = document.createElement('img');
            img.alt = 'New file preview';
            var badge = document.createElement('span');
            badge.className = 'ax-upload__badge';
            badge.textContent = 'New';
            preview.append(img, badge);
            window.axWatchLogo && window.axWatchLogo(preview);
            img.src = objectUrl;
            nameEl.textContent = file.name + ' — saved when you save settings';
            reset.hidden = false;
        });
        reset.addEventListener('click', restore);

        ['dragenter', 'dragover'].forEach(function (type) {
            box.addEventListener(type, function (e) { e.preventDefault(); box.classList.add('is-dragover'); });
        });
        ['dragleave', 'drop'].forEach(function (type) {
            box.addEventListener(type, function (e) { e.preventDefault(); box.classList.remove('is-dragover'); });
        });
        box.addEventListener('drop', function (e) {
            if (!e.dataTransfer || !e.dataTransfer.files.length) return;
            input.files = e.dataTransfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    /* ── Test email ───────────────────────────────────── */
    var testEmailBtn = document.getElementById('test-email-btn');
    var modalEl = document.getElementById('testEmailModal');
    if (testEmailBtn && modalEl && window.bootstrap) {
        var testEmailModal = new bootstrap.Modal(modalEl);
        var originalLabel = testEmailBtn.innerHTML;
        testEmailBtn.addEventListener('click', function (e) {
            e.preventDefault();
            testEmailModal.show();
        });
        modalEl.querySelectorAll('[data-template]').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                testEmailBtn.disabled = true;
                testEmailBtn.innerHTML = '<span class="ax-spinner me-2" aria-hidden="true"></span>Sending…';
                testEmailModal.hide();
                try {
                    var response = await fetch(@json(route('admin.settings.test-email')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ template: btn.dataset.template }),
                    });
                    var data = await response.json().catch(function () { return {}; });
                    window.axToast(data.message || (data.success ? 'Test email sent.' : 'Failed to send test email.'), data.success ? 'success' : 'danger');
                } catch (error) {
                    window.axToast('Could not reach the server. Please try again.', 'danger');
                } finally {
                    testEmailBtn.disabled = false;
                    testEmailBtn.innerHTML = originalLabel;
                }
            });
        });
    }
});
</script>
@endpush
