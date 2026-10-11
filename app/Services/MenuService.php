<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Reads the menus created in Admin > Menús (menu builder) for the public header and footer.
 * One cached query set; any change in the builder flushes it. Falls back to sane defaults so the site never loses its navigation.
 */
class MenuService
{
    public const CACHE_KEY = 'menus.tree.v2';

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Links offered in the builder's "Enlaces de la plataforma" box: route name => label. */
    public static function platformLinks(): array
    {
        $links = [
            'home' => 'Inicio',
            'courses.search-courses' => 'Catálogo de cursos',
            'events' => 'Eventos',
            'how-it-works' => 'Cómo funciona',
            'about' => 'Nosotros',
            'contacto' => 'Contacto',
            'legal.faq' => 'Preguntas frecuentes',
            'legal.terms' => 'Términos y condiciones',
            'legal.privacy' => 'Aviso de privacidad',
            'legal.refunds' => 'Política de reembolsos',
            'legal.cookies' => 'Política de cookies',
            'blogs' => 'Blog',
        ];
        if (!isPrivateAcademy()) {
            $links['find-tutors'] = 'Tutores';
        }

        return array_filter($links, fn ($label, $route) => \Illuminate\Support\Facades\Route::has($route), ARRAY_FILTER_USE_BOTH);
    }

    /** @return array<int, array{id:int,name:string,location:string,items:array}> */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $items = MenuItem::orderBy('sort')->orderBy('id')->get()->groupBy('menu_id');

            return Menu::orderBy('id')->get()->map(fn (Menu $menu) => [
                'id' => $menu->id,
                'name' => $menu->name,
                'location' => $menu->location,
                'items' => $this->tree($items->get($menu->id, collect())),
            ])->all();
        });
    }

    public function header(): array
    {
        $menu = collect($this->all())->first(fn ($m) => $m['location'] === 'header' && !empty($m['items']));

        return $this->visible($menu['items'] ?? $this->defaultHeader());
    }

    /** @return array<int, array{title:string, items:array}> */
    public function footerColumns(): array
    {
        $columns = collect($this->all())
            ->filter(fn ($m) => $m['location'] === 'footer' && !empty($m['items']))
            ->map(fn ($m) => ['title' => $m['name'], 'items' => $this->visible($m['items'])])
            ->filter(fn ($c) => !empty($c['items']))
            ->values()->all();

        return $columns ?: $this->defaultFooter();
    }

    private function tree($items, $parent = null): array
    {
        return $items->filter(fn ($i) => (int) $i->parent_id === (int) $parent)->sortBy('sort')->map(fn (MenuItem $i) => [
            'id' => $i->id,
            'label' => $i->label,
            'url' => $this->url($i),
            'target' => str_contains((string) $i->class, '_blank') ? '_blank' : '_self',
            'children' => $this->tree($items, $i->id),
        ])->values()->all();
    }

    /** Resolve an item into a portable URL (relative paths work on any host; demo "localhost" links are rewritten). */
    public function url(MenuItem $item): string
    {
        $value = trim((string) $item->route);
        if ($item->type === 'route' && $value !== '') {
            try {
                return route($value);
            } catch (\Throwable) {
                return '#';
            }
        }
        if ($value === '' || $value === '#') {
            return '#';
        }
        if (preg_match('#^https?://#i', $value)) {
            $parts = parse_url($value);
            $sameHost = in_array($parts['host'] ?? '', ['localhost', parse_url(config('app.url'), PHP_URL_HOST)], true);
            if ($sameHost) {
                $path = $parts['path'] ?? '/';

                return url($path . (isset($parts['query']) ? '?' . $parts['query'] : ''));
            }

            return $value;
        }

        return url('/' . ltrim($value, '/'));
    }

    /** Drop marketplace-only links in academy mode and keep only items that lead somewhere. */
    private function visible(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $path = '/' . ltrim((string) parse_url($item['url'], PHP_URL_PATH), '/');
            if (isPrivateAcademy() && (Str::startsWith($path, ['/buscar-tutores', '/find-tutors', '/tutor/']))) {
                continue;
            }
            $item['children'] = $this->visible($item['children'] ?? []);
            $out[] = $item;
        }

        return $out;
    }

    private function defaultHeader(): array
    {
        $items = [];
        foreach (['home' => 'Inicio', 'courses.search-courses' => 'Cursos', 'events' => 'Eventos', 'how-it-works' => 'Cómo funciona', 'contacto' => 'Contacto'] as $route => $label) {
            $items[] = ['id' => 0, 'label' => $label, 'url' => route($route), 'target' => '_self', 'children' => []];
        }

        return $items;
    }

    private function defaultFooter(): array
    {
        $col = fn (array $map) => array_map(fn ($route, $label) => ['id' => 0, 'label' => $label, 'url' => route($route), 'target' => '_self', 'children' => []], array_keys($map), $map);

        return [
            ['title' => 'Explorar', 'items' => $col(['home' => 'Inicio', 'courses.search-courses' => 'Cursos', 'events' => 'Eventos', 'how-it-works' => 'Cómo funciona', 'about' => 'Nosotros', 'contacto' => 'Contacto'])],
            ['title' => 'Ayuda y legal', 'items' => $col(['legal.faq' => 'Preguntas frecuentes', 'legal.terms' => 'Términos y condiciones', 'legal.privacy' => 'Aviso de privacidad', 'legal.refunds' => 'Política de reembolsos', 'legal.cookies' => 'Política de cookies'])],
        ];
    }
}
