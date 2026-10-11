# Horizontia: diagnóstico y hoja de ruta

Base: Lernen (Amentotech, Laravel 11 + Livewire 3) con módulo `Courses`. Nació como **marketplace de tutores**
(reservas por sesión, comisiones, wallet, retiros); el objetivo ahora es una **academia privada que vende cursos**.

## Estado (fase 1, hecha y probada)
| Área | Antes | Ahora |
|---|---|---|
| Checkout web | `updateInfo()` lanzaba excepción (regla `amount` sin propiedad) y el monto era editable desde el navegador | funciona; el total se recalcula en servidor y las propiedades de dinero están bloqueadas |
| Checkout API | `amount` venía del request | se cobra el total del carrito |
| Stripe | el *secret key* viajaba al navegador y `/payease/stripe` aceptaba monto/moneda del cliente | solo clave pública al navegador; monto y orden salen de la sesión del servidor |
| Mercado Pago | no existía | Checkout Pro (MXN), webhook firmado e idempotente, consulta del pago a la API, reembolso/contracargo revoca acceso (`docs/mercadopago.md`) |
| Videos de pago | URL pública permanente (`/storage/...`) | URL firmada y caducable; Range/seeking; R2 con URL prefirmada |
| Acceso a cursos | `setActiveCurriculum` aceptaba cualquier `media_path`; `nextCurriculum` cargaba lecciones de cualquier curso; una inscripción reembolsada seguía dando acceso | solo lecciones del curso, inscripción activa (`CourseAccessService`) |
| Quiz | la nota la enviaba el navegador | se calcula en servidor |
| Almacenamiento | solo disco local, rutas con `storage_path()` | discos `r2` / `r2_private`, `storage:copy`, SCORM sobre R2 (`docs/r2-storage.md`) |
| Scripts en `public/` | `run_deploy.php` ejecutaba `migrate`/`mysqldump` sin autenticación | exigen `DEPLOY_TOKEN` (fallan cerrado) |
| SCORM | reemplazar un paquete dejaba archivos viejos | se limpia la carpeta antes de extraer |

Pruebas: 145 pasan (`PaidContentSecurityTest`, `MercadoPagoGatewayTest`, `R2StorageTest`, `DemoAcademyFlowsTest` + las preexistentes).
Datos demo: `php artisan db:seed --class=DemoAcademySeeder` (5 cursos, todos los tipos de lección, estudiantes en cada estado de compra).

## Pendiente de seguridad / operación → hecho el 2026-10-11 (ver `docs/produccion.md`)
Comprobación automática: Admin → *Estado del sistema* o `php artisan academy:preflight`.
1. (hecho) Los `.zip` salieron de `public/` y del módulo (a `../_horizontia_cuarentena/`); el preflight falla si reaparece algo descargable. Quedan los scripts de mantenimiento protegidos por token: bórralos del servidor.
2. (plantilla) `.env.production.example`; el preflight marca `APP_DEBUG`, `APP_ENV`, HTTPS y cookies seguras (error en producción, aviso en desarrollo). Telescope no está registrado.
3. (hecho) Documentos de identidad en el disco privado con nombre aleatorio y ruta autenticada; ya no se incrustan en correos.
4. (hecho) Las respuestas de los quizzes y el texto de lecciones de pago no salen en la API pública ni en la ficha del curso (`CurriculumsResource`, `course-detail`).
5. (resuelto) `updateProgress` del API valida inscripción.
6. (hecho) Servicios `queue` y `scheduler` en docker-compose, latidos visibles en «Estado del sistema», respaldo diario `app:backup-database` (con copia a `BACKUP_DISK`), aviso de errores por correo (`ACADEMY_ALERT_EMAIL`).
7. (hecho) `orders.status` admite `refunded`; Admin → Inscripciones: icono de reembolso (Mercado Pago o marca manual) e *Inscribir manualmente*. El pedido reembolsado se conserva.
Sigue pendiente: proveedor de correo real + DNS (SPF/DKIM/DMARC), Sentry opcional, revisión legal de los textos, cambiar la contraseña del administrador de fábrica.

