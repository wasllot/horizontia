@unless($breadcrumbs->isEmpty())
    <nav aria-label="{{ __('Ruta de navegación') }}">
        <ol class="hz-crumbs">
            @foreach($breadcrumbs as $breadcrumb)
                @if(!is_null($breadcrumb->url) && !$loop->last)
                    <li><a href="{{ $breadcrumb->url }}" wire:navigate.remove>{{ $breadcrumb->title }}</a></li>
                @else
                    <li><span aria-current="page">{{ $breadcrumb->title }}</span></li>
                @endif
            @endforeach
        </ol>
    </nav>
@endunless
