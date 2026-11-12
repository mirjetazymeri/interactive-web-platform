{{-- Sticky save bar for a form marked data-ax-dirty-form (behaviour in resources/js/admin-shell.js). --}}
<div class="ax-savebar" data-ax-savebar>
    <span class="ax-savebar__status" data-ax-save-status aria-live="polite">{{ $errors->any() ? 'Not saved — see the errors above' : 'All changes saved' }}</span>
    <div class="ax-savebar__actions">
        <button type="button" class="btn btn-outline-secondary" data-ax-discard hidden>Discard changes</button>
        <button type="submit" class="btn btn-primary" data-ax-save>
            <i class="ri-save-line me-1" aria-hidden="true"></i><span>{{ $label ?? 'Save settings' }}</span>
        </button>
    </div>
</div>
