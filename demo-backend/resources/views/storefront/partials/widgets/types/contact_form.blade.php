@php
    $showPhone = !empty($config['show_phone']);
    $showSubject = !empty($config['show_subject']);
    $fixedSubject = trim((string) ($config['fixed_subject'] ?? 'Website Contact'));
    $channelCode = $storefront->channel?->code ?? 'default';
@endphp

<section class="sf-card sf-contact">
    @include('storefront.partials.widgets.section-header', [
        'widget' => $widget,
        'config' => $config,
        'heading' => $config['title'] ?? null,
        'subheading' => $config['subtitle'] ?? null,
    ])

    <form
        class="sf-form"
        data-sf-contact-form
        data-endpoint="{{ url('/api/v1/contact') }}"
        data-channel="{{ $channelCode }}"
        data-success="{{ $config['success_message'] ?? 'Your message has been sent successfully.' }}"
        novalidate
    >
        <div class="sf-form__row sf-form__row--2">
            <div class="sf-field">
                <label for="sf-contact-name-{{ $widget['id'] }}">Name</label>
                <input id="sf-contact-name-{{ $widget['id'] }}" type="text" name="name" required maxlength="100" autocomplete="name">
            </div>
            <div class="sf-field">
                <label for="sf-contact-email-{{ $widget['id'] }}">Email</label>
                <input id="sf-contact-email-{{ $widget['id'] }}" type="email" name="email" required maxlength="191" autocomplete="email">
            </div>
        </div>

        @if($showPhone)
            <div class="sf-field">
                <label for="sf-contact-phone-{{ $widget['id'] }}">Phone</label>
                <input id="sf-contact-phone-{{ $widget['id'] }}" type="tel" name="phone" maxlength="30" autocomplete="tel">
            </div>
        @endif

        @if($showSubject)
            <div class="sf-field">
                <label for="sf-contact-subject-{{ $widget['id'] }}">Subject</label>
                <input id="sf-contact-subject-{{ $widget['id'] }}" type="text" name="subject" required maxlength="255">
            </div>
        @else
            <input type="hidden" name="subject" value="{{ $fixedSubject }}">
        @endif

        <div class="sf-field">
            <label for="sf-contact-message-{{ $widget['id'] }}">Message</label>
            <textarea id="sf-contact-message-{{ $widget['id'] }}" name="message" rows="5" required minlength="10" maxlength="5000"></textarea>
        </div>

        <div class="sf-form__status" data-sf-form-status role="status" aria-live="polite"></div>

        <button type="submit" class="sf-btn sf-btn--primary">
            {{ $config['submit_label'] ?? 'Send Message' }}
        </button>
    </form>
</section>
