<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class CourseLiveStream extends Model
{
    use HasFactory;

    protected $table;

    public function __construct()
    {
        $this->table = (config('courses.db_prefix') ?? 'courses_') . 'course_live_streams';
    }

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'meeting_link',
        'date_time',
        'duration_minutes',
        'status',
        'notify_hours_before'
    ];

    protected $casts = [
        'date_time' => 'datetime',
    ];

    public const STATUS_SCHEDULED = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_CANCELLED = 3;

    public const STATUSES = [
        'scheduled' => self::STATUS_SCHEDULED,
        'completed' => self::STATUS_COMPLETED,
        'cancelled' => self::STATUS_CANCELLED,
    ];

    /**
     * Get the course that owns the live stream.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
