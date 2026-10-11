@if (config('academy.pwa', true))
    <script src="{{ asset('js/pwa.js') }}?v={{ @filemtime(public_path('js/pwa.js')) }}" defer data-sw="{{ route('pwa.sw') }}" data-offline="{{ route('pwa.offline') }}"></script>
@endif
