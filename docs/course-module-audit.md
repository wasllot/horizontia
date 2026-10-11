# Módulo de cursos — auditoría y arquitectura

Alcance: asistente de creación/edición (`/admin/crear-curso`, `/curso/editar/{tab}/{id}`), constructor de contenido, reproductor del estudiante, sesiones en vivo, pruebas (quiz), SCORM, publicación y compra de cursos gratis.

## 1. Qué estaba mal (hallazgos)

| Gravedad | Hallazgo | Estado |
|---|---|---|
| Crítica | **IDOR**: los pasos del asistente confiaban en `courseId`/`sectionId`/`curriculumId` enviados por el navegador (propiedades públicas de Livewire). Un instructor podía leer o modificar cursos, secciones, lecciones, avisos y sesiones en vivo de otro. | Corregido: `CourseAccess` + trait `GuardsCourseAccess`, ids `#[Locked]`, todo se resuelve en el servidor. |
| Crítica | **XSS almacenado**: artículos, FAQ, descripción y avisos se guardaban y se pintaban sin sanear. | Corregido: HTMLPurifier (perfil `lesson`, `cleanLessonHtml()`), al guardar y al pintar. |
| Crítica | **SCORM**: `ZipArchive::extractTo` sin filtro (PHP, `.htaccess`, `../`) y el JS del paquete corre en el mismo origen. | Extracción segura con lista blanca (`ScormService::safeExtract`). Falta aislar el dominio (ver §4). |
| Alta | El test (quiz) enviaba las respuestas correctas al navegador y la nota la calculaba el cliente. | El servidor nunca envía respuestas (`QuizService::forStudent`) y califica él (`grade`), con límite de intentos. |
| Alta | La lección **En vivo** no existía de verdad («Próximamente») y las sesiones no estaban ligadas a lecciones. | `LiveHandler` + `LiveSessionService`: sesión vinculada a la lección, aviso a inscritos, ventana de acceso (15 min), grabación, `.ics`, recordatorios por scheduler. |
| Alta | **Las lecciones en vivo no aparecían al estudiante** (el reproductor solo listaba lecciones con `media_path` o `article_content`). | Corregido con `Curriculum::withContent()` en reproductor, detalle y API. |
| Alta | El enlace de reunión viajaba en el estado público de Livewire aunque faltaran días. | `live_stream` se elimina del payload; `join_url` solo en fase `soon`/`live`. |
| Alta | **Comprar un curso gratis no inscribía**: la inscripción vive en un job en cola Redis y en desarrollo no había worker. | Completar la compra es síncrono (`dispatch_sync`), el job ya no revienta con importes 0, y se añadió el servicio `queue` a `docker-compose.yml`. |
| Media | «Atrás» enviaba a otra pantalla y los administradores rebotaban a rutas de instructor. | `courseEditUrl()` / `courseListUrl()` según rol. |
| Media | Video promocional «opcional» pero obligatorio en validación; avisos/foro/promociones cambiaban el estado del curso al guardar. | Corregido. |
| Media | Pasos, lecciones y secciones sin orden estable (`sort_order`); no se podía arrastrar entre secciones. | Migración + SortableJS. |
| Media | Publicar no comprobaba nada. | `CourseReadiness` (obligatorio/recomendado) + flujo admin → publicado / instructor → en revisión. |
| Baja | Símbolo de moneda como superíndice. | `formatAmount` escribe el símbolo en línea. |

## 2. Arquitectura resultante

```
CourseBuilder (Livewire, maestro-detalle)
   └─ LessonSaver ── registry ──► LessonHandler (uno por tipo guardado)
         video │ yt_link │ vm_link │ genially_link │ article │ assignment(test) │ scorm │ live
QuizService            normaliza / filtra para estudiante / califica
LiveSessionService     crear, mover, avisar, fases, .ics
CourseAccess           única autoridad de "quién puede tocar qué"
CourseReadiness        lista de verificación de publicación
```

- **Strategy por tipo de lección**: añadir un tipo = una clase `LessonHandler` + una vista parcial; no se toca el componente.
- **Autorización centralizada**: ningún id del cliente se usa sin pasar por `CourseAccess`.
- **El estudiante recibe una proyección** (`CourseTaking::activeFrom`), nunca el modelo.
- **Nada de dinero ni inscripción depende de un worker**: se completa en la petición; los correos y recordatorios sí van en cola.

## 3. Pendiente / recomendaciones por prioridad

**P0 (antes de producción)**
1. Worker de colas y scheduler supervisados (Supervisor/Horizon) — ya está en `docker-compose.yml` para desarrollo; en producción `php artisan schedule:work` + `queue:work`.
2. SCORM en origen aislado (subdominio sin cookies, `Content-Security-Policy: sandbox`) o reproductor `scorm-again` dentro de iframe con `sandbox`.
3. Duración de videos calculada en servidor (`ffprobe`/getID3); hoy la reporta el navegador.
4. Limitar subidas (rate limit) y tarea de limpieza de archivos huérfanos.

**P1 (producto)**
5. Video: Cloudflare Stream / Mux / Bunny (HLS adaptativo, subtítulos, DRM ligero) en lugar de MP4 directo; subida por `tus`/Uppy con reanudación.
6. Progreso real de videos incrustados con las APIs de YouTube/Vimeo (hoy solo «marcar como completada»).
7. Banco de preguntas, aleatorización, temporizador, preguntas abiertas y revisión por el instructor; pantalla «Tareas» con entregas de archivos y calificación.
8. Certificados al completar (plantilla + verificación pública), prerequisitos y liberación escalonada (drip) por lección.
9. Recursos adjuntos por lección y subtítulos (VTT).
10. Editor de artículos: sustituir Summernote por Tiptap/Lexical (JSON seguro, bloques, menos HTML arbitrario).

**P2 (calidad)**
11. Policies de Laravel en lugar de `CourseAccess` estático cuando crezcan los roles.
12. Versionado/borradores de cursos publicados y autoguardado en el constructor.
13. Notificaciones del flujo de revisión (enviado, aprobado, con cambios).
14. Registro de auditoría de acciones del instructor/administrador.
15. Traducir las cadenas fijas restantes del módulo.
16. Integraciones de sesiones en vivo: crear la reunión por API (Google Calendar/Meet, Zoom) en vez de pegar el enlace; asistencia automática.

## 4. Pruebas

- PHP: `tests/Feature/CourseBuilderTest.php` (autorización, cada tipo de lección, test sin fugas, en vivo, `.ics`, readiness), `ScormFlowTest`, `R2StorageTest`, `OnePageCheckoutTest` (curso gratis).
- E2E: `tests/e2e-panel/panel.e2e.mjs` (asistente con el nuevo stepper superior).
