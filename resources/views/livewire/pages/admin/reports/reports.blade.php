@php
    $labels = ['30d' => '30 días', '90d' => '90 días', 'year' => '12 meses', 'all' => 'Todo'];
    $methodNames = ['mercadopago' => 'Mercado Pago', 'stripe' => 'Tarjeta (Stripe)', 'paypal' => 'PayPal', 'sin dato' => 'Sin dato'];
@endphp
<main class="tb-main am-courses-system hz-rp">
    <header class="hz-seo__head">
        <div>
            <h2>Reportes</h2>
            <p>Ventas, aprendizaje de tus alumnos y cupones del periodo {{ $report->from->translatedFormat('j M Y') }} – {{ $report->to->translatedFormat('j M Y') }}.</p>
        </div>
    </header>

    <div class="hz-tabs" role="tablist" aria-label="Sección del reporte">
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" @class(['hz-tabs__tab', 'is-active' => $tab === $key]) wire:click="setTab('{{ $key }}')" data-testid="tab-{{ $key }}">{{ $label }}</button>
        @endforeach
        <span class="hz-rp__range" role="group" aria-label="Periodo">
            @foreach ($labels as $key => $label)
                <button type="button" @class(['hz-rp__chip', 'is-on' => $range === $key]) wire:click="setRange('{{ $key }}')" aria-pressed="{{ $range === $key ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </span>
    </div>

    @if ($tab === 'ventas')
        <section class="hz-kpis hz-rp__kpis" aria-label="Indicadores de ventas" data-testid="sales-kpis">
            <article class="hz-kpi hz-kpi--blue"><div class="hz-kpi__body"><span class="hz-kpi__label">Ventas</span><strong class="hz-kpi__value" data-testid="kpi-gross">{!! formatAmount($summary['gross'], true) !!}</strong><span class="hz-delta hz-delta--flat">{{ number_format($summary['orders']) }} pedidos pagados</span></div></article>
            <article class="hz-kpi hz-kpi--teal"><div class="hz-kpi__body"><span class="hz-kpi__label">Ticket promedio</span><strong class="hz-kpi__value">{!! formatAmount($summary['ticket'], true) !!}</strong></div></article>
            <article class="hz-kpi hz-kpi--green"><div class="hz-kpi__body"><span class="hz-kpi__label">Conversión de pedidos</span><strong class="hz-kpi__value" data-testid="kpi-conversion">{{ number_format($summary['conversion'], 1) }}%</strong><span class="hz-delta hz-delta--flat">{{ $summary['orders'] }} de {{ $summary['initiated'] }} iniciados</span></div></article>
            <article class="hz-kpi hz-kpi--gold"><div class="hz-kpi__body"><span class="hz-kpi__label">Descuentos dados</span><strong class="hz-kpi__value">{!! formatAmount($summary['discounts'], true) !!}</strong></div></article>
            <article class="hz-kpi hz-kpi--violet"><div class="hz-kpi__body"><span class="hz-kpi__label">Reembolsos</span><strong class="hz-kpi__value">{{ $summary['refunds'] }}</strong><span class="hz-delta hz-delta--flat">{!! formatAmount($summary['refunded_amount'], true) !!}</span></div></article>
        </section>

        <section class="hz-card hz-dash__card">
            <header class="hz-dash__cardhead">
                <div><h2>Ventas {{ $report->days() > 92 ? 'por mes' : 'por día' }}</h2><p>Pedidos pagados en el periodo</p></div>
                <a class="hz-btn hz-btn--ghost hz-btn--sm" href="{{ route('admin.reports.export', ['type' => 'ventas', 'periodo' => $range]) }}" data-testid="export-ventas">Descargar CSV</a>
            </header>
            @php $max = max($series->max() ?: 0, 1); $bars = $series->count(); @endphp
            @if ($summary['gross'] > 0)
                <div class="hz-rp__bars" role="img" aria-label="Gráfico de ventas">
                    @foreach ($series as $bucket => $v)
                        <span class="hz-rp__bar" style="height: {{ max(2, round($v / $max * 100)) }}%" title="{{ $bucket }}: {{ strip_tags(formatAmount($v, true)) }}"></span>
                    @endforeach
                </div>
            @else
                <p class="hz-rp__empty">Todavía no hay ventas en este periodo.</p>
            @endif
        </section>

        <div class="hz-rp__cols">
            <section class="hz-card hz-dash__card">
                <header class="hz-dash__cardhead"><div><h2>Cursos más vendidos</h2><p>Por ingresos</p></div><a class="hz-btn hz-btn--ghost hz-btn--sm" href="{{ route('admin.reports.export', ['type' => 'cursos', 'periodo' => $range]) }}">CSV</a></header>
                @if ($top->isNotEmpty())
                    <table class="tb-table" data-testid="top-courses"><thead><tr><th class="hz-col-main">Curso</th><th>Ventas</th><th>Ingresos</th><th>%</th></tr></thead><tbody>
                        @foreach ($top as $c)
                            <tr><td data-label="Curso"><strong>{{ $c->title }}</strong></td><td data-label="Ventas">{{ $c->sales }}</td><td data-label="Ingresos">{!! formatAmount($c->revenue, true) !!}</td><td data-label="%">{{ $c->share }}%</td></tr>
                        @endforeach
                    </tbody></table>
                @else
                    <p class="hz-rp__empty">Sin ventas de cursos en el periodo.</p>
                @endif
            </section>
            <section class="hz-card hz-dash__card">
                <header class="hz-dash__cardhead"><div><h2>Cómo pagan</h2><p>Por método de pago</p></div></header>
                @if ($methods->isNotEmpty())
                    <table class="tb-table" data-testid="methods"><thead><tr><th class="hz-col-main">Método</th><th>Pedidos</th><th>Monto</th></tr></thead><tbody>
                        @foreach ($methods as $m)
                            <tr><td data-label="Método">{{ $methodNames[$m->method] ?? ucfirst($m->method) }}</td><td data-label="Pedidos">{{ $m->orders }}</td><td data-label="Monto">{!! formatAmount($m->amount, true) !!}</td></tr>
                        @endforeach
                    </tbody></table>
                @else
                    <p class="hz-rp__empty">Sin pagos en el periodo.</p>
                @endif
                <p class="hz-rp__note">Pagos pendientes (OXXO/SPEI sin acreditar): <strong>{{ $summary['pending'] }}</strong> por {!! formatAmount($summary['pending_amount'], true) !!} · Carritos con cursos ahora: <strong>{{ $summary['carts'] }}</strong></p>
            </section>
        </div>
    @elseif ($tab === 'aprendizaje')
        <section class="hz-card hz-dash__card">
            <header class="hz-dash__cardhead"><div><h2>Finalización por curso</h2><p>De todos los inscritos activos: cuántos empezaron y cuántos terminaron</p></div><a class="hz-btn hz-btn--ghost hz-btn--sm" href="{{ route('admin.reports.export', ['type' => 'aprendizaje', 'periodo' => $range]) }}" data-testid="export-aprendizaje">Descargar CSV</a></header>
            @if ($learning->isNotEmpty())
                <table class="tb-table" data-testid="learning-table"><thead><tr><th class="hz-col-main">Curso</th><th>Inscritos</th><th>Empezaron</th><th>Terminaron</th><th>Avance medio</th><th>Finalización</th></tr></thead><tbody>
                    @foreach ($learning as $c)
                        <tr wire:key="l-{{ $c->id }}" @class(['is-selected' => $selected === $c->id])>
                            <td data-label="Curso"><button type="button" class="hz-rp__link" wire:click="$set('courseId', {{ $c->id }})">{{ $c->title }}</button></td>
                            <td data-label="Inscritos">{{ $c->enrolled }}</td><td data-label="Empezaron">{{ $c->started }}</td><td data-label="Terminaron">{{ $c->completed }}</td>
                            <td data-label="Avance medio">{{ $c->avg_progress }}%</td>
                            <td data-label="Finalización"><span class="hz-rp__meter"><i style="width: {{ $c->completion }}%"></i></span> {{ $c->completion }}%</td>
                        </tr>
                    @endforeach
                </tbody></table>
            @else
                <p class="hz-rp__empty">Aún no hay cursos en línea con alumnos inscritos.</p>
            @endif
        </section>

        @if ($selected && $funnel->isNotEmpty())
            <section class="hz-card hz-dash__card" data-testid="lesson-funnel">
                <header class="hz-dash__cardhead"><div><h2>Dónde abandonan</h2><p>% de los inscritos que terminó cada lección de «{{ $learning->firstWhere('id', $selected)->title }}». En rojo, las caídas más fuertes.</p></div></header>
                <ol class="hz-rp__lessons">
                    @foreach ($funnel as $i => $l)
                        <li @class(['is-drop' => $l->drop >= 15])>
                            <span class="hz-rp__ltitle">{{ $i + 1 }}. {{ $l->title }}</span>
                            <span class="hz-rp__meter hz-rp__meter--wide"><i style="width: {{ $l->percent }}%"></i></span>
                            <strong>{{ $l->percent }}%</strong>
                            @if ($l->drop >= 15)<em title="Caída respecto a la lección anterior">▼ {{ $l->drop }} pts</em>@endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
    @elseif ($tab === 'whatsapp')
        <section class="hz-card hz-dash__card" data-testid="whatsapp-report">
            <header class="hz-dash__cardhead">
                <div><h2>Avisos por WhatsApp</h2><p>{{ $waEnabled ? 'Activo' : 'Desactivado o sin configurar (Ajustes › Integraciones › WhatsApp)' }} · {{ number_format($wa['optins']) }} personas aceptaron recibirlos</p></div>
            </header>
            <p class="hz-rp__note">Enviados: <strong>{{ $wa['counts']['sent'] ?? 0 }}</strong> · En cola: <strong>{{ $wa['counts']['queued'] ?? 0 }}</strong> · Fallidos: <strong>{{ $wa['counts']['failed'] ?? 0 }}</strong> · Omitidos: <strong>{{ $wa['counts']['skipped'] ?? 0 }}</strong></p>
            @if ($wa['recent']->isNotEmpty())
                <table class="tb-table"><thead><tr><th class="hz-col-main">Aviso</th><th>Plantilla</th><th>Número</th><th>Estado</th><th>Cuándo</th></tr></thead><tbody>
                    @foreach ($wa['recent'] as $m)
                        <tr><td data-label="Aviso">{{ \App\Services\WhatsAppService::EVENTS[$m->event]['label'] ?? $m->event }}</td><td data-label="Plantilla">{{ $m->template }}</td><td data-label="Número">…{{ $m->to_tail }}</td>
                            <td data-label="Estado">@if ($m->status === 'sent')<x-panel.badge tone="success">Enviado</x-panel.badge>@elseif ($m->status === 'failed')<x-panel.badge tone="danger" :dot="false">Falló</x-panel.badge>@elseif ($m->status === 'skipped')<x-panel.badge tone="neutral">Omitido</x-panel.badge>@else<x-panel.badge tone="warning" :dot="false">En cola</x-panel.badge>@endif
                                @if ($m->error)<br><small>{{ \Illuminate\Support\Str::limit($m->error, 120) }}</small>@endif</td>
                            <td data-label="Cuándo">{{ $m->created_at->locale('es')->diffForHumans() }}</td></tr>
                    @endforeach
                </tbody></table>
            @else
                <p class="hz-rp__empty">Aún no se ha enviado ningún aviso en este periodo.</p>
            @endif
        </section>
    @elseif ($tab === 'busquedas')
        <div class="hz-rp__cols">
            <section class="hz-card hz-dash__card" data-testid="searches-empty">
                <header class="hz-dash__cardhead"><div><h2>Buscan y no encuentran</h2><p>{{ $searches['withoutResults'] }} de {{ $searches['total'] }} búsquedas sin resultados. Buenas ideas de cursos nuevos.</p></div></header>
                @if ($searches['empty']->isNotEmpty())
                    <table class="tb-table"><thead><tr><th class="hz-col-main">Búsqueda</th><th>Veces</th></tr></thead><tbody>
                        @foreach ($searches['empty'] as $r)<tr><td data-label="Búsqueda"><strong>{{ $r->query }}</strong></td><td data-label="Veces">{{ $r->searches }}</td></tr>@endforeach
                    </tbody></table>
                @else
                    <p class="hz-rp__empty">Todas las búsquedas del periodo encontraron algo. 🎉</p>
                @endif
            </section>
            <section class="hz-card hz-dash__card" data-testid="searches-top">
                <header class="hz-dash__cardhead"><div><h2>Lo más buscado</h2><p>Textos escritos en el catálogo de cursos</p></div></header>
                @if ($searches['top']->isNotEmpty())
                    <table class="tb-table"><thead><tr><th class="hz-col-main">Búsqueda</th><th>Veces</th></tr></thead><tbody>
                        @foreach ($searches['top'] as $r)<tr><td data-label="Búsqueda">{{ $r->query }}</td><td data-label="Veces">{{ $r->searches }}</td></tr>@endforeach
                    </tbody></table>
                @else
                    <p class="hz-rp__empty">Aún no hay búsquedas en este periodo.</p>
                @endif
            </section>
        </div>
    @else
        <section class="hz-card hz-dash__card">
            <header class="hz-dash__cardhead"><div><h2>Rendimiento de cupones</h2><p>Pedidos pagados que usaron un código en el periodo · {!! formatAmount($summary['discounts'], true) !!} en descuentos</p></div><a class="hz-btn hz-btn--ghost hz-btn--sm" href="{{ route('admin.coupons') }}">Administrar cupones</a></header>
            @if ($coupons->isNotEmpty())
                <table class="tb-table" data-testid="coupons-report"><thead><tr><th class="hz-col-main">Código</th><th>Descuento</th><th>Usos</th><th>Descontado</th><th>Ingresos con el código</th></tr></thead><tbody>
                    @foreach ($coupons as $c)
                        <tr><td data-label="Código"><strong>{{ $c->code }}</strong></td><td data-label="Descuento">{!! $c->label !!}</td><td data-label="Usos">{{ $c->uses }}</td><td data-label="Descontado">{!! formatAmount($c->discount, true) !!}</td><td data-label="Ingresos">{!! formatAmount($c->revenue, true) !!}</td></tr>
                    @endforeach
                </tbody></table>
            @else
                <p class="hz-rp__empty">Ningún pedido pagado usó un cupón en este periodo.</p>
            @endif
        </section>
    @endif
</main>
