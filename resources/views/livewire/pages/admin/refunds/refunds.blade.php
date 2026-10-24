<main class="tb-main am-courses-system hz-bn">
    <header class="hz-seo__head">
        <div>
            <h2>Reembolsos <span class="hz-count">{{ $pending }}</span></h2>
            <p>Solicitudes de la garantía de {{ config('academy.refund_days') }} días. Al aprobar se devuelve el pago por Mercado Pago y se retira el acceso al curso; al rechazar, la persona recibe tu motivo por correo.</p>
        </div>
    </header>

    <div class="hz-tabs" role="tablist" aria-label="Filtro de solicitudes">
        @foreach (['pending' => 'Por revisar', 'resolved' => 'Resueltas', 'all' => 'Todas'] as $key => $label)
            <button type="button" role="tab" aria-selected="{{ $filter === $key ? 'true' : 'false' }}" @class(['hz-tabs__tab', 'is-active' => $filter === $key]) wire:click="setFilter('{{ $key }}')" data-testid="refund-tab-{{ $key }}">{{ $label }}</button>
        @endforeach
    </div>

    <section class="hz-card hz-dash__card">
        @if ($requests->isNotEmpty())
            <table class="tb-table" data-testid="refund-table">
                <thead><tr><th class="hz-col-main">Persona y pedido</th><th>Monto</th><th>Avance</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    @foreach ($requests as $r)
                        @php $o = $r->order; @endphp
                        <tr wire:key="rr-{{ $r->id }}" data-testid="refund-{{ $r->id }}">
                            <td data-label="Persona y pedido">
                                <strong>{{ $r->user?->profile?->full_name ?: $r->user?->email }}</strong><br>
                                <small>#{{ $o->id }} · {{ \Illuminate\Support\Str::limit($o->items->pluck('title')->map(fn ($t) => html_entity_decode($t))->implode(' · '), 60) }}</small><br>
                                <small>{{ $r->created_at->translatedFormat('j M Y, H:i') }}</small>
                            </td>
                            <td data-label="Monto">{!! formatAmount($o->amount) !!}</td>
                            <td data-label="Avance">{{ $r->progress }} %</td>
                            <td data-label="Motivo" style="max-width: 280px; white-space: normal">{{ $r->reason }}</td>
                            <td data-label="Estado">
                                @if ($r->status === 'pending')<x-panel.badge tone="warning">Por revisar</x-panel.badge>
                                @elseif ($r->status === 'approved')<x-panel.badge tone="success">Aprobada</x-panel.badge>
                                @else<x-panel.badge tone="danger">Rechazada</x-panel.badge>@if ($r->review_note)<br><small>{{ $r->review_note }}</small>@endif
                                @endif
                            </td>
                            <td data-label="Acciones">
                                @if ($r->status === 'pending')
                                    <button type="button" class="hz-btn hz-btn--sm" wire:click="approve({{ $r->id }})" wire:confirm="¿Aprobar y devolver {{ strip_tags(formatAmount($o->amount)) }}? El acceso al curso se retira de inmediato." data-testid="refund-approve-{{ $r->id }}">Aprobar</button>
                                    <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="askReject({{ $r->id }})" data-testid="refund-reject-{{ $r->id }}">Rechazar</button>
                                @else
                                    <small>{{ $r->reviewed_at?->translatedFormat('j M Y') }}</small>
                                @endif
                            </td>
                        </tr>
                        @if ($rejecting === $r->id)
                            <tr wire:key="rj-{{ $r->id }}"><td colspan="6">
                                <form wire:submit="reject" class="hz-en__form" data-testid="refund-reject-form">
                                    <label for="rj-note">Motivo (se lo enviamos a la persona)</label>
                                    <textarea id="rj-note" class="form-control" wire:model="note" maxlength="300" rows="2" placeholder="Ej. Ya consumió más del 20 % del curso."></textarea>
                                    <small class="hz-en__err" aria-live="polite">{{ $errors->first('note') }}</small>
                                    <div><button type="submit" class="hz-btn hz-btn--sm" data-testid="refund-reject-send">Rechazar solicitud</button> <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="cancelReject">Cancelar</button></div>
                                </form>
                            </td></tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="hz-rp__empty">{{ $filter === 'pending' ? 'No hay solicitudes por revisar. 🎉' : 'Aún no hay solicitudes de reembolso.' }}</p>
        @endif
    </section>
</main>
