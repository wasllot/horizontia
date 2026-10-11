<link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ @filemtime(public_path('css/auth.css')) }}">
<div class="am-main-login ht-auth">
    <aside class="ht-auth__aside" style="--ht-auth-img: url('{{ webpAsset('images/nuevo/sign-in-horizontia.jpg') }}')">
        <div class="ht-auth__aside-top">
            {{ $logo }}
        </div>

        <div class="ht-auth__aside-bottom">
            <div class="ht-auth__title">
                <h2>{{ __('auth.login_left_h2') }}</h2>
                <span>{{ __('auth.login_left_span') }}</span>
            </div>

            {{-- Simulación (decorativa) de lo que verás dentro: retomar un curso con tu avance guardado. --}}
            <div class="ht-auth__mock" aria-hidden="true">
                <div class="ht-auth__mock-head"><i></i> Continúa donde te quedaste</div>
                <div class="ht-auth__mock-course">
                    <span class="ht-auth__mock-thumb">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#14213d" stroke-width="2" stroke-linecap="round"><path d="M3 18h18M6 18a6 6 0 0 1 12 0M12 5v3M4.6 9.6l2.1 2.1M19.4 9.6l-2.1 2.1"/></svg>
                    </span>
                    <span><strong>Respiración consciente</strong><small>Lección 6 de 9 · 12 min</small></span>
                </div>
                <div class="ht-auth__mock-bar"><i></i></div>
                <div class="ht-auth__mock-meta"><span>68 % completado</span><span>Certificado al terminar</span></div>
                <span class="ht-auth__mock-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
                    Avance guardado
                </span>
            </div>
        </div>
    </aside>

    <div class="am-login-right ht-auth__main">
        <div class="ht-auth__bar">
            <a href="{{ url('/') }}" class="ht-auth__back">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6"/></svg>
                Volver al inicio
            </a>
            <a href="{{ route('contacto') }}" class="ht-auth__help">¿Necesitas ayuda?</a>
        </div>

        <div class="ht-auth__body ht-rise">
            {{ $formHeader }}
            {{ $slot }}
        </div>

        <p class="ht-auth__legal">
            Al continuar aceptas los <a href="{{ route('legal.terms') }}">términos</a> y el <a href="{{ route('legal.privacy') }}">aviso de privacidad</a>.
        </p>
    </div>
</div>
