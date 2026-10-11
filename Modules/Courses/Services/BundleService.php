<?php

namespace Modules\Courses\Services;

use App\Facades\Cart;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Courses\Models\Bundle;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;

/**
 * A bundle is sold as its individual courses with the discount spread proportionally over them, so enrollments, receipts,
 * refunds and instructor earnings keep working per course with no new order type. The cart lines carry `bundle_id`; removing
 * one removes them all, and the checkout re-checks the whole set (all courses present, prices equal to the current shares).
 */
class BundleService
{
    /** Courses that may go in a bundle: published and online (an in-person course needs a date, which a bundle cannot choose). */
    public function eligibleCourses(): Collection
    {
        return Course::where('status', Course::STATUSES['active'])->where('delivery', 'online')->with('pricing')->orderBy('title')->get()
            ->filter(fn ($c) => (float) ($c->pricing?->final_price ?? 0) > 0)->values();
    }

    public function listPrice(Bundle $bundle): float
    {
        return round((float) $bundle->courses->sum(fn ($c) => (float) ($c->pricing?->final_price ?? 0)), 2);
    }

    public function savings(Bundle $bundle): float
    {
        return max(0, round($this->listPrice($bundle) - (float) $bundle->price, 2));
    }

    public function savingsPercent(Bundle $bundle): int
    {
        $list = $this->listPrice($bundle);

        return $list > 0 ? (int) round($this->savings($bundle) / $list * 100) : 0;
    }

    /** A bundle is sellable when it is active, has 2+ published online courses and really saves money. */
    public function isSellable(Bundle $bundle): bool
    {
        $courses = $bundle->courses;

        return $bundle->isActive() && $courses->count() >= 2
            && $courses->every(fn ($c) => $c->getRawOriginal('status') == Course::STATUSES['active'] && $c->delivery === 'online')
            && $this->savings($bundle) > 0;
    }

    /** Courses of the bundle the user does not own yet. */
    public function remainingFor(?User $user, Bundle $bundle): Collection
    {
        $owned = $user ? Enrollment::where('student_id', $user->id)->whereIn('course_id', $bundle->courses->pluck('id'))->pluck('course_id')->all() : [];

        return $bundle->courses->reject(fn ($c) => in_array($c->id, $owned, true))->values();
    }

    /**
     * Price of each remaining course: the bundle price spread in proportion to the individual prices (same % off for all);
     * the last course absorbs the rounding so the total is exact.
     *
     * @return array<int, float> course id => price
     */
    public function shares(Bundle $bundle, Collection $remaining): array
    {
        $full = $this->listPrice($bundle);
        if ($full <= 0 || $remaining->isEmpty()) {
            return [];
        }
        $target = round((float) $bundle->price * $remaining->sum(fn ($c) => (float) $c->pricing->final_price) / $full, 2);
        $shares = [];
        $running = 0.0;
        foreach ($remaining as $i => $course) {
            $isLast = $i === $remaining->count() - 1;
            $price = $isLast ? round($target - $running, 2) : round($target * (float) $course->pricing->final_price / $remaining->sum(fn ($c) => (float) $c->pricing->final_price), 2);
            $shares[$course->id] = $price;
            $running += $price;
        }

        return $shares;
    }

    /** Puts the bundle in the cart as its (remaining) courses at their shares. @return array{ok:bool, message:string} */
    public function addToCart(Bundle $bundle, User $user): array
    {
        $bundle->loadMissing('courses.pricing', 'courses.category', 'courses.subCategory', 'courses.thumbnail');
        if (!$this->isSellable($bundle)) {
            return ['ok' => false, 'message' => 'Este paquete no está disponible por ahora.'];
        }
        $remaining = $this->remainingFor($user, $bundle);
        if ($remaining->isEmpty()) {
            return ['ok' => false, 'message' => 'Ya tienes todos los cursos de este paquete.'];
        }

        $this->clearBundleLines($bundle->id);
        $shares = $this->shares($bundle, $remaining);
        foreach ($remaining as $course) {
            Cart::remove($course->id, Course::class);   // a course already in the cart at full price is replaced by its bundle price
            $price = $shares[$course->id];
            Cart::add(
                cartableId: $course->id, cartableType: Course::class, name: $course->title, qty: 1, price: $price,
                options: [
                    'id' => $course->id, 'title' => $course->title, 'price' => $price, 'category' => $course->category?->name, 'sub_category' => $course->subCategory?->name,
                    'slug' => $course->slug, 'image' => $course->thumbnail?->path, 'bundle_id' => $bundle->id, 'bundle_title' => $bundle->title,
                ]
            );
        }

        return ['ok' => true, 'message' => 'Paquete añadido al carrito.'];
    }

    public function clearBundleLines(int $bundleId): void
    {
        Cart::removeBundle($bundleId);
    }

    /**
     * Re-checks every bundle in the cart. When something does not add up (price changed, a course left the bundle, the bundle was
     * paused, a course got bought elsewhere) the bundle lines are rebuilt or removed and the buyer is told to review the cart.
     *
     * @return string|null message when the cart was changed
     */
    public function validateCart(iterable $content, User $user): ?string
    {
        $byBundle = collect($content)->filter(fn ($i) => ($i['cartable_type'] ?? null) === Course::class && !empty($i['options']['bundle_id']))->groupBy(fn ($i) => $i['options']['bundle_id']);

        foreach ($byBundle as $bundleId => $lines) {
            $bundle = Bundle::with('courses.pricing')->find($bundleId);
            $remaining = $bundle ? $this->remainingFor($user, $bundle) : collect();
            $expected = $bundle && $this->isSellable($bundle) ? $this->shares($bundle, $remaining) : [];

            $inCart = $lines->mapWithKeys(fn ($l) => [(int) $l['cartable_id'] => round((float) $l['price'], 2)])->all();
            ksort($inCart);
            ksort($expected);
            if ($expected === [] || $inCart != $expected) {
                $this->clearBundleLines((int) $bundleId);
                if ($bundle && $expected !== []) {
                    $this->addToCart($bundle, $user);

                    return 'El paquete «' . $bundle->title . '» cambió desde que lo añadiste. Revisa tu carrito y vuelve a pagar.';
                }

                return 'El paquete «' . ($bundle?->title ?? 'seleccionado') . '» ya no está disponible y se quitó de tu carrito.';
            }
        }

        return null;
    }

    public function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'paquete';
        $slug = $base;
        $i = 2;
        while (Bundle::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
