<?php

namespace App\Services;

use App\Models\SeoEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Finds the admin-defined SEO entry for the current request: exact path > route name > longest wildcard pattern.
 */
class SeoOverrideService
{
    private ?array $resolved = null;

    private function entries(): array
    {
        try {
            return Cache::rememberForever(SeoEntry::CACHE_KEY, fn () => Schema::hasTable('seo_entries')
                ? SeoEntry::where('is_active', true)->get()->map->toArray()->all()
                : []);
        } catch (\Throwable) {
            return [];
        }
    }

    public function current(?Request $request = null): ?array
    {
        $request ??= request();
        if ($this->resolved !== null && $this->resolved['uri'] === $request->getRequestUri()) {
            return $this->resolved['entry'];
        }

        $entry = $this->match($request->path() === '/' ? '/' : trim($request->path(), '/'), $request->route()?->getName());
        $this->resolved = ['uri' => $request->getRequestUri(), 'entry' => $entry];

        return $entry;
    }

    public function match(string $path, ?string $routeName = null): ?array
    {
        $entries = collect($this->entries());

        $exact = $entries->first(fn ($e) => $e['match_type'] === 'path' && $e['match_value'] === $path);
        if ($exact) {
            return $exact;
        }
        if ($routeName) {
            $byRoute = $entries->first(fn ($e) => $e['match_type'] === 'route' && $e['match_value'] === $routeName);
            if ($byRoute) {
                return $byRoute;
            }
        }

        return $entries->where('match_type', 'pattern')
            ->filter(fn ($e) => Str::is($e['match_value'], $path))
            ->sortByDesc(fn ($e) => strlen(str_replace('*', '', $e['match_value'])))
            ->first();
    }

    /** Applies the entry on top of the values a page computed itself; only non-empty override fields win. */
    public function apply(array $meta, ?Request $request = null): array
    {
        $entry = $this->current($request);
        if (!$entry) {
            return $meta;
        }
        foreach (['title', 'description', 'keywords', 'robots', 'canonical'] as $field) {
            if (!empty($entry[$field])) {
                $meta[$field] = $entry[$field];
            }
        }
        if (!empty($entry['og_image'])) {
            $meta['image'] = $entry['og_image'];
        }
        if (!empty($entry['schema'])) {
            $decoded = json_decode($entry['schema'], true);
            if (is_array($decoded)) {
                $meta['schema'] = $decoded;
            }
        }
        $meta['overridden'] = true;

        return $meta;
    }
}
