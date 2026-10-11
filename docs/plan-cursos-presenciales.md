# Plan de acción — Cursos presenciales con varias fechas (compra directa)

Estado: **implementado (fases 1–4)**. Sin reservas, retención de cupo, lista de espera, QR ni pase de lista: un curso presencial se **compra como cualquier otro**, pero la persona **elige una fecha** entre las habilitadas.

Decisiones del dueño: **varias fechas por curso, se elige una al comprar**, **cupo opcional (por fecha)**, y las **páginas informativas del curso** deben mostrar y vender esas fechas.

Base en el código actual: `Course`, `Pricing`, `Enrollment`, carrito/checkout de una página, Mercado Pago, `CompletePurchaseJob` (la compra crea la inscripción), `CourseAccess`, `LiveSessionService::ics()`, asistente de cursos (`CourseBuilder`, `CourseReadiness`), páginas informativas (`CoursePage`, `BuilderPageController::course`, `PageContentRenderer` con bloques `data-hz-block="course-info|courses"`, `PageTokens`).

## 1. Qué se construye

Un curso puede ser **presencial** (o **híbrido**: presencial + lecciones en línea de apoyo). Tiene una lista de **fechas** (cada una con su horario, sede y cupo opcional). El alumno elige una fecha, paga con el flujo de hoy y recibe los datos de esa fecha.

No incluye: reservas sin pago, retención de cupo, lista de espera, QR/check-in, precios distintos por fecha, reembolsos por API, facturación.

## 2. Diseño

**Datos**

```
courses            + delivery  online | in_person | hybrid     (por defecto online → nada cambia)
course_sessions    id, course_id, starts_at, ends_at, schedule_note ("Sábados 9:00–13:00"),
                   venue_name, address, city, state, maps_url?, lat?, lng?, venue_notes?,
                   capacity? (null = sin límite), sales_close_at? (por defecto = starts_at),
                   status (scheduled | cancelled), sort
enrollments        + session_id? (la fecha elegida; null en cursos en línea)
```

- La **sede va por fecha** (una edición puede ser en otra ciudad). El formulario permite *duplicar fecha* para no reescribir la sede.
- Precio único por curso (el de `Pricing`); cupón y descuentos funcionan igual.
- Un alumno tiene **una inscripción por curso** (como hoy): no compra dos fechas del mismo curso. Cambiarlo de fecha lo hace el equipo (sección 4).

**Elegir fecha y comprar**
- En la página del curso: tarjetas de fecha (día/hora, ciudad y sede, "quedan X lugares", estado) → se elige una → "Comprar ahora" / "Añadir al carrito". Enlace profundo `?fecha=ID` para correos y páginas informativas.
- El `session_id` viaja en el carrito/pedido (`options`) y **se valida en el servidor** en cada paso: pertenece al curso, no está cancelada, no cerró ventas y no está agotada. Si hay una sola fecha disponible se preselecciona. En el carrito, el mismo curso con otra fecha **reemplaza** la anterior.
- Cupo por fecha = inscripciones activas con esa `session_id`. Se comprueba al añadir y otra vez al crear el pedido, dentro de una transacción que bloquea la fila de la fecha. Sin retención de cupo puede haber sobrecupo si dos pagos pendientes compiten por el último lugar: **se confirma igualmente** (ya pagó) y se avisa al administrador.
- `CompletePurchaseJob` crea el `Enrollment` con `session_id` (rama nueva mínima).

**Después de pagar**
- Gracias y correo con **la fecha elegida**: día, hora, sede, cómo llegar y "Añadir al calendario" (`.ics`, generalizando el de las sesiones en vivo).
- "Mis cursos": *Presencial · 12 nov · Ciudad* y panel con los datos de su fecha (si es híbrido, también las lecciones).
- Recordatorio 24 h antes por fecha (mismo patrón que `SendLiveStreamReminders`).

