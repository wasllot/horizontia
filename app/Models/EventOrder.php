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
            'invoice' => 'array', 'utm' => 'array', 'items' => 'array',
            'has_telescope' => 'boolean', 'newsletter' => 'boolean', 'wants_invoice' => 'boolean',
            'unit_price' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2', 'iva' => 'decimal:2',
            'policies_accepted_at' => 'datetime', 'expires_at' => 'datetime', 'paid_at' => 'datetime', 'confirmation_sent_at' => 'datetime', 'hotel_notified_at' => 'datetime',
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

    /**
     * Paquetes de la compra: [{name, persons, qty, unit_price, subtotal}]. Las compras anteriores a los paquetes (sin `items`)
     * se muestran como entradas individuales.
     *
     * @return array<int, array{name: string, persons: int, qty: int, unit_price: float, subtotal: float}>
     */
    public function lines(): array
    {
        if (!empty($this->items)) {
            return array_values((array) $this->items);
        }

        return [['name' => 'Entrada individual', 'persons' => 1, 'qty' => (int) $this->quantity, 'unit_price' => (float) $this->unit_price, 'subtotal' => round((float) $this->unit_price * (int) $this->quantity, 2)]];
    }

    /** «2 × 2 personas + 1 × 1 persona». */
    public function packagesLabel(): string
    {
        return implode(' + ', array_map(fn ($l) => $l['qty'] . ' × ' . $l['name'], $this->lines()));
    }

    /** Código con el que el comprador reserva habitación en el hotel: el suyo, o el general del evento si lo hay. */
    public function roomCode(): ?string
    {
        return $this->hotel_code ?: $this->event?->sharedHotelCode();
    }

    public function subtotalBeforeIva(): float
    {
        return round((float) $this->total - (float) $this->iva, 2);
    }
}
