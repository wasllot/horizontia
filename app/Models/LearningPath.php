<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Courses\Models\Course;

/** Ruta de aprendizaje: cursos en un orden recomendado, con avance global y certificado propio. */
class LearningPath extends Model
{
    public const DRAFT = 'draft';
    public const ACTIVE = 'active';

    protected $guarded = [];

    public function scopeActive($q)
    {
        return $q->where('status', self::ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'learning_path_courses', 'learning_path_id', 'course_id')->withPivot('sort')->orderBy('learning_path_courses.sort');
    }
}