**Páginas informativas del curso (`/curso/{slug}/informacion/{pagina}`)**
- **Bloque dinámico nuevo `course-dates`** (`data-hz-block="course-dates"`) en el editor visual (y su equivalente en el constructor clásico): lista las próximas fechas con cupos y botón **"Elegir esta fecha"** → página del curso con `?fecha=ID`. Se incluye en `PageContentRenderer`.
- El bloque existente **`course-info`** muestra además "Próximas fechas" y la sede de la más cercana.
- **Barra fija "Elegir fecha y comprar"** que se añade sola a todas las páginas informativas de un curso presencial/híbrido (no hay que editarlas): muestra la próxima fecha, los lugares y el botón. Se oculta si no hay fechas disponibles ("Sin fechas disponibles — avísame" → formulario de contacto).
- **Tokens por curso** en `PageTokens` (se resuelven solo dentro de páginas de un curso): `[[curso_proxima_fecha]]`, `[[curso_ciudades]]`, `[[curso_sedes]]`, `[[curso_lugares]]`. Sirven para textos como "Próxima edición: [[curso_proxima_fecha]] en [[curso_ciudades]]".
- **SEO**: JSON-LD `Event` por cada fecha (con `offers` y `availability`) en la página del curso y en las informativas; la página sigue indexable aunque cambie la fecha.

**Instructor / administrador**
- Asistente: primer paso **Modalidad**; para presencial/híbrido aparece **"Fechas y sede"** (lista editable: añadir, duplicar, cancelar). `Contenido` pasa a opcional en presencial.
- `CourseReadiness` exige al menos **una fecha futura** con sede (dirección y ciudad) y cupo ≥ 1 si se define.
- **"Inscritos"** del curso agrupado/filtrable por fecha (nombre, correo, teléfono si existe, fecha de compra), **CSV / imprimir**.
- Acciones sobre fechas: *aviso de cambio* (correo a los inscritos de esa fecha), *mover inscritos a otra fecha*, *cancelar fecha* (solo si no tiene inscritos o tras moverlos; si se cancela con inscritos: aviso + reembolso manual).
- Reembolsos: manuales desde Mercado Pago; el webhook ya revoca la inscripción.

**Público**
- Etiqueta **Presencial** y "Próxima fecha · Ciudad" en tarjetas (catálogo y carrusel de la home); filtros **Modalidad**, **Ciudad** y **Fecha**; orden por próxima fecha.
- Fechas pasadas no se muestran; si todas pasaron, el curso muestra "Sin fechas disponibles".

## 2.1 Estado de implementación

- **Fase 1 (hecha):** modalidad, tabla `course_sessions`, `enrollments.session_id`, paso «Fechas y sede», lista de verificación.
- **Sección «Eventos» (hecha):** página pública `/eventos` (cabecera con los 3 pasos, filtros por ciudad/mes/texto, tarjetas agrupadas por mes con fecha, sede, horario, cupo y precio, estado vacío, datos estructurados `Event`, sitemap) y entrada «Eventos» asignable en `/admin/menus` (ya añadida a los menús). Los cursos `in_person` salen del catálogo `/cursos` y del carrusel de la home; los híbridos aparecen en ambos.
- **Fase 2 (hecha):** selector de fecha en la página del curso (`?fecha=ID`, validado siempre en el servidor, resumen en el panel de compra, botón «Elegir fecha» si falta), la fecha elegida viaja en el carrito (`options.session_id`; elegir otra la reemplaza), «Próximos eventos» en la portada, etiqueta «Presencial + en línea» en el catálogo, bloques `course-dates` y `events` en el editor, *tokens* `[[curso_proxima_fecha]]`, `[[curso_ciudades]]`, `[[curso_sedes]]`, `[[curso_lugares]]`, barra de las páginas informativas con la próxima fecha, «Próximas fechas» en el bloque de datos del curso y datos estructurados `Event` en la página del curso y en las informativas.
- **Fase 3 (hecha):** la fecha viaja del carrito al pedido (`OrderItem.options.session_id`) y a `Enrollment.session_id` (`CompletePurchaseJob`; un id manipulado o de otro curso se ignora). `OnsiteSeatsService` valida la fecha (falta, ajena, cancelada, cerrada, agotada) al crear el pedido dentro de la transacción con `lockForUpdate`, y el checkout avisa antes de pagar. Sin retención de cupo: si dos pagos compiten por el último lugar se confirman ambos y se avisa a los administradores (campana + log). Correo `OnsiteDateEmail` (confirmación, recordatorio, cambio, cancelación) con `.ics` adjunto, descarga autenticada `/fecha-presencial/{id}/calendario.ics`, bloque de fecha en «Gracias», «Mis cursos» (etiqueta, fecha, calendario) y en el aula; recordatorio 24 h (`app:send-onsite-reminders`, cada hora, `enrollments.reminded_at`, una sola vez).
- **Fase 4 (hecha):** pantalla «Inscritos» (`/curso/{id}/inscritos` para el instructor y `/admin/curso/{id}/inscritos`) con tarjetas por fecha (cupo, agotada, sobrecupo, cancelada), filtro por fecha (`?fecha=`), buscador, CSV (con protección contra fórmulas) e impresión; acciones *mover inscrito* (solo a fechas con lugar; recalcula el recordatorio y le avisa), *avisar cambio* (correo a los de esa fecha) y *cancelar fecha* (deja de venderse y avisa; el reembolso es manual en Mercado Pago). Accesos desde los listados de cursos (tutor y admin) y desde el paso «Fechas y sede»; al editar horario/sede con inscritos se sugiere avisar. Pruebas: `OnsitePhase3Test`, `OnsitePhase4Test` y E2E `tests/e2e-panel/onsite-purchase.e2e.mjs`.

