@extends(config('pagebuilder.site_layout'), ['page' => $page, 'edit' => false])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/events.css') }}?v={{ @filemtime(public_path('css/events.css')) }}">
    @if ($css !== '')<style id="hz-page-css">{!! $css !!}</style>@endif
@endpush

@section(config('pagebuilder.site_section'))
    @if (!empty($banner))
        <x-page-banner :title="$banner->title" :subtitle="(string) $banner->subtitle" :image="$banner->imageUrl()" :overlay="$banner->overlay" :align="$banner->align" />
    @endif
    <div id="hz-page" class="hz-page">{!! $html !!}</div>
    @foreach ($jsonld as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endforeach
@endsection
