@extends('layouts.frontend-app', ['pageTitle' => 'Pago pendiente', 'pageDescription' => 'Estado de tu pedido: falta completar o acreditar el pago.'])


@section('content')
<section class="ht-pend" data-testid="pending-page">
    <div class="ht-pend__card">
        <svg class="ht-pend__icon" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#14213d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        @if (($info['kind'] ?? 'offline') === 'review')
            <h1>Estamos revisando tu pago</h1>
            <p>Tu banco está validando el pago con tarjeta. Normalmente se aprueba en unos minutos y activamos tu acceso solos. Esta página se actualiza sola.</p>
        @else
            <h1>Falta completar tu pago{{ !empty($info['label']) ? ' con ' . $info['label'] : '' }}</h1>
            <p>Tu pedido <strong>#{{ $order->id }}</strong> quedó registrado. Apenas se acredite el pago activamos tu acceso y te lo avisamos por correo; con efectivo o SPEI puede tardar desde unos minutos hasta 1 día hábil.</p>
            @if (!empty($info['voucher_url']))
                <a class="ht-pend__cta" href="{{ $info['voucher_url'] }}" target="_blank" rel="noopener" data-testid="voucher-link">Ver instrucciones y ficha de pago</a>
            @else
                <p class="ht-pend__note">Te enviamos las instrucciones de pago por correo. Si no las encuentras, revisa tu carpeta de spam.</p>
            @endif
            @if (!empty($info['expires_at']))
                <p class="ht-pend__note">Paga antes del <strong>{{ $info['expires_at'] }}</strong>. Después la ficha caduca y tendrás que hacer un nuevo pedido.</p>
            @endif
        @endif

        <h2>Tu pedido</h2>
        <ul class="ht-pend__items">
            @foreach ($items as $title)<li>{{ html_entity_decode($title) }}</li>@endforeach
        </ul>
        <p class="ht-pend__total">Total: {!! formatAmount($order->amount, true) !!}</p>
        <p class="ht-pend__links"><a href="{{ route('courses.course-list') }}">Ir a mis cursos</a> · <a href="{{ route('contacto') }}">¿Necesitas ayuda?</a></p>
    </div>
</section>
<script>setTimeout(function () { location.reload(); }, 45000);</script>
@endsection
