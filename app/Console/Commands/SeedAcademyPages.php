<?php

namespace App\Console\Commands;

use App\Services\BuilderPageFactory;
use App\Services\MenuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Larabuild\Pagebuilder\Models\Page;

/**
 * Turns the legal/info pages that used to be hard-coded Blade views into editable page-builder pages
 * (Admin > Páginas). Safe to re-run: pages edited in the builder are kept unless --force is given.
 */
class SeedAcademyPages extends Command
{
    protected $signature = 'academy:pages {--force : Overwrite pages that already exist} {--only= : Only this page (slug), e.g. como-funciona} {--prune : Delete the English demo pages of the theme} {--legacy : Keep pages in the old block format (skip the visual-editor conversion)}';

    protected $description = 'Create the Spanish legal/info pages as page-builder pages';

    private const DEMO_SLUGS = ['home-two', 'home-three', 'home-four', 'home-five', 'home-six', 'home-seven', 'home-eight', 'home-nine', 'faq', 'how-it-works', 'about-us', 'terms-condition', 'privacy-policy'];

    public function handle(): int
    {
        foreach (\App\Http\Controllers\PageController::PAGES as $route => [$slug]) {
            if (in_array($route, \App\Http\Controllers\PageController::DEDICATED, true)) {
                continue;   // tiene vista propia
            }
            if ($this->option('only') && $this->option('only') !== $slug) {
                continue;
            }
            if (Page::where('slug', $slug)->exists() && !$this->option('force')) {
                $this->line("= {$slug} (ya existe)");
                continue;
            }
            [$title, $description, $blocks] = $this->convert($route, $slug);
            $page = BuilderPageFactory::upsert($slug, $title, $title, $description, $blocks);
            // Seeded pages are born editable in the visual editor (their block HTML becomes the page content).
            $site = \App\Models\SitePage::find($page->id);
            $site->forceFill(['gjs_html' => null, 'gjs_project' => null, 'gjs_css' => null])->save();
            if (!$this->option('legacy')) {
                app(\App\Services\Pages\LegacyPageConverter::class)->convert($site->refresh());
            }
            $this->info("+ {$slug}");
        }

        if ($this->option('prune')) {
            $n = Page::whereIn('slug', self::DEMO_SLUGS)->delete();
            $this->info("Páginas demo eliminadas: {$n}");
        }

        app(MenuService::class)->flush();

        return self::SUCCESS;
    }

    /** @return array{0:string,1:?string,2:array} */
    private function convert(string $route, string $slug): array
    {
        $view = str_replace('.', '/', \App\Http\Controllers\PageController::PAGES[$route][1]);
        $file = resource_path('views/' . $view . '.blade.php');
        $src = File::get($file);

        preg_match('~<x-front\.page\s+title="([^"]*)"(?:\s+description="([^"]*)")?(\s+:updated=)?[^>]*>(.*)</x-front\.page>~s', $src, $m);
        [$title, $description, $withUpdated, $body] = [$m[1], $m[2] ?? null, !empty($m[3]), $m[4]];

        $body = preg_replace('~@push\(.*?@endpush~s', '', $body);
        $body = str_replace("config('academy.refund_days')", "'[[refund_days]]'", $body);

        // Render with tokens in place of the real values, so the stored HTML stays config-driven.
        $html = Blade::render($body, [
            'name' => '[[company]]', 'email' => '[[email]]', 'address' => '[[address]]',
            'refundDays' => '[[refund_days]]', 'jurisdiction' => '[[jurisdiction]]',
        ]);
        $html = str_replace(rtrim(url('/'), '/') . '/', '/', $html);
        $html = trim(preg_replace('~[ \t]*\n[ \t]*~', "\n", $html));

        $blocks = [['ht-hero', ['heading' => $title, 'text' => $description, 'style' => 'light']]];

        if ($route === 'legal.faq') {
            [$items, $rest] = $this->parseAccordion($html);
            $blocks[] = ['ht-faq', ['heading' => '', 'items' => $items]];
            if (trim($rest) !== '') {
                $blocks[] = ['ht-rich-text', ['content' => $rest, 'width' => 'narrow']];
            }
        } elseif ($route === 'how-it-works') {
            $blocks[] = ['ht-steps', ['heading' => '', 'items' => [
                ['title' => 'Solicita tu acceso', 'text' => 'Nadie se registra solo: pide acceso como estudiante o instructor. Revisamos tu solicitud y, al aprobarla, te enviamos un correo para elegir tu contraseña.'],
                ['title' => 'Elige tu curso', 'text' => 'Con tu cuenta activa, explora el catálogo, revisa el temario, las lecciones de vista previa y las reseñas.'],
                ['title' => 'Paga con Mercado Pago', 'text' => 'Tarjeta, efectivo o transferencia. Los precios son en MXN y el pago es seguro.'],
                ['title' => 'Aprende a tu ritmo', 'text' => 'Tu curso se activa al aprobarse el pago. Mira los videos, resuelve evaluaciones y pregunta al tutor IA o a tu instructor.'],
                ['title' => 'Obtén tu certificado', 'text' => 'Al completar el curso recibes un certificado con código y página pública para verificarlo.'],
            ]]];
            $blocks[] = ['ht-rich-text', ['content' => '<p>¿Dudas? Revisa las <a href="/preguntas-frecuentes">preguntas frecuentes</a> o la <a href="/politica-de-reembolsos">política de reembolsos</a>.</p>', 'width' => 'narrow']];
            $blocks[] = ['ht-cta', ['heading' => 'Empieza hoy', 'text' => 'Pide tu acceso y empieza a aprender a tu ritmo.', 'button_text' => 'Solicitar acceso', 'button_url' => '/solicitar-acceso', 'style' => 'gold']];
        } else {
            if ($withUpdated) {
                $html .= "\n" . '<p class="ht-prose__meta">Última actualización: [[updated]]</p>';
            }
            $blocks[] = ['ht-rich-text', ['content' => $html, 'width' => 'narrow']];
            if ($route === 'about') {
                $blocks[] = ['ht-cta', ['heading' => '¿Listo para aprender?', 'text' => 'Explora el catálogo y empieza hoy.', 'button_text' => 'Ver cursos', 'button_url' => '/cursos', 'style' => 'gold']];
            }
        }

        return [$title, $description, $blocks];
    }

    /** @return array{0: array<int,array{question:string,answer:string}>, 1: string} accordion items + HTML left after it */
    private function parseAccordion(string $html): array
    {
        preg_match_all('~<button[^>]*accordion-button[^>]*>(.*?)</button>.*?<div class="accordion-body">(.*?)</div>~s', $html, $m, PREG_SET_ORDER);
        $items = array_map(fn ($x) => ['question' => trim(strip_tags($x[1])), 'answer' => trim($x[2])], $m);

        $pos = strrpos($html, '</div>');
        $rest = $pos === false ? '' : substr($html, $pos + 6);

        return [$items, trim($rest)];
    }
}
