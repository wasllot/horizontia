#!/usr/bin/env bash
# Arma el paquete para subir a Plesk SIN SSH: código + `vendor` de producción (sin dev) + assets ya compilados.
# Uso:  scripts/build-release.sh [--with-db]
#   --with-db  añade además un volcado de la base de datos local (horizontia-db.sql.gz) para importarlo en Plesk.
# Requiere: docker con el contenedor de la app en marcha (usa su PHP/Composer), rsync y python3 en tu equipo.
# Resultado: ../_horizontia_release/horizontia-AAAAMMDD-HHMM.zip   (guía: docs/plesk.md)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CONTAINER="${APP_CONTAINER:-horizontia-laravel.test-1}"
OUT_DIR="$(dirname "$ROOT")/_horizontia_release"
STAMP="$(date +%Y%m%d-%H%M)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"; docker exec -u root "$CONTAINER" rm -rf /tmp/release >/dev/null 2>&1 || true' EXIT
mkdir -p "$OUT_DIR"

echo "1/5 Copiando el código (sin tests, logs, archivos de trabajo ni secretos)…"
rsync -a \
  --exclude='.git' --exclude='.github' --exclude='node_modules' --exclude='/vendor' --exclude='/tests' --exclude='/docker' --exclude='/docs' \
  --exclude='/storage/logs/*' --exclude='/storage/framework/*' --exclude='/storage/app/*' --exclude='/bootstrap/cache/*' \
  --exclude='.env' --exclude='.env.*' --exclude='*.zip' --exclude='*.log' --exclude='*.sql' \
  --exclude='/container_logs.txt' --exclude='/docker_logs.txt' --exclude='/laravel_container_logs.txt' --exclude='/output.html' --exclude='/Inicio _ Horizontia.html' \
  --exclude='/check_image*.php' --exclude='/fix_*.php' --exclude='/discovery.php' --exclude='/Makefile' --exclude='/docker-compose.yml' --exclude='/demo_scorm' \
  --exclude='/public/hot' --exclude='/public/storage' --exclude='/public/test.php' --exclude='/public/readlog.php' --exclude='/public/scorm_diag.php' \
  --exclude='/public/check_thumbnails.php' --exclude='/public/fix_course_view.php' --exclude='/public/clear_views.php' --exclude='/public/clear_cache.php' \
  --exclude='C:*' --exclude='.phpunit.result.cache' --exclude='*.map' \
  "$ROOT/" "$WORK/release/"
# La carpeta storage debe existir con su estructura (y la marca de "instalado").
mkdir -p "$WORK/release/storage/app/public" "$WORK/release/storage/framework/"{cache/data,sessions,views,testing} "$WORK/release/storage/logs" "$WORK/release/bootstrap/cache"
[ -f "$ROOT/storage/installed" ] && cp "$ROOT/storage/installed" "$WORK/release/storage/installed"

echo "2/5 Instalando dependencias de producción con Composer (en el contenedor)…"
docker exec -u root "$CONTAINER" rm -rf /tmp/release
docker cp "$WORK/release" "$CONTAINER:/tmp/release"
docker exec -u root "$CONTAINER" chown -R sail:sail /tmp/release   # docker cp crea los archivos como root
docker exec -u sail -w /tmp/release "$CONTAINER" bash -c '
  composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --prefer-dist >/dev/null &&
  APP_ENV=production APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= APP_DEBUG=false php artisan package:discover --ansi >/dev/null
'
docker cp "$CONTAINER:/tmp/release/vendor" "$WORK/release/vendor"
docker cp "$CONTAINER:/tmp/release/bootstrap/cache/." "$WORK/release/bootstrap/cache/"

# `packages/` solo hace falta para instalar (vendor ya trae su copia): fuera del zip.
rm -rf "$WORK/release/packages"

echo "3/5 Comprobando que los assets compilados están (public/build/manifest.json)…"
[ -f "$WORK/release/public/build/manifest.json" ] || { echo "Falta public/build: ejecuta 'npm run build' y vuelve a intentar"; exit 1; }

if [ "${1:-}" = "--with-db" ]; then
  echo "4/5 Exportando la base de datos local…"
  docker exec -u sail "$CONTAINER" php artisan app:backup-database >/dev/null
  LATEST="$(docker exec -u sail "$CONTAINER" bash -c 'ls -t storage/app/backups/horizontia-*.sql.gz | head -1')"
  docker cp "$CONTAINER:/var/www/html/$LATEST" "$OUT_DIR/horizontia-db-$STAMP.sql.gz"
else
  echo "4/5 (sin base de datos; usa --with-db si quieres exportarla)"
fi

echo "5/5 Creando el zip…"
python3 -c "import shutil,sys; shutil.make_archive(sys.argv[1], 'zip', sys.argv[2])" "$OUT_DIR/horizontia-$STAMP" "$WORK/release"   # sin depender del comando zip
echo; echo "Listo:"; ls -lh "$OUT_DIR" | tail -n +2
