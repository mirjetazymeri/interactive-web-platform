@php
    $socials = $storefront->socialLinks();
    $showSocials = $storefront->bool('footer.show_socials', true);
    $email = trim((string) $storefront->get('store.email', ''));
    $phone = trim((string) $storefront->get('store.phone', ''));

    $footerGroups = collect($footerMenu ?? [])
        ->groupBy(fn (array $item) => $item['section_key'] ?? 'general');
@endphp

<footer class="sf-footer" role="contentinfo">
    <div class="sf-shell">
        <div class="sf-footer__grid">
            @forelse($footerGroups as $sectionKey => $items)
                @php $sectionLabel = $items->first()['section_label'] ?? ucfirst(str_replace('_', ' ', $sectionKey)); @endphp
                <div>
                    <h3 class="sf-footer__title">{{ $sectionLabel }}</h3>
                    <ul class="sf-footer__links">
                        @foreach($items as $item)
                            <li>
                                <a href="{{ $item['url'] }}" target="{{ $item['target'] ?? '_self' }}"
                                   @if(($item['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                            @foreach($item['children'] ?? [] as $child)
                                <li class="sf-footer__link-child">
                                    <a href="{{ $child['url'] }}" target="{{ $child['target'] ?? '_self' }}"
                                       @if(($child['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif>
                                        {{ $child['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @empty
                @foreach($storefront->footerSections() as $section)
                    <div>
                        <h3 class="sf-footer__title">{{ $section }}</h3>
                        <p style="margin: 0; color: #94a3b8; font-size: 0.875rem;">
                            Mark pages as “Show in footer” in Admin → Pages.
                        </p>
                    </div>
                @endforeach
            @endforelse

            @if($showSocials && !empty($socials))
                <div>
                    <h3 class="sf-footer__title">Follow us</h3>
                    <div class="sf-social">
                        @foreach($socials as $network => $url)
                            <a href="{{ $url }}" rel="noopener noreferrer" target="_blank">{{ ucfirst($network) }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="sf-footer__bottom">
            <span>© {{ date('Y') }} {{ $storefront->get('general.app_name', config('app.name')) }}</span>
            <span>
                @if($email !== '')<a href="mailto:{{ $email }}">{{ $email }}</a>@endif
                @if($phone !== '') · <span>{{ $phone }}</span>@endif
            </span>
        </div>
    </div>
</footer>
