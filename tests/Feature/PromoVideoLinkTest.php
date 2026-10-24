<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Courses\Services\PromoVideo;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Video promocional por enlace de YouTube o Vimeo (sin el límite de tamaño de la subida). */
class PromoVideoLinkTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    public function test_youtube_and_vimeo_links_are_recognised_and_normalised(): void
    {
        foreach (['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://youtu.be/dQw4w9WgXcQ?t=5', 'https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'] as $url) {
            $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', PromoVideo::normalize($url), $url);
        }
        $this->assertSame('https://vimeo.com/123456789', PromoVideo::normalize('https://vimeo.com/123456789'));
        $this->assertSame('https://vimeo.com/123456789', PromoVideo::normalize('https://player.vimeo.com/video/123456789'));
        $this->assertSame('https://vimeo.com/123456789/abcdef1234', PromoVideo::normalize('https://vimeo.com/123456789/abcdef1234'), 'private Vimeo links keep their hash');
    }

    public function test_anything_else_is_refused_and_empty_clears_it(): void
    {
        foreach (['https://evil.example/video.mp4', 'javascript:alert(1)', 'https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ', 'texto cualquiera', 'https://vimeo.com/abc'] as $url) {
            $this->assertNull(PromoVideo::normalize($url), $url);
            $this->assertNull(PromoVideo::embedUrl($url), $url);
        }
        $this->assertNull(PromoVideo::normalize('   '));
        $this->assertNull(PromoVideo::normalize(null));
    }

    public function test_the_embed_uses_the_privacy_friendly_hosts(): void
    {
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0', PromoVideo::embedUrl('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertSame('https://player.vimeo.com/video/123456789?autoplay=1', PromoVideo::embedUrl('https://vimeo.com/123456789'));
        $this->assertSame('https://player.vimeo.com/video/123456789?autoplay=1&h=abcdef1234', PromoVideo::embedUrl('https://vimeo.com/123456789/abcdef1234'));
    }

    public function test_the_course_page_plays_the_link_in_an_iframe_and_the_api_exposes_it(): void
    {
        $course = $this->course();
        $course->update(['promo_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

        $html = $this->get(route('courses.course-detail', ['slug' => $course->slug]))->assertOk()->getContent();
        $this->assertStringContainsString('youtube-nocookie.com\/embed\/dQw4w9WgXcQ', str_replace('\\/', '\\/', $html) . str_replace('/', '\\/', $html));
        $this->assertStringContainsString('Reproducir video', $html, 'the play button shows even without an uploaded file');

        $course->update(['promo_video_url' => null]);
        $this->assertStringNotContainsString('youtube-nocookie', $this->get(route('courses.course-detail', ['slug' => $course->slug]))->getContent());
    }
}
