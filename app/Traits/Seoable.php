<?php

namespace App\Traits;

trait Seoable
{
    public function getSeoTitleAttribute(): ?string
    {
        return $this->seo_title ?? $this->title ?? $this->name ?? null;
    }

    public function getSeoDescriptionAttribute(): ?string
    {
        return $this->seo_description ?? $this->description ?? $this->bio ?? null;
    }

    public function getSeoKeywordsAttribute(): ?string
    {
        return $this->seo_keywords ?? null;
    }

    public function getSeoImageAttribute(): ?string
    {
        return $this->seo_image ?? $this->image ?? $this->thumbnail ?? null;
    }

    public function getSchemaDataAttribute(): ?array
    {
        return null;
    }

    public function toSchemaOrg(): ?array
    {
        return null;
    }

    public function getMetaTags(array $overrides = []): array
    {
        $siteTitle = setting('_general.site_name') ?? 'Horizontia';
        $siteUrl = config('app.url');
        
        $title = $overrides['title'] ?? $this->seo_title ?? $this->title ?? $this->name;
        $description = $overrides['description'] ?? $this->seo_description ?? $this->description ?? $this->bio;
        $image = $overrides['image'] ?? $this->seo_image ?? $this->image ?? $this->thumbnail;
        $url = $overrides['url'] ?? null;
        
        $fullTitle = $title && !str_contains($title, $siteTitle) 
            ? $title . ' | ' . $siteTitle 
            : ($title ?? $siteTitle);
        
        return [
            'title' => $fullTitle,
            'description' => $description ? \Illuminate\Support\Str::limit(strip_tags($description), 160) : null,
            'keywords' => $overrides['keywords'] ?? $this->seo_keywords,
            'image' => $image ? (str_starts_with($image, 'http') ? $image : asset($image)) : null,
            'url' => $url ?? request()->fullUrl(),
            'type' => $overrides['type'] ?? 'website',
            'robots' => $overrides['robots'] ?? 'index, follow',
            'schema' => $overrides['schema'] ?? $this->toSchemaOrg(),
        ];
    }
}
