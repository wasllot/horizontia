<?php

namespace App\Services;

use App\Traits\Seoable;

class SeoService
{
    protected array $meta = [];
    protected string $siteTitle;
    protected string $siteUrl;

    public function __construct()
    {
        $this->siteTitle = setting('_general.site_name') ?? 'Horizontia';
        $this->siteUrl = config('app.url');
        $this->reset();
    }

    public function reset(): self
    {
        $this->meta = [
            'title' => null,
            'description' => null,
            'keywords' => null,
            'image' => null,
            'url' => null,
            'type' => 'website',
            'robots' => 'index, follow',
            'schema' => null,
            'canonical' => null,
        ];
        return $this;
    }

    public function setTitle(?string $title): self
    {
        $this->meta['title'] = $title;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->meta['description'] = $description;
        return $this;
    }

    public function setKeywords(?string $keywords): self
    {
        $this->meta['keywords'] = $keywords;
        return $this;
    }

    public function setImage(?string $image): self
    {
        $this->meta['image'] = $image;
        return $this;
    }

    public function setUrl(?string $url): self
    {
        $this->meta['url'] = $url;
        return $this;
    }

    public function setType(string $type): self
    {
        $this->meta['type'] = $type;
        return $this;
    }

    public function setRobots(string $robots): self
    {
        $this->meta['robots'] = $robots;
        return $this;
    }

    public function setSchema(?array $schema): self
    {
        $this->meta['schema'] = $schema;
        return $this;
    }

    public function setCanonical(?string $canonical): self
    {
        $this->meta['canonical'] = $canonical;
        return $this;
    }

    public function forModel(object $model, array $overrides = []): self
    {
        if (in_array(Seoable::class, class_uses_recursive($model))) {
            $seoData = $model->getMetaTags($overrides);
            $this->setTitle($seoData['title'])
                 ->setDescription($seoData['description'])
                 ->setKeywords($seoData['keywords'])
                 ->setImage($seoData['image'])
                 ->setUrl($seoData['url'])
                 ->setType($seoData['type'])
                 ->setRobots($seoData['robots'])
                 ->setSchema($seoData['schema']);
        }
        return $this;
    }

    public function forCourse(array $course): self
    {
        $title = $course['title'] ?? null;
        $description = $course['description'] ?? $course['subtitle'] ?? null;
        $image = $course['thumbnail'] ?? $course['image'] ?? null;
        $instructor = $course['instructor'] ?? $course['user'] ?? null;
        
        $instructorName = is_array($instructor) ? ($instructor['full_name'] ?? $instructor['name'] ?? null) : ($instructor?->full_name ?? $instructor?->name ?? null);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $title,
            'description' => $description,
            'provider' => [
                '@type' => 'Organization',
                'name' => $this->siteTitle,
                'url' => $this->siteUrl,
            ],
        ];

        if ($instructorName) {
            $schema[' instructor'] = [
                '@type' => 'Person',
                'name' => $instructorName,
            ];
        }

        return $this
            ->setTitle($title)
            ->setDescription($description)
            ->setImage($image)
            ->setType('article')
            ->setSchema($schema);
    }

    public function forTutor(array $tutor, ?string $specialty = null): self
    {
        $title = $tutor['full_name'] ?? $tutor['name'] ?? null;
        $description = $tutor['description'] ?? $tutor['bio'] ?? null;
        $image = $tutor['image'] ?? $tutor['profile_image'] ?? null;
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $title,
            'description' => $description,
            'url' => $this->siteUrl . '/tutor/' . ($tutor['slug'] ?? ''),
        ];

        if (!empty($tutor['hourly_rate'])) {
            $schema['knowsAbout'] = [
                '@type' => 'Offer',
                'price' => $tutor['hourly_rate'],
                'priceCurrency' => setting('_general.currency') ?? 'USD',
            ];
        }

        return $this
            ->setTitle($title)
            ->setDescription($description)
            ->setImage($image)
            ->setKeywords($specialty ? "$specialty tutor, online tutor, $specialty teacher" : 'online tutor, tutoring platform')
            ->setType('profile')
            ->setSchema($schema);
    }

    public function forBlogPost(array $post): self
    {
        $title = $post['title'] ?? null;
        $description = $post['description'] ?? $post['excerpt'] ?? null;
        $image = $post['image'] ?? $post['thumbnail'] ?? null;
        $publishedAt = $post['published_at'] ?? $post['created_at'] ?? null;
        $author = $post['author'] ?? $post['user'] ?? null;
        $authorName = is_array($author) ? ($author['full_name'] ?? $author['name'] ?? null) : ($author?->full_name ?? $author?->name ?? 'Horizontia');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $title,
            'description' => $description,
            'image' => $image ? (str_starts_with($image, 'http') ? $image : $this->siteUrl . $image) : null,
            'datePublished' => $publishedAt,
            'author' => [
                '@type' => 'Person',
                'name' => $authorName,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->siteTitle,
                'url' => $this->siteUrl,
            ],
        ];

        return $this
            ->setTitle($title)
            ->setDescription($description)
            ->setImage($image)
            ->setType('article')
            ->setSchema($schema);
    }

    public function forSearch(string $query, int $resultCount = 0): self
    {
        $title = "Search results for '$query'";
        $description = "Found $resultCount results for '$query'. Find the best tutors and courses matching your search.";

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'SearchResultsPage',
            'name' => $title,
            'description' => $description,
            'query' => $query,
        ];

        return $this
            ->setTitle($title)
            ->setDescription($description)
            ->setKeywords("$query, search, tutors, courses")
            ->setSchema($schema);
    }

    public function getMeta(): array
    {
        $title = $this->meta['title'];
        $fullTitle = $title && !str_contains($title, $this->siteTitle) 
            ? $title . ' | ' . $this->siteTitle 
            : ($title ?? $this->siteTitle);

        return [
            'pageTitle' => $title,
            'fullTitle' => $fullTitle,
            'pageDescription' => $this->meta['description'],
            'pageKeywords' => $this->meta['keywords'],
            'metaImage' => $this->meta['image'],
            'canonicalUrl' => $this->meta['canonical'] ?? request()->fullUrl(),
            'type' => $this->meta['type'],
            'robots' => $this->meta['robots'],
            'schema' => $this->meta['schema'],
        ];
    }

    public function getViewData(): array
    {
        return $this->getMeta();
    }
}
