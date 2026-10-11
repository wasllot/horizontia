<?php

namespace Modules\Courses\Services\Lessons;

class VimeoHandler extends LinkHandler
{
    public function type(): string { return 'vm_link'; }
    protected function field(): string { return 'vm_link'; }
    protected function invalidMessage(): string { return 'Ingresa un enlace de Vimeo válido (vimeo.com/123456789).'; }

    /** @return array{id:string, hash:?string}|null */
    public static function parse(string $url): ?array
    {
        if (preg_match('~vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d{6,})(?:/([A-Za-z0-9]+))?~', trim($url), $m)) {
            return ['id' => $m[1], 'hash' => $m[2] ?? null];
        }

        return null;
    }

    public function normalize(string $url): ?string
    {
        $v = self::parse($url);

        return $v ? 'https://vimeo.com/' . $v['id'] . ($v['hash'] ? '/' . $v['hash'] : '') : null;
    }
}
