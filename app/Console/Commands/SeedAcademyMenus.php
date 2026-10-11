<?php

namespace App\Console\Commands;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\MenuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedAcademyMenus extends Command
{
    protected $signature = 'academy:menus {--force : Replace the existing menus (including any edited ones)}';

    protected $description = 'Create the academy header/footer menus in Spanish and remove the theme demo menus (only when untouched, or with --force).';

    public function handle(): int
    {
        $isDemo = Menu::count() > 0 && MenuItem::where('route', 'like', 'http://localhost%')->exists();
        if (Menu::where('name', 'Menú principal')->exists() && !$this->option('force')) {
            $this->info('Academy menus already exist (use --force to rebuild).');

            return self::SUCCESS;
        }
        if (Menu::count() > 0 && !$isDemo && !$this->option('force')) {
            $this->warn('Custom menus detected; not replacing them. Use --force to override.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            MenuItem::query()->delete();
            Menu::query()->delete();

            $this->menu('Menú principal', 'header', [
                ['Inicio', 'home'], ['Cursos', 'courses.search-courses'], ['Eventos', 'events'], ['Cómo funciona', 'how-it-works'], ['Nosotros', 'about'], ['Contacto', 'contacto'],
            ]);
            $this->menu('Explorar', 'footer', [
                ['Inicio', 'home'], ['Cursos', 'courses.search-courses'], ['Eventos', 'events'], ['Cómo funciona', 'how-it-works'], ['Nosotros', 'about'], ['Contacto', 'contacto'],
            ]);
            $this->menu('Ayuda y legal', 'footer', [
                ['Preguntas frecuentes', 'legal.faq'], ['Términos y condiciones', 'legal.terms'], ['Aviso de privacidad', 'legal.privacy'],
                ['Política de reembolsos', 'legal.refunds'], ['Política de cookies', 'legal.cookies'],
            ]);
        });
        MenuService::flush();
        $this->info('Academy menus created.');

        return self::SUCCESS;
    }

    private function menu(string $name, string $location, array $items): void
    {
        $menu = Menu::create(['name' => $name, 'location' => $location]);
        foreach ($items as $i => [$label, $route]) {
            MenuItem::create(['menu_id' => $menu->id, 'label' => $label, 'route' => $route, 'type' => 'route', 'sort' => $i]);
        }
    }
}
