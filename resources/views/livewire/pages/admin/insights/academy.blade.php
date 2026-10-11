@php
    $kpis = $dash->kpis();
    $series = $dash->revenueSeries();
    $top = $dash->topCourses();
    $orders = $dash->recentOrders();
    $enrollments = $dash->recentEnrollments();
    $catalog = $dash->catalog();
    $totals = $dash->totals();
    $attention = $dash->attention();
    $name = $this->user?->profile?->first_name ?: __('administrador');
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? __('Buenos días') : ($hour < 19 ? __('Buenas tardes') : __('Buenas noches'));
    $labels = ['7d' => __('7 días'), '30d' => __('30 días'), '90d' => __('90 días'), 'month' => __('Este mes')];
    $cards = [
        'revenue' => ['label' => __('Ingresos'), 'icon' => 'icon-dollar-sign', 'tone' => 'blue'],
        'orders' => ['label' => __('Pedidos completados'), 'icon' => 'icon-shopping-bag', 'tone' => 'green'],
        'enrollments' => ['label' => __('Nuevas inscripciones'), 'icon' => 'icon-book-open', 'tone' => 'gold'],
        'students' => ['label' => __('Alumnos nuevos'), 'icon' => 'icon-users', 'tone' => 'violet'],
        'ticket' => ['label' => __('Ticket promedio'), 'icon' => 'icon-trending-up', 'tone' => 'teal'],
    ];
    $max = max($series->max() ?: 0, 1);
    $n = max($series->count(), 1);
    $W = 720; $H = 190; $padL = 8; $padB = 26; $padT = 10;
    $slot = ($W - $padL) / $n;
    $barW = max(min($slot * .68, 22), 3);
