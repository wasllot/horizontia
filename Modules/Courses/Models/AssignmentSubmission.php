<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'curriculum_id',
        'user_id',
        'file_path',
        'student_comment',
        'score',
        'tutor_feedback',
        'status',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = (config('courses.db_prefix') ?? 'courses_') . 'assignment_submissions';
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
