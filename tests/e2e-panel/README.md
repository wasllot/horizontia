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