## Fase 2: modo "academia privada" (hecha)
`ACADEMY_PRIVATE_MODE=true` (ya activo en tu `.env` de desarrollo; por defecto `false` para no romper instalaciones existentes).
- Apaga: búsqueda y perfiles de tutor, reservas, favoritos, disputas, retiros, comisiones, billetera en el checkout, materias/grupos de materias; también sus rutas API y entradas de menú.
- Los tutores entran a «Mis cursos» (instructor) y los estudiantes a «Mis cursos» (alumno). Facturas e historial de compras se conservan.
- Pendiente: roles/registro de instructores cerrado, inscripciones manuales y reembolsos desde el panel admin (ya existe `MercadoPago::refund()`).

## Fase 3: tutor IA con Gemini (hecha)
Admin → *Ajustes → API → Tutor IA (Gemini)*: interruptor, clave, modelo (por defecto `gemini-3.5-flash-lite`), límite diario y instrucciones extra.
**Sin interruptor y clave el widget no se renderiza.** Solo lo ven usuarios con acceso al curso. La clave que tienes en `.env` (`GEMINI_APY_KEY`, mal escrita) **no se usa**: pégala en el panel.
Guardarraíles: método socrático, el contenido de quizzes nunca se envía a Gemini, historial no manipulable desde el navegador, 6 mensajes/min y límite diario por alumno, no se registran mensajes ni claves.
Pendiente: transcripciones de video como contexto (hoy solo artículos, temario y descripción), RAG para cursos grandes y panel de uso.

## Fase 4: frontend y páginas (parcial)
Hecho: Términos, Aviso de privacidad (LFPDPPP), Reembolsos, Cookies, FAQ (con datos estructurados), Nosotros y Cómo funciona; footer con enlaces legales; banner de cookies; `sitemap.xml` y `robots.txt` dinámicos; portada con texto de academia y carrusel de cursos corregido (filtraba un estado inexistente y nunca mostraba nada).
Los textos legales son una base para México: **revísalos con un asesor legal**. Datos de la empresa: `ACADEMY_LEGAL_NAME`, `ACADEMY_CONTACT_EMAIL`, `ACADEMY_ADDRESS`, `ACADEMY_REFUND_DAYS`.
Pendiente: el footer y los datos de contacto siguen con valores de plantilla (teléfono, correo y dirección de ejemplo salen de *Ajustes*); las miniaturas demo traen la marca «Lernen» del tema; `home-static` y `tutor-detail` siguen con estilos en línea; pulir ficha de curso, carrito y checkout; correos transaccionales en español; certificados.


## URLs en español (hecho)
Todas las páginas de usuario viven en español: `/cursos`, `/curso/{slug}`, `/aula/{slug}`, `/mis-cursos`, `/finalizar-compra`, `/gracias/{id}`, `/iniciar-sesion`, `/estudiante/...`, `/tutor/...`, `/gestionar-cursos`, `/crear-curso`, `/admin/usuarios`, `/admin/cursos`, etc. Los **nombres de ruta no cambiaron** (`route('login')` sigue igual), así que todos los enlaces generados se actualizaron solos.
- Cada URL antigua en inglés responde **301** a la nueva (`routes/legacy.php`, 95 rutas), conservando parámetros y `?query`. Excepción: enlaces de verificación de correo ya enviados (su firma incluye la ruta antigua; basta pedir uno nuevo).
- Se dejaron en inglés a propósito (están registrados fuera de la app o los usa JS/paquetes): `/api/*`, webhooks (`/mercadopago/webhook`, `/payfast/webhook`), callbacks OAuth (`/auth/{proveedor}`, `/google/callback`), `/scorm/progress`, `/admin/pages*` (page builder), `/admin/translations*`, `/messenger`, `/livewire/*` y los POST del constructor de ajustes (`/admin/option-builder/*`). Los slugs de páginas creadas con el page builder son datos: cámbialos desde *Admin → Páginas*.
- Prueba de regresión: `SpanishUrlsTest` falla si reaparece un segmento en inglés en una ruta de usuario o si una URL antigua deja de redirigir.

