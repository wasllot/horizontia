<?php

namespace App\Services;

use App\Casts\OrderStatusCast;
use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;

/**
 * Numbers for the admin dashboard of a private academy: sales, enrolments and students for a period compared with the
 * previous one, a daily revenue series, best sellers, latest orders and the things that need the admin's attention.
 */
class AcademyDashboardService
{
    public const RANGES = ['7d' => 7, '30d' => 30, '90d' => 90, 'month' => 0];

    public readonly CarbonImmutable $from;
    public readonly CarbonImmutable $to;
    public readonly CarbonImmutable $prevFrom;
    public readonly CarbonImmutable $prevTo;

    public function __construct(public readonly string $range = '30d', ?CarbonImmutable $now = null)
    {
        $now ??= CarbonImmutable::now();
        if (!array_key_exists($range, self::RANGES)) {
            $range = '30d';
        }

        if ($range === 'month') {
            $this->from = $now->startOfMonth();
            $this->to = $now->endOfDay();
        } else {
            $this->from = $now->subDays(self::RANGES[$range] - 1)->startOfDay();
            $this->to = $now->endOfDay();
        }

        $days = (int) $this->from->diffInDays($this->to->startOfDay()) + 1;
        $this->prevTo = $this->from->subSecond();
        $this->prevFrom = $this->from->subDays($days)->startOfDay();
    }

    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to->startOfDay()) + 1;
    }

    private function completedOrders(CarbonImmutable $from, CarbonImmutable $to)
    {
        return Order::query()
            ->where('status', OrderStatusCast::$statuses['complete'])
            ->whereBetween('created_at', [$from, $to]);
    }

    /** @return array<string, array{value: float|int, previous: float|int, delta: ?float, kind: string}> */
    public function kpis(): array
    {
        $cur = $this->metrics($this->from, $this->to);
        $prev = $this->metrics($this->prevFrom, $this->prevTo);

        $out = [];
        foreach ($cur as $key => $value) {
            $out[$key] = ['value' => $value, 'previous' => $prev[$key], 'delta' => self::delta($value, $prev[$key]), 'kind' => $key === 'revenue' || $key === 'ticket' ? 'money' : 'count'];
        }

        return $out;
    }

    private function metrics(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $orders = $this->completedOrders($from, $to);
        $count = (clone $orders)->count();
        $revenue = (float) (clone $orders)->sum('amount');

        return [
            'revenue' => $revenue,
            'orders' => $count,
            'ticket' => $count > 0 ? $revenue / $count : 0.0,
            'enrollments' => Enrollment::whereBetween('created_at', [$from, $to])->count(),
            'students' => self::withRole('student')->whereBetween('created_at', [$from, $to])->count(),
        ];
    }

    private static function withRole(string $role)
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', $role));
    }

    public static function delta(float|int $current, float|int $previous): ?float
    {
        if ($previous == 0) {
            return $current == 0 ? null : 100.0;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    /** Revenue per day for the period, zero-filled. @return Collection<string, float> */
    public function revenueSeries(): Collection
    {
        $rows = $this->completedOrders($this->from, $this->to)
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = collect();
        for ($i = 0; $i < $this->days(); $i++) {
            $day = $this->from->addDays($i)->toDateString();
            $series[$day] = (float) ($rows[$day] ?? 0);
        }

        return $series;
    }

    /** Best selling courses of the period. */
    public function topCourses(int $limit = 5): Collection
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', OrderStatusCast::$statuses['complete'])
            ->whereBetween('orders.created_at', [$this->from, $this->to])
            ->where('order_items.orderable_type', Course::class)
            ->groupBy('order_items.orderable_id')
            ->selectRaw('order_items.orderable_id as course_id, COUNT(*) as sales, SUM(order_items.total) as revenue')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        $courses = Course::whereIn('id', $rows->pluck('course_id'))->pluck('title', 'id');

        return $rows->map(fn ($r) => (object) [
            'id' => (int) $r->course_id,
            'title' => html_entity_decode($courses[$r->course_id] ?? __('Curso eliminado')),
            'sales' => (int) $r->sales,
            'revenue' => (float) $r->revenue,
        ]);
    }

    public function recentOrders(int $limit = 6): Collection
    {
        return Order::with(['userProfile', 'items'])->latest('id')->limit($limit)->get();
    }

    public function recentEnrollments(int $limit = 5): Collection
    {
        return Enrollment::with(['student', 'course'])->latest('id')->limit($limit)->get();
    }

    /** Catalog size by status. @return array<string, int> */
    public function catalog(): array
    {
        $counts = Course::query()->select('status', DB::raw('COUNT(*) as n'))->groupBy('status')->pluck('n', 'status');
        $out = [];
        foreach (Course::STATUSES as $name => $code) {
            $out[$name] = (int) ($counts[$code] ?? 0);
        }

        return $out;
    }

    /** Things that wait for the admin. Each item: key, count, label, route. */
    public function attention(): array
    {
        $items = [
            ['key' => 'access', 'count' => \App\Models\AccessRequest::where('status', 'pending')->count(), 'label' => 'Solicitudes de acceso por revisar', 'route' => 'admin.access-requests', 'tone' => 'warning'],
            ['key' => 'review', 'count' => Course::where('status', Course::STATUSES['under_review'])->count(), 'label' => 'Cursos esperando revisión', 'route' => 'courses.admin.courses', 'tone' => 'warning'],
            ['key' => 'pending', 'count' => Order::where('status', OrderStatusCast::$statuses['pending'])->where('created_at', '<', now()->subHour())->count(), 'label' => 'Pedidos pendientes de pago (más de 1 h)', 'route' => 'admin.invoices', 'tone' => 'warning'],
            ['key' => 'messages', 'count' => Contact::where('created_at', '>=', now()->subDays(7))->count(), 'label' => 'Mensajes de contacto esta semana', 'route' => 'admin.leads', 'tone' => 'info'],
            ['key' => 'unverified', 'count' => User::whereNull('email_verified_at')->count(), 'label' => 'Usuarios sin verificar su correo', 'route' => 'admin.users', 'tone' => 'neutral'],
        ];

        return array_values(array_filter($items, fn ($i) => $i['count'] > 0));
    }

    public function totals(): array
    {
        return [
            'students' => self::withRole('student')->count(),
            'instructors' => self::withRole('tutor')->count(),
            'courses' => Course::where('status', Course::STATUSES['active'])->count(),
        ];
    }
}
