<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Snapshot of a visual-editor page (the editor keeps the latest ones so any save can be rolled back). */
class PageRevision extends Model
{
    public const KEEP = 25;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['created_at' => 'datetime'];
}
