<?php

namespace App\Services\Pages;

/**
 * Cleans what the visual editor sends before it is stored and published.
 * Admins are trusted, but stored markup is rendered on public pages, so scripts, inline handlers and
 * script-capable URLs never make it through (defence in depth against a hijacked admin session).
 */
class PageHtmlSanitizer
{
    private const DROP_TAGS = ['script', 'style', 'object', 'embed', 'link', 'meta', 'base', 'applet', 'frame', 'frameset', 'noscript'];

    private const URL_ATTRS = ['href', 'src', 'action', 'formaction', 'poster', 'xlink:href', 'data-src', 'srcset'];

    private const IFRAME_HOSTS = ['www.youtube-nocookie.com', 'www.youtube.com', 'player.vimeo.com', 'www.google.com', 'maps.google.com'];

    public static function html(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="hz-root">' . $html . '</div>', LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementById('hz-root');
        if (!$root) {
            return '';
        }
        self::clean($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    private static function clean(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP_TAGS, true) || ($tag === 'iframe' && !self::iframeAllowed($child))) {
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                if (str_starts_with($name, 'on') || $name === 'srcdoc') {
                    $child->removeAttribute($attr->name);
                } elseif (in_array($name, self::URL_ATTRS, true) && !self::urlAllowed($attr->value)) {
                    $child->removeAttribute($attr->name);
                } elseif ($name === 'style' && preg_match('/expression\s*\(|javascript:|behavior\s*:|@import/i', $attr->value)) {
                    $child->removeAttribute($attr->name);
                }
            }
            if ($tag === 'a' && strtolower((string) $child->getAttribute('target')) === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            self::clean($child);
        }
    }

    private static function urlAllowed(string $value): bool
    {
        $v = strtolower(preg_replace('/[\s\x00-\x1f]+/', '', html_entity_decode($value)));
        if ($v === '' || $v[0] === '#' || $v[0] === '/' || $v[0] === '?' || $v[0] === '.') {
            return true;
        }
        if (preg_match('~^(https?:|mailto:|tel:|//)~', $v)) {
            return true;
        }

        return (bool) preg_match('~^data:image/(png|jpe?g|gif|webp|avif);base64,~', $v);
    }

    private static function iframeAllowed(\DOMElement $el): bool
    {
        $host = parse_url((string) $el->getAttribute('src'), PHP_URL_HOST);

        return $host && in_array(strtolower($host), self::IFRAME_HOSTS, true);
    }

    public static function css(string $css): string
    {
        $css = preg_replace('~/\*.*?\*/~s', '', $css);
        $css = preg_replace('/@import[^;]*;?/i', '', $css);
        $css = preg_replace('/expression\s*\([^)]*\)|behavior\s*:[^;}]*|-moz-binding\s*:[^;}]*/i', '', $css);
        $css = preg_replace('/url\(\s*[\'"]?\s*javascript:[^)]*\)/i', 'none', $css);
        // A stray </style> would let the stored CSS break out of the <style> block it is printed in.
        $css = str_ireplace(['</style', '<script'], ['', ''], $css);

        return trim($css);
    }
}