@endphp
<div class="hz-dash">
    <header class="hz-dash__head">
        <div>
            <h1>{{ $greeting }}, {{ $name }}</h1>
            <p>{{ ucfirst(now()->translatedFormat('l j \d\e F, Y')) }} · {{ __('Así va tu academia') }}</p>
        </div>
        <div class="hz-dash__actions">
            <a class="hz-btn hz-btn--sm" href="{{ route('courses.admin.create-course') }}"><i class="icon-plus" aria-hidden="true"></i> {{ __('Crear curso') }}</a>
            <a class="hz-btn hz-btn--sm hz-btn--secondary" href="{{ route('admin.invoices') }}">{{ __('Ver pedidos') }}</a>
        </div>
    </header>

    <div class="hz-tabs" role="tablist" aria-label="{{ __('Periodo') }}">
        @foreach ($labels as $key => $label)
            <button type="button" role="tab" aria-selected="{{ $range === $key ? 'true' : 'false' }}" @class(['hz-tabs__tab', 'is-active' => $range === $key]) wire:click="setRange('{{ $key }}')">{{ $label }}</button>
        @endforeach
        <span class="hz-dash__period" wire:loading.class="is-busy">{{ $dash->from->translatedFormat('j M') }} – {{ $dash->to->translatedFormat('j M Y') }}</span>
    </div>

    <section class="hz-kpis" aria-label="{{ __('Indicadores del periodo') }}">
        @foreach ($cards as $key => $card)
            @php $k = $kpis[$key]; @endphp
            <article class="hz-kpi hz-kpi--{{ $card['tone'] }}">
                <span class="hz-kpi__icon"><i class="{{ $card['icon'] }}" aria-hidden="true"></i></span>
                <div class="hz-kpi__body">
                    <span class="hz-kpi__label">{{ $card['label'] }}</span>
                    <strong class="hz-kpi__value">{{ $k['kind'] === 'money' ? formatAmount($k['value']) : number_format($k['value']) }}</strong>
                    @if ($k['delta'] === null)
                        <span class="hz-delta hz-delta--flat">{{ __('Sin cambios') }}</span>
                    @elseif ($k['previous'] == 0)
                        <span class="hz-delta hz-delta--up" title="{{ __('No hubo actividad en el periodo anterior') }}">▲ {{ __('Nuevo') }}</span>
                    @else
                        <span @class(['hz-delta', 'hz-delta--up' => $k['delta'] > 0, 'hz-delta--down' => $k['delta'] < 0, 'hz-delta--flat' => $k['delta'] == 0]) title="{{ __('Periodo anterior') }}: {{ $k['kind'] === 'money' ? formatAmount($k['previous']) : number_format($k['previous']) }}">
                            {{ $k['delta'] > 0 ? '▲' : ($k['delta'] < 0 ? '▼' : '•') }} {{ number_format(abs($k['delta']), 1) }}% <small>{{ __('vs. anterior') }}</small>
                        </span>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    <div class="hz-dash__grid">
        <div class="hz-dash__main">
            <section class="hz-card hz-dash__card" aria-labelledby="hz-rev-title">
                <header class="hz-dash__cardhead">
                    <div>
                        <h2 id="hz-rev-title">{{ __('Ingresos por día') }}</h2>
                        <p>{{ __('Pedidos completados en el periodo') }}</p>
                    </div>
                    <strong class="hz-dash__total">{{ formatAmount($kpis['revenue']['value']) }}</strong>
                </header>
                @if ($kpis['revenue']['value'] > 0)
                    <svg class="hz-chart" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="{{ __('Gráfico de ingresos diarios') }}" preserveAspectRatio="none">
                        @foreach ([0.25, 0.5, 0.75, 1] as $g)
                            <line x1="{{ $padL }}" x2="{{ $W }}" y1="{{ $padT + ($H - $padB - $padT) * (1 - $g) }}" y2="{{ $padT + ($H - $padB - $padT) * (1 - $g) }}" class="hz-chart__grid" />
                        @endforeach
                        @foreach ($series->values() as $i => $v)
                            @php
                                $h = ($H - $padB - $padT) * ($v / $max);
                                $x = $padL + $slot * $i + ($slot - $barW) / 2;
                                $day = \Carbon\Carbon::parse($series->keys()[$i]);
                            @endphp
                            <rect x="{{ round($x, 2) }}" y="{{ round($H - $padB - max($h, 2), 2) }}" width="{{ round($barW, 2) }}" height="{{ round(max($h, 2), 2) }}" rx="3" @class(['hz-chart__bar', 'is-zero' => $v == 0])>
                                <title>{{ $day->translatedFormat('j M') }}: {{ formatAmount($v) }}</title>
                            </rect>
                        @endforeach
                        @foreach ([0, (int) floor(($n - 1) / 2), $n - 1] as $idx)
                            @if (isset($series->keys()[$idx]))
                                <text x="{{ round($padL + $slot * $idx + $slot / 2, 2) }}" y="{{ $H - 6 }}" text-anchor="{{ $idx === 0 ? 'start' : ($idx === $n - 1 ? 'end' : 'middle') }}" class="hz-chart__label">{{ \Carbon\Carbon::parse($series->keys()[$idx])->translatedFormat('j M') }}</text>
                            @endif
                        @endforeach
                    </svg>
                @else
                    <div class="hz-empty"><i class="icon-bar-chart-2" aria-hidden="true"></i><h3>{{ __('Aún no hay ventas en este periodo') }}</h3><p>{{ __('Cuando se complete un pedido verás aquí la evolución diaria.') }}</p></div>
                @endif
            </section>

            <section class="hz-card hz-dash__card" aria-labelledby="hz-top-title">
                <header class="hz-dash__cardhead">
                    <div><h2 id="hz-top-title">{{ __('Cursos más vendidos') }}</h2><p>{{ __('Por ingresos en el periodo') }}</p></div>
                    <a class="hz-link" href="{{ route('courses.admin.courses') }}">{{ __('Ver cursos') }} →</a>
                </header>
                @forelse ($top as $i => $course)
                    <div class="hz-rank">
                        <span class="hz-rank__pos">{{ $i + 1 }}</span>
                        <div class="hz-rank__body">
                            <strong>{{ $course->title }}</strong>
                            <span class="hz-rank__bar"><span style="width: {{ round($course->revenue / max($top->first()->revenue, 1) * 100) }}%"></span></span>
                        </div>
                        <div class="hz-rank__num"><strong>{{ formatAmount($course->revenue) }}</strong><small>{{ trans_choice(':count venta|:count ventas', $course->sales) }}</small></div>
                    </div>
                @empty
                    <div class="hz-empty"><i class="icon-book-open" aria-hidden="true"></i><h3>{{ __('Sin ventas de cursos todavía') }}</h3><p>{{ __('Aquí aparecerán tus cursos más vendidos.') }}</p></div>
                @endforelse
            </section>

            <section class="hz-card hz-dash__card" aria-labelledby="hz-orders-title">
                <header class="hz-dash__cardhead">
                    <div><h2 id="hz-orders-title">{{ __('Últimas ventas') }}</h2><p>{{ __('Los pedidos más recientes') }}</p></div>
                    <a class="hz-link" href="{{ route('admin.invoices') }}">{{ __('Ver todos') }} →</a>
                </header>
                @if ($orders->isEmpty())
                    <div class="hz-empty"><i class="icon-shopping-bag" aria-hidden="true"></i><h3>{{ __('Todavía no hay pedidos') }}</h3></div>
                @else
                    <div class="hz-rows">
                        @foreach ($orders as $order)
                            @php $first = $order->items->first(); @endphp
                            <div class="hz-row">
                                <x-panel.user-cell :name="$order->userProfile?->full_name ?: $order->email" :email="$first ? \Illuminate\Support\Str::limit(html_entity_decode($first->title), 42) . ($order->items->count() > 1 ? ' +' . ($order->items->count() - 1) : '') : null" :image="$order->userProfile?->image" />
                                <span class="hz-row__date">{{ $order->created_at->translatedFormat('j M, H:i') }}</span>
                                <x-panel.badge :tone="$order->status === 'complete' ? 'success' : 'warning'">{{ statusLabel($order->status) }}</x-panel.badge>
                                <strong class="hz-row__amount">{{ formatAmount($order->amount) }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside class="hz-dash__side">
            <section class="hz-card hz-dash__card" aria-labelledby="hz-att-title">
                <header class="hz-dash__cardhead"><div><h2 id="hz-att-title">{{ __('Requiere tu atención') }}</h2></div></header>
                @forelse ($attention as $item)
                    <a class="hz-todo hz-todo--{{ $item['tone'] }}" href="{{ route($item['route']) }}">
                        <span class="hz-todo__n">{{ $item['count'] }}</span>
                        <span class="hz-todo__t">{{ __($item['label']) }}</span>
                        <i class="icon-chevron-right" aria-hidden="true"></i>
                    </a>
                @empty
                    <div class="hz-callout hz-callout--success"><p><strong>{{ __('Todo al día.') }}</strong> {{ __('No hay nada pendiente por revisar.') }}</p></div>
                @endforelse
            </section>

            <section class="hz-card hz-dash__card" aria-labelledby="hz-cat-title">
                <header class="hz-dash__cardhead"><div><h2 id="hz-cat-title">{{ __('Tu catálogo') }}</h2></div><a class="hz-link" href="{{ route('courses.admin.courses') }}">{{ __('Gestionar') }} →</a></header>
                @php
                    $totalCourses = max(array_sum($catalog), 1);
                    $names = ['active' => [__('Publicados'), 'success'], 'under_review' => [__('En revisión'), 'warning'], 'draft' => [__('Borradores'), 'neutral'], 'need_revision' => [__('Con cambios pedidos'), 'danger'], 'inactive' => [__('Inactivos'), 'neutral']];
                @endphp
                <div class="hz-stack" role="img" aria-label="{{ __('Distribución del catálogo') }}">
                    @foreach ($names as $key => [$label, $tone])
                        @if ($catalog[$key] > 0)<span class="hz-stack__seg hz-stack__seg--{{ $tone }}" style="flex: {{ $catalog[$key] }}" title="{{ $label }}: {{ $catalog[$key] }}"></span>@endif
                    @endforeach
                </div>
                <ul class="hz-legend">
                    @foreach ($names as $key => [$label, $tone])
                        <li><span class="hz-legend__dot hz-legend__dot--{{ $tone }}"></span>{{ $label }}<strong>{{ $catalog[$key] }}</strong></li>
                    @endforeach
                </ul>
            </section>

            <section class="hz-card hz-dash__card" aria-labelledby="hz-people-title">
                <header class="hz-dash__cardhead"><div><h2 id="hz-people-title">{{ __('Comunidad') }}</h2></div><a class="hz-link" href="{{ route('admin.users') }}">{{ __('Ver usuarios') }} →</a></header>
                <div class="hz-mini">
                    <div><strong>{{ number_format($totals['students']) }}</strong><span>{{ __('Alumnos') }}</span></div>
                    <div><strong>{{ number_format($totals['instructors']) }}</strong><span>{{ __('Instructores') }}</span></div>
                    <div><strong>{{ number_format($totals['courses']) }}</strong><span>{{ __('Cursos activos') }}</span></div>
                </div>
                <h3 class="hz-dash__sub">{{ __('Últimas inscripciones') }}</h3>
                @forelse ($enrollments as $e)
                    <div class="hz-feed">
                        <x-panel.user-cell :name="$e->student?->full_name ?: __('Alumno')" :email="\Illuminate\Support\Str::limit(html_entity_decode($e->course?->title ?? ''), 36)" :image="$e->student?->image" />
                        <time class="hz-feed__time" datetime="{{ $e->created_at->toIso8601String() }}">{{ $e->created_at->diffForHumans(short: true) }}</time>
                    </div>
                @empty
                    <p class="hz-dash__none">{{ __('Aún no hay inscripciones.') }}</p>
                @endforelse
            </section>
        </aside>
    </div>
</div>