## Diseño unificado y rendimiento (parcial, hecho en esta pasada)
**Sistema de diseño único** (`public/css/horizontia-ui.css`, cargado en todos los layouts con `<x-ui-styles />`): tokens (navy `#14213d`, dorado `#fed304`), barra lateral navy con logo claro, encabezado, tarjeta de contenido, botones, formularios, tablas e insignias iguales para **estudiante, tutor y administrador**. Ficha pública del curso: hero compacto, pestañas que ya no quedan bajo la tarjeta de compra, tarjetas laterales con separación constante. Dashboard: espaciado único, rejilla de estadísticas y de cursos, encabezado sin solapes, panel admin con métricas de ventas/inscripciones en modo academia.
Errores corregidos al revisar la UI: todo salía «Gratis» (`isPaidSystem()` apagado; ahora siempre activo en modo academia), cursos demo en «Afrikaans», clave de traducción sin resolver, cadenas en inglés en el aula, billetera y botones de tutor visibles en modo academia, logo cargado desde `horizontia.com`.

**Rendimiento medido en navegador real** (antes → después): portada 19,3 MB → 3,2 MB (miniaturas WebP, imágenes de marca WebP, `loading="lazy"`); página de dashboard 3,9 MB → 1,8 MB (fuente SF Pro OTF 2,2 MB → WOFF2 30 KB); TTFB 400–500 ms → 50–100 ms (OPcache en el servidor de desarrollo, 4 workers, y el HTML ya no incluye ~400 KB de JS/CSS inline de la búsqueda rápida). `php artisan media:optimize` genera las variantes WebP; las miniaturas se crean solas al primer uso.
Importante en Sail: ejecuta SIEMPRE `docker exec -u sail ...`; como root deja vistas compiladas ilegibles para el servidor web y el sitio devuelve 500.

### Pulido de diseño pendiente (toda la plataforma)
1. **Cursos y ficha pública**: reemplazar las miniaturas demo («Lernen») por portadas propias; avatar con iniciales para usuarios sin foto; reseñas con foto/iniciales; mejorar el temario (acordeón más ligero) y la tarjeta de compra en móvil (barra fija inferior con precio y botón); fechas y duraciones localizadas («Oct 10, 2026», «5 mins»); nombres de idioma en español; tarjetas del catálogo con altura uniforme.
2. **Dashboards**: terminar de migrar las pantallas con estilos en línea (`home-static`, `tutor-detail`, perfil, facturas, creación de curso) a componentes Blade con los tokens del sistema; estados vacíos ilustrados; tablas con paginación y filtros homogéneos; modo oscuro opcional.
3. **Admin**: traducir etiquetas en inglés («All users», «Name», «Verified», fechas), unificar iconografía y quitar de los menús lo que no aplica a una academia.
4. **Aula**: reproductor con modo cine, progreso por sección, siguiente lección automática, atajos de teclado.
5. **Accesibilidad y móvil**: contraste AA, foco visible en todo, navegación lateral colapsable en móvil, revisión de todas las vistas a 360 px.
6. **Rendimiento (siguientes pasos)**: servir con nginx/Caddy (gzip/brotli y `Cache-Control` largo para `/build`, `/css`, `/js`, `/images`), dominio propio en R2 (el `r2.dev` no cachea en CDN), minificar `css/main.css` (641 KB) y `admin/css/main.css`, cargar `video.min.js` (647 KB) solo donde hay video, `php artisan config:cache route:cache view:cache event:cache`, Redis para sesiones, y evaluar Octane/FrankenPHP. El build de Vite está roto en este repo (`tailwindcss` sin `stubs/config.full.js`): reinstalar `node_modules` antes de volver a compilar.