## 3. Fases y esfuerzo

| Fase | Contenido | Días |
|---|---|---|
| 1 | Migraciones y modelos, modalidad y paso "Fechas y sede" (lista editable) en el asistente, readiness, duplicar fecha | 4 |
| 2 | Selector de fecha en la página del curso, etiqueta/filtros/orden en catálogo y home, bloque `course-dates`, "Próximas fechas" en `course-info`, barra fija y tokens en las páginas informativas, JSON-LD `Event` | 4 |
| 3 | Carrito/pedido con `session_id` y validación, reglas de venta por fecha con bloqueo, `Enrollment.session_id`, correo y gracias con `.ics`, "Mis cursos", recordatorio 24 h | 4 |
| 4 | "Inscritos" por fecha + CSV, aviso de cambio, mover inscritos, cancelar fecha, aviso de sobrecupo, pruebas PHP y E2E | 3 |

**Total ≈ 15 días; vendible tras la fase 3 (≈ 12).**

## 4. Pruebas

- PHP: la fecha es obligatoria y válida (de otro curso, cancelada, cerrada o agotada → rechazada); cupo por fecha y bloqueo ante compras simultáneas; carrito reemplaza fecha; `session_id` manipulado (IDOR); readiness; tokens y bloque `course-dates` en páginas informativas; visibilidad de inscritos solo para el personal del curso; JSON-LD válido; `.ics` correcto.
- E2E: instructor crea curso presencial con 3 fechas (una con cupo 1) → desde una **página informativa** el alumno elige la fecha y compra → recibe correo/`.ics` y ve su fecha en "Mis cursos" → otro alumno ve esa fecha agotada y puede comprar otra → el administrador mueve un inscrito de fecha.

## 5. Riesgos y valores por defecto

| Tema | Por defecto |
|---|---|
| Zona horaria | La del sitio, mostrada de forma explícita («hora de Ciudad de México»). |
| Sobrecupo por pagos simultáneos | Se confirma y se avisa al administrador (no hay retención de cupo). |
| Reembolsos y cancelaciones | Manuales en Mercado Pago; el aviso ya recibido revoca la inscripción. |
| Mismo alumno, dos fechas del mismo curso | No permitido; el equipo puede moverlo de fecha. |
| Precio por fecha | Único por curso en v1. |
| Cursos gratuitos | Mismo flujo; la inscripción es inmediata. |
| Mapa | Enlace a Google/OpenStreetMap; mapa incrustado opcional. |
