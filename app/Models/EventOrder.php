<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Compra de entradas de un evento. Sin cuenta de usuario: se identifica por un código no adivinable. */
class EventOrder extends Model
{
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const EXPIRED = 'expired';
    public const REFUNDED = 'refunded';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'invoice' => 'array', 'utm' => 'array',
            'has_telescope' => 'boolean', 'newsletter' => 'boolean', 'wants_invoice' => 'boolean',
            'unit_price' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2', 'iva' => 'decimal:2',
            'policies_accepted_at' => 'datetime', 'expires_at' => 'datetime', 'paid_at' => 'datetime', 'confirmation_sent_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    /** Referencia que viaja a Mercado Pago (distinta de la de los pedidos de cursos). */
    public function reference(): string
    {
        return 'EV-' . $this->id;
    }

    public function subtotalBeforeIva(): float
    {
        return round((float) $this->total - (float) $this->iva, 2);
    }
}
