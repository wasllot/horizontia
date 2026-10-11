@php
    $name = config('academy.legal_name');
    $email = config('academy.contact_email') ?: setting('_front_page_settings.footer_email');
    $address = config('academy.address') ?: setting('_front_page_settings.footer_address');
    $refundDays = config('academy.refund_days');
    $jurisdiction = config('academy.jurisdiction');
@endphp
<x-front.page title="Aviso de privacidad" description="Cómo tratamos tus datos personales." :updated="config('academy.legal_updated_at')">
<h2>1. Responsable</h2>
<p><strong>{{ $name }}</strong>@if ($address), con domicilio en {{ $address }},@endif es responsable del tratamiento de tus datos personales conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP).</p>

<h2>2. Datos que recabamos</h2>
<ul>
  <li><strong>Identificación y contacto:</strong> nombre, correo electrónico, teléfono, país y ciudad.</li>
  <li><strong>Facturación:</strong> datos de la orden (monto, método de pago, referencia de la transacción). Los datos de tu tarjeta los procesa directamente Mercado Pago; nosotros no los vemos ni los guardamos.</li>
  <li><strong>Uso de la plataforma:</strong> cursos adquiridos, progreso, calificaciones de evaluaciones, reseñas y mensajes.</li>
  <li><strong>Técnicos:</strong> dirección IP, tipo de dispositivo y navegador, cookies (ver <a href="{{ route('legal.cookies') }}">Política de cookies</a>).</li>
  <li><strong>Mensajes al tutor IA</strong>, cuando usas esa función.</li>
</ul>

<h2>3. Finalidades</h2>
<p><strong>Necesarias:</strong> crear tu cuenta, procesar pagos y darte acceso a los cursos, emitir certificados, brindarte soporte, prevenir fraudes y cumplir obligaciones legales y fiscales.</p>
<p><strong>Adicionales (puedes oponerte):</strong> enviarte novedades y promociones, y mejorar nuestros servicios con estadísticas agregadas. Si no deseas estas finalidades, escríbenos o usa el enlace de baja de nuestros correos.</p>

<h2>4. Transferencias y proveedores</h2>
<p>Compartimos datos únicamente con proveedores que nos prestan servicios, bajo obligaciones de confidencialidad:</p>
<ul>
  <li><strong>Mercado Pago</strong> (procesamiento de pagos).</li>
  <li><strong>Cloudflare</strong> (almacenamiento y entrega de archivos de los cursos).</li>
  <li><strong>Google (Gemini)</strong> — solo si el tutor IA está activo — para generar respuestas a tus preguntas. Los mensajes se envían junto con el contexto de la lección.</li>
  <li>Proveedores de correo electrónico y de alojamiento.</li>
</ul>
<p>No vendemos tus datos. Podremos divulgarlos cuando lo exija una autoridad competente.</p>

<h2>5. Derechos ARCO</h2>
<p>Puedes ejercer tus derechos de <strong>acceso, rectificación, cancelación y oposición</strong>, así como revocar tu consentimiento, enviando una solicitud a @if ($email) <a href="mailto:{{ $email }}">{{ $email }}</a>@else nuestro <a href="{{ route('contacto') }}">formulario de contacto</a>@endif con tu nombre, medio para responderte, descripción clara de lo que solicitas y copia de una identificación. Responderemos en un máximo de 20 días hábiles.</p>

<h2>6. Conservación y seguridad</h2>
<p>Conservamos tus datos mientras tu cuenta esté activa y durante los plazos que exijan las leyes fiscales y de protección al consumidor. Aplicamos medidas técnicas y administrativas razonables: cifrado en tránsito, control de acceso por inscripción y URLs temporales para los videos.</p>

<h2>7. Menores de edad</h2>
<p>Si eres menor de edad, tu madre, padre o tutor debe autorizar el uso de la plataforma y el tratamiento de tus datos.</p>

<h2>8. Cambios a este aviso</h2>
<p>Publicaremos cualquier modificación en esta página. Si el cambio altera las finalidades, te lo notificaremos por correo.</p>
</x-front.page>
