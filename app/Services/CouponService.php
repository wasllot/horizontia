<?php

namespace App\Services;

use App\Casts\OrderStatusCast;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\Course;

/**
 * Códigos de descuento. El código aplicado vive en la sesión del comprador y se vuelve a validar cada vez que
 * se calcula el carrito (nunca se confía en lo que envía el navegador). Solo descuenta cursos sueltos:
 * las líneas de un paquete ya traen su propio descuento y no se acumulan.
 */
class CouponService
{
    public const SESSION = 'cart_coupon';

    public static function normalize(?string $code): string
    {
        return mb_strtoupper(preg_replace('/[^A-Za-z0-9_\-]/', '', trim((string) $code)));
    }

    public function find(?string $code): ?Coupon
    {
        $code = self::normalize($code);

        return $code === '' ? null : Coupon::where('code', $code)->first();
    }

    /** Líneas del carrito que el cupón puede descontar. */
    private function eligible(Coupon $coupon, iterable $lines): array
    {
        $out = [];
        foreach ($lines as $line) {
            $options = (array) data_get($line, 'options', []);
            if (ltrim((string) data_get($line, 'cartable_type'), '\\') !== Course::class || !empty($options['bundle_id'])) {
                continue;
            }
            if (!empty($coupon->course_ids) && !in_array((int) data_get($line, 'cartable_id'), array_map('intval', $coupon->course_ids), true)) {
                continue;
            }
            $out[] = $line;
        }

        return $out;
    }

    /**
     * @return array{ok: bool, message: string, discount: float, shares: array<int, float>}
     *         shares: descuento repartido por id de curso (para que cada línea del pedido quede con su precio real).
     */
    public function quote(Coupon $coupon, ?User $user, iterable $lines): array
    {
        $fail = fn (string $m) => ['ok' => false, 'message' => $m, 'discount' => 0.0, 'shares' => []];

        if (!$coupon->active) {
            return $fail('Este código ya no está activo.');
        }
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return $fail('Este código todavía no está vigente.');
        }
        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return $fail('Este código ya venció.');
        }
        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            return $fail('Este código ya alcanzó su límite de usos.');
        }
        if ($user) {
            if ($coupon->per_user_limit > 0 && $coupon->redemptions()->where('user_id', $user->id)->count() >= $coupon->per_user_limit) {
                return $fail('Ya usaste este código el máximo de veces permitido.');
            }
            if ($coupon->first_purchase_only && Order::where('user_id', $user->id)->whereIn('status', [OrderStatusCast::$statuses['complete'], OrderStatusCast::$statuses['processed']])->exists()) {
                return $fail('Este código es solo para tu primera compra.');
            }
        }

        $eligible = $this->eligible($coupon, $lines);
        $base = round((float) collect($eligible)->sum(fn ($l) => (float) data_get($l, 'qty', 1) * (float) data_get($l, 'price')), 2);
        if ($base <= 0) {
            return $fail(empty($coupon->course_ids) ? 'Este código no aplica a los cursos de tu carrito (los paquetes ya incluyen su descuento).' : 'Este código solo aplica a cursos específicos que no están en tu carrito.');
        }
        if ($coupon->min_amount !== null && $base < $coupon->min_amount) {
            return $fail('Este código requiere una compra mínima de ' . strip_tags(formatAmount($coupon->min_amount, true)) . '.');
        }

        $discount = $coupon->type === Coupon::PERCENT ? round($base * min(100, $coupon->value) / 100, 2) : min($base, round($coupon->value, 2));
        $shares = [];
        $left = $discount;
        foreach (array_values($eligible) as $i => $line) {
            $lineTotal = (float) data_get($line, 'qty', 1) * (float) data_get($line, 'price');
            $share = $i === count($eligible) - 1 ? $left : min($left, round($discount * $lineTotal / $base, 2));
            $shares[(int) data_get($line, 'cartable_id')] = $share;
            $left = round($left - $share, 2);
        }

        return ['ok' => true, 'message' => 'Código aplicado.', 'discount' => $discount, 'shares' => $shares];
    }

    /** Aplica un código escrito por el comprador: lo guarda en su sesión si es válido. */
    public function apply(string $code, ?User $user, iterable $lines): array
    {
        $coupon = $this->find($code);
        if (!$coupon) {
            return ['ok' => false, 'message' => 'Ese código no existe. Revisa que esté bien escrito.', 'discount' => 0.0, 'shares' => []];
        }
        $quote = $this->quote($coupon, $user, $lines);
        if ($quote['ok']) {
            session([self::SESSION => $coupon->code]);
        }

        return $quote;
    }

    public function forget(): void
    {
        session()->forget(self::SESSION);
    }

    /** El cupón de la sesión, revalidado contra el carrito de ahora. Si dejó de valer se descarta. */
    public function applied(?User $user, iterable $lines): ?array
    {
        $code = session(self::SESSION);
        if (!$code) {
            return null;
        }
        $coupon = $this->find($code);
        $quote = $coupon ? $this->quote($coupon, $user, $lines) : null;
        if (!$quote || !$quote['ok']) {
            $this->forget();

            return null;
        }

        return $quote + ['coupon' => $coupon];
    }

    /** Se llama al completarse el pedido (pago aprobado o pedido gratis). Idempotente por pedido. */
    public function redeem(Order $order): void
    {
        if (empty($order->coupon_code) || (float) $order->discount_amount <= 0) {
            return;
        }
        $coupon = Coupon::where('code', $order->coupon_code)->first();
        if (!$coupon || CouponRedemption::where('order_id', $order->id)->exists()) {
            return;
        }
        DB::transaction(function () use ($coupon, $order) {
            CouponRedemption::create(['coupon_id' => $coupon->id, 'user_id' => $order->user_id, 'order_id' => $order->id, 'amount' => $order->discount_amount]);
            $coupon->increment('used_count');
        });
    }
}
