<?php

namespace App\Services\ConfigTransfer;

use App\Models\SeoEntry;
use App\Services\MenuService;
use App\Services\Pages\PageHtmlSanitizer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Moves the *configuration* of the site between installations (local → production): pages, menus, SEO, settings, templates,
 * categories and, if asked, form submissions. Never courses, blogs or users.
 *
 * Rules: credentials stay out of the file unless explicitly included; importing never touches what the file does not carry;
 * a dry run executes the real import inside a transaction that is rolled back, so the preview is exactly what would happen.
 */
class ConfigTransfer
{
    public const FORMAT = 'horizontia-config';
    public const VERSION = 1;
    public const MAX_BYTES = 20_000_000;

    public const MODE_SKIP = 'skip';           // only add what is missing
    public const MODE_OVERWRITE = 'overwrite'; // update what already exists

    /** Setting keys that hold secrets (stripped from the export unless credentials are included). */
    private const SECRET_KEY = '/(secret|token|password|passwd|private|webhook|signature|credential|api_?key|access_?key)/i';
    /** Setting keys that hold references to uploaded files (we warn: the files themselves are not copied). */
    private const FILE_KEY = '/(logo|image|img|favicon|avatar|icon|splash|media|video|banner)/i';
    /** Settings holding page ids, which differ between installations: exported with the page slug and re-linked on import. */
    private const PAGE_REF_KEYS = ['_front_page_settings' => ['header_variation_for_pages', 'footer_variation_for_pages', 'seo_settings']];

    /** Option-builder sections that each settings group may carry (anything else in a file is ignored: e.g. maintenance mode). */
    private const SETTING_SECTIONS = [
        'general' => ['_general', '_lernen', '_front_page_settings', '_app', '_social', '_dispute_setting', '_badges', '_forum_wise', '_quiz', '_ai_writer_settings'],
        'integrations' => ['_integrations', '_scripts_styles'],
        'payments' => ['admin_settings'],
        'credentials' => ['_api', '_storage'],
        'templates' => ['_email'],
    ];

    /** @return array<string, array{label:string, desc:string, default:bool, sensitive:bool}> */
    public static function sections(): array
    {
        return [
            'pages' => ['label' => 'Páginas', 'desc' => 'Inicio, legales, cómo funciona… con su contenido, diseño y banner (sin el historial de versiones ni las páginas ligadas a cursos). «Nosotros» y «Contacto» tienen vista propia: su banner va en «Banners de páginas».', 'default' => true, 'sensitive' => false],
            'banners' => ['label' => 'Banners de páginas fijas', 'desc' => 'Título, subtítulo, oscurecimiento y alineación del banner de Nosotros, Contacto y Solicitar acceso (Admin → Banners de páginas).', 'default' => true, 'sensitive' => false],
            'menus' => ['label' => 'Menús', 'desc' => 'Menú principal y columnas del pie de página, con sus enlaces y submenús.', 'default' => true, 'sensitive' => false],
            'seo' => ['label' => 'SEO por URL', 'desc' => 'Títulos, descripciones, imagen social, canónica y datos estructurados definidos en Admin → SEO.', 'default' => true, 'sensitive' => false],
            'general' => ['label' => 'Ajustes generales', 'desc' => 'Nombre del sitio, moneda, formato de fecha, logos, textos de portada y pie, redes sociales y demás ajustes de «Ajustes generales».', 'default' => true, 'sensitive' => false],
            'integrations' => ['label' => 'Integraciones', 'desc' => 'reCAPTCHA, Google Tag Manager, botón de WhatsApp y scripts/estilos personalizados.', 'default' => true, 'sensitive' => false],
            'templates' => ['label' => 'Correo y notificaciones', 'desc' => 'Remitente y firma del correo, plantillas de correo y de notificaciones.', 'default' => true, 'sensitive' => false],
            'payments' => ['label' => 'Métodos de pago y comisiones', 'desc' => 'Qué métodos de pago están activos y su configuración. Las claves secretas no se incluyen salvo que lo pidas.', 'default' => false, 'sensitive' => true],
            'coupons' => ['label' => 'Cupones de descuento', 'desc' => 'Códigos, tipo y valor, vigencia, límites y cursos a los que aplican (por enlace del curso). No incluye los canjes ni el contador de usos.', 'default' => false, 'sensitive' => false],
            'paths' => ['label' => 'Rutas de aprendizaje', 'desc' => 'Rutas con su descripción y el orden de sus cursos (por enlace del curso). Si falta algún curso en este sitio, la ruta queda en borrador.', 'default' => false, 'sensitive' => false],
            'categories' => ['label' => 'Categorías de cursos', 'desc' => 'Solo la estructura de categorías y subcategorías (nunca los cursos).', 'default' => false, 'sensitive' => false],
            'forms' => ['label' => 'Datos de formularios', 'desc' => 'Mensajes recibidos del formulario de contacto y suscriptores del boletín. Son datos personales.', 'default' => false, 'sensitive' => true],
            'credentials' => ['label' => 'Claves de API y credenciales', 'desc' => 'Claves de Google, OpenAI, Zoom y almacenamiento. Solo se exporta si marcas «Incluir credenciales».', 'default' => false, 'sensitive' => true],
        ];
    }

