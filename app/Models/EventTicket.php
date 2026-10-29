<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Una entrada nominal (pulsera, pasaporte y diploma llevan el nombre). El código es la clave de acceso y el contenido del QR. */
class EventTicket extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EventOrder::class, 'event_order_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
