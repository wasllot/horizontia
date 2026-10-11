@php
    $name = config('academy.legal_name');
    $email = config('academy.contact_email') ?: setting('_front_page_settings.footer_email');
    $address = config('academy.address') ?: setting('_front_page_settings.footer_address');
    $refundDays = config('academy.refund_days');
    $jurisdiction = config('academy.jurisdiction');
@endphp
<x-front.page title="Política de cookies" description="Qué cookies usamos y cómo controlarlas." :updated="config('academy.legal_updated_at')">
<p>Las cookies son pequeños archivos que se guardan en tu navegador. Usamos las siguientes:</p>

<h2>Necesarias (siempre activas)</h2>
<ul>
  <li><strong>Sesión y seguridad</strong> (<code>laravel_session</code>, <code>XSRF-TOKEN</code>): mantienen tu sesión iniciada y protegen los formularios.</li>
  <li><strong>Preferencias</strong>: idioma, moneda y tu elección sobre este aviso de cookies.</li>
  <li><strong>Carrito y compra</strong>: recuerdan tu carrito y el proceso de pago.</li>
</ul>

<h2>De terceros</h2>
<ul>
  <li><strong>Mercado Pago</strong>: durante el pago, para prevenir fraude.</li>
  <li><strong>YouTube / Vimeo</strong>: cuando una lección incrusta un video de estas plataformas.</li>
</ul>
<p>No usamos cookies publicitarias propias. Si en el futuro añadimos analítica o publicidad, pediremos tu consentimiento antes.</p>

<h2>Cómo gestionarlas</h2>
<p>Puedes borrar o bloquear cookies desde la configuración de tu navegador. Si bloqueas las necesarias, partes del sitio (inicio de sesión, carrito, pagos) dejarán de funcionar.</p>
<p>Más información sobre el tratamiento de tus datos en el <a href="{{ route('legal.privacy') }}">Aviso de privacidad</a>.</p>
</x-front.page>