    // ------------------------------------------------------------------------------------------------ export

    /**
     * @param  array<int, string>      $sections
     * @param  array{pages?: ?array<int,string>, credentials?: bool} $options  pages = slugs to export (null = all)
     */
    public function export(array $sections, array $options = []): array
    {
        $credentials = !empty($options['credentials']);
        $sections = array_values(array_intersect($sections, array_keys(self::sections())));
        if (!$credentials) {
            $sections = array_values(array_diff($sections, ['credentials']));
        }

        $data = [];
        foreach ($sections as $s) {
            $data[$s] = match ($s) {
                'pages' => $this->exportPages($options['pages'] ?? null),
                'banners' => $this->exportBanners(),
                'menus' => $this->exportMenus(),
                'seo' => $this->exportSeo(),
                'general', 'integrations', 'payments', 'credentials' => ['settings' => $this->exportSettings(self::SETTING_SECTIONS[$s], $credentials)],
                'templates' => ['email' => $this->exportTemplates('email_templates'), 'notification' => $this->exportTemplates('notification_templates'), 'settings' => $this->exportSettings(self::SETTING_SECTIONS['templates'], $credentials)],
                'coupons' => $this->exportCoupons(),
                'paths' => $this->exportPaths(),
                'categories' => $this->exportCategories(),
                'forms' => $this->exportForms(),
            };
        }

        return [
            'meta' => [
                'format' => self::FORMAT,
                'version' => self::VERSION,
                'exported_at' => now()->toIso8601String(),
                'source' => config('app.url'),
                'site' => (string) (setting('_general.site_name') ?: config('app.name')),
                'sections' => array_keys($data),
                'includes_credentials' => $credentials,
                'checksum' => $this->checksum($data),
            ],
            'data' => $data,
        ];
    }

