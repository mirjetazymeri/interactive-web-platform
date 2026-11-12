@php
    $authUser = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($authUser?->name ?? '')))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'U';
    $currentLocale = app()->getLocale();
@endphp
<header class="ax-topbar" id="header">
    <div class="ax-topbar__left">
        {{-- Desktop: collapses the sidebar to an icon rail. Mobile: opens the navigation drawer. --}}
        <button type="button" class="ax-icon-btn" data-ax-sidebar-toggle aria-controls="ax-sidebar" aria-expanded="true" aria-label="Toggle navigation">
            <i class="ri-menu-2-line" aria-hidden="true"></i>
        </button>

        <nav class="ax-breadcrumb" aria-label="Breadcrumb">
            <ol>
                <li><a href="{{ route('admin.dashboard') }}">{{ $adminBrand['name'] }}</a></li>
                @foreach ($adminTrail as $crumb)
                    <li @if ($loop->last) aria-current="page" @endif>{{ $crumb }}</li>
                @endforeach
            </ol>
        </nav>
    </div>

    <div class="ax-topbar__right">
        @if (!empty($availableLocales) && count($availableLocales) > 1)
            <div class="dropdown">
                <button type="button" class="ax-topbar__chip" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Interface language">
                    <i class="ri-global-line" aria-hidden="true"></i>
                    <span class="ax-hide-sm">{{ strtoupper($currentLocale) }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end ax-menu">
                    <li class="ax-menu__title">Language</li>
                    @foreach ($availableLocales as $code => $locale)
                        <li>
                            <a class="dropdown-item {{ $code === $currentLocale ? 'active' : '' }}" href="{{ route('admin.locale.switch', $code) }}">
                                @if (!empty($locale['flag']))
                                    <img src="{{ asset('build/assets/images/flags/' . $locale['flag']) }}" alt="" class="ax-flag" onerror="this.style.visibility='hidden'">
                                @endif
                                {{ $locale['name'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @isset($userChannels)
            <div class="dropdown">
                <button type="button" class="ax-topbar__chip" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Current channel">
                    <i class="ri-broadcast-line" aria-hidden="true"></i>
                    <span class="ax-topbar__chip-text">{{ $currentChannel->name ?? 'Select channel' }}</span>
                    <i class="ri-arrow-down-s-line ax-topbar__chip-caret" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end ax-menu">
                    <li class="ax-menu__title">Channel</li>
                    @forelse ($userChannels as $channel)
                        <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center gap-3 {{ optional($currentChannel)->id === $channel->id ? 'active' : '' }}"
                               href="{{ route('admin.channels.switch', $channel->id) }}">
                                <span>{{ $channel->name }}</span>
                                @if (optional($currentChannel)->id === $channel->id)
                                    <i class="ri-check-line" aria-label="Current"></i>
                                @endif
                            </a>
                        </li>
                    @empty
                        <li><span class="dropdown-item-text text-muted">No channels</span></li>
                    @endforelse
                </ul>
            </div>
        @endisset

        {{-- `.layout-setting` is the theme toggle wired up (and persisted) by resources/assets/js/custom.js. --}}
        <button type="button" class="ax-icon-btn layout-setting" aria-label="Toggle dark mode" title="Toggle dark mode">
            <i class="ri-moon-line ax-theme-icon-light" aria-hidden="true"></i>
            <i class="ri-sun-line ax-theme-icon-dark" aria-hidden="true"></i>
        </button>

        @if ($authUser)
            <div class="dropdown">
                <button type="button" class="ax-user" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                    <span class="ax-avatar" aria-hidden="true">{{ $initials }}</span>
                    <span class="ax-user__name ax-hide-md">{{ $authUser->name }}</span>
                    <i class="ri-arrow-down-s-line ax-hide-md" aria-hidden="true"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end ax-menu ax-menu--account">
                    <div class="ax-menu__account">
                        <span class="ax-avatar ax-avatar--lg" aria-hidden="true">{{ $initials }}</span>
                        <div class="min-w-0">
                            <div class="ax-menu__account-name">{{ $authUser->name }}</div>
                            <div class="ax-menu__account-email">{{ $authUser->email }}</div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="ri-user-3-line" aria-hidden="true"></i>Profile</a>
                    <a class="dropdown-item" href="{{ route('admin.security') }}"><i class="ri-shield-keyhole-line" aria-hidden="true"></i>Security</a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item ax-menu__danger"><i class="ri-logout-box-r-line" aria-hidden="true"></i>Log out</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</header>
