# Avisos automáticos por WhatsApp

Horizontia puede enviar por WhatsApp (API oficial de Meta, *WhatsApp Business Cloud API*) tres avisos:

| Aviso | Cuándo | Parámetros de la plantilla |
|---|---|---|
| Compra confirmada | Al aprobarse el pago | {{1}} nombre · {{2}} cursos · {{3}} enlace a «Mis cursos» |
| Sesión en vivo | Cuando se abre la ventana de recordatorio de la sesión | {{1}} nombre · {{2}} sesión · {{3}} fecha y hora · {{4}} enlace al aula |
| Curso presencial | Menos de 24 h antes de la fecha | {{1}} nombre · {{2}} curso · {{3}} fecha y hora · {{4}} lugar |

Solo recibe avisos quien marca la casilla **«Quiero recibir avisos por WhatsApp»** en el pago (queda guardado su número y la fecha en que aceptó). Cada aviso se envía **una sola vez**. Si algo falla (sin configurar, plantilla sin aprobar, error de Meta) la compra y los correos siguen funcionando igual; el error queda en **Reportes › WhatsApp**.

## Configuración (una vez)

1. En [Meta for Developers](https://developers.facebook.com) crea una app de tipo *Business* y agrega el producto **WhatsApp**. Conecta (o registra) el número de tu academia.
2. En *Configuración de la API* copia el **ID del número de teléfono**.
3. Crea un **usuario del sistema** en el Administrador de Negocios, dale acceso a la cuenta de WhatsApp y genera un **token permanente** con el permiso `whatsapp_business_messaging`.
4. En *Plantillas de mensajes* crea y envía a aprobación estas tres (categoría **Utilidad**, idioma Español (México)). Los nombres pueden ser otros; se configuran abajo.

   **horizontia_compra**
   > ¡Hola {{1}}! Gracias por tu compra en Horizontia 🎉 Ya tienes acceso a: {{2}}. Entra aquí: {{3}}

   **horizontia_en_vivo**
   > Hola {{1}}, tu sesión en vivo «{{2}}» empieza pronto: {{3}}. Entra desde el aula: {{4}}

   **horizontia_presencial**
   > Hola {{1}}, te recordamos tu curso presencial «{{2}}»: {{3}}. Lugar: {{4}}. ¡Te esperamos!

5. En **Administración › Ajustes › Integraciones › WhatsApp** activa «Avisos automáticos», pega el ID del número y el token, y escribe el nombre de cada plantilla (y el idioma con el que las aprobaste).
6. Comprueba en **Reportes › WhatsApp** que los primeros avisos salen como *Enviado*.

## Notas

- Los envíos van por la **cola**: en Plesk activa `ACADEMY_QUEUE_VIA_SCHEDULER=true` (ver `docs/plesk.md`).
- Los teléfonos de 10 dígitos se toman como México (+52). Cambia el prefijo con `ACADEMY_WHATSAPP_COUNTRY_CODE`.
- El token es privado: no se incluye al exportar la configuración.
- Costos: Meta cobra por conversación según la categoría de la plantilla; revisa su tarifa vigente.
