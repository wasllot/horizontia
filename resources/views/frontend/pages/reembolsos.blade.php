@php
    $name = config('academy.legal_name');
    $email = config('academy.contact_email') ?: setting('_front_page_settings.footer_email');
    $address = config('academy.address') ?: setting('_front_page_settings.footer_address');
    $refundDays = config('academy.refund_days');
    $jurisdiction = config('academy.jurisdiction');
@endphp
<x-front.page title="Política de reembolsos" description="Cuándo y cómo puedes pedir la devolución de tu pago." :updated="config('academy.legal_updated_at')">
<div class="ht-callout"><strong>Garantía de {{ $refundDays }} días.</strong> Si el curso no es lo que esperabas, puedes solicitar el reembolso dentro de los {{ $refundDays }} días naturales posteriores a la compra.</div>

<h2>Condiciones</h2>
<ul>
  <li>La solicitud debe hacerse dentro de {{ $refundDays }} días naturales desde la fecha de pago aprobado.</li>
  <li>No haber consumido más del <strong>20%</strong> del contenido del curso ni haber descargado materiales o certificado.</li>
  <li>Los cursos <strong>gratuitos</strong> no generan reembolso.</li>
  <li>Si detectamos uso indebido (compartir cuenta, descarga masiva de contenido), podremos negar el reembolso.</li>
</ul>

<h2>Cómo solicitarlo</h2>
<ol>
  <li>Escríbenos a @if ($email) <a href="mailto:{{ $email }}">{{ $email }}</a>@else nuestro <a href="{{ route('contacto') }}">formulario de contacto</a>@endif desde el correo de tu cuenta indicando el curso y el número de orden.</li>
  <li>Revisaremos tu solicitud y te responderemos en un máximo de 3 días hábiles.</li>
  <li>Si procede, el reembolso se hace al <strong>mismo medio de pago</strong> a través de Mercado Pago.</li>
</ol>

<h2>Tiempos</h2>
<p>La devolución se acredita según tu banco o medio de pago; normalmente entre 3 y 15 días hábiles. Los pagos en efectivo o por transferencia pueden requerir datos adicionales para devolver el importe.</p>

<h2>Efecto en tu acceso</h2>
<p>Al aprobarse el reembolso, el acceso al curso se retira de inmediato. Si tu banco inicia un contracargo, el acceso también se suspende mientras se resuelve.</p>

<p>Esta política complementa, y no limita, los derechos que te corresponden conforme a la Ley Federal de Protección al Consumidor.</p>
</x-front.page>
