<?php

namespace App\Services;

use App\Casts\OrderStatusCast;
use App\Models\Coupon;
use App\Models\CourseCertificate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SearchLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Watchtime;

/**
 * Reportes de negocio de la academia: ventas, embudo de pedidos, cursos más vendidos, finalización y abandono por lección,
 * cupones. Todo se calcula en el servidor con las tablas reales; el periodo se elige por rango.
 */
class BusinessReportService
{
    public const RANGES = ['30d' => 30, '90d' => 90, 'year' => 365, 'all' => 0];

    public readonly CarbonImmutable $from;
    public readonly CarbonImmutable $to;

    public function __construct(public readonly string $range = '30d', ?CarbonImmutable $now = null)
    {
        $now ??= CarbonImmutable::now();
        $this->to = $now->endOfDay();
        $range = array_key_exists($range, self::RANGES) ? $range : '30d';
        if ($range === 'all') {
            $first = Order::min('created_at');
            $this->from = $first ? CarbonImmutable::parse($first)->startOfDay() : $now->subDays(29)->startOfDay();
        } else {
            $this->from = $now->subDays(self::RANGES[$range] - 1)->startOfDay();
        }
    }

    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to->startOfDay()) + 1;
    }

    private function paid()
    {
        return Order::query()->where('status', OrderStatusCast::$statuses['complete'])->whereBetween('created_at', [$this->from, $this->to]);
    }

    /** @return array<string, float|int> */
    public function summary(): array
    {
        $paid = $this->paid();
        $orders = (clone $paid)->count();
        $gross = round((float) (clone $paid)->sum('amount'), 2);
        $initiated = Order::whereBetween('created_at', [$this->from, $this->to])->count();
        $refunded = Order::where('status', OrderStatusCast::$statuses['refunded'])->whereBetween('created_at', [$this->from, $this->to]);
        $pending = Order::where('status', OrderStatusCast::$statuses['pending'])->whereBetween('created_at', [$this->from, $this->to]);

        return [
            'gross' => $gross,
            'orders' => $orders,
            'ticket' => $orders > 0 ? round($gross / $orders, 2) : 0.0,
            'discounts' => round((float) (clone $paid)->sum('discount_amount'), 2),
            'refunds' => (clone $refunded)->count(),
            'refunded_amount' => round((float) (clone $refunded)->sum('amount'), 2),
            'initiated' => $initiated,
            'pending' => (clone $pending)->count(),
            'pending_amount' => round((float) (clone $pending)->sum('amount'), 2),
            'conversion' => $initiated > 0 ? round($orders / $initiated * 100, 1) : 0.0,
            'carts' => (int) DB::table('cart_items')->whereNotNull('user_id')->distinct()->count('user_id'),
        ];
    }

    /** Ventas por día (o por mes si el periodo es largo). @return Collection<string, float> */
    public function series(): Collection
    {
        $monthly = $this->days() > 92;
        $format = $monthly ? "DATE_FORMAT(created_at, '%Y-%m')" : 'DATE(created_at)';
        $rows = $this->paid()->selectRaw("$format as bucket, SUM(amount) as total")->groupBy('bucket')->pluck('total', 'bucket');

        $out = collect();
        $cursor = $monthly ? $this->from->startOfMonth() : $this->from;
        while ($cursor <= $this->to) {
            $key = $monthly ? $cursor->format('Y-m') : $cursor->toDateString();
            $out[$key] = (float) ($rows[$key] ?? 0);
            $cursor = $monthly ? $cursor->addMonth() : $cursor->addDay();
        }

        return $out;
    }

    public function topCourses(int $limit = 15): Collection
    {
        $rows = OrderItem::query()->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', OrderStatusCast::$statuses['complete'])->whereBetween('orders.created_at', [$this->from, $this->to])
            ->where('order_items.orderable_type', Course::class)
            ->groupBy('order_items.orderable_id')->selectRaw('order_items.orderable_id as course_id, COUNT(*) as sales, SUM(order_items.total) as revenue')
            ->orderByDesc('revenue')->limit($limit)->get();
        $titles = Course::whereIn('id', $rows->pluck('course_id'))->pluck('title', 'id');
        $total = max((float) $rows->sum('revenue'), 0.01);

        $known = $rows->filter(fn ($r) => isset($titles[$r->course_id]))->map(fn ($r) => (object) ['id' => (int) $r->course_id, 'title' => html_entity_decode($titles[$r->course_id]), 'sales' => (int) $r->sales, 'revenue' => round((float) $r->revenue, 2), 'share' => round((float) $r->revenue / $total * 100, 1)]);
        // Los cursos que ya no existen se juntan en una sola fila para no ensuciar la lista.
        $gone = $rows->reject(fn ($r) => isset($titles[$r->course_id]));
        if ($gone->isNotEmpty()) {
            $known->push((object) ['id' => 0, 'title' => 'Cursos eliminados (' . $gone->count() . ')', 'sales' => (int) $gone->sum('sales'), 'revenue' => round((float) $gone->sum('revenue'), 2), 'share' => round((float) $gone->sum('revenue') / $total * 100, 1)]);
        }

        return $known->sortByDesc('revenue')->values();
    }

    /** Cómo pagan. @return Collection<int, object{method:string, orders:int, amount:float}> */
    public function byMethod(): Collection
    {
        return $this->paid()->selectRaw('payment_method, COUNT(*) as orders, SUM(amount) as amount')->groupBy('payment_method')->orderByDesc('amount')->get()
            ->map(fn ($r) => (object) ['method' => (string) ($r->payment_method ?: 'sin dato'), 'orders' => (int) $r->orders, 'amount' => round((float) $r->amount, 2)]);
    }

    /** Rendimiento de cada cupón en el periodo. */
    public function coupons(): Collection
    {
        $rows = $this->paid()->whereNotNull('coupon_code')->selectRaw('coupon_code, COUNT(*) as uses, SUM(discount_amount) as discount, SUM(amount) as revenue')->groupBy('coupon_code')->orderByDesc('uses')->get();
        $defs = Coupon::whereIn('code', $rows->pluck('coupon_code'))->get()->keyBy('code');

        return $rows->map(fn ($r) => (object) ['code' => $r->coupon_code, 'label' => $defs[$r->coupon_code]?->label() ?? '—', 'uses' => (int) $r->uses, 'discount' => round((float) $r->discount, 2), 'revenue' => round((float) $r->revenue, 2)]);
    }

    /**
     * Qué se busca en el catálogo: lo más buscado y lo que se busca sin encontrar nada (ideas de cursos nuevos).
     *
     * @return array{top: Collection<int, object{query:string, searches:int}>, empty: Collection<int, object{query:string, searches:int}>, total:int, withoutResults:int}
     */
    public function searches(int $limit = 15): array
    {
        $base = fn () => SearchLog::query()->whereBetween('created_at', [$this->from, $this->to]);
        $group = fn ($q) => $q->selectRaw('query, COUNT(*) as searches')->groupBy('query')->orderByDesc('searches')->orderBy('query')->limit($limit)->get()->map(fn ($r) => (object) ['query' => $r->query, 'searches' => (int) $r->searches]);

        return [
            'top' => $group($base()),
            'empty' => $group($base()->where('results', 0)),
            'total' => $base()->count(),
            'withoutResults' => $base()->where('results', 0)->count(),
        ];
    }

    /** Avisos automáticos de WhatsApp del periodo: totales por estado y los últimos con su error si fallaron. */
    public function whatsapp(int $limit = 30): array
    {
        $base = fn () => \App\Models\WhatsappMessage::query()->whereBetween('created_at', [$this->from, $this->to]);

        return [
            'counts' => $base()->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status')->map(fn ($n) => (int) $n)->all(),
            'optins' => \App\Models\User::whereNotNull('whatsapp_optin_at')->count(),
            'recent' => $base()->orderByDesc('id')->limit($limit)->get(),
        ];
    }

    /** Alumnos que empezaron, terminaron y avance medio de cada curso en línea con inscritos. */
    public function learning(int $limit = 25): Collection
    {
        $enr = (new Enrollment())->getTable();
        $courses = Course::query()->where('delivery', 'online')->where('content_length', '>', 0)
            ->whereIn('id', Enrollment::where('status', Enrollment::STATUSES['active'])->select('course_id'))->get(['id', 'title', 'content_length']);
        if ($courses->isEmpty()) {
            return collect();
        }
        $ids = $courses->pluck('id');
        $enrolled = Enrollment::where('status', Enrollment::STATUSES['active'])->whereIn('course_id', $ids)->groupBy('course_id')->selectRaw('course_id, COUNT(DISTINCT student_id) n')->pluck('n', 'course_id');
        $perStudent = Watchtime::whereIn('course_id', $ids)->groupBy('course_id', 'user_id')->selectRaw('course_id, user_id, SUM(duration) s')->get()->groupBy('course_id');
        $certs = CourseCertificate::whereIn('course_id', $ids)->groupBy('course_id')->selectRaw('course_id, COUNT(*) n')->pluck('n', 'course_id');

        return $courses->map(function ($c) use ($enrolled, $perStudent, $certs) {
            $n = (int) ($enrolled[$c->id] ?? 0);
            $rows = $perStudent[$c->id] ?? collect();
            $pcts = $rows->map(fn ($r) => min(100, (int) floor($r->s / $c->content_length * 100)));
            $started = $pcts->filter(fn ($p) => $p > 0)->count();
            $done = $pcts->filter(fn ($p) => $p >= 99)->count();

            return (object) [
                'id' => $c->id, 'title' => html_entity_decode($c->title), 'enrolled' => $n, 'started' => min($started, $n), 'completed' => min($done, $n), 'certificates' => (int) ($certs[$c->id] ?? 0),
                'completion' => $n > 0 ? round(min($done, $n) / $n * 100, 1) : 0.0,
                'avg_progress' => $n > 0 ? (int) round($pcts->sum() / $n) : 0,
            ];
        })->sortByDesc('enrolled')->take($limit)->values();
    }

    /** Por lección: qué porcentaje de los inscritos la terminó (para ver dónde abandonan). */
    public function lessonFunnel(int $courseId): Collection
    {
        $course = Course::find($courseId);
        if (!$course) {
            return collect();
        }
        $enrolled = max(1, (int) Enrollment::where('status', Enrollment::STATUSES['active'])->where('course_id', $courseId)->distinct('student_id')->count('student_id'));
        $lessons = Curriculum::query()->whereHas('section', fn ($q) => $q->where('course_id', $courseId))->with('section')->get()
            ->sortBy(fn ($l) => [$l->section->sort_order, $l->sort_order])->values();
        $watch = Watchtime::where('course_id', $courseId)->get(['curriculum_id', 'user_id', 'duration'])->groupBy('curriculum_id');

        $prev = null;
        return $lessons->map(function ($l) use ($watch, $enrolled, &$prev) {
            $rows = $watch[$l->id] ?? collect();
            $done = $l->content_length > 0 ? $rows->filter(fn ($r) => $r->duration >= $l->content_length)->pluck('user_id')->unique()->count() : $rows->pluck('user_id')->unique()->count();
            $pct = (int) round(min($done, $enrolled) / $enrolled * 100);
            $row = (object) ['id' => $l->id, 'title' => html_entity_decode($l->title), 'type' => $l->type, 'done' => min($done, $enrolled), 'percent' => $pct, 'drop' => $prev === null ? 0 : max(0, $prev - $pct)];
            $prev = $pct;

            return $row;
        });
    }

    /** Filas del CSV de ventas (un pedido pagado por fila). @return array<int, array<int, string|float|int>> */
    public function salesCsvRows(): array
    {
        $rows = [['Pedido', 'Fecha', 'Correo', 'Método de pago', 'Cupón', 'Descuento', 'Total pagado', 'Cursos']];
        $this->paid()->with('items')->orderBy('id')->chunk(500, function ($orders) use (&$rows) {
            foreach ($orders as $o) {
                $rows[] = [$o->id, $o->created_at->format('Y-m-d H:i'), $o->email, $o->payment_method, $o->coupon_code, (float) $o->discount_amount, (float) $o->amount, $o->items->map(fn ($i) => html_entity_decode($i->title))->implode(' | ')];
            }
        });

        return $rows;
    }

    public function coursesCsvRows(): array
    {
        $rows = [['Curso', 'Ventas', 'Ingresos', '% de ingresos']];
        foreach ($this->topCourses(1000) as $c) {
            $rows[] = [$c->title, $c->sales, $c->revenue, $c->share];
        }

        return $rows;
    }

    public function learningCsvRows(): array
    {
        $rows = [['Curso', 'Inscritos', 'Empezaron', 'Terminaron', 'Certificados', '% de finalización', 'Avance medio %']];
        foreach ($this->learning(1000) as $c) {
            $rows[] = [$c->title, $c->enrolled, $c->started, $c->completed, $c->certificates, $c->completion, $c->avg_progress];
        }

        return $rows;
    }
}
