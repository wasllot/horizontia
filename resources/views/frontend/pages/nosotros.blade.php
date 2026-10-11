@php
    $name = config('academy.legal_name');
    $email = config('academy.contact_email') ?: setting('_front_page_settings.footer_email');
    $refundDays = config('academy.refund_days');
@endphp
<x-front.page title="Nosotros" description="Una academia en línea para aprender a tu ritmo, con instructores que acompañan.">
<p><strong>{{ $name }}</strong> es una academia en línea privada: cursos diseñados por instructores especialistas, con contenido en video, lecturas, ejercicios y evaluaciones, y certificado al terminar.</p>

<h2>Lo que nos distingue</h2>
<ul>
  <li><strong>Aprendizaje práctico:</strong> cada curso termina con aplicaciones reales, no solo teoría.</li>
  <li><strong>A tu ritmo:</strong> accede cuando quieras, desde computadora, tableta o celular, y retoma donde te quedaste.</li>
  <li><strong>Acompañamiento:</strong> foro de dudas por curso y un tutor con inteligencia artificial que te guía con pistas.</li>
  <li><strong>Contenido protegido:</strong> los materiales son exclusivos para quienes se inscriben.</li>
  <li><strong>Pagos seguros</strong> con Mercado Pago.</li>
</ul>

<h2>Nuestro compromiso</h2>
<p>Cuidamos la calidad del contenido, la claridad de los precios y la protección de tus datos. Si algo no sale como esperabas, tienes una <a href="{{ route('legal.refunds') }}">garantía de reembolso de {{ config('academy.refund_days') }} días</a>.</p>

<p><a class="am-btn" href="{{ route('courses.search-courses') }}">Ver cursos</a></p>
</x-front.page>
