{{--
    Settings Center shell: page header + secondary settings navigation.
    Used by All Configs (tabs inside one form) and by the dedicated SEO / Tracking pages,
    which keep their own forms and save logic. Children set $settingsActive and fill
    the "settings-panel" (and optionally "settings-actions") sections.
--}}
@extends('layouts.master')

@php
    $centerTabs = $centerTabs ?? \App\Support\SettingsCenter::tabs(\App\Support\SettingsCenter::flatten(config('settings')));
    $centerNav = \App\Support\SettingsCenter::navigation($centerTabs);
    $onIndex = request()->routeIs('admin.settings.index');
    $settingsActive = $settingsActive ?? 'general';
    $errorTabs = $errorTabs ?? [];
@endphp

@section('title', 'Settings')

@section('content')
<div class="ax-page-header">
    <div>
        <h1 class="ax-page-header__title">Settings</h1>
        <p class="ax-page-header__desc">Configure the application, the public site and system integrations for <strong>{{ $currentChannel->name ?? 'the current channel' }}</strong>.</p>
    </div>
    <div class="ax-page-header__actions">
        @yield('settings-actions')
    </div>
</div>

<div class="ax-settings">
    <nav class="ax-settings__nav" aria-label="Settings sections">
        @foreach ($centerNav as $category => $items)
            <div class="ax-snav__group">
                <div class="ax-snav__heading">{{ $category }}</div>
                <ul class="ax-snav__list">
                    @foreach ($items as $key => $item)
                        @php $isActive = $key === $settingsActive; @endphp
                        <li>
                            @if (isset($item['url']))
                                <a href="{{ $item['url'] }}" class="ax-snav__link {{ $isActive ? 'is-active' : '' }}" @if ($isActive) aria-current="page" @endif>
                                    <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                                    <span class="ax-snav__label">{{ $item['label'] }}</span>
                                </a>
                            @elseif ($onIndex)
                                <button type="button" class="ax-snav__link {{ $isActive ? 'is-active' : '' }}" data-ax-tab="{{ $key }}"
                                        aria-controls="settings-panel-{{ $key }}" @if ($isActive) aria-current="true" @endif>
                                    <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                                    <span class="ax-snav__label">{{ $item['label'] }}</span>
                                    <span class="ax-snav__dot" data-ax-tab-error="{{ $key }}" title="Contains errors" @if (!in_array($key, $errorTabs, true)) hidden @endif></span>
                                </button>
                            @else
                                <a href="{{ route('admin.settings.index') }}#{{ $key }}" class="ax-snav__link">
                                    <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                                    <span class="ax-snav__label">{{ $item['label'] }}</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Compact navigation below 992px --}}
    <div class="ax-settings__select">
        <label class="form-label" for="ax-settings-select">Settings section</label>
        <select id="ax-settings-select" class="form-select" data-ax-settings-select>
            @foreach ($centerNav as $category => $items)
                <optgroup label="{{ $category }}">
                    @foreach ($items as $key => $item)
                        @php
                            $value = isset($item['url']) ? $item['url'] : ($onIndex ? '#'.$key : route('admin.settings.index').'#'.$key);
                        @endphp
                        <option value="{{ $value }}" data-ax-tab-option="{{ isset($item['url']) ? '' : $key }}" @selected($key === $settingsActive)>{{ $item['label'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    <div class="ax-settings__panel">
        @yield('settings-panel')
    </div>
</div>
@endsection

@push('scripts')
<script>
    @if (session('success'))
        document.addEventListener('DOMContentLoaded', function () {
            window.axToast && window.axToast(@json(session('success')), 'success');
        });
    @endif
    (function () {
        // Keep the active entry visible inside the (scrollable) settings navigation.
        var nav = document.querySelector('.ax-settings__nav');
        var active = nav && nav.querySelector('.is-active');
        if (active && active.offsetTop + active.offsetHeight > nav.clientHeight) {
            nav.scrollTop = active.offsetTop - nav.clientHeight / 2;
        }

        var select = document.querySelector('[data-ax-settings-select]');
        if (!select) return;
        select.addEventListener('change', function () {
            var value = select.value;
            if (value.charAt(0) === '#' && window.axSettingsShowTab) {
                window.axSettingsShowTab(value.slice(1), true);
            } else {
                window.location.href = value;
            }
        });
    })();
</script>
@endpush
