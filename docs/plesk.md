# Subir Horizontia a Plesk sin SSH

Todo se hace con el panel de Plesk (Administrador de archivos, Bases de datos, Tareas programadas) y dos archivos del proyecto: el paquete `.zip` y `public/tareas.php`. No hace falta Composer ni Node en el servidor: el paquete ya trae `vendor` (sin dependencias de desarrollo) y los assets compilados.

## 0. Requisitos del hosting
- **PHP 8.2 o superior** (Dominio → *PHP Settings*), con las extensiones: `bcmath, ctype, curl, dom, exif, fileinfo, gd, intl, mbstring, openssl, pdo_mysql, tokenizer, xml, zip, sodium`. `exec` debería estar permitido (lo usa el respaldo; si no, `tareas.php` lo avisa).
- **MySQL 8 / MariaDB 10.6+** y un dominio con **HTTPS** (Plesk → *SSL/TLS Certificates* → Let's Encrypt).
- Sugerido en *PHP Settings*: `memory_limit 512M`, `upload_max_filesize` y `post_max_size` 128M o más (se suben videos/archivos), `max_execution_time 120`.

## 1. En tu equipo: generar el paquete y exportar tu configuración
```bash
scripts/build-release.sh
```
Crea `../_horizontia_release/horizontia-AAAAMMDD-HHMM.zip` (~245 MB). Repite el script en cada actualización.

**Tus páginas, menús, ajustes, etc. no van en la base de datos:** en tu sitio local entra a **Administración → Ajustes → Exportar / importar**, elige qué llevar y pulsa *Descargar archivo* (queda un `.json`). Nunca incluye cursos, blogs ni usuarios; las claves secretas solo viajan si marcas «Incluir credenciales». Detalles en `docs/exportar-importar.md`.

> El servidor arranca con una **base de datos nueva y limpia** (sin cursos, blogs ni usuarios de demostración). No copies tu base local.

## 2. Subir y descomprimir
1. Plesk → **Archivos** (Administrador de archivos). Entra a la **raíz de la suscripción** (donde ves `httpdocs`, `logs`…), no dentro de `httpdocs`.
2. Crea la carpeta `horizontia` y entra. **Subir** el zip (si el navegador se corta por el tamaño, usa FTP con los datos de *Cuentas FTP* de Plesk).
3. Clic derecho sobre el zip → **Extraer archivos**. Después puedes borrar el zip.
4. Permisos: `storage` y `bootstrap/cache` deben poder escribirse (en Plesk normalmente ya; si hay errores 500, *Cambiar permisos* → lectura/escritura para el propietario en ambas carpetas, con subcarpetas).

## 3. Apuntar el dominio a `public`
Plesk → **Dominios → tu dominio → Configuración de hospedaje → Directorio raíz del documento** = `horizontia/public`. Guardar. (Así solo `public` queda expuesto; el código, `.env` y `vendor` quedan fuera del alcance web.)

## 4. Base de datos y `.env`
1. Plesk → **Bases de datos → Añadir base de datos**: anota nombre, usuario y contraseña. Déjala **vacía**.
2. En **Archivos**, dentro de `horizontia`, copia `.env.production.example` como `.env` y edítalo:
   - `APP_URL=https://tudominio.com`, `APP_ENV=production`, `APP_DEBUG=false`
   - `APP_KEY=`: genera una en tu equipo con `php artisan key:generate --show` (o, en Docker: `docker exec -u sail horizontia-laravel.test-1 php artisan key:generate --show`) y pégala.
   - `DB_*` con los datos del paso 4.1 (`DB_HOST=localhost`)
   - `CACHE_STORE=file`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=database`, `ACADEMY_QUEUE_VIA_SCHEDULER=true` (sin Redis ni worker)
   - `ACADEMY_ADMIN_EMAIL` (tu correo de administrador) y, si quieres, `ACADEMY_ADMIN_PASSWORD` (10+ caracteres; si la dejas vacía se genera una y se muestra una sola vez)
   - Correo real (`MAIL_*`), `ACADEMY_ALERT_EMAIL`, datos legales `ACADEMY_*`, discos de archivos (`FILESYSTEM_DISK`, R2…) y `DEPLOY_TOKEN=` con una clave larga y secreta (24+ caracteres, solo letras y números).

## 5. Tareas programadas (obligatorio)
Plesk → **Dominio → Tareas programadas → Añadir tarea** → *Ejecutar un comando*, cada minuto (`* * * * *`):
```
/opt/plesk/php/8.2/bin/php /var/www/vhosts/TUDOMINIO.com/horizontia/artisan schedule:run >/dev/null 2>&1
```
(la ruta de la suscripción aparece en *Archivos*; la versión de PHP puede ser `8.3`: mira `/opt/plesk/php/`). Ese único cron hace todo: procesa la cola de correos (`ACADEMY_QUEUE_VIA_SCHEDULER=true`), recordatorios de sesiones y de carrito, respaldo diario y latidos.

## 6. Tareas de arranque desde el navegador
Abre `https://tudominio.com/tareas.php?key=TU_DEPLOY_TOKEN` y ejecuta **en este orden**:
1. **Crear/actualizar las tablas.**
2. **Instalación limpia**: crea roles, países, idiomas, plantillas de correo, las páginas y menús en español y **un administrador** (muestra su contraseña una sola vez si no la pusiste en el `.env`; guárdala). Solo funciona mientras la base no tenga usuarios.
3. **Crear el enlace public/storage** (si falla por `symlink` deshabilitado no pasa nada mientras uses R2 para los archivos).
4. **Limpiar cachés**.

`tareas.php` solo ejecuta esas acciones fijas, y responde 404 mientras `DEPLOY_TOKEN` esté vacío o sea incorrecto.

## 7. Importar tu configuración
Inicia sesión como administrador → **Ajustes → Exportar / importar → Importar**: sube el `.json` del paso 1, mira la vista previa y elige:
- **Solo añadir lo que falta** o **Reemplazar lo existente** (las páginas de la instalación limpia tienen los mismos enlaces que las tuyas: para quedarte con las tuyas elige *Reemplazar*).
- Qué secciones (y qué páginas) importar, y el respaldo previo (recomendado).
Después revisa en **Ajustes generales** el logo y las imágenes: los archivos no viajan; se ven si usas el mismo almacenamiento (el mismo bucket de R2).

## 8. Probar
Abre el sitio, inicia sesión como administrador, entra a **Administración → Estado del sistema** y haz una compra de prueba (modo prueba de Mercado Pago). Configura en Mercado Pago el webhook `https://tudominio.com/mercadopago/webhook` (ver `docs/mercadopago.md`).

## 9. Cerrar lo temporal
- **Borra `public/tareas.php` y `public/run_deploy.php`** (y deja `DEPLOY_TOKEN=` vacío).
- Revisa **Estado del sistema**: HTTPS, correo real (SPF/DKIM/DMARC), cron activo (latido del planificador y de la cola), respaldo reciente. Más detalle en `docs/produccion.md`.

## Actualizar después
1. `scripts/build-release.sh` → nuevo zip.
2. Súbelo y **extráelo sobre la misma carpeta** (sobrescribe): no incluye `.env`, `storage/app` ni `storage/logs`, así que tus datos y configuración se conservan.
3. Sube `tareas.php` de nuevo (o ponlo en el zip) → «Crear/actualizar las tablas» + «Limpiar cachés» → borra `tareas.php`.
4. Cambios de **páginas, menús, SEO o ajustes** hechos en tu equipo: no hace falta tocar el servidor por archivos; exporta desde tu sitio local y usa *Importar → Reemplazar lo existente* en producción.
Alternativa: extensión **Git** de Plesk con acciones de despliegue adicionales, si más adelante subes el código a un repositorio (hoy los cambios no están confirmados en git).

## Si algo falla
- *Error 500 / pantalla en blanco:* pon `APP_DEBUG=true` un momento, o abre `horizontia/storage/logs/laravel.log` desde el Administrador de archivos.
- *«No se encontró vendor/autoload.php»:* el zip no se extrajo completo; vuelve a extraerlo.
- *«Instalación limpia» dice que ya hay usuarios:* la base ya se usó; si es una base nueva, revisa que `DB_*` apunte a la correcta.
- *Redirige a `/install`:* falta `storage/installed`; vuelve a extraer el zip.
- *Los correos no salen:* revisa que el cron del paso 5 esté creado y `QUEUE_CONNECTION=database`.