    public function toJson(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function checksum(array $data): string
    {
        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
    }

    private function pageTable(): string
    {
        return config('pagebuilder.db_prefix', 'pagebuilder__') . 'pages';
    }

    /** Tabla de cursos (el prefijo lo define el módulo). */
    private function courseTable(): string
    {
        return (new \Modules\Courses\Models\Course())->getTable();
    }

    private function settingsTable(): string
    {
        return config('optionbuilder.db_prefix', 'optionbuilder__') . 'settings';
    }

    private function exportPages(?array $slugs): array
    {
        return DB::table($this->pageTable())->whereNotIn('slug', $this->dedicatedSlugs())->when($slugs !== null, fn ($q) => $q->whereIn('slug', $slugs))->orderBy('id')
            ->get(['id', 'name', 'title', 'description', 'slug', 'settings', 'status', 'gjs_project', 'gjs_html', 'gjs_css', 'edited_at'])
            ->map(function ($p) {
                $row = (array) $p;
                $banner = \App\Models\PageBanner::where('page_key', 'page:' . $row['id'])->first();   // título, subtítulo e imagen de la portada
                unset($row['id']);
                if ($banner) {
                    $row['banner'] = $banner->only(['enabled', 'title', 'subtitle', 'image', 'overlay', 'align']);
                }

                return $row;
            })->all();
    }

    /** Slugs de páginas con vista propia (Nosotros): no se editan con el editor, así que no viajan en «Páginas». */
    private function dedicatedSlugs(): array
    {
        return collect(\App\Http\Controllers\PageController::DEDICATED)->map(fn ($r) => \App\Http\Controllers\PageController::PAGES[$r][0])->all();
    }

    /** Banners de las páginas fijas (clave system:*), por nombre de página. */
    private function exportBanners(): array
    {
        return \App\Models\PageBanner::where('page_key', 'like', 'system:%')->orderBy('page_key')->get()
            ->map(fn ($b) => ['page' => substr($b->page_key, 7)] + $b->only(['enabled', 'title', 'subtitle', 'image', 'overlay', 'align']))->all();
    }

    private function exportCoupons(): array
    {
        $slugs = DB::table($this->courseTable())->pluck('slug', 'id');

        return DB::table('coupons')->orderBy('id')->get()->map(function ($c) use ($slugs) {
            $ids = json_decode((string) $c->course_ids, true);

            return [
                'code' => $c->code, 'description' => $c->description, 'type' => $c->type, 'value' => (float) $c->value, 'min_amount' => $c->min_amount !== null ? (float) $c->min_amount : null,
                'max_uses' => $c->max_uses, 'per_user_limit' => (int) $c->per_user_limit, 'first_purchase_only' => (bool) $c->first_purchase_only,
                'courses' => is_array($ids) ? collect($ids)->map(fn ($id) => $slugs[$id] ?? null)->values()->all() : null,   // null = todos los cursos
                'starts_at' => $c->starts_at, 'ends_at' => $c->ends_at, 'active' => (bool) $c->active,
            ];
        })->all();
    }

    private function exportPaths(): array
    {
        $slugs = DB::table($this->courseTable())->pluck('slug', 'id');
        $courses = DB::table('learning_path_courses')->orderBy('sort')->orderBy('id')->get()->groupBy('learning_path_id');

        return DB::table('learning_paths')->orderBy('sort')->orderBy('id')->get()->map(fn ($p) => [
            'title' => $p->title, 'slug' => $p->slug, 'subtitle' => $p->subtitle, 'description' => $p->description, 'status' => $p->status, 'sort' => (int) $p->sort,
            'courses' => $courses->get($p->id, collect())->map(fn ($c) => $slugs[$c->course_id] ?? null)->filter()->values()->all(),
        ])->all();
    }

    /** @return array<int, array{name:string, location:string, items:array}> */
    private function exportMenus(): array
    {
        $items = DB::table('menu_items')->orderBy('sort')->orderBy('id')->get()->groupBy('menu_id');
        $tree = function ($rows, $parent = null) use (&$tree) {
            return $rows->where('parent_id', $parent)->values()->map(fn ($i) => [
                'label' => $i->label, 'route' => $i->route, 'type' => $i->type, 'class' => $i->class, 'children' => $tree($rows, $i->id),
            ])->all();
        };

        return DB::table('menu')->orderBy('id')->get()->map(fn ($m) => [
            'name' => $m->name, 'location' => $m->location, 'items' => $tree($items->get($m->id, collect())),
        ])->all();
    }

    private function exportSeo(): array
    {
        return DB::table('seo_entries')->orderBy('id')->get(['match_type', 'match_value', 'label', 'title', 'description', 'keywords', 'og_image', 'canonical', 'robots', 'schema', 'is_active'])
            ->map(fn ($r) => (array) $r)->all();
    }

    /** @return array<int, array{section:string, key:string, value:mixed, ser:bool, scrubbed?:bool}> */
    private function exportSettings(array $sections, bool $credentials): array
    {
        $out = [];
        $slugById = DB::table($this->pageTable())->pluck('slug', 'id')->all();
        foreach (DB::table($this->settingsTable())->whereIn('section', $sections)->orderBy('section')->orderBy('key')->get() as $row) {
            if (!$credentials && preg_match(self::SECRET_KEY, $row->key)) {
                continue;   // a whole setting that is a secret (e.g. reCAPTCHA secret key)
            }
            [$value, $ser] = $this->decode($row->value);
            $item = ['section' => $row->section, 'key' => $row->key, 'ser' => $ser];
            if ($ser && is_array($value)) {
                if (!$credentials) {
                    [$value, $removed] = $this->scrub($value);
                    if ($removed) {
                        $item['scrubbed'] = true;
                    }
                }
                if (in_array($row->key, self::PAGE_REF_KEYS[$row->section] ?? [], true)) {
                    $value = $this->tagPageRefs($value, $slugById);
                }
            }
            $item['value'] = $value;
            $out[] = $item;
        }

        return $out;
    }

    /** @return array{0: mixed, 1: bool} decoded value and whether it was a serialized array */
    private function decode(?string $raw): array
    {
        $raw = (string) $raw;
        if (preg_match('/^a:\d+:\{/', $raw)) {
            $v = @unserialize($raw, ['allowed_classes' => false]);
            if (is_array($v)) {
                return [$v, true];
            }
        }

        return [$raw, false];
    }

    /** @return array{0: array, 1: bool} */
    private function scrub(array $value): array
    {
        $removed = false;
        foreach ($value as $k => $v) {
            if (is_string($k) && preg_match(self::SECRET_KEY, $k)) {
                unset($value[$k]);
                $removed = true;
            } elseif (is_array($v)) {
                [$value[$k], $r] = $this->scrub($v);
                $removed = $removed || $r;
            }
        }

        return [$value, $removed];
    }

    private function tagPageRefs(array $value, array $slugById): array
    {
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                if (isset($v['page_id']) && is_numeric($v['page_id']) && isset($slugById[(int) $v['page_id']])) {
                    $v['page_slug'] = $slugById[(int) $v['page_id']];
                    unset($v['page_id']);
                    $value[$k] = $v;
                } else {
                    $value[$k] = $this->tagPageRefs($v, $slugById);
                }
            }
        }

