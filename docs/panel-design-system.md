# Sistema de diseño de los paneles (admin · instructor · alumno)

Un solo shell y una sola hoja de estilos hecha a medida para los tres roles. Colores de marca: azul marino `#14213d`
y amarillo `#fed304`; tipografía **Inter** autoalojada (variable, `public/fonts/inter`, licencia OFL).

## Piezas
| Archivo | Qué hace |
|---|---|
| `public/css/panel.css` | Tokens (`--hz-*`), base, shell, componentes y pantallas. Todo bajo `body.hz`. Sin `@import`, sin colores sueltos (solo tokens), `!important` solo para ocultar y `prefers-reduced-motion`. |
| `public/css/vendor/theme-*.css` | **Generados** por `php artisan panel:build-theme`: copia del CSS del tema sin los `!important` visuales (color, fondo, borde, espaciado, tipografía) y sin Google Fonts. El sitio público sigue usando los originales. |
| `public/js/panel-compat.js` | Puente del tema con la navegación asíncrona (ver abajo). Se carga justo después de jQuery. |
| `public/js/panel.js` | Sidebar colapsable, cajón móvil, grupos, menús emergentes, SPA, pulso en segundo plano, refresco de listados, barra de progreso. |
| `resources/views/components/panel/*` | `icon`, `topbar-actions` (idioma, carrito, notificaciones, mensajes, cuenta), `language`, `avatar-img`. |
| `resources/views/livewire/admin/sidebar.blade.php`, `livewire/pages/common/navigation.blade.php`, `livewire/header/header.blade.php` | Sidebar de admin, sidebar de instructor/alumno y topbar. |
| `app/Support/PanelLive.php`, `PanelPulseController` | Qué pantallas se refrescan solas y el endpoint `/panel/pulso`. |

## Convenciones
- BEM con prefijo `hz-` (`hz-nav__link`, `hz-btn--secondary`). Los componentes nuevos se escriben con `hz-*`; las clases del tema (`am-*`, `tb-*`, `op-*`, `cr-*`) solo se re-estilizan en las secciones «puentes» de `panel.css`.
- Estado en clases (`is-active`, `is-open`) y ARIA (`aria-current`, `aria-expanded`); nada de estilos en línea en el shell.
- Movimiento: `--hz-ease` y `--hz-t`/`--hz-t-fast`; todo se reduce con `prefers-reduced-motion`.
- Contraste mínimo AA: lo verifica `PanelDesignSystemTest` sobre los pares de tokens.
- La clase `hz-collapsed` / `hz-nav-open` vive en `<html>` (sobrevive al cambio de `<body>` de la navegación asíncrona) y el estado guardado se lee en `<head>` para no parpadear.

## Asíncrono y tiempo real
1. **Navegación sin recargas**: los enlaces internos del panel (`/admin`, `/tutor`, `/estudiante`, `/mis-cursos`, `/gestionar-cursos`, `/crear-curso`, `/curso/editar`) usan `Alpine.navigate` de Livewire. Descargas, cierre de sesión, páginas públicas, el aula y el messenger siguen con carga normal. Un enlace puede excluirse con `data-no-spa`.
2. **Scripts idempotentes**: las librerías del layout llevan `data-navigate-once`. `panel-compat.js` ejecuta de inmediato los `DOMContentLoaded`/`load` registrados tras la carga, retira los listeners de pantallas anteriores y sustituye (no acumula) los handlers delegados de jQuery con la misma fuente. Al escribir scripts de pantalla: sin estado global que dependa de cargar una sola vez, y comprueba `if (!el.noUiSlider)`-style antes de inicializar plugins.
3. **Pulso** (`GET /panel/pulso`, JSON, `no-store`): cada 15 s mientras la pestaña esté visible (espera creciente si falla) actualiza los badges de notificaciones y mensajes y refresca la lista de notificaciones cuando llegan nuevas.
4. **Listados en vivo**: `PanelLive::SCREENS` declara qué pantallas (solo lectura: dashboards y listados) se re-renderizan solas cada N segundos con `data-hz-live`. Nunca formularios; se omite si hay un modal abierto, un menú abierto o un campo con foco.
5. Cambios de idioma, guardados y acciones ya son peticiones Livewire/AJAX; el selector de idioma refresca la vista sin recargar a mano.
   Websockets: `laravel/reverb` está instalado; si se activa Echo, el pulso puede sustituirse por `echo-private:` sin tocar la UI (los badges y `notifications-refresh` ya son el punto de enganche).

## Diseño tonal (v2): sin contornos marcados
Investigación aplicada (guías de tablas de datos y de formularios de Material, Queensland DS, Toyota DX y Smashing): la jerarquía se expresa con **tono y sombra suave**, no con líneas; los campos rellenos reducen el peso visual cuando hay muchos juntos; las acciones frecuentes van visibles y las raras agrupadas; una barra de acciones pegada solo en formularios largos y con borde/sombra superior; y las tablas se degradan a tarjetas «etiqueta: valor» en móvil.

