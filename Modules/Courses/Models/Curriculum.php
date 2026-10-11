<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class Curriculum extends Model
{

    protected $table;

    public function __construct(array $attributes = [])
    {
        $this->table = (config('courses.db_prefix') ?? 'courses_') . 'curriculums';
        parent::__construct($attributes);   // boots traits and global scopes (SoftDeletes) and fills the attributes
    }

    protected $fillable = [
        'section_id',
        'title',
        'description',
        'type',
        'media_path',
        'thumbnail',
        'article_content',
        'content_length',
        'sort_order',
        'is_preview',
    ];

    protected $casts = [
        'type' => 'string',
        'is_preview' => 'boolean',
    ];

    /**
     * Get the section that owns the curriculum.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Lessons a student can actually open: they carry media or text, or (live lessons) a scheduled session.
     * Grouped on purpose so it can be combined with other constraints without the OR leaking out.
     */
    public function scopeWithContent($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('media_path')
                ->orWhereNotNull('article_content')
                ->orWhere(fn ($live) => $live->where('type', 'live')->whereHas('liveStream'));
        });
    }

    /** The scheduled session of a "live" lesson. */
    public function liveStream(): HasOne
    {
        return $this->hasOne(CourseLiveStream::class, 'curriculum_id');
    }

    public function watchtime(): HasOne
    {
        return $this->hasOne(Watchtime::class)
            ->where('user_id', Auth::id());
    }
}
