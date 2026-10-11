<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * The academy is "Horizontia": no "Lernen" (the theme's original name) may reach the user, and the SEO title
 * typed by the admin is the whole window title.
 */
class BrandTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.private_mode' => true]);
    }

    public function test_translations_modules_and_defaults_do_not_mention_the_old_brand(): void
    {
        $dirs = [base_path('lang'), base_path('Modules/Courses/resources/lang'), base_path('Modules/Courses/config'), base_path('database/seeders'), base_path('config')];
        $hits = [];
        foreach ($dirs as $dir) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile() && preg_match('/\.(php|json)$/', $file->getFilename()) && str_contains(file_get_contents($file->getPathname()), 'Lernen')) {
                    $hits[] = str_replace(base_path() . '/', '', $file->getPathname());
                }
            }
        }
        $this->assertSame([], $hits, 'files still saying Lernen: ' . implode(', ', $hits));
        $this->assertSame('Horizontia', config('app.name'));
    }

    public function test_public_pages_say_horizontia_never_lernen(): void
    {
        foreach (['/', '/cursos', '/blog', '/contacto', '/nosotros', '/como-funciona', '/preguntas-frecuentes', '/terminos-y-condiciones', '/aviso-de-privacidad'] as $uri) {
            $this->assertStringNotContainsStringIgnoringCase('lernen', $this->get($uri)->assertOk()->getContent(), "$uri mentions Lernen");
        }
        $this->assertStringNotContainsStringIgnoringCase('lernen', $this->actingAs($this->student())->get('/mis-cursos')->getContent());
        $this->assertStringContainsString('Horizontia', $this->get('/nosotros')->getContent());
    }

    public function test_option_group_label_is_translated_without_the_old_brand(): void
    {
        $this->assertStringNotContainsString('Lernen', __('settings.lernen_settings'));
        $this->assertStringContainsString('Horizontia', __('settings.lernen_settings'));
    }

    public function test_brand_rename_keeps_php_serialized_values_valid_and_leaves_setting_keys_alone(): void
    {
        $payload = serialize(['subject' => 'Gracias por reservar con Lernen', 'nested' => ['x' => 'Lernen Pro']]);
        $id = DB::table('email_templates')->insertGetId(['title' => 'T', 'type' => 'brand-test', 'role' => 'admin', 'content' => $payload, 'status' => 'active']);
        $plain = DB::table('email_templates')->insertGetId(['title' => 'P', 'type' => 'brand-test2', 'role' => 'admin', 'content' => '<p>Bienvenido a Lernen</p>', 'status' => 'active']);
        $key = DB::table('optionbuilder__settings')->where('section', '_lernen')->first();

        $this->artisan('brand:rename')->assertSuccessful();

        $fixed = DB::table('email_templates')->where('id', $id)->value('content');
        $data = @unserialize($fixed);
        $this->assertIsArray($data, 'serialized value was corrupted by the rename');
        $this->assertSame('Gracias por reservar con Horizontia', $data['subject']);
        $this->assertSame('Horizontia Pro', $data['nested']['x']);
        $this->assertSame('<p>Bienvenido a Horizontia</p>', DB::table('email_templates')->where('id', $plain)->value('content'));
        if ($key) {
            $this->assertSame(1, DB::table('optionbuilder__settings')->where('section', '_lernen')->where('key', $key->key)->count(), 'setting identifiers must not be renamed');
        }
    }

    public function test_seo_title_is_used_verbatim_in_title_and_social_tags(): void
    {
        \App\Models\SeoEntry::create(['match_type' => 'path', 'match_value' => 'nosotros', 'is_active' => true, 'title' => 'N']);
        \Illuminate\Support\Facades\Cache::forget(\App\Models\SeoEntry::CACHE_KEY);

        $html = $this->get('/nosotros')->assertOk()->getContent();

        $this->assertStringContainsString('<title>N</title>', $html);
        $this->assertStringContainsString('<meta property="og:title" content="N">', $html);
        $this->assertStringContainsString('<meta name="twitter:title" content="N">', $html);
    }
}
