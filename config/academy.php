<?php

/**
 * Datos que alimentan las páginas legales y de información.
 * Los textos legales son una base razonable para México, pero DEBEN ser revisados por un asesor legal.
 */
return [
    // true = academia privada: se apagan reservas de tutores, perfiles de tutor, retiros y comisiones
    // Usos diarios de la IA en el Estudio de contenido por persona (0 = sin límite).
    'studio_daily_limit' => (int) env('ACADEMY_STUDIO_DAILY_LIMIT', 40),
    // Comunidad (preguntas y respuestas) dentro de cada curso (ACADEMY_COMMUNITY=false la apaga).
    'community'     => (bool) env('ACADEMY_COMMUNITY', true),
    // Código de país que se antepone a teléfonos de 10 dígitos para WhatsApp.
    'whatsapp_country_code' => env('ACADEMY_WHATSAPP_COUNTRY_CODE', '52'),
    // Página «Solicitar acceso» (nadie se registra solo: el equipo aprueba). ACADEMY_ACCESS_REQUESTS=false la oculta.
    'access_requests' => (bool) env('ACADEMY_ACCESS_REQUESTS', true),
    // Mensajes entre alumnos, instructores y soporte (ACADEMY_CHAT=false los apaga).
    'chat'          => (bool) env('ACADEMY_CHAT', true),
    // App instalable (PWA): manifiesto, service worker y página sin conexión (ACADEMY_PWA=false la apaga).
    'pwa'           => (bool) env('ACADEMY_PWA', true),
    // Certificados verificables al terminar un curso en línea (ACADEMY_CERTIFICATES=false para apagarlos).
    'certificates'  => (bool) env('ACADEMY_CERTIFICATES', true),
    'private_mode'  => (bool) env('ACADEMY_PRIVATE_MODE', false),
    'legal_name'    => env('ACADEMY_LEGAL_NAME', env('APP_NAME', 'Horizontia')),
    'contact_email' => env('ACADEMY_CONTACT_EMAIL'),
    'address'       => env('ACADEMY_ADDRESS'),
    'jurisdiction'  => env('ACADEMY_JURISDICTION', 'México'),
    'refund_days'   => (int) env('ACADEMY_REFUND_DAYS', 7),
    // Zona horaria de las fechas de cursos presenciales (se muestra la hora de la sede, no la del servidor)
    'timezone'      => env('ACADEMY_TIMEZONE', 'America/Mexico_City'),
    // Instalación limpia (ProductionSeeder): el administrador inicial. Sin contraseña se genera una aleatoria y se muestra una sola vez.
    'admin_email'      => env('ACADEMY_ADMIN_EMAIL'),
    'admin_password'   => env('ACADEMY_ADMIN_PASSWORD'),
    // Operación (se leen aquí y no con env() para que funcionen con `php artisan config:cache`)
    'alert_email'      => env('ACADEMY_ALERT_EMAIL'),                       // recibe un correo por cada error inesperado (máx. 1/hora por error)
    'error_alerts'     => (bool) env('ACADEMY_ERROR_ALERTS', false),        // forzar los avisos de error también fuera de producción
    'backup_disk'      => env('BACKUP_DISK'),                               // copia de cada respaldo fuera del servidor (p. ej. r2_private)
    'backup_keep_days' => (int) env('ACADEMY_BACKUP_KEEP_DAYS', 14),
    // Avisos «retoma tu curso» a quien dejó de estudiar (ACADEMY_PROGRESS_REMINDERS=false los apaga).
    'progress_reminders' => (bool) env('ACADEMY_PROGRESS_REMINDERS', true),
    'cart_reminders'   => (bool) env('ACADEMY_CART_REMINDERS', true),
    // Hosting sin proceso permanente (Plesk/compartido): el planificador procesa la cola cada minuto en vez de un worker
    'queue_via_scheduler' => (bool) env('ACADEMY_QUEUE_VIA_SCHEDULER', false),
    // Ultima revision de los textos legales (se muestra en cada pagina)
    'legal_updated_at' => env('ACADEMY_LEGAL_UPDATED_AT', '2026-10-09'),
];
