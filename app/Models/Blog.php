<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'status', 'image', 'author_id', 'slug', 'meta_title', 'meta_description'];

    public const STATUSES = [
        'draft'             => 0,
        'published'         => 1,
    ];

    /**
     * Get and set the status attribute.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Arr::get(array_flip(self::STATUSES), $value, null),
            set: fn($value) => Arr::get(self::STATUSES, $value, null)
        );
    }

    /** Minutes needed to read the post (about 200 words per minute, never less than 1). */
    protected function readingMinutes(): Attribute
    {
        return Attribute::get(fn () => max(1, (int) ceil(str_word_count(strip_tags(html_entity_decode((string) $this->description))) / 200)));
    }

    /** Plain-text summary for cards and meta description. */
    public function excerpt(int $words = 30): string
    {
        return \Illuminate\Support\Str::words(trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode((string) $this->description)))), $words, '…');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_tag_links', 'blog_id', 'tag_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_category_link', 'blog_id', 'blog_category_id')
            ->where('status', 'active');
    }
}