## Páginas dinámicas, SEO y menús (hecho)
**Constructor de páginas** (larabuild `pagebuilder`, el que ya traía la plantilla; no se añadió un segundo sistema). Admin → *Páginas*. Bloques propios de la academia en `resources/views/pagebuilder/ht-*`: Encabezado, Texto enriquecido, Llamado a la acción, Preguntas frecuentes (con JSON-LD `FAQPage`), Pasos, Video (YouTube/Vimeo sin cookies), Cursos destacados y *Datos y compra del curso*.
- Todas las páginas legales/informativas (`/terminos-y-condiciones`, `/aviso-de-privacidad`, `/politica-de-reembolsos`, `/politica-de-cookies`, `/preguntas-frecuentes`, `/nosotros`, `/como-funciona`) **viven en la base de datos** y se editan visualmente. Los nombres de ruta `legal.*`, `about`, `how-it-works` no cambiaron. Textos con marcadores `[[company]]`, `[[email]]`, `[[address]]`, `[[refund_days]]`, `[[jurisdiction]]`, `[[updated]]` que siguen a `config/academy.php` (`App\Services\PageTokens`).
- Cualquier página nueva se sirve en `/{slug}` (`BuilderPageController`): borradores en 404 (el admin puede previsualizar con `?preview=1`), SEO propio, `<title>` y descripción del registro de la página.
- `php artisan academy:pages [--force] [--prune]` siembra/convierte las páginas en español (`--prune` borra las páginas demo en inglés de la plantilla). `App\Services\BuilderPageFactory` genera páginas desde arrays PHP (útil para seeders y pruebas).
- **Páginas informativas por curso**: Admin → *Páginas de curso* asocia páginas del constructor a un curso (`/curso/{slug}/informacion/{pagina}`), con etiqueta y orden; aparecen como pestañas en la ficha pública y en el sitemap. El bloque *Datos y compra del curso* muestra precio y botón dentro de esas páginas.
- **SEO** (Admin → *SEO*): título, descripción, palabras clave, imagen social, canónica, robots y JSON-LD por URL exacta, por nombre de ruta o por patrón (`curso/*`); precedencia URL > ruta > patrón más largo; ajustes globales por defecto. Las áreas privadas siguen `noindex`. `sitemap.xml` incluye páginas del constructor, páginas de curso, blog y excluye borradores y URLs marcadas `noindex`.
- **Constructor de menús** (Admin → *Menús*): arrastrar y soltar (SortableJS) con un nivel de submenú, edición en línea, enlaces de plataforma/páginas/cursos/URL propia (se rechazan `javascript:` y `data:`), guardado automático y caché vaciada en cada cambio. Encabezado y pie del sitio salen de ahí (`MenuService`); si no hay menú se usan valores por defecto. `php artisan academy:menus [--force]` crea los menús iniciales.
- Seguridad: las pantallas admin nuevas comprueban el rol en **cada** petición Livewire (`AuthorizesAdmin`) y `RoleMiddleware` es middleware persistente de Livewire.