        return $value;
    }

    private function exportTemplates(string $table): array
    {
        return DB::table($table)->whereNull('deleted_at')->orderBy('id')->get(['title', 'type', 'role', 'content', 'status'])->map(fn ($r) => (array) $r)->all();
    }

    private function exportCategories(): array
    {
        $rows = DB::table('courses_categories')->whereNull('deleted_at')->orderBy('id')->get();
        $tree = function ($parent) use (&$tree, $rows) {
            return $rows->where('parent_id', $parent)->values()->map(fn ($c) => [
                'name' => $c->name, 'slug' => $c->slug, 'description' => $c->description, 'image' => $c->image, 'status' => $c->status, 'children' => $tree($c->id),
            ])->all();
        };

        return $tree(null);
    }

    private function exportForms(): array
    {
        return [
            'contacts' => DB::table('contacts')->orderBy('id')->get(['name', 'email', 'subject', 'message', 'created_at'])->map(fn ($r) => (array) $r)->all(),
            'subscribers' => Schema::hasTable('subscribers') ? DB::table('subscribers')->orderBy('id')->get(['email', 'is_active'])->map(fn ($r) => (array) $r)->all() : [],
        ];
    }

    // ------------------------------------------------------------------------------------------------ read / validate

    /** Parses and validates the contents of an exported file. @throws ConfigTransferException */
    public function parse(string $json): array
    {
        if (strlen($json) > self::MAX_BYTES) {
            throw new ConfigTransferException('El archivo es demasiado grande (máximo ' . (int) (self::MAX_BYTES / 1_000_000) . ' MB).');
        }
        $payload = json_decode($json, true, 64);
        if (!is_array($payload) || !isset($payload['meta'], $payload['data']) || ($payload['meta']['format'] ?? null) !== self::FORMAT) {
            throw new ConfigTransferException('No es un archivo de exportación de Horizontia (o está dañado).');
        }
        if ((int) ($payload['meta']['version'] ?? 0) > self::VERSION) {
            throw new ConfigTransferException('El archivo viene de una versión más nueva de Horizontia. Actualiza esta instalación antes de importarlo.');
        }
        if (($payload['meta']['checksum'] ?? '') !== $this->checksum($payload['data'])) {
            throw new ConfigTransferException('El archivo fue modificado o se dañó al copiarlo (la suma de verificación no coincide). Expórtalo de nuevo.');
        }

        return $payload;
    }

    /** What the file carries, for the import screen. */
    public function summarize(array $payload): array
    {
        $labels = self::sections();
        $out = [];
        foreach ($payload['data'] as $key => $data) {
            if (!isset($labels[$key])) {
                continue;
            }
            $out[$key] = [
                'label' => $labels[$key]['label'],
                'count' => match ($key) {
                    'pages', 'menus', 'seo', 'banners', 'coupons', 'paths' => count($data),
                    'categories' => $this->countTree($data),
                    'templates' => count($data['email'] ?? []) + count($data['notification'] ?? []) + count($data['settings'] ?? []),
                    'forms' => count($data['contacts'] ?? []) + count($data['subscribers'] ?? []),
                    default => count($data['settings'] ?? []),
                },
                'sensitive' => $labels[$key]['sensitive'],
            ];
        }

        return $out;
    }

    private function countTree(array $nodes): int
    {
        return array_sum(array_map(fn ($n) => 1 + $this->countTree($n['children'] ?? []), $nodes));
    }

    // ------------------------------------------------------------------------------------------------ import

    /**
     * @param  array<int, string> $sections
     * @param  array{pages?: ?array<int,string>, dry?: bool} $options  pages = slugs to import (null = all in the file)
     * @return array{sections: array<string, array{created:int, updated:int, skipped:int, notes: array<int,string>}>, warnings: array<int,string>, dry: bool}
     */
    public function import(array $payload, array $sections, string $mode = self::MODE_SKIP, array $options = []): array
    {
        $dry = !empty($options['dry']);
        $mode = $mode === self::MODE_OVERWRITE ? self::MODE_OVERWRITE : self::MODE_SKIP;
        $known = array_keys(self::sections());
        $sections = array_values(array_intersect($known, array_intersect($sections, array_keys($payload['data']))));
        if (!empty($payload['data']['credentials']) && empty($payload['meta']['includes_credentials'])) {
            $sections = array_values(array_diff($sections, ['credentials']));   // never trust a file that says it has no credentials
        }

        $report = ['sections' => [], 'warnings' => [], 'dry' => $dry];
        DB::beginTransaction();
        try {
            foreach ($sections as $s) {
                $data = $payload['data'][$s];
                $report['sections'][$s] = match ($s) {
                    'pages' => $this->importPages($data, $mode, $options['pages'] ?? null),
                    'banners' => $this->importBanners($data, $mode),
                    'menus' => $this->importMenus($data, $mode),
                    'seo' => $this->importSeo($data, $mode),
                    'general', 'integrations', 'payments', 'credentials' => $this->importSettings($data['settings'] ?? [], self::SETTING_SECTIONS[$s], $mode),
                    'templates' => $this->importTemplates($data, $mode),
                    'coupons' => $this->importCoupons($data, $mode),
                    'paths' => $this->importPaths($data, $mode),
                    'categories' => $this->importCategories($data, $mode),
                    'forms' => $this->importForms($data),
                };
            }
            $report['warnings'] = $this->warnings($payload, $sections);
            $dry ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        if (!$dry) {
            $this->flushCaches();
            Log::info('Importación de configuración', ['by' => auth()->id(), 'mode' => $mode, 'sections' => array_map(fn ($r) => Arr::only($r, ['created', 'updated', 'skipped']), $report['sections'])]);
        }

        return $report;
    }

    private function flushCaches(): void
    {
        Cache::forget('optionbuilder__settings');
        Cache::forget(SeoEntry::CACHE_KEY);
        MenuService::flush();
    }

    private function counter(): array
    {
        return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'notes' => []];
    }

    private function importPages(array $pages, string $mode, ?array $only): array
    {
        $r = $this->counter();
        $table = $this->pageTable();
        foreach ($pages as $p) {
            $slug = (string) ($p['slug'] ?? '');
            if ($slug === '' || !preg_match('#^(/|[a-z0-9][a-z0-9_-]*(/[a-z0-9][a-z0-9_-]*)*)$#i', $slug) || in_array($slug, $this->dedicatedSlugs(), true) || ($only !== null && !in_array($slug, $only, true))) {
                $r['skipped']++;
                continue;
            }
            $row = [
                'name' => mb_substr((string) ($p['name'] ?? $slug), 0, 255), 'title' => (string) ($p['title'] ?? $p['name'] ?? $slug), 'description' => $p['description'] ?? null,
                'settings' => $p['settings'] ?? '{}', 'status' => in_array($p['status'] ?? '', ['published', 'draft'], true) ? $p['status'] : 'draft',
                'gjs_project' => $p['gjs_project'] ?? null,
                'gjs_html' => isset($p['gjs_html']) ? PageHtmlSanitizer::html((string) $p['gjs_html']) : null,
                'gjs_css' => isset($p['gjs_css']) ? PageHtmlSanitizer::css((string) $p['gjs_css']) : null,
                'edited_at' => $p['edited_at'] ?? null,
            ];
            $existing = DB::table($table)->where('slug', $slug)->first();
            $pageId = null;
            if ($existing) {
                if ($mode === self::MODE_OVERWRITE) {
                    DB::table($table)->where('id', $existing->id)->update($row + ['updated_at' => now()]);
                    $pageId = $existing->id;
                    $r['updated']++;
                } else {
                    $r['skipped']++;
                }
            } else {
                $pageId = DB::table($table)->insertGetId($row + ['slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
                $r['created']++;
            }
            if ($pageId && is_array($p['banner'] ?? null)) {
                $this->importBanner($pageId, $p['banner']);
            }
        }

        return $r;
    }

    /** Banner de una página importada: textos limpios; la imagen solo si es una ruta de nuestro almacenamiento (nunca una URL de fuera). */
    private function importBanner(int $pageId, array $b): void
    {
        $image = (string) ($b['image'] ?? '');
        \App\Models\PageBanner::updateOrCreate(['page_key' => 'page:' . $pageId], [
            'enabled' => (bool) ($b['enabled'] ?? true),
            'title' => mb_substr(trim(strip_tags((string) ($b['title'] ?? ''))), 0, 140) ?: null,
            'subtitle' => mb_substr(trim(strip_tags((string) ($b['subtitle'] ?? ''))), 0, 280) ?: null,
            'image' => preg_match('#^page-banners/[A-Za-z0-9/_\-.]+$#', $image) && !str_contains($image, '..') ? $image : null,
            'overlay' => max(0, min(85, (int) ($b['overlay'] ?? 55))),
            'align' => ($b['align'] ?? 'left') === 'center' ? 'center' : 'left',
        ]);
        \App\Services\PageBannerService::forget('page:' . $pageId);
    }

    /** Banners de páginas fijas: solo las páginas que existen en la aplicación; mismos textos limpios que en las páginas del editor. */
    private function importBanners(array $rows, string $mode): array
    {
        $r = $this->counter();
        foreach ($rows as $b) {
            $name = (string) ($b['page'] ?? '');
            if (!isset(\App\Services\PageBannerService::SYSTEM[$name])) {
                $r['skipped']++;
                continue;
            }
            $key = 'system:' . $name;
            $exists = \App\Models\PageBanner::where('page_key', $key)->exists();
            if ($exists && $mode !== self::MODE_OVERWRITE) {
                $r['skipped']++;
                continue;
            }
            $image = (string) ($b['image'] ?? '');
            \App\Models\PageBanner::updateOrCreate(['page_key' => $key], [
                'enabled' => (bool) ($b['enabled'] ?? true),
                'title' => mb_substr(trim(strip_tags((string) ($b['title'] ?? ''))), 0, 140) ?: null,
                'subtitle' => mb_substr(trim(strip_tags((string) ($b['subtitle'] ?? ''))), 0, 280) ?: null,
                'image' => preg_match('#^page-banners/[A-Za-z0-9/_\-.]+$#', $image) && !str_contains($image, '..') ? $image : null,
                'overlay' => max(0, min(85, (int) ($b['overlay'] ?? 55))),
                'align' => ($b['align'] ?? 'left') === 'center' ? 'center' : 'left',
            ]);
            \App\Services\PageBannerService::forget($key);
            $exists ? $r['updated']++ : $r['created']++;
        }

        return $r;
    }

    /** Cupones: nunca se amplía un descuento por error: si falta un curso al que estaba limitado, el cupón no se importa. */
    private function importCoupons(array $rows, string $mode): array
    {
        $r = $this->counter();
        $ids = DB::table($this->courseTable())->pluck('id', 'slug');
        foreach ($rows as $c) {
            $code = strtoupper(trim((string) ($c['code'] ?? '')));
            $type = (string) ($c['type'] ?? '');
            $value = (float) ($c['value'] ?? 0);
            if (!preg_match('/^[A-Z0-9_-]{3,40}$/', $code) || !in_array($type, ['percent', 'fixed'], true) || $value <= 0 || ($type === 'percent' && $value > 100)) {
                $r['skipped']++;
                continue;
            }
            $courseIds = null;
            if (is_array($c['courses'] ?? null)) {
                $courseIds = collect($c['courses'])->map(fn ($slug) => $ids[(string) $slug] ?? null)->all();
                if (in_array(null, $courseIds, true) || $courseIds === []) {
                    $r['skipped']++;
                    $r['notes'][] = "Cupón {$code}: no se importó porque uno de sus cursos no existe en este sitio.";
                    continue;
                }
            }
            $row = [
                'description' => isset($c['description']) ? mb_substr(strip_tags((string) $c['description']), 0, 160) : null, 'type' => $type, 'value' => $value,
                'min_amount' => isset($c['min_amount']) ? max(0, (float) $c['min_amount']) : null,
                'max_uses' => isset($c['max_uses']) ? max(0, (int) $c['max_uses']) : null, 'per_user_limit' => max(1, (int) ($c['per_user_limit'] ?? 1)),
                'first_purchase_only' => (bool) ($c['first_purchase_only'] ?? false), 'course_ids' => $courseIds === null ? null : json_encode(array_values($courseIds)),
                'starts_at' => $c['starts_at'] ?? null, 'ends_at' => $c['ends_at'] ?? null, 'active' => (bool) ($c['active'] ?? true),
            ];
            $existing = DB::table('coupons')->where('code', $code)->first();
            if ($existing) {
                if ($mode === self::MODE_OVERWRITE) {
                    DB::table('coupons')->where('id', $existing->id)->update($row + ['updated_at' => now()]);   // used_count se conserva
                    $r['updated']++;
                } else {
                    $r['skipped']++;
                }
            } else {
                DB::table('coupons')->insert($row + ['code' => $code, 'used_count' => 0, 'created_at' => now(), 'updated_at' => now()]);
                $r['created']++;
            }
        }

        return $r;
    }

    /** Rutas de aprendizaje: los cursos se enlazan por su slug; si falta alguno la ruta queda en borrador para que no se publique incompleta. */
    private function importPaths(array $rows, string $mode): array
    {
        $r = $this->counter();
        $ids = DB::table($this->courseTable())->pluck('id', 'slug');
        foreach ($rows as $p) {
            $slug = (string) ($p['slug'] ?? '');
            $title = mb_substr(trim(strip_tags((string) ($p['title'] ?? ''))), 0, 160);
            if ($title === '' || !preg_match('/^[a-z0-9][a-z0-9_-]{0,189}$/i', $slug)) {
                $r['skipped']++;
                continue;
            }
            $wanted = array_values(array_filter((array) ($p['courses'] ?? []), 'is_string'));
            $found = collect($wanted)->map(fn ($s) => $ids[$s] ?? null)->filter()->unique()->values();
            $incomplete = $found->count() < count($wanted);
            $status = ($p['status'] ?? 'draft') === 'active' && !$incomplete && $found->isNotEmpty() ? 'active' : 'draft';
            $row = [
                'title' => $title, 'subtitle' => isset($p['subtitle']) ? mb_substr(strip_tags((string) $p['subtitle']), 0, 255) : null,
                'description' => isset($p['description']) ? strip_tags((string) $p['description'], '<p><br><strong><em><ul><ol><li><a>') : null,
                'status' => $status, 'sort' => max(0, (int) ($p['sort'] ?? 0)),
            ];
            $existing = DB::table('learning_paths')->where('slug', $slug)->first();
            if ($existing && $mode !== self::MODE_OVERWRITE) {
                $r['skipped']++;
                continue;
            }
            if ($existing) {
                DB::table('learning_paths')->where('id', $existing->id)->update($row + ['updated_at' => now()]);
                DB::table('learning_path_courses')->where('learning_path_id', $existing->id)->delete();
                $pathId = $existing->id;
                $r['updated']++;
            } else {
                $pathId = DB::table('learning_paths')->insertGetId($row + ['slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
                $r['created']++;
            }
            foreach ($found as $i => $courseId) {
                DB::table('learning_path_courses')->insert(['learning_path_id' => $pathId, 'course_id' => $courseId, 'sort' => $i]);
            }
            if ($incomplete && ($p['status'] ?? '') === 'active') {
                $r['notes'][] = "Ruta «{$title}»: faltan cursos en este sitio, quedó en borrador.";
            }
        }

        return $r;
    }

    private function importMenus(array $menus, string $mode): array
    {
        $r = $this->counter();
        $bad = 0;
        foreach ($menus as $m) {
            $name = mb_substr(trim((string) ($m['name'] ?? '')), 0, 120);
            $location = in_array($m['location'] ?? '', ['header', 'footer'], true) ? $m['location'] : null;
            if ($name === '' || !$location) {
                $r['skipped']++;
                continue;
            }
            $existing = DB::table('menu')->where('name', $name)->where('location', $location)->first();
            if ($existing && $mode !== self::MODE_OVERWRITE) {
                $r['skipped']++;
                continue;
            }
            if ($existing) {
                DB::table('menu_items')->where('menu_id', $existing->id)->delete();
                $menuId = $existing->id;
                $r['updated']++;
            } else {
                $menuId = DB::table('menu')->insertGetId(['name' => $name, 'location' => $location, 'created_at' => now(), 'updated_at' => now()]);
                $r['created']++;
            }
            $this->insertMenuItems($menuId, $m['items'] ?? [], null, $bad);
        }
        if ($bad) {
            $r['notes'][] = "{$bad} enlace(s) no válidos se omitieron.";
        }

        return $r;
    }

    private function insertMenuItems(int $menuId, array $items, ?int $parent, int &$bad): void
    {
        foreach (array_values($items) as $sort => $i) {
            $type = in_array($i['type'] ?? '', ['route', 'url', 'page'], true) ? $i['type'] : null;
            $target = trim((string) ($i['route'] ?? ''));
            $label = mb_substr(trim((string) ($i['label'] ?? '')), 0, 120);
            if (!$type || $label === '' || $target === '' || preg_match('/^\s*(javascript|data|vbscript):/i', $target) || ($type === 'route' && !Route::has($target))) {
                $bad++;
                continue;
            }
            $id = DB::table('menu_items')->insertGetId([
                'menu_id' => $menuId, 'parent_id' => $parent, 'label' => $label, 'route' => mb_substr($target, 0, 500), 'type' => $type,
                'sort' => $sort, 'class' => $i['class'] ?? null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            if (!empty($i['children']) && $parent === null) {   // the builder supports one level of submenu
                $this->insertMenuItems($menuId, $i['children'], $id, $bad);
            }
        }
    }

    private function importSeo(array $rows, string $mode): array
    {
        $r = $this->counter();
        foreach ($rows as $s) {
            $type = (string) ($s['match_type'] ?? '');
            $value = (string) ($s['match_value'] ?? '');
            if ($type === '' || $value === '') {
                $r['skipped']++;
                continue;
            }
            $row = [
                'label' => $s['label'] ?? null, 'title' => $s['title'] ?? null, 'description' => $s['description'] ?? null, 'keywords' => $s['keywords'] ?? null,
                'og_image' => $s['og_image'] ?? null, 'canonical' => $s['canonical'] ?? null, 'robots' => $s['robots'] ?? null, 'schema' => $s['schema'] ?? null,
                'is_active' => (int) ($s['is_active'] ?? 1),
            ];
            $existing = DB::table('seo_entries')->where('match_type', $type)->where('match_value', $value)->first();
            if ($existing) {
                if ($mode === self::MODE_OVERWRITE) {
                    DB::table('seo_entries')->where('id', $existing->id)->update($row + ['updated_at' => now()]);
                    $r['updated']++;
                } else {
                    $r['skipped']++;
                }
            } else {
                DB::table('seo_entries')->insert($row + ['match_type' => $type, 'match_value' => $value, 'created_at' => now(), 'updated_at' => now()]);
                $r['created']++;
            }
        }

        return $r;
    }

    /** @param array<int, array> $rows */
    private function importSettings(array $rows, array $allowedSections, string $mode): array
    {
        $r = $this->counter();
        $table = $this->settingsTable();
        $idBySlug = null;
        foreach ($rows as $s) {
            $section = (string) ($s['section'] ?? '');
            $key = (string) ($s['key'] ?? '');
            if ($section === '' || $key === '' || !in_array($section, $allowedSections, true)) {
                $r['skipped']++;
                continue;
            }
            $value = $s['value'] ?? '';
            $ser = !empty($s['ser']);
            if ($ser && is_array($value)) {
                if (in_array($key, self::PAGE_REF_KEYS[$section] ?? [], true)) {
                    $idBySlug ??= DB::table($this->pageTable())->pluck('id', 'slug')->all();
                    $value = $this->resolvePageRefs($value, $idBySlug);
                }
            } elseif (!is_string($value)) {
                $r['skipped']++;
                continue;
            }

            $existing = DB::table($table)->where('section', $section)->where('key', $key)->first();
            if ($existing && $mode !== self::MODE_OVERWRITE) {
                $r['skipped']++;
                continue;
            }
            if ($existing && $ser && !empty($s['scrubbed'])) {
                // The file left its secrets out: keep the ones this installation already has.
                [$current, $isSer] = $this->decode($existing->value);
                if ($isSer && is_array($current)) {
                    $value = array_replace_recursive($current, $value);
                }
            }
            $stored = $ser && is_array($value) ? serialize($value) : (string) $value;
            DB::table($table)->updateOrInsert(['section' => $section, 'key' => $key], ['value' => $stored]);
            $existing ? $r['updated']++ : $r['created']++;
        }

        return $r;
    }

    private function resolvePageRefs(array $value, array $idBySlug): array
    {
        foreach ($value as $k => $v) {
            if (!is_array($v)) {
                continue;
            }
            if (isset($v['page_slug'])) {
                if (isset($idBySlug[$v['page_slug']])) {
                    $v['page_id'] = $idBySlug[$v['page_slug']];
                    unset($v['page_slug']);
                    $value[$k] = $v;
                } else {
                    unset($value[$k]);   // that page does not exist here: the entry would point nowhere
                }
            } else {
                $value[$k] = $this->resolvePageRefs($v, $idBySlug);
            }
        }

        return is_array($value) && array_is_list($value) ? array_values($value) : $value;
    }

    private function importTemplates(array $data, string $mode): array
    {
        $r = $this->counter();
        foreach (['email' => 'email_templates', 'notification' => 'notification_templates'] as $key => $table) {
            foreach ($data[$key] ?? [] as $t) {
                $type = (string) ($t['type'] ?? '');
                $role = (string) ($t['role'] ?? '');
                $content = (string) ($t['content'] ?? '');
                if ($type === '' || $content === '' || preg_match('/^[OC]:\d+:/', $content)) {   // never store a serialized object
                    $r['skipped']++;
                    continue;
                }
                $row = ['title' => mb_substr((string) ($t['title'] ?? $type), 0, 255), 'content' => $content, 'status' => (string) ($t['status'] ?? 'active')];
                $existing = DB::table($table)->where('type', $type)->where('role', $role)->whereNull('deleted_at')->first();
                if ($existing) {
                    if ($mode === self::MODE_OVERWRITE) {
                        DB::table($table)->where('id', $existing->id)->update($row + ['updated_at' => now()]);
                        $r['updated']++;
                    } else {
                        $r['skipped']++;
                    }
                } else {
                    DB::table($table)->insert($row + ['type' => $type, 'role' => $role, 'created_at' => now(), 'updated_at' => now()]);
                    $r['created']++;
                }
            }
        }
        $s = $this->importSettings($data['settings'] ?? [], self::SETTING_SECTIONS['templates'], $mode);
        foreach (['created', 'updated', 'skipped'] as $k) {
            $r[$k] += $s[$k];
        }

        return $r;
    }

    private function importCategories(array $tree, string $mode, ?int $parent = null, ?array &$r = null): array
    {
        $r ??= $this->counter();
        foreach ($tree as $c) {
            $slug = (string) ($c['slug'] ?? '');
            $name = mb_substr((string) ($c['name'] ?? ''), 0, 255);
            if ($slug === '' || $name === '') {
                $r['skipped']++;
                continue;
            }
            $row = ['name' => $name, 'description' => $c['description'] ?? $name, 'image' => $c['image'] ?? null, 'status' => in_array($c['status'] ?? '', ['active', 'inactive'], true) ? $c['status'] : 'active', 'parent_id' => $parent];
            // The same slug can exist under different parents (e.g. "photoshop" in two areas): a category is (parent, slug).
            $existing = DB::table('courses_categories')->where('slug', $slug)->when($parent === null, fn ($q) => $q->whereNull('parent_id'), fn ($q) => $q->where('parent_id', $parent))->first();
            if ($existing) {
                $id = $existing->id;
                if ($mode === self::MODE_OVERWRITE || $existing->deleted_at) {
                    DB::table('courses_categories')->where('id', $id)->update($row + ['deleted_at' => null, 'updated_at' => now()]);
                    $r['updated']++;
                } else {
                    $r['skipped']++;
                }
            } else {
                $id = DB::table('courses_categories')->insertGetId($row + ['slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
                $r['created']++;
            }
            $this->importCategories($c['children'] ?? [], $mode, (int) $id, $r);
        }

        return $r;
    }

    private function importForms(array $data): array
    {
        $r = $this->counter();
        foreach ($data['contacts'] ?? [] as $c) {
            $email = (string) ($c['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || DB::table('contacts')->where('email', $email)->where('subject', $c['subject'] ?? '')->where('created_at', $c['created_at'] ?? null)->exists()) {
                $r['skipped']++;
                continue;
            }
            DB::table('contacts')->insert([
                'name' => mb_substr((string) ($c['name'] ?? ''), 0, 255), 'email' => $email, 'subject' => mb_substr((string) ($c['subject'] ?? ''), 0, 255),
                'message' => (string) ($c['message'] ?? ''), 'created_at' => $c['created_at'] ?? now(), 'updated_at' => now(),
            ]);
            $r['created']++;
        }
        foreach ($data['subscribers'] ?? [] as $s) {
            $email = (string) ($s['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !Schema::hasTable('subscribers') || DB::table('subscribers')->where('email', $email)->exists()) {
                $r['skipped']++;
                continue;
            }
            DB::table('subscribers')->insert(['email' => $email, 'is_active' => (int) ($s['is_active'] ?? 1), 'created_at' => now(), 'updated_at' => now()]);
            $r['created']++;
        }

        return $r;
    }

    /** @return array<int, string> */
    private function warnings(array $payload, array $sections): array
    {
        $w = [];
        $files = 0;
        foreach ($sections as $s) {
            foreach ($payload['data'][$s]['settings'] ?? [] as $row) {
                if (preg_match(self::FILE_KEY, (string) ($row['key'] ?? '')) && !empty($row['value'])) {
                    $files++;
                }
            }
        }
        if ($files) {
            $w[] = "{$files} ajuste(s) apuntan a imágenes o archivos (logos, favicon…). Los archivos NO se copian: se verán si usas el mismo almacenamiento (p. ej. el mismo bucket de R2); si no, vuelve a subirlos.";
        }
        if (in_array('pages', $sections, true)) {
            $w[] = 'Las imágenes que usan las páginas tampoco se copian; con el mismo almacenamiento se ven igual.';
        }
        if (in_array('banners', $sections, true)) {
            $w[] = 'Las imágenes de banner propias (subidas en Admin) no se copian; las imágenes por defecto de cada página viajan con el código.';
        }
        if (array_intersect(['coupons', 'paths'], $sections)) {
            $w[] = 'Cupones y rutas enlazan cursos por su enlace (slug): importa antes los cursos en este sitio para que se reconozcan.';
        }
        if (in_array('menus', $sections, true) && !in_array('pages', $sections, true)) {
            $w[] = 'Los enlaces del menú a páginas propias solo funcionan si esas páginas existen en este sitio.';
        }
        if (in_array('credentials', $sections, true) || !empty($payload['meta']['includes_credentials'])) {
            $w[] = 'Este archivo contiene credenciales: guárdalo en un lugar seguro y bórralo después de importarlo.';
        }

        return $w;
    }
}
