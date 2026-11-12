{{-- Contact Form type config block --}}
<div id="cfg-contact_form" class="cfg d-none">

  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="ri-mail-send-line text-secondary fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">Contact Form Options</span>
  </div>

  {{-- Layout & field toggles --}}
  <div class="row g-3 mb-4">

    <div class="col-md-3">
      <label class="form-label">Layout</label>
      <select name="config[layout]" class="form-select">
        @foreach(['card' => 'Card (bordered box)', 'flat' => 'Flat (no border)', 'minimal' => 'Minimal (labels only)'] as $lv => $ll)
          <option value="{{ $lv }}" @selected(($cfg['layout'] ?? 'card') === $lv)>{{ $ll }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-3 d-flex flex-column justify-content-end">
      <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" name="config[show_phone]" value="1" id="cfShowPhone"
               @checked(!empty($cfg['show_phone']))>
        <label class="form-check-label" for="cfShowPhone">Show phone field</label>
      </div>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="config[show_subject]" value="1" id="cfShowSubject"
               @checked($cfg['show_subject'] ?? true)>
        <label class="form-check-label" for="cfShowSubject">Show subject field</label>
      </div>
    </div>

    {{-- Fixed subject (shown when subject field is hidden) --}}
    <div class="col-md-6" id="cfFixedSubjectWrap" @style(['display:none' => ($cfg['show_subject'] ?? true)])>
      <label class="form-label">Fixed Subject <span class="badge bg-info-subtle text-info ms-1">translatable</span></label>
      <input type="text" name="config[fixed_subject]" class="form-control"
             value="{{ $cfg['fixed_subject'] ?? '' }}" placeholder="e.g. Website Enquiry">
      <div class="form-text">Used as the email subject when the subject field is hidden.</div>
    </div>

  </div>

  {{-- Content --}}
  <div class="d-flex align-items-center gap-2 mb-3 mt-2">
    <i class="ri-text fs-16 text-muted"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">Content</span>
  </div>

  <div class="row g-3 mb-4">

    <div class="col-md-6">
      <label class="form-label">
        Title
        <span class="badge bg-info-subtle text-info ms-1">translatable</span>
      </label>
      <input type="text" name="config[title]" class="form-control"
             value="{{ $cfg['title'] ?? '' }}" placeholder="e.g. Contact Us">
    </div>

    <div class="col-md-6">
      <label class="form-label">
        Subtitle
        <span class="badge bg-info-subtle text-info ms-1">translatable</span>
      </label>
      <input type="text" name="config[subtitle]" class="form-control"
             value="{{ $cfg['subtitle'] ?? '' }}" placeholder="e.g. We'd love to hear from you.">
    </div>

    <div class="col-md-4">
      <label class="form-label">
        Submit Button Label
        <span class="badge bg-info-subtle text-info ms-1">translatable</span>
      </label>
      <input type="text" name="config[submit_label]" class="form-control"
             value="{{ $cfg['submit_label'] ?? 'Send Message' }}" placeholder="Send Message">
    </div>

    <div class="col-md-8">
      <label class="form-label">
        Success Message
        <span class="badge bg-info-subtle text-info ms-1">translatable</span>
      </label>
      <input type="text" name="config[success_message]" class="form-control"
             value="{{ $cfg['success_message'] ?? 'Your message has been sent. We will get back to you shortly.' }}"
             placeholder="Your message has been sent. We will get back to you shortly.">
    </div>

  </div>

  {{-- Info note --}}
  <div class="alert alert-light border d-flex gap-2 align-items-start py-2 px-3 mt-2">
    <i class="ri-information-line text-info fs-16 mt-1 flex-shrink-0"></i>
    <div class="small text-muted">
      Submissions are sent to the admin email configured in <strong>Notifications</strong> settings
      and require <code>notifications.admin.notify_contact_form</code> to be enabled.
      Fields validated: <strong>name</strong>, <strong>email</strong>, <strong>subject</strong>, <strong>message</strong> (required); <strong>phone</strong> (optional).
    </div>
  </div>

</div>

@push('scripts')
<script>
(function () {
  const subjectToggle = document.getElementById('cfShowSubject');
  const fixedWrap     = document.getElementById('cfFixedSubjectWrap');

  if (!subjectToggle || !fixedWrap) return;

  function syncFixedSubject() {
    fixedWrap.style.display = subjectToggle.checked ? 'none' : '';
  }

  subjectToggle.addEventListener('change', syncFixedSubject);
  syncFixedSubject();
})();
</script>
@endpush
