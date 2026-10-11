<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Larabuild\Pagebuilder\Models\Page;
use Modules\Courses\Models\Course;

/** Attaches a page-builder page to a course as an informative sub-page (/curso/{slug}/informacion/{page}). */
class CoursePage extends Model
{
    protected $table = 'course_pages';

    protected $fillable = ['course_id', 'page_id', 'label', 'sort'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
