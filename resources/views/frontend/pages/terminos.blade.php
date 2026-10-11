@php
    $name = config('academy.legal_name');
    $email = config('academy.contact_email') ?: setting('_front_page_settings.footer_email');
    $address = config('academy.address') ?: setting('_front_page_settings.footer_address');
    $refundDays = config('academy.refund_days');
    $jurisdiction = config('academy.jurisdiction');
@endphp
<x-front.page title="Términos y condiciones" description="Reglas para usar la plataforma y adquirir cursos." :updated="config('academy.legal_updated_at')">
<p>Estos términos regulan el uso del sitio y la compra de cursos de <strong>{{ $name }}</strong> (en adelante, «la Academia»). Al crear una cuenta o realizar una compra aceptas estos términos.</p>

<h2>1. Cuenta de usuario</h2>
<ul>
  <li>Debes ser mayor de edad o contar con autorización de tu madre, padre o tutor.</li>
  <li>Eres responsable de la confidencialidad de tu contraseña y de la actividad de tu cuenta. La cuenta es personal: no se puede compartir ni revender.</li>
  <li>Debes proporcionar datos verdaderos y mantenerlos actualizados.</li>
</ul>

<h2>2. Cursos, precios y pago</h2>
<ul>
  <li>Los precios se muestran en pesos mexicanos (MXN) e incluyen los impuestos aplicables cuando corresponda.</li>
  <li>Los pagos se procesan a través de <strong>Mercado Pago</strong>. La Academia no almacena los datos de tu tarjeta.</li>
  <li>El acceso al curso se habilita cuando el pago es <em>aprobado</em>. Si el pago queda pendiente (por ejemplo, pago en efectivo o transferencia), el acceso se activa al acreditarse.</li>
</ul>

<h2>3. Licencia de uso del contenido</h2>
<p>Al comprar un curso recibes una licencia personal, no exclusiva e intransferible para ver el contenido con fines de aprendizaje. Queda prohibido copiar, descargar, grabar, redistribuir, compartir enlaces de acceso o publicar el contenido sin autorización. Los videos se entregan mediante enlaces temporales ligados a tu sesión.</p>

<h2>4. Propiedad intelectual</h2>
<p>Los cursos, textos, videos, marcas y materiales pertenecen a la Academia o a sus instructores y están protegidos por la Ley Federal del Derecho de Autor y demás normas aplicables.</p>

<h2>5. Conducta</h2>
<p>No está permitido usar la plataforma para actividades ilícitas, acosar a otras personas, intentar vulnerar la seguridad del sitio, extraer contenido de forma automatizada o suplantar identidades. Podemos suspender cuentas que incumplan estas reglas.</p>

<h2>6. Evaluaciones y certificados</h2>
<p>Los certificados se emiten al cumplir los requisitos de cada curso. Son constancias de participación emitidas por la Academia y no sustituyen títulos oficiales ni cédulas profesionales, salvo que el curso indique lo contrario.</p>

<h2>7. Tutor con inteligencia artificial</h2>
<p>Cuando está disponible, el tutor IA ofrece orientación sobre las lecciones. Sus respuestas pueden contener errores, no constituyen asesoría profesional y no sustituyen al instructor. Tus mensajes se procesan a través de un proveedor de IA externo (ver el <a href="{{ route('legal.privacy') }}">Aviso de privacidad</a>). No compartas datos personales sensibles en el chat.</p>

<h2>8. Reembolsos</h2>
<p>Consulta la <a href="{{ route('legal.refunds') }}">Política de reembolsos</a>.</p>

<h2>9. Disponibilidad y cambios</h2>
<p>Procuramos que el sitio esté disponible de forma continua, pero puede haber interrupciones por mantenimiento o causas ajenas. Podemos actualizar, reorganizar o retirar contenido; si retiramos un curso que ya compraste, mantendrás acceso por un plazo razonable o recibirás una alternativa equivalente.</p>

<h2>10. Limitación de responsabilidad</h2>
<p>La Academia no garantiza resultados específicos derivados del aprendizaje. En la medida permitida por la ley, nuestra responsabilidad se limita al monto pagado por el curso de que se trate.</p>

<h2>11. Modificaciones</h2>
<p>Podemos modificar estos términos; publicaremos la versión vigente en esta página con su fecha. El uso continuado del sitio implica aceptación.</p>

<h2>12. Contacto y jurisdicción</h2>
<p>Para dudas sobre estos términos escríbenos a @if ($email) <a href="mailto:{{ $email }}">{{ $email }}</a>@else nuestro <a href="{{ route('contacto') }}">formulario de contacto</a>@endif. Estos términos se rigen por las leyes de {{ $jurisdiction }}; para su interpretación las partes se someten a los tribunales competentes de dicha jurisdicción, sin perjuicio de los derechos que la Ley Federal de Protección al Consumidor te otorga.</p>
</x-front.page>
