@php
    $name = config('academy.legal_name');
    $email = config('academy.contact_email') ?: setting('_front_page_settings.footer_email');
    $refundDays = config('academy.refund_days');
@endphp
<x-front.page title="Preguntas frecuentes" description="Respuestas rápidas sobre compras, acceso, pagos y certificados.">
  <div class="accordion" id="ht-faq">
    <div class="accordion-item">
      <h2 class="accordion-header" id="faq-h0">
        <button class="accordion-button " type="button" data-bs-toggle="collapse" data-bs-target="#faq-c0" aria-expanded="true" aria-controls="faq-c0">¿Cómo compro un curso?</button>
      </h2>
      <div id="faq-c0" class="accordion-collapse collapse show" aria-labelledby="faq-h0" data-bs-parent="#ht-faq">
        <div class="accordion-body">Elige el curso, pulsa «Agregar al carrito» y completa el pago con Mercado Pago (tarjeta, efectivo o transferencia según disponibilidad). En cuanto el pago se aprueba, el curso aparece en «Mis cursos».</div>
      </div>
    </div>
    <div class="accordion-item">
      <h2 class="accordion-header" id="faq-h1">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c1" aria-expanded="false" aria-controls="faq-c1">¿Qué medios de pago aceptan?</button>
      </h2>
      <div id="faq-c1" class="accordion-collapse collapse " aria-labelledby="faq-h1" data-bs-parent="#ht-faq">
        <div class="accordion-body">Pagamos a través de Mercado Pago: tarjetas de crédito y débito, pago en efectivo y transferencia, según lo que Mercado Pago ofrezca en tu cuenta al momento de pagar. Los precios están en pesos mexicanos (MXN).</div>
      </div>
    </div>
    <div class="accordion-item">
      <h2 class="accordion-header" id="faq-h2">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c2" aria-expanded="false" aria-controls="faq-c2">¿Cuánto tarda en activarse mi curso?</button>
      </h2>
      <div id="faq-c2" class="accordion-collapse collapse " aria-labelledby="faq-h2" data-bs-parent="#ht-faq">
        <div class="accordion-body">Con tarjeta, casi de inmediato. Con pagos en efectivo o transferencia, cuando el pago se acredita (puede tomar de minutos a un par de días hábiles). Te avisamos por correo.</div>
      </div>
    </div>
    <div class="accordion-item">
      <h2 class="accordion-header" id="faq-h3">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c3" aria-expanded="false" aria-controls="faq-c3">¿Por cuánto tiempo tengo acceso?</button>
      </h2>
      <div id="faq-c3" class="accordion-collapse collapse " aria-labelledby="faq-h3" data-bs-parent="#ht-faq">
        <div class="accordion-body">Una vez comprado, tienes acceso a tu curso mientras esté disponible en la plataforma, incluyendo sus actualizaciones.</div>
      </div>
    </div>
    <div class="accordion-item">
      <h2 class="accordion-header" id="faq-h4">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c4" aria-expanded="false" aria-controls="faq-c4">¿Puedo pedir un reembolso?</button>
      </h2>
      <div id="faq-c4" class="accordion-collapse collapse " aria-labelledby="faq-h4" data-bs-parent="#ht-faq">
        <div class="accordion-body">Sí. Tienes {{ $refundDays }} días naturales desde la compra, siempre que no hayas consumido más del 20% del curso. Consulta la <a href="{{ route('legal.refunds') }}">Política de reembolsos</a>.</div>
      </div>
    </div>
    <div class="accordion-item">
      <h2 class="accordion-header" id="faq-h5">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c5" aria-expanded="false" aria-controls="faq-c5">¿Los cursos dan certificado?</button>
      </h2>
      <div id="faq-c5" class="accordion-collapse collapse " aria-labelledby="faq-h5" data-bs-parent="#ht-faq">
        <div class="accordion-body">Sí, al completar los requisitos del curso puedes descargar tu certificado de finalización desde «Certificados».</div>
      </div>
    </div>
    <div class="accordion-item">
      <h2 class="accordion-header" id="faq-h6">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c6" aria-expanded="false" aria-controls="faq-c6">¿Puedo ver los videos desde el celular?</button>
      </h2>
      <div id="faq-c6" class="accordion-collapse collapse " aria-labelledby="faq-h6" data-bs-parent="#ht-faq">
        <div class="accordion-body">Sí. La plataforma funciona en computadora, tableta y celular. Los videos se reproducen en streaming; no se pueden descargar.</div>
      </div>
    </div>
    <div class="accordion-item">
      <h2 class="accordion-header" id="faq-h7">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c7" aria-expanded="false" aria-controls="faq-c7">¿Puedo compartir mi cuenta o los videos?</button>
      </h2>
      <div id="faq-c7" class="accordion-collapse collapse " aria-labelledby="faq-h7" data-bs-parent="#ht-faq">
        <div class="accordion-body">No. La licencia es personal. Los enlaces de video son temporales y están ligados a tu sesión.</div>
      </div>
    </div>
    <div class="accordion-item">
      <h2 class="accordion-header" id="faq-h8">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c8" aria-expanded="false" aria-controls="faq-c8">¿Qué es el tutor IA?</button>
      </h2>
      <div id="faq-c8" class="accordion-collapse collapse " aria-labelledby="faq-h8" data-bs-parent="#ht-faq">
        <div class="accordion-body">Es un asistente que te orienta con dudas de la lección que estás viendo. Te da pistas y explicaciones, pero no resuelve tus evaluaciones y puede equivocarse: ante dudas importantes consulta a tu instructor. Solo está disponible si la Academia lo tiene activado.</div>
      </div>
    </div>
    <div class="accordion-item">
      <h2 class="accordion-header" id="faq-h9">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c9" aria-expanded="false" aria-controls="faq-c9">Olvidé mi contraseña, ¿qué hago?</button>
      </h2>
      <div id="faq-c9" class="accordion-collapse collapse " aria-labelledby="faq-h9" data-bs-parent="#ht-faq">
        <div class="accordion-body">En la pantalla de inicio de sesión elige «¿Olvidaste tu contraseña?» y sigue las instrucciones que te enviaremos por correo.</div>
      </div>
    </div>
    <div class="accordion-item">
      <h2 class="accordion-header" id="faq-h10">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c10" aria-expanded="false" aria-controls="faq-c10">Mi pago salió aprobado pero no veo el curso</button>
      </h2>
      <div id="faq-c10" class="accordion-collapse collapse " aria-labelledby="faq-h10" data-bs-parent="#ht-faq">
        <div class="accordion-body">Espera unos minutos y recarga «Mis cursos». Si sigue sin aparecer, escríbenos con tu número de orden desde el <a href="{{ route('contacto') }}">formulario de contacto</a>.</div>
      </div>
    </div>
  </div>
  <p style="margin-top:2rem;">¿No encontraste tu respuesta? Escríbenos desde el <a href="{{ route('contacto') }}">formulario de contacto</a>@if ($email) o a <a href="mailto:{{ $email }}">{{ $email }}</a>@endif.</p>
  @push('scripts')
  <script type="application/ld+json">{"@@context": "https://schema.org", "@@type": "FAQPage", "mainEntity": [{"@@type": "Question", "name": "¿Cómo compro un curso?", "acceptedAnswer": {"@@type": "Answer", "text": "Elige el curso, pulsa «Agregar al carrito» y completa el pago con Mercado Pago (tarjeta, efectivo o transferencia según disponibilidad). En cuanto el pago se aprueba, el curso aparece en «Mis cursos»."}}, {"@@type": "Question", "name": "¿Qué medios de pago aceptan?", "acceptedAnswer": {"@@type": "Answer", "text": "Pagamos a través de Mercado Pago: tarjetas de crédito y débito, pago en efectivo y transferencia, según lo que Mercado Pago ofrezca en tu cuenta al momento de pagar. Los precios están en pesos mexicanos (MXN)."}}, {"@@type": "Question", "name": "¿Cuánto tarda en activarse mi curso?", "acceptedAnswer": {"@@type": "Answer", "text": "Con tarjeta, casi de inmediato. Con pagos en efectivo o transferencia, cuando el pago se acredita (puede tomar de minutos a un par de días hábiles). Te avisamos por correo."}}, {"@@type": "Question", "name": "¿Por cuánto tiempo tengo acceso?", "acceptedAnswer": {"@@type": "Answer", "text": "Una vez comprado, tienes acceso a tu curso mientras esté disponible en la plataforma, incluyendo sus actualizaciones."}}, {"@@type": "Question", "name": "¿Puedo pedir un reembolso?", "acceptedAnswer": {"@@type": "Answer", "text": "Sí. Tienes 7 días naturales desde la compra, siempre que no hayas consumido más del 20% del curso. Consulta la Política de reembolsos."}}, {"@@type": "Question", "name": "¿Los cursos dan certificado?", "acceptedAnswer": {"@@type": "Answer", "text": "Sí, al completar los requisitos del curso puedes descargar tu certificado de finalización desde «Certificados»."}}, {"@@type": "Question", "name": "¿Puedo ver los videos desde el celular?", "acceptedAnswer": {"@@type": "Answer", "text": "Sí. La plataforma funciona en computadora, tableta y celular. Los videos se reproducen en streaming; no se pueden descargar."}}, {"@@type": "Question", "name": "¿Puedo compartir mi cuenta o los videos?", "acceptedAnswer": {"@@type": "Answer", "text": "No. La licencia es personal. Los enlaces de video son temporales y están ligados a tu sesión."}}, {"@@type": "Question", "name": "¿Qué es el tutor IA?", "acceptedAnswer": {"@@type": "Answer", "text": "Es un asistente que te orienta con dudas de la lección que estás viendo. Te da pistas y explicaciones, pero no resuelve tus evaluaciones y puede equivocarse: ante dudas importantes consulta a tu instructor. Solo está disponible si la Academia lo tiene activado."}}, {"@@type": "Question", "name": "Olvidé mi contraseña, ¿qué hago?", "acceptedAnswer": {"@@type": "Answer", "text": "En la pantalla de inicio de sesión elige «¿Olvidaste tu contraseña?» y sigue las instrucciones que te enviaremos por correo."}}, {"@@type": "Question", "name": "Mi pago salió aprobado pero no veo el curso", "acceptedAnswer": {"@@type": "Answer", "text": "Espera unos minutos y recarga «Mis cursos». Si sigue sin aparecer, escríbenos con tu número de orden desde el formulario de contacto."}}]}</script>
  @endpush
</x-front.page>
