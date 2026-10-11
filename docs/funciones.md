# Funciones de la academia (guía rápida)

Todas se pueden apagar con una variable del `.env` (ver `.env.example`). Cada una tiene pruebas automáticas.

| Función | Dónde | Para quién |
|---|---|---|
| Cupones | Admin › Finanzas › Cupones (`/admin/cupones`) | Admin; el alumno lo aplica en el pago |
| Certificados | `/estudiante/certificados`, `/certificado/{código}`, `/verificar-certificado`, Admin › Certificados | Alumno / público / admin |
| Rutas de aprendizaje | Admin › Rutas de aprendizaje; público `/rutas` y `/ruta/{slug}` | Admin / alumno |
| Recordatorios de avance | Automático (correo) | Alumno |
| Notas, marcadores y retomar | Aula › pestaña «Mis notas»; el video recuerda el minuto | Alumno |
| Grabación de sesiones en vivo | Automático al terminar la sesión con grabación | Instructor / alumno |
| App instalable (PWA) | Automático en todo el sitio | Todos |
| Reportes | Admin › Reportes (`/admin/reportes`) | Admin |
| Estudio de contenido (IA) | Admin y Instructor › Estudio de contenido | Instructor / admin |
| Búsqueda y recomendaciones | `/cursos`, Mis cursos, página del curso | Todos |
| Comunidad | Aula › pestaña «Comunidad»; Admin › Comunidad | Alumno, instructor, admin |
| WhatsApp automático | Ajustes › Integraciones › WhatsApp (ver `docs/whatsapp.md`) | Admin |

## 1. Cupones
- Porcentaje o monto fijo, con compra mínima, usos totales y por persona, vigencia, «solo primera compra» y limitado a ciertos cursos.
- **Solo descuentan cursos sueltos**: las líneas de un paquete ya traen su descuento y no se acumulan.
- El código se valida en el servidor en cada cálculo del carrito (si se pausa, vence o se agota deja de aplicar). El pedido guarda el código y el descuento; el uso se cuenta **cuando el pedido se paga** (o es gratis), una sola vez aunque el webhook se repita.
- Un cupón de 100 % deja el pedido en 0 y se inscribe sin pasar por Mercado Pago.

## 2. Certificados verificables
- Se emiten solos al terminar un curso en línea (99 % cuenta como terminado, igual que el aula). Cada uno tiene un código `HZ-XXXX-XXXX-XXXX`.
- Página pública con QR para imprimir o guardar en PDF desde el navegador; `/verificar-certificado` comprueba un código. El admin puede **revocar** (la página lo muestra como revocado).
- El certificado guarda una copia del nombre y del curso del día de emisión.
- Quienes ya terminaron antes de esta función: `php artisan academy:issue-certificates`.
- Apagar: `ACADEMY_CERTIFICATES=false`.

## 3. Rutas de aprendizaje
Cursos en un orden recomendado, con avance por persona y **certificado de la ruta** al terminar todos. No bloquean el acceso ni venden por sí solas: los cursos se compran sueltos o en un paquete. Para mostrarlas en el menú, agrega `/rutas` desde Admin › Menús.

## 4. Recordatorios de avance
Correo a quien dejó de estudiar un curso en línea: a los **5 días** de silencio, luego a los 7 y a los 14 (máximo 3 seguidos; si la persona vuelve, el contador se reinicia). Solo entre 9:00 y 20:00 (hora de la academia), un correo por persona con hasta 3 cursos y baja en un clic. Comando: `php artisan app:send-progress-reminders --dry-run` para ver a quién se enviaría. Apagar: `ACADEMY_PROGRESS_REMINDERS=false`.

## 5. Notas, marcadores y retomar
Cada alumno tiene sus notas por lección (con el minuto del video si lo hay) y marcadores; solo él las ve. El video guarda el minuto cada 10 s y al pausar, y al volver salta ahí (solo videos MP4/R2).

## 6. Grabaciones de sesiones en vivo
Cuando una sesión ya terminó y tiene enlace de grabación, se publica sola como lección «Grabación: …» en la misma sección (YouTube y Vimeo se incrustan; otros enlaces salen como botón). Quien termina la grabación cumple la sesión en vivo. Sin trabajo manual con **Zoom**: en tu app de Zoom activa el evento `recording.completed` apuntando a `https://TU-DOMINIO/zoom/webhook` y pon `ZOOM_WEBHOOK_SECRET` en el `.env`; la reunión se reconoce por el número del enlace `…/j/123456789`. Se revisa cada 10 minutos (`app:publish-live-recordings`).

## 7. App instalable (PWA)
Manifiesto, iconos y service worker (solo funciona con HTTPS). Las páginas **nunca** se guardan sin conexión (pueden ser privadas): sin internet se muestra «Estás sin conexión»; solo se guardan archivos estáticos (css, js, imágenes). Si cambias la lógica de `public/js/sw.js`, sube `VERSION`. Apagar: `ACADEMY_PWA=false`.

## 8. Reportes
Ventas (con conversión de pedidos y reembolsos), aprendizaje (finalización por curso y **dónde abandonan** por lección), cupones, búsquedas (lo que buscan y no encuentran) y WhatsApp. Exporta CSV de ventas, cursos y aprendizaje.

## 9. Estudio de contenido
Resumen, preguntas de examen (se crean como lección de test, sin vista previa), descripción de venta y esquema de curso, con la misma clave de Gemini del tutor IA. El texto que pegas se trata como datos, el instructor solo trabaja con sus cursos y nunca se lee el contenido de un test (guarda las respuestas). Límite: `ACADEMY_STUDIO_DAILY_LIMIT` por persona al día.

## 10. Búsqueda y recomendaciones
La búsqueda ignora acentos, plurales y errores de tecleo, pesa más el título y sugiere «¿Quisiste decir…?». «Otros alumnos también tomaron» y «Recomendados para ti» usan alumnos en común y la categoría; nunca sugieren lo que ya tienes.

## 11. Comunidad
Preguntas y respuestas por curso para quien tiene acceso y su instructor: solución marcada, fijar, reportar, límites contra spam (4 por minuto, 40 al día). El instructor recibe como máximo un correo por hora por curso. Apagar: `ACADEMY_COMMUNITY=false`.

## 12. WhatsApp automático
Ver `docs/whatsapp.md`.
