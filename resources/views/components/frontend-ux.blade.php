{{-- UX y animaciones compartidas del sitio público (public/css/frontend-ux.css y public/js/frontend-ux.js). --}}
<link rel="stylesheet" href="{{ asset('css/frontend-ux.css') }}?v={{ @filemtime(public_path('css/frontend-ux.css')) }}">
<script src="{{ asset('js/frontend-ux.js') }}?v={{ @filemtime(public_path('js/frontend-ux.js')) }}" defer></script>
