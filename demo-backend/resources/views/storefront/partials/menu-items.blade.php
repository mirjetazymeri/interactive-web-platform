@foreach($items as $item)
    <li @class(['sf-menu-item', 'sf-menu-item--has-children' => !empty($item['children'])])>
        <a
            href="{{ $item['url'] }}"
            target="{{ $item['target'] ?? '_self' }}"
            @if(($item['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif
        >
            {{ $item['label'] }}
        </a>
        @if(!empty($item['children']))
            <ul class="sf-submenu" role="menu">
                @include('storefront.partials.menu-items', ['items' => $item['children']])
            </ul>
        @endif
    </li>
@endforeach
