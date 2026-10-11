# Almacenamiento en Cloudflare R2

La plataforma usa **dos buckets**:

| Disco | Bucket | Contenido | Acceso |
|---|---|---|---|
| `r2` | público (dominio propio) | miniaturas, avatares, imágenes del blog | URL pública permanente |
| `r2_private` | privado (sin dominio público) | videos de lecciones, paquetes SCORM, tareas | solo URL firmada temporal, emitida tras verificar inscripción |

## 1. Crear en Cloudflare
1. R2 → crear `horizontia-public` y `horizontia-private`.
2. En `horizontia-public`: *Custom Domains* → `media.tudominio.com`. **No** habilites acceso público en el privado.
3. R2 → *Manage API tokens* → token con permiso *Object Read & Write* sobre ambos buckets.
4. CORS del bucket **privado** (necesario para subida directa desde el navegador y para reproducir video):
```json
[{
  "AllowedOrigins": ["https://tudominio.com"],
  "AllowedMethods": ["GET", "HEAD", "PUT"],
  "AllowedHeaders": ["*"],
  "ExposeHeaders": ["ETag", "Content-Range", "Accept-Ranges"],
  "MaxAgeSeconds": 3600
}]
```

## 2. Variables `.env`
Ver el bloque R2 de `.env.example`. Con `PRIVATE_FILESYSTEM_DISK` vacío todo vive en un solo disco (comportamiento anterior).
En instalaciones **sin R2** se recomienda `PRIVATE_FILESYSTEM_DISK=local` para que los videos de pago no queden en `public/storage`.

`config/filesystems.php` ya incluye `request_checksum_calculation=when_required` y `response_checksum_validation=when_required`:
sin ellos aws-sdk-php ≥ 3.337 envía checksums CRC32 que R2 rechaza.

## 3. Migrar archivos existentes (nunca borra el origen)
```bash
php artisan storage:copy public r2 --dirs=media --dirs=blogs --dirs=profile_images --dirs=subjects --dry-run
php artisan storage:copy public r2 --dirs=media --dirs=blogs --dirs=profile_images --dirs=subjects
php artisan storage:copy public r2_private --dirs=curriculum_videos --dirs=assignments
php artisan storage:copy public r2_private --dirs=scorm --rewrite-scorm
```
Después cambia `FILESYSTEM_DISK`/`PRIVATE_FILESYSTEM_DISK`, ejecuta `php artisan config:clear` y verifica un curso. Conserva el origen hasta validar.

## 4. Cómo se protege el contenido de pago
- Lecciones de video: `lessonMediaUrl()` → URL prefirmada de R2 (120 min) o ruta firmada local con soporte `Range`.
- SCORM en R2: `/scorm-content/{lección}/{archivo}` comprueba inscripción en cada archivo.
- La inscripción se valida en `CourseAccessService` (admin, instructor dueño, estudiante con inscripción activa; lecciones *preview* son públicas).
- Un reembolso elimina la inscripción y el acceso se pierde de inmediato (las URLs ya emitidas caducan en ≤ 120 min).

## 5. Pruebas
`tests/Feature/R2StorageTest.php`: la parte de configuración siempre corre; la de integración se activa con
`R2_TEST_ENDPOINT, R2_TEST_KEY, R2_TEST_SECRET, R2_TEST_PUBLIC_BUCKET, R2_TEST_PRIVATE_BUCKET`
(en desarrollo se probó contra MinIO, que es compatible con S3). **No se ha probado contra un R2 real** ni la subida directa del navegador (`LIVEWIRE_TMP_DISK=r2_private`).

## Configuración actual del entorno de desarrollo
Solo existe el bucket `horizontia` con URL pública `r2.dev` (el token no puede crear buckets). Por eso:
- `FILESYSTEM_DISK=r2` → imágenes públicas (miniaturas, avatares, blog) en R2.
- `PRIVATE_FILESYSTEM_DISK=local` → videos de lecciones, SCORM y tareas se quedan en `storage/app` del servidor y se sirven con URL firmada (`/video-seguro/...`) o por el proxy de inscripción (`/contenido-scorm/...`).
- **No subas contenido de pago al bucket público**: cualquiera con la URL lo descarga. Para pasar los videos a R2 crea un segundo bucket **sin** acceso público (`horizontia-private`), dale permiso al token, y define `R2_PRIVATE_BUCKET=horizontia-private` y `PRIVATE_FILESYSTEM_DISK=r2_private`; luego `php artisan storage:copy local r2_private --dirs=curriculum_videos --dirs=courses --dirs=scorm`.
- Ejecuta `storage:copy` como el usuario del servidor web (en Sail: `sail artisan ...`); si lo corres como root los archivos quedan ilegibles para PHP.
- `public/storage/...` conserva las copias originales: bórralas cuando verifiques todo (los videos de pago ahí siguen siendo públicos por URL directa).
