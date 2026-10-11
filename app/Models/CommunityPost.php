<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pregunta (parent_id null) o respuesta de la comunidad de un curso. */
class CommunityPost extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['pinned' => 'boolean', 'is_answer' => 'boolean', 'hidden_at' => 'datetime', 'last_activity_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    public function scopeVisible($q)
    {
        return $q->whereNull('hidden_at');
    }
}