## Catálogo, carrito/checkout y blog (hecho)
- **Catálogo `/cursos`**: se renderiza en la primera respuesta (SEO y velocidad, sin esqueletos ni `wire:init`), buscador, chips de categoría con conteos, filtros aplicados como chips removibles, 6 ordenamientos (recientes, populares, mejor calificados, precio ↑/↓, antiguos), filtros precio/nivel/duración/calificación/idioma (cajón lateral en móvil), tarjeta unificada (WebP 16:9, precio y descuento, calificación, nivel, duración), «Cargar más», estado vacío útil, «Continúa donde lo dejaste» para alumnos y `ItemList` JSON-LD. Los filtros viven en la URL y se pueden compartir.
- **Checkout de una página** (modo academia): solo nombre, apellido, correo y método de pago (Mercado Pago preseleccionado), resumen fijo con cupón, garantía de reembolso y señales de confianza, barra de pago fija en móvil, total siempre calculado en el servidor. En la ficha del curso, **«Comprar ahora»** agrega al carrito y abre el checkout (a invitados los manda a iniciar sesión y vuelven al curso). El checkout del marketplace sigue igual cuando `ACADEMY_PRIVATE_MODE=false`. Corregido: el interruptor de billetera enviado desde el navegador rompía el checkout de la academia.
- **Blog**: índice con buscador, chips de categoría, artículo destacado y «Cargar más» (renderizado en servidor); artículo con tiempo de lectura, compartir (WhatsApp, LinkedIn, X, correo, copiar enlace), artículos relacionados (con respaldo), cursos recomendados al final, `BlogPosting` y `BreadcrumbList` JSON-LD, `og:type=article`, título/descripción con respaldo automático, `<h1>` correcto y fecha en español.
- Pruebas nuevas: `BuilderPagesTest`, `SeoAndMenusTest`, `CourseCatalogTest`, `OnePageCheckoutTest`, `BlogTest`. **No corras `php artisan test` sin argumentos**: `ProfileTest` y `Auth/*` usan `RefreshDatabase` y borran la BD `testing` (se recompone sola, pero tarda y esos tests fallan en este repo). Usa la lista de archivos de `tests/Feature` sin ellos.

## Rediseño de los paneles (hecho)
Admin, instructor y alumno comparten un shell nuevo y una hoja hecha a medida (`public/css/panel.css`, tipografía Inter autoalojada, azul marino + amarillo): sidebar con grupos animados y modo riel recordado, topbar translúcido, menú de cuenta/idioma/carrito/notificaciones accesibles, cajón móvil, tablas, formularios, pestañas, modales y ajustes (option builder) unificados. Detalles y convenciones en `docs/panel-design-system.md`.
- **Asíncrono y en tiempo real**: navegación entre pantallas sin recargas (Livewire `navigate` + `panel-compat.js`), pulso en segundo plano para badges, listados que se refrescan solos solo con la pestaña visible, idioma/guardados sin recargar.
- **Pruebas**: `PanelDesignSystemTest` (CSS, tema generado, shell por rol, pulso) y la batería E2E `tests/e2e-panel` (26 pruebas Playwright).
- Se corrigieron errores heredados al volver las pantallas idempotentes (`initPriceRange` indefinido en «Mis cursos», doble inicialización de `noUiSlider` en el listado de cursos del instructor).

## Editor visual de páginas (GrapesJS) y marca (hecho)
El constructor de bloques antiguo (y su loader) se reemplazó por **GrapesJS** integrado directamente (ver `docs/page-editor.md`): lista de páginas en Admin → Páginas, editor a pantalla completa con bloques de la academia, versiones, subida de imágenes, bloques dinámicos de cursos y sanitización al guardar. Todo texto «Lernen» pasó a «Horizontia» (idiomas, módulos, seeders, contenido de la base de datos con `php artisan brand:rename`, que respeta valores PHP serializados). El título SEO escrito en Admin → SEO se usa tal cual en la pestaña.

### Pendiente tras esta pasada
1. Miniaturas demo con la marca «Lernen» y textos demo en inglés de la plantilla (blog, cursos de ejemplo); nombres de idioma en inglés en filtros.
2. Bloques extra del constructor (testimonios, tarjetas de precios, imagen+texto) y plantillas de página de curso listas para usar.
3. Menús: vista previa con estilo real del sitio y selector de categorías del catálogo.
4. Checkout: Apple/Google Pay y OXXO solo si Mercado Pago los habilita en la cuenta; recuperación de carritos abandonados por correo.
5. Blog: tabla de contenidos automática y comentarios.

## Sobre migrar a otro LMS open source
No conviene. No hay un LMS Laravel maduro y activo; los candidatos (Moodle GPL, Chamilo GPL, Frappe LMS/Open edX AGPL) exigirían reescribir la plataforma y su licencia condiciona el código propio.
Lo que sí vale la pena replicar *como ideas*: finalización condicionada, certificados verificables por código, banco de preguntas, y para SCORM un runtime JS independiente (p. ej. `scorm-again`, MIT, verificar versión).
