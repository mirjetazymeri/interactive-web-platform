{{--
    All Configs panes. Every tab is part of the single settings form, so "Save" keeps
    saving all groups together exactly as before (SettingController@update).
    Expects: $centerTabs (SettingsCenter::tabs), $settings, $settingsActive.
--}}
@php $entries = \App\Support\SettingsCenter::entries(); @endphp

@foreach ($centerTabs as $tab => $sections)
    @php
        $meta = $entries[$tab] ?? ['label' => ucfirst(str_replace('_', ' ', $tab)), 'icon' => 'ri-settings-line', 'desc' => ''];
        $single = count($sections) === 1 && array_key_first($sections) === 'other';
    @endphp
    <section class="ax-settings-pane" id="settings-panel-{{ $tab }}" data-ax-pane="{{ $tab }}"
             aria-labelledby="settings-title-{{ $tab }}" @if ($tab !== $settingsActive) hidden @endif>

        <header class="ax-panel-head">
            <span class="ax-panel-head__icon" aria-hidden="true"><i class="{{ $meta['icon'] }}"></i></span>
            <div>
                <h2 class="ax-panel-head__title" id="settings-title-{{ $tab }}">{{ $meta['label'] }}</h2>
                @if (!empty($meta['desc']))
                    <p class="ax-panel-head__desc">{{ $meta['desc'] }}</p>
                @endif
            </div>
        </header>

        @foreach ($sections as $sectionKey => $section)
            <div class="ax-section">
                @unless ($single)
                    <div class="ax-section__head">
                        <h3 class="ax-section__title">{{ $section['title'] }}</h3>
                        @if (!empty($section['desc']))
                            <p class="ax-section__desc">{{ $section['desc'] }}</p>
                        @endif
                    </div>
                @endunless
                <div class="ax-section__body">
                    @foreach ($section['fields'] as $key => $field)
                        @include('admin.settings._field', ['key' => $key, 'field' => $field])
                    @endforeach
                </div>
            </div>
        @endforeach

        @if ($tab === 'theme')
            <div class="ax-section">
                <div class="ax-section__body ax-section__body--stack ax-legacy-panel">
                    @include('admin.settings.partials.typography', ['settings' => $settings])
                </div>
            </div>
        @endif

        @if ($tab === 'layout')
            <div class="ax-section">
                <div class="ax-section__body ax-section__body--stack ax-legacy-panel">
                    @include('admin.settings.partials.layout-preview', ['settings' => $settings])
                </div>
            </div>
        @endif

        @if ($tab === 'notifications')
            <div class="ax-section">
                <div class="ax-section__head">
                    <h3 class="ax-section__title">Test delivery</h3>
                    <p class="ax-section__desc">Sends a sample email to {{ auth()->user()->email }} using the <strong>saved</strong> mail settings — save your changes first.</p>
                </div>
                <div class="ax-section__body ax-section__body--stack">
                    <button type="button" id="test-email-btn" class="btn btn-outline-secondary">
                        <i class="ri-mail-check-line me-1" aria-hidden="true"></i>Send test email
                    </button>
                </div>
            </div>
        @endif
    </section>
@endforeach
