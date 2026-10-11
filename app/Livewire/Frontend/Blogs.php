<?php

namespace App\Livewire\Frontend;

use App\Models\Blog;
use App\Models\BlogCategory;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Public blog index: search, category chips, "load more". Server-rendered on first paint (good for SEO).
 */
class Blogs extends Component
{
    use WithPagination;

    public const PER_PAGE = 9;

    #[Url(as: 'q')]
    public $keyword = '';
    #[Url(as: 'category')]
    public $categorySlug = '';
    public $orderBy = 'desc';
    public $perPage = self::PER_PAGE;
    public $searchDescription = '';

    public function mount()
    {
        $this->perPage = self::PER_PAGE;
    }

    public function setCategory(string $slug = ''): void
    {
        $this->categorySlug = $slug;
        $this->resetList();
    }

    public function loadMore(): void
    {
        $this->perPage = min($this->perPage + self::PER_PAGE, 90);
    }

    public function updatedKeyword(): void
    {
        $this->resetList();
    }

    public function clearFilters(): void
    {
        $this->keyword = '';
        $this->categorySlug = '';
        $this->resetList();
    }

    private function resetList(): void
    {
        $this->perPage = self::PER_PAGE;
        $this->resetPage();
    }

    public function render()
    {
        $categories = BlogCategory::query()
            ->whereHas('blogs', fn ($q) => $q->where('status', Blog::STATUSES['published']))
            ->withCount(['blogs as posts_count' => fn ($q) => $q->where('status', Blog::STATUSES['published'])])
            ->orderBy('name')->get();
        $category = $this->categorySlug ? $categories->firstWhere('slug', $this->categorySlug) : null;
        $keyword = trim((string) $this->keyword);

        $blogs = Blog::query()
            ->where('status', Blog::STATUSES['published'])
            ->when($keyword !== '', fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', "%{$keyword}%")->orWhere('description', 'like', "%{$keyword}%")))
            ->when($category, fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('blog_category_id', $category->id)))
            ->with(['categories', 'author.profile'])
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        $title = setting('_front_page_settings.blog_title') ?: 'Blog';

        return view('livewire.pages.blogs', [
            'blogs' => $blogs,
            'categories' => $categories,
            'activeCategory' => $category,
            'filtered' => $keyword !== '' || $category !== null,
        ])->extends('layouts.frontend-app', [
            'pageTitle' => $category ? "{$category->name} · {$title}" : $title,
            'pageDescription' => 'Artículos, guías y consejos para aprender mejor. Explora por categoría y descubre contenido de nuestros instructores.',
        ]);
    }
}
