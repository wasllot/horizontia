<?php

namespace App\Models;

use App\Support\EventContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** Evento de entrada única con página propia (Astrofest, Perseidas, Leónidas…). Todo precio, cupo y texto sale de aquí, no del código. */
class Event extends Model
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';
    public const FINISHED = 'finished';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime', 'end_at' => 'datetime', 'countdown_from' => 'datetime',
            'sales_open_at' => 'datetime', 'sales_close_at' => 'datetime',
            'price' => 'decimal:2', 'iva_rate' => 'decimal:2',
            'sales_enabled' => 'boolean', 'redirect_events_index' => 'boolean',
            'discounts' => 'array', 'content' => 'array', 'assets' => 'array',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(EventOrder::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class);
    }

    public static function timezone(): string
    {
        return (string) config('academy.timezone', 'America/Mexico_City');
    }

    public function local(?Carbon $date): ?Carbon
    {
        return $date?->copy()->timezone(self::timezone());
    }

    public function url(string $path = ''): string
    {
        return url('/eventos/' . $this->slug . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    public function isPublic(): bool
    {
        return in_array($this->status, [self::PUBLISHED, self::FINISHED], true);
    }

    public function isFinished(): bool
    {
        return $this->status === self::FINISHED || ($this->end_at && $this->end_at->isPast());
    }

    /** Texto editable: el valor guardado del evento o el texto por defecto de la guía. */
    public function text(string $key): string
    {
        return (string) (data_get($this->content, 'text.' . $key) ?: data_get(EventContent::defaults(), $key, ''));
    }

    /** Bloque visible salvo que el panel lo oculte. */
    public function show(string $block): bool
    {
        return data_get($this->content, 'hide.' . $block) !== true;
    }

    public function asset(string $key): ?string
    {
        $path = data_get($this->assets, $key);

        return $path ? asset($path) : null;
    }

    public function policiesReady(): bool
    {
        return trim(strip_tags((string) $this->policies_html)) !== '';
    }

    public function hasPrice(): bool
    {
        return (float) $this->price > 0;
    }

    /** IVA contenido en un monto con IVA incluido. */
    public function ivaIn(float $total): float
    {
        $rate = (float) $this->iva_rate;

        return $rate > 0 ? round($total - $total / (1 + $rate / 100), 2) : 0.0;
    }
}
