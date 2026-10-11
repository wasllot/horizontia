@php
    $name = config('academy.legal_name');
    $email = config('academy.contact_email') ?: setting('_front_page_settings.footer_email');
    $refundDays = config('academy.refund_days');
@endphp
<x-front.page title="Cómo funciona" description="De tu solicitud de acceso a tu certificado en cinco pasos.">
<ol style="list-style:none; padding:0; display:grid; gap:18px;">
  @foreach ([
    ['1', 'Solicita tu acceso', 'Nadie se registra solo: pide acceso como estudiante o instructor. Revisamos tu solicitud y, al aprobarla, te enviamos un correo para elegir tu contraseña.'],
    ['2', 'Elige tu curso', 'Con tu cuenta activa, explora el catálogo, revisa el temario, las lecciones de vista previa y las reseñas.'],
    ['3', 'Paga con Mercado Pago', 'Tarjeta, efectivo o transferencia. Los precios son en MXN y el pago es seguro.'],
    ['4', 'Aprende a tu ritmo', 'Tu curso se activa al aprobarse el pago. Mira los videos, resuelve evaluaciones y pregunta al tutor IA o a tu instructor.'],
    ['5', 'Obtén tu certificado', 'Al completar el curso recibes un certificado con código y página pública para verificarlo.'],
  ] as [$n, $t, $d])
    <li style="display:flex; gap:16px; align-items:flex-start; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px;">
      <span style="flex:0 0 40px; height:40px; border-radius:50%; background:#fed304; color:#14213d; font-weight:800; display:flex; align-items:center; justify-content:center;">{{ $n }}</span>
      <div><h3 style="margin:0 0 4px;">{{ $t }}</h3><p style="margin:0;">{{ $d }}</p></div>
    </li>
  @endforeach
</ol>
<p>¿Dudas? Revisa las <a href="{{ route('legal.faq') }}">preguntas frecuentes</a> o la <a href="{{ route('legal.refunds') }}">política de reembolsos</a>.</p>
</x-front.page>
