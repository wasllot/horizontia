<?php

namespace App\Livewire\Frontend;

use App\Models\Blog;
use Livewire\Component;
use Modules\Courses\Models\Course;

class BlogDetails extends Component
{
    public $blogSlug;
    public $blog;
    public $relatedBlogs;
    public $courses;

    public function mount()
    {
        $this->blogSlug = request()->route('slug');

        $this->blog = Blog::where('slug', $this->blogSlug)
            ->when(request()->source != 'admin', function ($query) {
                $query->where('status', Blog::STATUSES['published']);
            })
            ->with('author.profile', 'tags', 'categories')
            ->first();

        if (!$this->blog) {
            abort(404);
        }

        if (request()->source != 'admin') {
            $this->manageViews($this->blog);
        }

        $categoryIds = $this->blog->categories->pluck('id');

        // Related posts: same category first, then the latest ones so the section is never empty.
        $related = Blog::where('status', Blog::STATUSES['published'])->where('id', '!=', $this->blog->id)->with(['author.profile', 'categories']);
        $this->relatedBlogs = (clone $related)
            ->when($categoryIds->isNotEmpty(), fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereIn('blog_category_id', $categoryIds)))
            ->latest('updated_at')->take(3)->get();
        if ($this->relatedBlogs->count() < 3) {
            $this->relatedBlogs = $this->relatedBlogs->concat(
                $related->whereNotIn('id', $this->relatedBlogs->pluck('id'))->latest('updated_at')->take(3 - $this->relatedBlogs->count())->get()
            );
        }

        // The academy sells courses: close every article with a few of them.
        $this->courses = Course::with(['thumbnail', 'pricing'])->where('status', Course::STATUSES['active'])->latest('id')->take(3)->get();
    }

    public function render()
    {
        $blog = $this->blog;

        return view('livewire.pages.blog-details', [
            'relatedBlogs' => $this->relatedBlogs,
            'courses' => $this->courses,
        ])->layout('layouts.frontend-app', [
            'pageTitle' => $blog?->meta_title ?: $blog?->title,
            'pageDescription' => $blog?->meta_description ?: $blog?->excerpt(28),
            'metaImage' => !empty($blog?->image) && mediaExists($blog->image) ? mediaUrl($blog->image) : null,
            'ogType' => 'article',
        ]);
    }

    public function getPageTitle()
    {
        return $this->blog ? $this->blog->title : 'Blog';
    }

    public function manageViews($blog)
    {
        $blogSessionKey = 'blog_viewed_' . $blog->id;

        if (!session()->has($blogSessionKey)) {
            $blog->increment('views_count');
            session()->put($blogSessionKey, true);
        }
    }
}
