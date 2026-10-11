<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A public page of the site (table `pagebuilder__pages`, shared with the legacy block builder).
 * Pages made with the GrapesJS visual editor store their project, HTML and CSS here.
 */
class SitePage extends Model
{
    protected $guarded = [];

    protected $casts = ['settings' => 'array', 'edited_at' => 'datetime'];

    public function getTable()
    {
        return (config('pagebuilder.db_prefix') ?? 'pagebuilder__') . 'pages';
    }

    public function usesVisualEditor(): bool
    {
        return $this->gjs_html !== null;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function url(): string
    {
        return $this->slug === '/' ? url('/') : url($this->slug);
    }
}
