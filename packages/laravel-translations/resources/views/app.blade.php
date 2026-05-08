@extends(config('translations.layout'))

@push('styles')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
    @routes

    {{ translationsUIAssets() }}

    @inertiaHead
@endpush

@section('content')
<body class="font-sans antialiased h-full">
    @inertia
</body>
@endsection