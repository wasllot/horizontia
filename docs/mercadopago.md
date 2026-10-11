# Mercado Pago (Checkout Pro, México / MXN)

Integración en `Modules/LaraPayease/Drivers/MercadoPago.php` usando el cliente HTTP de Laravel (sin SDK).
El monto, la moneda y la orden se leen siempre de la `Order` en base de datos, nunca de parámetros del cliente.

## Flujo

1. Checkout crea la `Order` (pending) y redirige a `/mercadopago/process/payment`.
2. El driver crea la preferencia (`POST /checkout/preferences`) con `external_reference` = id de la orden y redirige a `init_point` (o `sandbox_init_point` si el modo de desarrollo está activo).
3. Mercado Pago notifica a `POST /mercadopago/webhook`. Se valida `x-signature`, se consulta `GET /v1/payments/{id}`, se comparan monto y moneda con la orden y, si `status=approved`, se completa la orden una sola vez (`lockForUpdate`) y se despacha `CompletePurchaseJob`.
4. El navegador vuelve a `GET /mercadopago/retorno?order={id}` (autenticada, solo el dueño de la orden). No completa nada por el query string: consulta el pago en Mercado Pago y, si ya está aprobado y el webhook aún no llegó, usa la misma función idempotente.

Estados de pago: `approved` completa; `pending`/`in_process` (OXXO/efectivo, SPEI o tarjeta en revisión) **no completan pero se atienden**: se guardan las instrucciones, se envía un correo una sola vez (`PaymentPendingEmail`) y el navegador va a `/pago-pendiente/{pedido}` (ficha de pago, vigencia, se actualiza sola); el carrito se vacía para no pagar dos veces y la inscripción llega cuando el webhook avise `approved` (se envía `PurchaseConfirmedEmail`). `rejected`/`cancelled` se registran en el log y la orden queda `pending` para reintentar; `refunded`/`charged_back` retiran la inscripción y dejan el pedido en `refunded` (solo si el `transaction_id` coincide).

**OXXO, SPEI y meses sin intereses** los ofrece Checkout Pro según lo que tenga habilitado tu cuenta de Mercado Pago (Tu negocio → Costos y meses sin intereses). Aquí no hay que activar nada salvo: *modo binario* debe estar **apagado** (apagado = permite pagos pendientes) y, si quieres limitar las mensualidades, el campo opcional *Máximo de mensualidades* (1–24).

## 1. Crear la aplicación

1. Entra a <https://www.mercadopago.com.mx/developers/panel/app> con tu cuenta de Mercado Pago.
2. "Crear aplicación" -> nombre (p. ej. Horizontia), solución de pago "Pagos online", producto **Checkout Pro**.
3. En "Credenciales de prueba" copia el **Access Token** y la **Public Key** de pruebas; en "Credenciales de producción" las de producción (requiere activarlas).

## 2. Configurar en Horizontia

Admin -> Métodos de pago -> Mercado Pago -> Editar:

| Campo | Valor |
|---|---|
| Access token | `TEST-...` (pruebas) o `APP_USR-...` (producción) |
| Public key | Public key de la misma aplicación |
| Clave secreta del webhook | La que genera el panel en el paso 4 |
| Moneda | MXN (ARS, BRL, CLP, COP, PEN, UYU también disponibles) |
| Tipo de cambio | Vacío si la moneda del sitio ya es la del gateway; si no, 1 moneda del sitio = N moneda del gateway |
| Modo desarrollo | Activo en pruebas: redirige a `sandbox_init_point` |
| Modo binario | Si se activa, solo `approved` o `rejected` (sin OXXO ni pagos pendientes) |

Después activa el interruptor del método y, si quieres, márcalo como predeterminado.

## 3. Usuarios de prueba (México)

En el panel de la aplicación -> "Cuentas de prueba" crea dos cuentas de país México: un **vendedor** y un **comprador**.
Usa las credenciales de prueba de la aplicación (o las del usuario vendedor de prueba) en Horizontia y paga con la cuenta compradora de prueba (no con tu cuenta real). Los usuarios de prueba no pueden operar con cuentas reales.

