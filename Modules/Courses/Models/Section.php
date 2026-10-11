<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Courses\Models\Curriculum;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    protected $table;

    public function __construct(array $attributes = [])
    {
        $this->table = (config('courses.db_prefix') ?? 'courses_') . 'sections';
        parent::__construct($attributes);   // boots traits and global scopes (SoftDeletes) and fills the attributes
    }

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'sort_order',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get section curriculums
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function curriculums(): HasMany
    {
        return $this->hasMany(Curriculum::class)->orderBy('sort_order')->orderBy('id');
    }
}
