<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Several courses sold together at one price. Bought as the individual courses with the discount spread over them (see BundleService). */
class Bundle extends Model
{
    public const ACTIVE = 'active';
    public const DRAFT = 'draft';

    protected $fillable = ['title', 'slug', 'subtitle', 'description', 'price', 'status', 'sort'];

    protected $casts = ['price' => 'decimal:2'];

    public function __construct(array $attributes = [])
    {
        $this->table = (config('courses.db_prefix') ?? 'courses_') . 'bundles';
        parent::__construct($attributes);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, (config('courses.db_prefix') ?? 'courses_') . 'bundle_items', 'bundle_id', 'course_id')->withPivot('sort')->orderBy('pivot_sort');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }
}
