<?php

namespace Modules\Courses\Services;

use Modules\Courses\Services\Lessons\VimeoHandler;
use Modules\Courses\Services\Lessons\YoutubeHandler;

/**
 * Video promocional del curso por enlace (YouTube o Vimeo): evita el límite de tamaño de la subida y los costos de almacenamiento.
 * Solo se acepta lo que se reconoce: nunca se incrusta una dirección cualquiera.
 */
class PromoVideo
{
    /** Enlace canónico que se guarda, o null si no es de YouTube/Vimeo. */
    public static function normalize(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if ($id = YoutubeHandler::videoId($url)) {
            return 'https://www.youtube.com/watch?v=' . $id;
        }
        if ($v = VimeoHandler::parse($url)) {
            return 'https://vimeo.com/' . $v['id'] . ($v['hash'] ? '/' . $v['hash'] : '');
        }

        return null;
    }

    /** Dirección para el <iframe>, con la reproducción automática al abrirlo. */
    public static function embedUrl(?string $url): ?string
    {
        $url = (string) $url;
        if ($id = YoutubeHandler::videoId($url)) {
            return 'https://www.youtube-nocookie.com/embed/' . $id . '?autoplay=1&rel=0';
        }
        if ($v = VimeoHandler::parse($url)) {
            return 'https://player.vimeo.com/video/' . $v['id'] . '?autoplay=1' . ($v['hash'] ? '&h=' . $v['hash'] : '');
        }

        return null;
    }
}
