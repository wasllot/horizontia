# UX: qué se hizo y cómo mantenerlo

Revisión del 2026-10-11 con navegador real (390 px y 1440 px). Hoja de estilos nueva: `public/css/ux-polish.css` (se carga al final desde `<x-ui-styles />`); las reglas de los paneles van en `public/css/panel.css` (secciones 21–22).

## Hecho
- **Móvil:** título del curso completo (era un `<h2>…</h1>` mal cerrado con `line-clamp: 1`), encabezado de una fila, menú desplegable a todo el ancho, aviso de cookies compacto, sin desplazamiento horizontal, objetivos táctiles de 36–44 px, texto mínimo de 12 px.
- **Acceso:** botón y enlaces con contraste AA (el tema usaba dorado pálido `#F2D07F`), `h1` en login/registro.
- **Estructura:** una sola `h1` por pantalla y títulos de pestaña con sentido (`currentPageTitle()` por ruta; el aula usa el título del curso); enlace «Saltar al contenido»; foco visible; etiquetas en todos los campos.
- **Contenido en español:** `languageLabel()` («Spanish» → «Español»), `dateEs()` («11 oct. 2026»), formato de fecha del sitio `d/m/Y` (lo fija `DemoAcademySeeder`; en producción: Ajustes → General), portadas de demostración de marca (ya no «Find out more about…») y cursos de ejemplo del tema desactivados.
- **Aprendizaje:** «Mis cursos» dice *Comenzar / Continuar: lección X / Repasar curso* y abre en esa lección (`?leccion=ID`, `ResumeService`); el aula retoma sola en la primera lección sin completar; «Siguiente lección» avanza en 8 s con opción de quedarse; estado vacío con botón «Explorar cursos»; meta de certificado («Te falta X%») cuando el curso lo da.
- **Conversión:** barra de compra fija en móvil (aparece cuando el botón principal sale de pantalla), «Curso nuevo» en lugar de «0.0 (0 reseñas)», nota «Paga con Tarjeta · OXXO · SPEI / garantía de reembolso» (solo promete OXXO/SPEI si Mercado Pago está activo y el modo binario apagado).

## Reglas para no perderlo
- Un solo `h1` por pantalla; los textos de tema van en español.
- Botones principales: amarillo `#fed304` con texto `#14213d`. Nunca texto amarillo/dorado sobre blanco: usar `--ux-link` (`#1b4fc4`).
- Mínimo 12 px de texto y 40 px de alto en elementos táctiles; probar a 390 px.
- Pruebas: `tests/Feature/UxPolishTest.php`, `ResumeLearningTest.php`, `ErrorPagesTest.php`.

## Pendiente (no hecho)
- Sugerencias de búsqueda en el catálogo (hoy filtra en vivo, con filtros en panel lateral en móvil).
- Registro dentro del checkout: no aplica mientras el registro esté cerrado (academia privada).
- Unificar por completo los formularios de ajustes del administrador y del perfil (siguen con el estilo del tema).

## Pantalla de Ajustes (`/admin/ajustes`)

Pantalla curada en español, sin el constructor de opciones en inglés del tema. El catálogo vive en `app/Support/SettingsRegistry.php` (páginas: Sitio, Formatos, Archivos, Redes sociales, Correo, Integraciones, Tutor con IA, Acceso con Google, Scripts y estilos, Mantenimiento); `SettingsController` guarda **solo los campos de la página abierta** y nunca toca claves que la pantalla no ofrece. Para añadir un ajuste: declararlo en el registro (reutiliza la definición de `app/Optionbuilder/*.php` con textos en español) y leerlo con `setting('_seccion.clave')`. Correo y Acceso con Google reescriben además el `.env` al guardar (`admin.update-smtp-settings`, `admin.update-social-login-settings`). Pruebas: `SettingsScreenTest`, E2E «ajustes del admin».

## Capa de UX del sitio público (`frontend-ux`)

- `public/css/frontend-ux.css` + `public/js/frontend-ux.js`, cargados con `<x-frontend-ux />` desde `layouts/guest` y `layouts/frontend-app`.
- Incluye: scroll suave, barra de progreso de lectura, encabezado fijo con sombra (`body.ht-scrolled`), aparición al scroll (`.ht-reveal` → `.is-in`), contadores (`data-count`), elevación de tarjetas, microinteracciones de botones, anillos de foco y fundido de imágenes diferidas. Respeta `prefers-reduced-motion`.
- Dentro de componentes Livewire **no** uses `.ht-reveal` (el morph quitaría `.is-in`); usa `.ht-rise`, una animación solo CSS. El JS ya marca como visibles los `.ht-reveal` que encuentre dentro de `[wire:id]`.
- Banners de páginas estáticas: `PageBannerService::SYSTEM` define título, subtítulo e imagen por defecto (`public/images/nuevo/banner-*.jpg`); se editan en Admin → Banners de páginas.

## Páginas con vista propia: Contacto y Nosotros

- `/contacto` y `/nosotros` son vistas Blade (no páginas del editor visual). Su banner (título, subtítulo, imagen) se edita en Admin › Banners de páginas porque están en `PageBannerService::SYSTEM`; las imágenes por defecto son `public/images/nuevo/banner-*.jpg` (generadas con Gemini, 21:9).
- `PageController::DEDICATED` lista las rutas de `PAGES` que tienen vista propia: no se ofrecen en Admin › Páginas ni las recrea `academy:pages`.
- `/nosotros` (`resources/views/frontend/nosotros.blade.php` + `public/css/about.css`) incluye un recorrido de 5 pasos con mockups hechos solo con HTML/CSS (`.mk*`), accesibles como pestañas (Alpine) con autoavance que se pausa al pasar el cursor. Los mockups usan datos de ejemplo y lo dicen. Las funciones opcionales (comunidad, PWA, solicitud de acceso) solo se muestran si están activas.
- Pantallas de acceso (`x-auth-card` + `public/css/auth.css`): panel de marca con mockup de avance, barra «Volver al inicio / ¿Necesitas ayuda?» y formulario con foco dorado.
