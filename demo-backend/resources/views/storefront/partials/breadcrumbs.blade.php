<nav class="sf-breadcrumbs" aria-label="Breadcrumb">
    <ol>
        <li><a href="{{ route('storefront.home') }}">Home</a></li>
        @foreach($breadcrumbs as $crumb)
            <li>
                @if(!$loop->last)
                    <a href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a>
                @else
                    <span aria-current="page">{{ $crumb['title'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
