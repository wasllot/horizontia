# Producción: lista de verificación y operación

Todo lo de esta página se comprueba solo: **Admin → Estado del sistema** o `php artisan academy:preflight`
(código de salida 1 si hay errores; úsalo en tu despliegue). Mientras desarrollas, lo que solo importa en producción sale como aviso.

## 1. Antes de abrir al público
1. Copia `.env.production.example` como `.env` y completa lo marcado con CAMBIAR (`APP_ENV=production`, `APP_DEBUG=false`, HTTPS, cookies seguras).
2. **Cambia la contraseña del administrador inicial** (`admin@amentotech.com`, de fábrica es `google`) y elimina las cuentas `@horizontia.test`.
3. Que `public/` no contenga archivos descargables (`.zip`, `.sql`, `.env*`). Los de este repositorio se movieron a `../_horizontia_cuarentena/`.
   Los scripts de mantenimiento (`run_deploy.php`, `readlog.php`, `scorm_diag.php`…) están protegidos por `DEPLOY_TOKEN` (cerrados si está vacío), pero **bórralos del servidor**: sus tareas existen como comandos artisan.
4. Quita `.env.qa`, `.env.backup` y `.env.docker` del servidor (no están en `public/`, pero no deben viajar al despliegue).

## 2. Tareas automáticas (obligatorias)
Sin ellas **no salen los correos** (confirmación de compra con `.ics`, recordatorios 24 h) ni los respaldos.
- **Worker de colas**: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600` bajo supervisor/systemd (en desarrollo: servicio `queue` de `docker-compose.yml`).
- **Planificador**: cron `* * * * * cd /ruta && php artisan schedule:run >> /dev/null 2>&1` (en desarrollo: servicio `scheduler`).
  Tareas programadas: latidos (cada minuto), recordatorios de sesiones en vivo (15 min) y presenciales (cada hora), respaldo de la base de datos (03:15).
- «Estado del sistema» muestra el último latido de cada uno; si se detienen, aparece en rojo.

## 3. Respaldos
- `php artisan app:backup-database` crea `storage/app/backups/horizontia-AAAAMMDD-HHMMSS.sql.gz` (mysqldump comprimido, permisos 600) y conserva `ACADEMY_BACKUP_KEEP_DAYS` días.
- Con `BACKUP_DISK=r2_private` cada respaldo se copia también a `backups/` en ese disco: **una copia solo en el servidor no es un respaldo**.
- Restaurar: `gunzip -c horizontia-….sql.gz | mysql -u USUARIO -p BASE`. Prueba una restauración en otro entorno al menos una vez.
- Los archivos (videos, imágenes) viven en R2; activa el versionado del bucket si quieres poder deshacer borrados.

## 4. Correo que sí llega
1. Contrata un proveedor transaccional (Resend, Postmark, Amazon SES, Brevo) y pon sus datos en `MAIL_*`.
2. En el DNS de tu dominio publica **SPF**, **DKIM** (te lo da el proveedor) y **DMARC** (`_dmarc` con `v=DMARC1; p=none; rua=mailto:…` para empezar).
3. `MAIL_FROM_ADDRESS` debe ser de ese dominio. «Estado del sistema» comprueba SPF y DMARC.
4. Envía una compra de prueba a Gmail y a Outlook y revisa que no caiga en spam.

## 5. Cobros (Mercado Pago)
Token de producción (`APP-USR-…`), webhook `https://tudominio.com/mercadopago/webhook` y su clave secreta guardada (ver `docs/mercadopago.md`).
Reembolsos: **Admin → Inscripciones → icono de reembolso** (con Mercado Pago llama a su API; con otro medio de pago solo marca el pedido «Reembolsado» y retira el acceso, y el dinero lo devuelves tú). Si reembolsas desde el panel de Mercado Pago, el webhook hace lo mismo solo.
**Inscribir manualmente** da acceso sin pedido (cortesías, transferencias). El pedido reembolsado nunca se borra.

