<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * SEO overrides managed in Admin > SEO. Anything left empty falls back to what the page computes by itself.
 */
class SeoEntry extends Model
{
    public const CACHE_KEY = 'seo.entries.v1';

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public static function normalizePath(string $path): string
    {
        $path = trim($path);
        $path = parse_url($path, PHP_URL_PATH) ?: $path;

        return trim($path, '/') === '' ? '/' : trim($path, '/');
    }
}
