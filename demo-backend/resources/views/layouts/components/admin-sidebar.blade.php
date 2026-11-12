{{-- Admin sidebar. Items come from App\Support\AdminNavigation (via AdminLayoutComposer). --}}
<aside class="ax-sidebar" id="ax-sidebar" aria-label="Main navigation">

    <div class="ax-sidebar__brand">
        <a href="{{ route('admin.dashboard') }}" class="ax-brand" aria-label="{{ $adminBrand['name'] }} — Dashboard">
            @if ($adminBrand['logo'])
                <span class="ax-brand__logo-wrap" data-ax-logo>
                    <img src="{{ $adminBrand['logo'] }}" alt="{{ $adminBrand['name'] }}" class="ax-brand__logo" decoding="async">
                </span>
            @else
                <span class="ax-brand__mark" aria-hidden="true">{{ $adminBrand['initial'] }}</span>
                <span class="ax-brand__name">{{ $adminBrand['name'] }}</span>
            @endif

            {{-- Collapsed rail: the favicon, or the initial when there is none. --}}
            <span class="ax-brand__compact" aria-hidden="true">
                @if ($adminBrand['favicon'])
                    <img src="{{ $adminBrand['favicon'] }}" alt="" class="ax-brand__icon">
                @else
                    <span class="ax-brand__mark">{{ $adminBrand['initial'] }}</span>
                @endif
            </span>
        </a>

        <button type="button" class="ax-icon-btn ax-sidebar__close" data-ax-drawer-close aria-label="Close navigation">
            <i class="ri-close-line" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="ax-nav" id="ax-nav">
        @foreach ($adminNav as $section)
            <div class="ax-nav__section">
                <div class="ax-nav__heading">{{ $section['heading'] }}</div>
                <ul class="ax-nav__list">
                    @foreach ($section['items'] as $item)
                        @if (!empty($item['children']))
                            @php $groupId = 'ax-group-'.$loop->parent->index.'-'.$loop->index; @endphp
                            <li class="ax-nav__group {{ $item['active'] ? 'is-open is-active' : '' }}" data-ax-group>
                                <button type="button" class="ax-nav__link" aria-expanded="{{ $item['active'] ? 'true' : 'false' }}"
                                        aria-controls="{{ $groupId }}" data-ax-tip="{{ $item['label'] }}">
                                    <i class="{{ $item['icon'] }} ax-nav__icon" aria-hidden="true"></i>
                                    <span class="ax-nav__label">{{ $item['label'] }}</span>
                                    <i class="ri-arrow-down-s-line ax-nav__chevron" aria-hidden="true"></i>
                                </button>
                                <ul class="ax-nav__sub" id="{{ $groupId }}" aria-label="{{ $item['label'] }}">
                                    <li class="ax-nav__flyout-title" aria-hidden="true">{{ $item['label'] }}</li>
                                    @foreach ($item['children'] as $child)
                                        <li>
                                            <a href="{{ $child['url'] }}" class="ax-nav__sublink {{ $child['active'] ? 'is-active' : '' }}"
                                               @if ($child['active']) aria-current="page" @endif>{{ $child['label'] }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li>
                                <a href="{{ $item['url'] }}" class="ax-nav__link {{ $item['active'] ? 'is-active' : '' }}"
                                   data-ax-tip="{{ $item['label'] }}" @if ($item['active']) aria-current="page" @endif>
                                    <i class="{{ $item['icon'] }} ax-nav__icon" aria-hidden="true"></i>
                                    <span class="ax-nav__label">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="ax-sidebar__footer">
        <button type="button" class="ax-nav__link ax-collapse-btn" data-ax-collapse aria-pressed="false" data-ax-tip="Expand sidebar">
            <i class="ri-side-bar-line ax-nav__icon" aria-hidden="true"></i>
            <span class="ax-nav__label">Collapse sidebar</span>
        </button>
    </div>
</aside>
<div class="ax-backdrop" data-ax-drawer-close aria-hidden="true"></div>
