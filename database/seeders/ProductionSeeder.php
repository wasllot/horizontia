<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Base de una instalación NUEVA en producción: catálogos (países, idiomas), roles, plantillas de correo y notificaciones,
 * ajustes por defecto, páginas y menús de la academia en español y UN administrador. Sin cursos, blogs, alumnos ni
 * instructores de demostración. Es idempotente: no duplica nada y no toca al administrador si ya existe.
 *
 *   ACADEMY_ADMIN_EMAIL / ACADEMY_ADMIN_PASSWORD en el .env (si falta la contraseña se genera una y se muestra una vez).
 */
class ProductionSeeder extends Seeder
{
    /** Set after run(): the generated password (null when it came from the .env or the admin already existed). */
    public ?string $generatedPassword = null;

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        $this->call([
            CountrySeeder::class,
            CountryStatesSeeder::class,
            LanguageSeeder::class,
            RolePermissionsSeeder::class,
            EmailTemplateSeeder::class,
            NotificationTemplateSeeder::class,
            DefaultSettingSeeder::class,
        ]);
        Schema::enableForeignKeyConstraints();

        // Páginas legales/informativas y menús en español (se pueden reemplazar después importando tu configuración).
        // Sin Artisan::call(): una llamada anidada reemplaza Artisan::output() y se perdería el mensaje con la contraseña generada.
        foreach ([['academy:pages', ['--prune' => true]], ['academy:menus', []]] as [$command, $args]) {
            $this->command ? $this->command->callSilent($command, $args) : Artisan::call($command, $args);
        }

        $this->admin();   // last, so its message (with a generated password) is the last thing printed
    }

    private function admin(): void
    {
        $email = (string) (config('academy.admin_email') ?: 'admin@' . (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost'));
        if (User::where('email', $email)->exists()) {
            return;
        }
        $password = (string) config('academy.admin_password');
        if (strlen($password) < 10) {
            $password = $this->generatedPassword = Str::password(16, symbols: false);
        }

        $admin = User::create(['email' => $email, 'password' => Hash::make($password), 'email_verified_at' => now()]);
        $admin->profile()->create(['first_name' => 'Administrador', 'last_name' => config('app.name'), 'slug' => 'admin']);
        $admin->assignRole('admin');
        if ($this->command) {
            $this->command->info("Administrador creado: {$email}" . ($this->generatedPassword ? " · contraseña: {$this->generatedPassword} (guárdala ahora; no se vuelve a mostrar)" : ''));
        }
    }
}
