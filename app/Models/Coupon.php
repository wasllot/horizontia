<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Código de descuento de la academia (porcentaje o monto fijo), con límites de uso y vigencia. */
class Coupon extends Model
{
    public const PERCENT = 'percent';
    public const FIXED = 'fixed';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'float', 'min_amount' => 'float', 'active' => 'boolean', 'first_purchase_only' => 'boolean',
            'course_ids' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function label(): string
    {
        return $this->type === self::PERCENT ? rtrim(rtrim(number_format($this->value, 2), '0'), '.') . '%' : strip_tags(formatAmount($this->value, true));
    }
}
