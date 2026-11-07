# E2E de los paneles

Batería Playwright (sin dependencias nuevas: usa `playwright-core` de `tests/e2e-stagehand`).

```bash
node --test tests/e2e-panel/
```

Variables: `E2E_BASE_URL` (por defecto `http://localhost:8000`), `E2E_CHROME` (ruta al Chrome de Playwright).
Necesita la demo sembrada (`DemoAcademySeeder`) y el contenedor `horizontia-laravel.test-1` para crear datos "en vivo".
Las capturas quedan en `tests/e2e-panel/screenshots/` (ignorada por git).

Flujos largos:

```bash
node --test --test-reporter=spec tests/e2e-panel/course-wizard.e2e.mjs
```

Crea un curso de punta a punta (instructora → todos los tipos de lección con `fixtures/` → revisión → publica el admin → la alumna lo cursa) y borra lo que creó al terminar.

## Compra de entradas con Mercado Pago simulado (Astrofest)

```bash
node --test --test-reporter=spec tests/e2e-panel/event-mp.e2e.mjs
```

Recorre en navegador real la compra de entradas con **Checkout Pro · API de Preferences** contra un Mercado Pago simulado (`mp-mock.mjs`): tarjeta aprobada con correo y QR,
webhook repetido y firma falsa, pago rechazado y reintento, efectivo pendiente que luego se aprueba, monto alterado, confirmación sin webhook (el sitio lee el pago al volver),
último lugar con dos compradores a la vez, celular y el CSV del admin. Necesita en el `.env` (solo local, quítalo al terminar):

```
MERCADOPAGO_API_URL=http://127.0.0.1:5173
ACADEMY_EVENT_CHECKOUT_PER_MINUTE=200
ACADEMY_EVENT_CART_PER_MINUTE=200
```

El simulador corre dentro del contenedor (puerto 5173, ya publicado por Sail) y la prueba lo arranca y lo detiene sola. La pasarela y el evento se configuran para la prueba y se restauran al final.

