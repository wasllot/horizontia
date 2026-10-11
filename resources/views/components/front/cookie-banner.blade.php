@php $analytics = (bool) gtmContainerId(); @endphp
<div x-data="{
        show: false,
        init() { try { this.show = !localStorage.getItem('ht_cookie_ok'); } catch (e) { this.show = false; } },
        choose(value) {
            try { localStorage.setItem('ht_cookie_ok', value); } catch (e) {}
            this.show = false;
            if (value === 'all') { window.dispatchEvent(new CustomEvent('ht:cookies-accepted')); }
        },
     }"
     x-show="show" x-cloak style="display:none;"
     role="dialog" aria-label="Aviso de cookies">
    <div class="ht-cookie">
        <p class="ht-cookie__text">
            <span class="ht-cookie__long">
                @if ($analytics)
                    Usamos cookies necesarias para que el sitio funcione (sesión, carrito y pagos) y, solo si las aceptas, cookies de analítica para mejorar la plataforma.
                @else
                    Usamos cookies necesarias para que el sitio funcione (sesión, carrito y pagos).
                @endif
            </span>
            <span class="ht-cookie__short">Usamos cookies para que el sitio funcione{{ $analytics ? ' y, si aceptas, para mejorarlo' : '' }}.</span>
            <a href="{{ route('legal.cookies') }}">Cookies</a> · <a href="{{ route('legal.privacy') }}">Privacidad</a>
        </p>
        <div class="ht-cookie__actions">
            @if ($analytics)
                <button type="button" class="ht-cookie__btn ht-cookie__btn--ghost" @click="choose('necessary')" data-testid="cookies-necessary">Solo necesarias</button>
                <button type="button" class="ht-cookie__btn" @click="choose('all')" data-testid="cookies-all">Aceptar todas</button>
            @else
                <button type="button" class="ht-cookie__btn" @click="choose('necessary')" data-testid="cookies-ok">Entendido</button>
            @endif
        </div>
    </div>
</div>