## 5b. Correos de compra y carritos abandonados
- Al aprobarse un pago (o inscribirse a un curso gratis) el alumno recibe `PurchaseConfirmedEmail`; con OXXO/SPEI recibe antes `PaymentPendingEmail` con la ficha de pago.
- `app:send-cart-reminders` (cada hora): primer recordatorio tras 3 h sin actividad en el carrito, segundo 2 días después, nunca un tercero. No escribe a quien ya tiene el curso, está esperando un pago en OXXO/SPEI, tiene la fecha agotada/cancelada o se dio de baja (enlace firmado en cada correo). Apágalo con `ACADEMY_CART_REMINDERS=false`.

## 6. Avisos de errores
- Básico (incluido): `ACADEMY_ALERT_EMAIL` recibe un correo por cada error inesperado distinto, máximo uno por hora.
- Recomendado además: Sentry (`composer require sentry/sentry-laravel`, `php artisan sentry:publish --dsn=…`). No está instalado por defecto.
- `/up` responde 200 si la aplicación arranca: úsalo en tu monitor de disponibilidad (UptimeRobot, Better Stack…).

## 7. Qué se protegió en código
- Las respuestas de los quizzes y el texto de lecciones de pago ya no viajan en la API pública ni en la ficha del curso.
- Los documentos de identidad se guardan en el disco privado con nombre aleatorio y se sirven solo al dueño y a los administradores (`/identidad/{id}/{campo}`); los correos ya no los incrustan.
- Pruebas: `ProductionHardeningTest`, `RefundsAndManualEnrollmentTest`.

## 8. Llevar todo lo nuevo a producción (paso a paso)

1. **Código y archivos**: sube el código completo, incluidos `public/css/` (`frontend-ux.css`, `about.css`, `auth.css`, `page-banner.css`), `public/js/frontend-ux.js` y `public/images/nuevo/` (los banners por defecto `banner-*.jpg/.webp` y las imágenes del inicio viajan con el código, no con la exportación).
2. **Variables de entorno**: compara tu `.env` con `.env.example`. Nuevas: `ACADEMY_CERTIFICATES`, `ACADEMY_COMMUNITY`, `ACADEMY_PWA`, `ACADEMY_CHAT`, `ACADEMY_ACCESS_REQUESTS`, `ACADEMY_PROGRESS_REMINDERS`, `ACADEMY_STUDIO_DAILY_LIMIT`, `ACADEMY_WHATSAPP_COUNTRY_CODE` y `ZOOM_WEBHOOK_SECRET` (solo si usas sesiones en vivo con grabación). Todas tienen un valor por defecto razonable.
3. **Migraciones**: `php artisan migrate --force`. Crean: cupones y canjes, certificados, rutas de aprendizaje, recordatorios de avance, notas y posición en lecciones, búsquedas, comunidad, automatización de WhatsApp, banners de páginas, mensajes (chat) y solicitudes de acceso.
4. **Cachés**: `php artisan optimize:clear` y después `php artisan config:cache route:cache view:cache`.
5. **Configuración desde tu equipo**: en el sitio de desarrollo ve a Administración → Ajustes → *Exportar / importar* y exporta *Páginas, Banners de páginas fijas, Menús, SEO, Ajustes generales, Integraciones y Correo y notificaciones* (y *Cupones* si ya los creaste). En producción impórtalo en modo «solo añadir lo que falta» y revisa la vista previa antes de confirmar.
6. **Cursos y rutas**: los cursos no viajan en la exportación; créalos (o restaura la base de datos) antes de importar *Rutas de aprendizaje* o cupones limitados a cursos.
7. **Credenciales**: Mercado Pago, Gemini, Zoom, WhatsApp (Meta) y almacenamiento se capturan a mano en el sitio de producción (Ajustes). No las exportes.
8. **Verifica**: Administración → Estado del sistema (planificador y cola con latido reciente) y `php artisan academy:preflight`. Revisa `/nosotros`, `/contacto`, `/solicitar-acceso`, `/iniciar-sesion` y una compra de prueba.
