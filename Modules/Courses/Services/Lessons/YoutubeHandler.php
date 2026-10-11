<?php

namespace Modules\Courses\Services\Lessons;

class YoutubeHandler extends LinkHandler
{
    public function type(): string { return 'yt_link'; }
    protected function field(): string { return 'yt_link'; }
    protected function invalidMessage(): string { return 'Ingresa un enlace de YouTube válido (watch, youtu.be, embed o shorts).'; }

    public static function videoId(string $url): ?string
    {
        return preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', trim($url), $m) ? $m[1] : null;
    }

    public function normalize(string $url): ?string
    {
        $id = self::videoId($url);

        return $id ? 'https://www.youtube.com/embed/' . $id : null;
    }
}
