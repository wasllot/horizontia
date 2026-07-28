#!/bin/bash

# Este script debe ejecutarse en el servidor para realizar un backup de la base de datos
# y aplicar las migraciones de forma segura sin perder datos.

echo "Iniciando proceso de despliegue seguro..."

# 1. Cargar variables de entorno desde el archivo .env
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
else
    echo "Error: No se encontró el archivo .env"
    exit 1
fi

# 2. Configurar el nombre del archivo de backup con la fecha actual
BACKUP_DIR="storage/app/backups"
mkdir -p $BACKUP_DIR
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/db_backup_${TIMESTAMP}.sql"

echo "Creando backup de la base de datos: ${DB_DATABASE}..."

# 3. Realizar el dump de la base de datos según el tipo de conexión
if [ "$DB_CONNECTION" == "mysql" ]; then
    # Se usa mysqldump. Asegúrate de que el comando mysqldump esté disponible en el servidor.
    # Usamos las variables exportadas del .env
    mysqldump -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        echo "Backup creado exitosamente en: $BACKUP_FILE"
    else
        echo "Error: Falló la creación del backup de la base de datos."
        echo "Por seguridad, abortando el proceso. No se ejecutarán migraciones."
        exit 1
    fi
elif [ "$DB_CONNECTION" == "pgsql" ]; then
    export PGPASSWORD="$DB_PASSWORD"
    pg_dump -h "${DB_HOST:-127.0.0.1}" -p "${DB_PORT:-5432}" -U "$DB_USERNAME" -d "$DB_DATABASE" > "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        echo "Backup creado exitosamente en: $BACKUP_FILE"
    else
        echo "Error: Falló la creación del backup de la base de datos PostgreSQL."
        exit 1
    fi
else
    echo "Advertencia: Tipo de base de datos ($DB_CONNECTION) no soportado para backup automático en este script."
    echo "Se recomienda hacer un backup manual antes de continuar."
    read -p "¿Deseas continuar con las migraciones de todos modos? (s/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        exit 1
    fi
fi

# 4. Ejecutar las migraciones
echo "Ejecutando migraciones..."
# --force es necesario en producción para que no pida confirmación interactiva.
# IMPORTANTE: migrate (a secas) NO borra datos, solo aplica nuevas migraciones.
# NUNCA uses migrate:fresh o migrate:refresh en producción.
php artisan migrate --force

if [ $? -eq 0 ]; then
    echo "Migraciones ejecutadas correctamente."
else
    echo "Ocurrió un error al ejecutar las migraciones."
    exit 1
fi

echo "Proceso finalizado con éxito."
