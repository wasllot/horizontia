# Editor visual de páginas (GrapesJS)

Reemplaza al constructor de bloques antiguo (larabuild). Admin → **Páginas** (`/admin/paginas`).

## Por qué GrapesJS
Comparé las opciones libres actuales: **GrapesJS** (BSD-3, 0.23.6, agnóstico de framework, HTML/CSS portable, bloques, gestor de estilos, capas, activos, plugins), **Puck** (MIT, solo React y exporta árbol React), **Craft.js** (solo React, sin UI de editor, poco mantenido) y **Silex** (aplicación aparte, AGPL, no embebible). Los paquetes Laravel que envuelven GrapesJS (`hansschouten/laravel-pagebuilder`, `streats22/laragrape` —requiere Filament—, `webkleur/builder`, `lamoud/laravel-grapesjs`) están desactualizados, atados a otro CMS o a Filament, así que se integró GrapesJS directamente: ~300 líneas de JS y un controlador, sin dependencias nuevas de Composer/npm.
Fuentes: gjs.market (comparativas 2026), npmjs/jsDelivr (versiones y licencias), packagist/laraplugins (paquetes Laravel).

## Piezas
| Archivo | Qué hace |
|---|---|
| `public/vendor/grapesjs/**` | GrapesJS 0.23.6 + plugins BSD/MIT (preset-webpage, blocks-basic, forms, navbar, tabs, custom-code, countdown, style-bg, touch, tooltip, parser-postcss) y locale `es`. Todo local: el editor no carga nada de terceros. |
| `resources/views/pages/editor.blade.php`, `public/js/page-editor.js`, `public/css/page-editor.css`, `page-editor-canvas.css` | Pantalla del editor: barra propia (guardar, deshacer, dispositivos, versiones, ajustes), bloques de la academia, componente dinámico, tema azul marino/amarillo. |
| `app/Http/Controllers/Admin/PageEditorController.php` | Cargar/guardar proyecto, ajustes de página (nombre, título, descripción, URL, estado), versiones (últimas 25), subida de imágenes. Solo admin. |
| `app/Livewire/Pages/Admin/Pages/PagesManager.php` | Lista: buscar, filtrar, crear, duplicar, publicar/despublicar, eliminar (las páginas del sistema no se pueden eliminar). |
| `app/Services/Pages/PageHtmlSanitizer.php` | Limpia HTML/CSS antes de guardar: sin scripts, estilos incrustados, manejadores `on*`, URLs peligrosas ni iframes fuera de YouTube/Vimeo/Maps. |
| `app/Services/Pages/PageContentRenderer.php` | Al publicar: tokens `[[company]]`…, bloques dinámicos (`data-hz-block="courses|course-info"`) y JSON-LD `FAQPage` a partir de cualquier acordeón. |
| `app/Services/Pages/LegacyPageConverter.php`, `php artisan pages:to-editor` | Convierte páginas del constructor antiguo a HTML editable (conserva sus `settings` por si hay que volver atrás). `academy:pages` ya crea las páginas del sistema en formato visual. |
| Tablas | `pagebuilder__pages` (+ `gjs_project`, `gjs_html`, `gjs_css`, `edited_at`) y `page_revisions`. |

## Uso
- **Nueva página** crea un borrador y abre el editor. Arrastra bloques de *Academia* (encabezado, texto, imagen+texto, pasos, FAQ, CTA, cifras, testimonios, precios, video, cursos destacados, datos del curso), *Estructura*, *Contenido* y *Formularios*.
- **Guardado**: automático (2,5 s tras cambiar), Ctrl+S o botón. *Versiones* restaura cualquiera de las últimas 25.
- **Ajustes**: nombre, título, descripción, URL (se rechazan prefijos reservados de la aplicación) y estado. SEO avanzado (imagen social, indexación, JSON-LD propio) en Admin → SEO.
- **Bloques dinámicos**: *Cursos destacados* (cantidad/categoría/título) y *Datos y compra del curso* (solo en páginas asociadas a un curso) se guardan como marcadores y se renderizan en el servidor con datos vivos.
- Las páginas asociadas a un curso (`Admin → Páginas de curso`) siguen funcionando igual.

## Pruebas
`tests/Feature/PageEditorTest.php` (acceso, API, versiones, sanitización, bloques dinámicos, JSON-LD, subidas, lista, conversión) y el E2E `tests/e2e-panel` *«editor visual»* (crea, edita, guarda, publica y verifica la página pública).
