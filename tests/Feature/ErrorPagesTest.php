<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Las páginas de error usan el mismo diseño que el sitio (encabezado y pie con estilos). */
class ErrorPagesTest extends TestCase
{
    public function test_the_404_page_loads_the_site_design_and_says_what_happened(): void
    {
        $html = $this->get('/esta-pagina-no-existe-' . uniqid())->assertNotFound()->getContent();

        $this->assertStringContainsString('css/horizontia-ui.css', $html, 'the header/footer are styled by the site design system');
        $this->assertStringContainsString('css/main.css', $html);
        $this->assertStringContainsString('No encontramos esa página', $html);
        $this->assertStringContainsString('js/select2.min.js', $html, 'the header language picker needs its scripts');
    }

    public function test_error_pages_use_the_same_stylesheet_set_as_the_frontend_layout(): void
    {
        $error = $this->get('/esta-pagina-no-existe-' . uniqid())->getContent();
        $home = $this->get('/cursos')->getContent();

        foreach (['horizontia-ui.css', 'css/main.css'] as $css) {
            $this->assertSame(str_contains($home, $css), str_contains($error, $css), $css);
        }
    }
}
