<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Banner (título, subtítulo e imagen) de una página estática. */
class PageBanner extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'overlay' => 'integer'];
    }

    public function imageUrl(): ?string
    {
        return $this->image ? mediaUrl($this->image, getStorageDisk()) : null;
    }
}
