<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Encabezado del sitio sin desbordes en teléfonos y hoja de impresión de la confirmación de eventos. */
class MobileHeaderAndPrintTest extends TestCase
{
    public function test_guest_header_offers_request_access_in_the_bar_and_inside_the_mobile_menu(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('am-request-link am-request-link--top', $html, 'enlace de la barra (escritorio y tabletas)');
        $this->assertStringContainsString('ht-nav__request', $html, 'el mismo enlace dentro del menú plegable');
        $this->assertSame(1, preg_match('#<li class="nav-item ht-nav__item ht-nav__request"><a class="nav-link" href="[^"]+">Solicitar acceso</a></li>#', $html));
    }

    public function test_narrow_phones_move_request_access_into_the_menu_and_the_newsletter_cannot_overflow(): void
    {
        $css = file_get_contents(public_path('css/ux-polish.css'));
        $this->assertStringContainsString("@media (max-width: 430px) {\n    .am-header_two_wrap .am-request-link--top { display: none !important; }\n    .ht-nav__request { display: list-item; }", $css);
        $this->assertStringContainsString('.am-newsletter .am-newsletter-text, .am-newsletter .am-newsletter-form { min-width: 0 !important;', $css);
    }

    public function test_event_print_sheet_hides_site_chrome_and_keeps_tickets_on_white(): void
    {
        $css = file_get_contents(public_path('css/event.css'));
        $print = substr($css, (int) strrpos($css, '@media print'));
        foreach (['.evp-sub', '.ev-steps', '.evf', '.hz-wa', '#hz-install', '[aria-label="Aviso de cookies"]', '.ev-actions'] as $hidden) {
            $this->assertStringContainsString($hidden, $print, "se oculta al imprimir: $hidden");
        }
        $this->assertStringContainsString('display:none !important', $print);
        $this->assertStringContainsString('background:#fff !important', $print);
        $this->assertStringContainsString('.ev-ticket-card { break-inside:avoid', $print);
        $this->assertStringContainsString('print-color-adjust:exact', $print);
    }
}
