<?php

namespace App\Services;

use App\Models\PageBanner;
use App\Models\SitePage;
use Illuminate\Support\Facades\Cache;

/**
 * Banner de las páginas estáticas: título, subtítulo e imagen que se cambian desde Administración › Banners de páginas.
 * Mientras una página no tenga banner propio activado, se ve exactamente como siempre (el bloque de portada de su contenido).
 */
class PageBannerService
{
    /** Páginas fijas de la aplicación (no se editan con el editor visual) cuyo banner también se puede cambiar. */
    public const SYSTEM = [
        'contacto' => ['label' => 'Contacto', 'title' => 'Hablemos', 'subtitle' => 'Resolvemos tus dudas sobre cursos, pagos y acceso. Normalmente respondemos en menos de 24 horas.', 'url' => '/contacto', 'image' => 'images/nuevo/banner-contacto.jpg'],
        'nosotros' => ['label' => 'Nosotros', 'title' => 'Nosotros', 'subtitle' => 'Una academia en línea para aprender a tu ritmo, con instructores que acompañan.', 'url' => '/nosotros', 'image' => 'images/nuevo/banner-nosotros.jpg'],
        'solicitar-acceso' => ['label' => 'Solicitar acceso', 'title' => 'Solicitar acceso', 'subtitle' => 'Pide tu cuenta como estudiante o como instructor.', 'url' => '/solicitar-acceso', 'image' => 'images/nuevo/banner-acceso.jpg'],
    ];

    public static function keyFor(SitePage $page): string
    {
        return 'page:' . $page->id;
    }

    public function find(string $key): ?PageBanner
    {
        return Cache::remember('page_banner:' . $key, 600, fn () => PageBanner::where('page_key', $key)->first() ?? false) ?: null;
    }

    public static function forget(string $key): void
    {
        Cache::forget('page_banner:' . $key);
    }

    /** El banner activo de una página del editor, o null para mostrar su portada de siempre. */
    public function forPage(SitePage $page): ?PageBanner
    {
        $banner = $this->find(self::keyFor($page));

        return $banner && $banner->enabled && trim((string) $banner->title) !== '' ? $banner : null;
    }

    /** Datos listos para dibujar un banner de página fija (con los valores de fábrica si no hay uno propio). */
    public function system(string $name): array
    {
        $def = self::SYSTEM[$name];
        $banner = $this->find('system:' . $name);
        $on = $banner && $banner->enabled && trim((string) $banner->title) !== '';

        return [
            'title' => $on ? $banner->title : $def['title'],
            'subtitle' => $on ? (string) $banner->subtitle : $def['subtitle'],
            'image' => $on ? $banner->imageUrl() : (!empty($def['image']) ? webpAsset($def['image']) : null),
            'overlay' => $on ? (int) $banner->overlay : 55,
            'align' => $on ? $banner->align : 'left',
        ];
    }

    /** Título y subtítulo del bloque de portada (.ht-hero) de una página del editor, para precargar el formulario. @return array{title:string, subtitle:string} */
    public function heroOf(SitePage $page): array
    {
        $html = (string) $page->gjs_html;
        if (!str_contains($html, 'ht-hero')) {
            return ['title' => (string) $page->title, 'subtitle' => ''];
        }
        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="r">' . $html . '</div>', LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $xp = new \DOMXPath($dom);
        $hero = $xp->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' ht-hero ')]")->item(0);
        if (!$hero) {
            return ['title' => (string) $page->title, 'subtitle' => ''];
        }
        $h = $xp->query('.//h1|.//h2', $hero)->item(0);
        $p = $xp->query('.//p', $hero)->item(0);

        return ['title' => trim((string) ($h?->textContent ?: $page->title)), 'subtitle' => trim((string) $p?->textContent)];
    }

    /** Quita del contenido la primera portada (.ht-hero) cuando el banner propio la reemplaza. */
    public function stripHero(string $html): string
    {
        $re = '#^\s*<section class="pb-themesection">((?:(?!<section class="pb-themesection").)*?)<section class="ht-block ht-hero[^"]*">.*?</section>\s*</div>\s*</div>\s*</div>\s*</section>#s';
        $out = preg_replace($re, '', $html, 1, $n);

        return $n === 1 && $out !== null ? $out : $html;
    }
}
