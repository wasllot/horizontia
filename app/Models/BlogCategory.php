<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class BlogCategory extends Model
{
    use HasFactory, HasRecursiveRelationships, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'status',
        'parent_id',
        'image',
    ];

    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_category_link', 'blog_category_id', 'blog_id');
    }
}
