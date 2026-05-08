# Horizontia - Plataforma de Tutorías Online

> Plataforma de tutorías y cursos online construida con Laravel, Livewire y Spatie.

## Requisitos del Sistema

- PHP >= 8.2
- Composer
- Node.js >= 18.x
- MySQL >= 8.0 o MariaDB >= 10.6
- Extensiones PHP requeridas:
  - BCMath PHP Extension
  - Ctype PHP Extension
  - JSON PHP Extension
  - Mbstring PHP Extension
  - OpenSSL PHP Extension
  - PDO PHP Extension
  - Tokenizer PHP Extension
  - XML PHP Extension
  - ZIP PHP Extension

## Instalación

### 1. Clonar el repositorio

```bash
git clone <repositorio-url> horizontia
cd horizontia
```

### 2. Instalar dependencias

```bash
# Instalar dependencias PHP
composer install

# Instalar dependencias Node.js
npm install
```

### 3. Configuración del entorno

```bash
# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### 4. Configurar base de datos

Editar el archivo `.env` con las credenciales de la base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=horizontia
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Migraciones y datos de.seed

```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders (opcional)
php artisan db:seed
```

### 6. Compilar assets

```bash
# Compilar para desarrollo
npm run dev

# Compilar para producción
npm run build
```

### 7. Crear symlink de storage

```bash
php artisan storage:link
```

### 8. Iniciar servidor

```bash
# Servidor de desarrollo
php artisan serve
```

## Estructura del Proyecto

```
horizontia/
├── app/                    # Código principal de la aplicación
│   ├── Http/              # Controladores, Middleware, Requests
│   ├── Livewire/          # Componentes Livewire
│   ├── Models/            # Modelos Eloquent
│   ├── Services/          # Servicios de negocio
│   └── Helpers/           # Funciones helper
├── bootstrap/             # Archivos de bootstrap de Laravel
├── config/                # Archivos de configuración
├── database/             # Migraciones, seeders y factories
├── lang/                 # Archivos de idioma
├── Modules/              # Módulos del sistema (Courses, etc.)
├── public/               # Archivos públicos
├── resources/            # Vistas, assets, idiomas
├── routes/               # Definición de rutas
├── storage/              # Archivos storage y logs
└── vendor/               # Dependencias Composer
```

## Configuración de Archivos

### Variables de Entorno (.env)

```env
APP_NAME=Horizontia
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=horizontia
DB_USERNAME=root
DB_PASSWORD=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null

# Storage
FILESYSTEM_DISK=local

# Pusher (para websockets)
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
```

## Comandos Útiles

```bash
# Limpiar caché
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Regenerar archivos de ayuda IDE
php artisan ide-helper:generate

# Ver rutas
php artisan route:list

# Ver eventos
php artisan event:list
```

## Configuración de Producción

1. Configurar `APP_ENV=production` y `APP_DEBUG=false`
2. Usar `FILESYSTEM_DISK=public` para almacenamiento público
3. Ejecutar `php artisan storage:link`
4. Configurar scheduler: `* * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1`
5. Configurar cola: configurar supervisor en producción

## Contribución

1. Crear una rama (`git checkout -b feature/amazing-feature`)
2. Hacer commit (`git commit -m 'Add some amazing feature'`)
3. Push a la rama (`git push origin feature/amazing-feature`)
4. Crear un Pull Request

## Licencia

[MIT](LICENSE)