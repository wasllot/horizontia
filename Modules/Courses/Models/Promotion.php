<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $table;

    public function __construct(array $attributes = [])
    {
        $this->table = (config('courses.db_prefix') ?? 'courses_') . 'promotions';
        parent::__construct($attributes);   // boots traits and global scopes (SoftDeletes) and fills the attributes
    }

    protected $fillable = [
        'code',
        'description',
        'valid_from',
        'valid_to',
        'color',
        'discount_percentage',
        'maximum_users',
    ];
}