- **Tokens** (`panel.css` §2): `--hz-field` / `--hz-field-hover` (campos y píldoras), `--hz-tint` / `--hz-tint-hover` (bloques y filas), `--hz-hairline` (separadores casi invisibles), `--hz-shadow-card` (elevación de tarjeta).
- **Tarjetas**: una por pantalla, con sombra; las cajas anidadas pasan a tono (`--hz-tint`) y sin sombra. Excepciones por `:not(.cr-create-course)` y `:has(> .builder-container)`.
- **Campos**: rellenos con borde transparente; al enfocar se aclaran y aparece el anillo. Un solo selector (select2) con flecha propia; interruptores unificados (`.tb-switchbtn`, `.cr-toggle`, `.tb-paymethod-items .checkbox`).
- **Tablas** (§14.1): `table:has(> thead)` → cabecera ligera + cada fila es un bloque tonal con hover; filas de una línea con elipsis y desplazamiento dentro del bloque si no caben; `panel.js` (`labelTables`) pone `data-label` en cada celda (también tras repintados de Livewire) y a ≤820 px las filas pasan a tarjetas. `BuildPanelTheme` elimina los anchos fijos por columna del tema. Piezas Blade: `<x-panel.user-cell>`, `<x-panel.badge tone>`, `<x-panel.action icon label tone>`; barra de listado `.hz-toolbar`.
- **Menú lateral**: ítems agrupados con título (`Academia · Contenido · Negocio · Sistema` en admin; `Enseñanza/Aprendizaje · Mi cuenta` en instructor y alumno), cada ítem de una línea; en modo colapsado los títulos pasan a separadores. Cada ítem declara `group` (admin) o se agrupa por ruta (`Navigation::groupMenu`).
- **Asistente de cursos**: `public/css/course-wizard.css` (cargado solo ahí; sin `<style>` en las vistas): stepper superior discreto con progreso (`cr-stepper`), contenido a todo el ancho en dos columnas (`cr-cols`), constructor maestro-detalle de lecciones, barra de acciones fija inferior y lista de verificación de publicación. Análisis y arquitectura: `docs/course-module-audit.md`. Listado de cursos del admin: `hz-coursecell` (portada + título en 2 líneas), `hz-twoline`, `x-panel.badge|user-cell|action`.
- **Primitivas** (§14.7): `.hz-pagehead`, `.hz-empty`, `.hz-callout`, `.hz-overlay/.hz-dialog`, `.hz-fgrid`, `.hz-field`, `.hz-panel`; úsalas en lugar de estilos en línea (las sesiones en vivo ya migraron).
- Regla: ninguna vista del panel lleva colores en `style=""`; `PanelTonalUiTest` lo vigila en las pantallas migradas.

## Pruebas
- `tests/Feature/PanelTonalUiTest.php`: menú agrupado, estilos tonales, tablas en bloques/tarjetas, indicadores `wire:loading` ocultos, tema sin anchos fijos, componentes de fila, estados traducidos, asistente sin CSS en línea y pantallas de sesiones en vivo sin colores sueltos.
- `tests/Feature/PanelDesignSystemTest.php`: calidad del CSS (llaves, tokens definidos, sin colores sueltos, `!important` acotado, tamaño, contraste AA, fuente), tema generado al día, shell accesible y sin restos del diseño antiguo en los 3 roles, scripts «una sola vez», pulso y `PanelLive`.
- `node --test tests/e2e-panel/` (Playwright, ver `tests/e2e-panel/README.md`): por rol, shell/landmarks/Inter/sin desbordes, **navegación asíncrona por todo el menú sin recarga ni errores de JS**, atrás/adelante, colapso persistente, menú de cuenta con teclado, contraste, cajón móvil (390 px); y flujos: badge de notificaciones **en tiempo real**, listado de usuarios que se **actualiza solo**, perfil del alumno y ajustes del admin guardando **sin recargar**.
- Al cambiar `public/admin/css/main.css` o `public/css/main.css`: `php artisan panel:build-theme` (la suite falla si los generados están viejos).

## Pendiente conocido
- `public/css/horizontia-ui.css` conserva las secciones de panel que aún usan el aula (player) y el layout del módulo Courses; migrarlas a `panel.css` permitiría borrarlas.
- Las pantallas con vistas muy antiguas del tema (algunas tablas anidadas, formularios del aula) se re-estilizan por «puentes»; conviene migrarlas poco a poco a componentes `hz-*`.
- Modo oscuro: los tokens están preparados (todo usa variables) pero no hay tema oscuro.