## 4. Registrar el webhook y el secret

1. Panel -> tu aplicación -> **Webhooks -> Configurar notificaciones**.
2. Pestaña "Modo productivo" (y la de pruebas si la usas): URL `https://TU-DOMINIO/mercadopago/webhook` (HTTPS público; en local usa un túnel tipo ngrok y `APP_URL` con esa URL).
3. Evento: **Pagos**. Guarda la configuración: se genera la **clave secreta**; revélala y pégala en "Clave secreta del webhook" en el admin.
4. La preferencia también envía `notification_url` (`https://.../mercadopago/webhook`), que tiene prioridad sobre la URL del panel.

El endpoint responde 401 si falta el secret o la firma no es válida, y 500 si Mercado Pago no responde al consultar el pago (para que reintente). Debe responder en menos de 22 s.

## 5. Tarjetas de prueba (México)

Fuente: documentación oficial de Mercado Pago (Checkout Pro -> Tarjetas de prueba).

| Tipo | Marca | Número | CVV | Vencimiento |
|---|---|---|---|---|
| Crédito | Mastercard | 5474 9254 3267 0366 | 123 | 11/30 |
| Crédito | Visa | 4075 5957 1648 3764 | 123 | 11/30 |
| Crédito | American Express | 3711 803032 57522 | 1234 | 11/30 |
| Débito | Mastercard | 5579 0534 6148 2647 | 123 | 11/30 |
| Débito | Visa | 4189 1412 2126 7633 | 123 | 11/30 |

El resultado lo decide el **nombre del titular**:

| Titular | Resultado |
|---|---|
| APRO | Pago aprobado |
| OTHE | Rechazado por error general |
| CONT | Pendiente de pago |
| CALL | Rechazado, requiere validación para autorizar |
| FUND | Rechazado por importe insuficiente |
| SECU | Rechazado por código de seguridad inválido |
| EXPI | Rechazado por fecha de vencimiento |
| FORM | Rechazado por error de formulario |

(La documentación lista más códigos: CARD, INST, DUPL, LOCK, CTNA, ATTE, BLAC, UNSU, TEST.) La página oficial no indica un valor de documento; si el formulario lo pide, usa uno de prueba cualquiera (p. ej. `123456789`).

## 6. Pagos de prueba y notificaciones

Los pagos de prueba no disparan de forma fiable notificaciones reales al webhook. Para probar el webhook:

1. En Webhooks -> Configurar notificaciones guarda la URL y pulsa **Simular**.
2. Elige la URL (pruebas o producción), el evento **Pagos** y un **Data ID** (el id de un pago real de pruebas que exista en tu cuenta).
3. "Enviar prueba": verás la petición, la firma y la respuesta de tu servidor.

Un Data ID inexistente devuelve 200 sin efectos (el pago no se encuentra en la API). Aunque el webhook no llegue, la ruta de retorno (`/mercadopago/retorno`) confirma el pago consultándolo en la API cuando el comprador vuelve al sitio (`auto_return=approved`).

## 7. Reembolsos

`getGatewayObject('mercadopago')->refund($paymentId, $amount = null)` llama a `POST /v1/payments/{id}/refunds` (total si `$amount` es null). Cuando Mercado Pago notifique `refunded` o `charged_back` se revoca la inscripción. No hay UI de reembolso en el admin.

## 8. Limitaciones conocidas

- `orders.status` no tiene estado de fallido/reembolsado: tras un reembolso la orden sigue `complete` (solo se revoca el acceso al curso). Los fondos pendientes del tutor y las reservas/suscripciones de la orden no se revierten.
- Los reembolsos parciales (`status=approved` con `status_detail=partially_refunded`) no revocan acceso.
- Para probar: `docker exec horizontia-laravel.test-1 bash -c "cd /var/www/html && php artisan test --env=testing tests/Feature/MercadoPagoGatewayTest.php"`.
