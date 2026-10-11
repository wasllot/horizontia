@props(['panel' => false])
@if ($url = whatsappUrl($panel))
    @php
        $left = setting('_integrations.whatsapp_position') === 'left';
        // The AI tutor lives bottom-right on the lesson page: lift the button so they never overlap.
        $raised = !$left && request()->routeIs('courses.course-taking');
    @endphp
    <link rel="stylesheet" href="{{ asset('css/whatsapp-button.css') }}?v={{ @filemtime(public_path('css/whatsapp-button.css')) }}">
    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" data-testid="whatsapp-button"
       @class(['hz-wa', 'hz-wa--left' => $left, 'hz-wa--raised' => $raised])
       aria-label="{{ __('Escríbenos por WhatsApp') }}" title="{{ __('Escríbenos por WhatsApp') }}">
        <svg viewBox="0 0 32 32" width="28" height="28" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16.04 3C8.84 3 3 8.8 3 15.95c0 2.28.6 4.5 1.74 6.46L3 29l6.76-1.77a13.1 13.1 0 0 0 6.28 1.6h.01C23.24 28.83 29 23.03 29 15.88 29 12.43 27.65 9.2 25.2 6.76A12.93 12.93 0 0 0 16.04 3Zm0 23.64h-.01a10.9 10.9 0 0 1-5.55-1.52l-.4-.24-4.01 1.05 1.07-3.9-.26-.4a10.7 10.7 0 0 1-1.65-5.7c0-5.94 4.86-10.77 10.84-10.77 2.9 0 5.62 1.12 7.67 3.16a10.66 10.66 0 0 1 3.18 7.62c0 5.94-4.86 10.7-10.88 10.7Zm5.96-8.06c-.33-.16-1.93-.95-2.23-1.06-.3-.11-.52-.16-.74.17-.22.32-.85 1.06-1.04 1.28-.19.22-.38.24-.7.08-.33-.16-1.38-.51-2.62-1.6-.97-.86-1.62-1.92-1.81-2.25-.19-.32-.02-.5.14-.66.15-.15.33-.38.49-.57.16-.19.22-.33.33-.55.11-.22.05-.41-.03-.57-.08-.16-.74-1.78-1.01-2.44-.27-.64-.54-.55-.74-.56h-.63c-.22 0-.57.08-.87.41-.3.32-1.15 1.12-1.15 2.74s1.18 3.18 1.34 3.4c.16.22 2.32 3.54 5.62 4.96.79.34 1.4.54 1.88.69.79.25 1.5.22 2.07.13.63-.09 1.93-.79 2.2-1.55.27-.76.27-1.41.19-1.55-.08-.14-.3-.22-.63-.38Z"/></svg>
        <span class="hz-wa__label">{{ __('WhatsApp') }}</span>
    </a>
@endif
