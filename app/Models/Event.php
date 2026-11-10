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
            'discounts' => 'array', 'content' => 'array', 'assets' => 'array', 'packages' => 'array',
            'hotel_summary_sent_at' => 'datetime',
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

    /** Archivos por defecto (tomados del flyer del cliente); lo que se sube en el panel los reemplaza. */
    public const DEFAULT_ASSETS = [
        'logo' => 'images/eventos/astrofest-logo.webp', 'galileo' => 'images/eventos/galileo.webp', 'telescopio' => 'images/eventos/telescopio.webp',
        'fama' => 'images/eventos/fama.webp', 'club' => 'images/eventos/club.webp', 'luz' => 'images/eventos/luz-calderon.webp',
    ];

    public function asset(string $key): ?string
    {
        // Los logotipos de los aliados (FAMA, Club) salen salvo que el panel los oculte.
        if (in_array($key, ['fama', 'club'], true) && !$this->show('partners')) {
            return null;
        }
        $path = data_get($this->assets, $key) ?: (self::DEFAULT_ASSETS[$key] ?? null);

        if (!$path) {
            return null;
        }
        $file = public_path($path);
        $version = is_file($file) ? '?v=' . filemtime($file) : '';

        return asset($path) . $version;
    }

    /** Buscadores: un evento puede quedar «No indexar» mientras el cliente lo aprueba. Si no se ha definido, es indexable. */
    public function indexable(): bool
    {
        return data_get($this->content, 'noindex') !== true;
    }

    public function policiesReady(): bool
    {
        return trim(strip_tags((string) $this->policies_html)) !== '';
    }

    /**
     * Paquetes a la venta, del más chico al más grande: [{key, name, persons, price}]. Sin paquetes configurados, el evento vende
     * una «entrada individual» al precio base (así los eventos de entrada única siguen funcionando igual).
     *
     * @return array<int, array{key: string, name: string, persons: int, price: float}>
     */
    public function packageList(): array
    {
        $list = [];
        foreach ((array) $this->getAttribute('packages') as $p) {
            $persons = (int) ($p['persons'] ?? 0);
            $price = round((float) ($p['price'] ?? 0), 2);
            if (($p['active'] ?? true) === false || $persons < 1 || $price <= 0) {
                continue;
            }
            $list[] = ['key' => 'p' . $persons, 'name' => trim((string) ($p['name'] ?? '')) ?: ($persons === 1 ? '1 persona' : $persons . ' personas'), 'persons' => $persons, 'price' => $price];
        }
        if (!$list && (float) $this->price > 0) {
            return [['key' => 'p1', 'name' => 'Entrada individual', 'persons' => 1, 'price' => round((float) $this->price, 2)]];
        }
        $list = array_column($list, null, 'key');   // una sola opción por número de personas
        uasort($list, fn ($a, $b) => $a['persons'] <=> $b['persons']);

        return array_values($list);
    }

    public function findPackage(string $key): ?array
    {
        foreach ($this->packageList() as $p) {
            if ($p['key'] === $key) {
                return $p;
            }
        }

        return null;
    }

    /** true = el evento define paquetes de varias personas (la página habla de «paquetes», no de «entrada por persona»). */
    public function sellsPackages(): bool
    {
        return count($this->packageList()) > 1 || ($this->packageList()[0]['persons'] ?? 1) > 1;
    }

    /** Precio del paquete más barato (el «desde»). */
    public function startingPrice(): float
    {
        $prices = array_column($this->packageList(), 'price');

        return $prices ? (float) min($prices) : 0.0;
    }

    public function hasPrice(): bool
    {
        return $this->packageList() !== [];
    }

    /** Correos del hotel que reciben el código de cada compra pagada (válidos y sin repetir). */
    public function hotelEmails(): array
    {
        $emails = preg_split('/[\s,;]+/', (string) $this->hotel_email, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(array_map('mb_strtolower', $emails), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))));
    }

    /** Código de habitación compartido por todos (opcional): si está, se usa en vez de uno por compra. */
    public function sharedHotelCode(): ?string
    {
        $code = trim((string) data_get($this->content, 'text.rooms.code'));

        return $code !== '' ? $code : null;
    }

    /** IVA contenido en un monto con IVA incluido. */
    public function ivaIn(float $total): float
    {
        $rate = (float) $this->iva_rate;

        return $rate > 0 ? round($total - $total / (1 + $rate / 100), 2) : 0.0;
    }
}
