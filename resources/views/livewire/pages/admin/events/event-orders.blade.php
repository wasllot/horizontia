<main class="tb-main am-courses-system hz-bn">
    <header class="hz-seo__head">
        <div>
            <h2>Compras de eventos</h2>
            @if ($current)
                <p>{{ $current->name }} · {{ $stats['paid'] }} personas con lugar · ${{ number_format($stats['revenue'], 2) }} MXN · {{ $stats['pending'] }} reservadas por pagar{{ $current->capacity ? ' · cupo ' . $current->capacity : '' }}</p>
            @else
                <p>Las compras son de invitado, sin cuenta, y se cobran con Mercado Pago.</p>
            @endif
        </div>
        @if ($current)
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                @if ($events->count() > 1)
                    <select class="form-control" wire:model.live="eventId" aria-label="Evento" style="width:auto" data-testid="orders-event">
                        @foreach ($events as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
                    </select>
                @endif
                <select class="form-control" wire:model.live="orderStatus" aria-label="Estado" style="width:auto" data-testid="orders-status">
                    <option value="paid">Pagadas</option><option value="pending">Por pagar</option><option value="expired">Vencidas</option><option value="refunded">Reembolsadas</option><option value="all">Todas</option>
                </select>
                <a class="hz-btn hz-btn--ghost hz-btn--sm" href="{{ route('admin.events.export', $current) }}?estado={{ $status }}" data-testid="event-csv">Descargar CSV</a>
                @if ($current->hotelEmails())
                    <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="sendHotelList" wire:confirm="¿Enviar al hotel la lista completa de códigos de reservación?" data-testid="hotel-list">Enviar lista al hotel</button>
                @endif
            </div>
        @endif
    </header>

    @if ($current)
        <div class="am-disputelist_wrap"><div class="am-disputelist">
            @if ($orders->isNotEmpty())
                <table class="tb-table" data-testid="orders-table">
                    <thead><tr><th class="hz-col-main">Compra</th><th>Asistentes</th><th>Total</th><th>Estado</th><th><span class="hz-sr">Acciones</span></th></tr></thead>
                    <tbody>
                        @foreach ($orders as $o)
                            <tr wire:key="eo-{{ $o->id }}">
                                <td data-label="Compra"><strong>#{{ $o->id }} · {{ $o->name }}</strong><br><small>{{ $o->email }} · {{ $o->phone }}<br>{{ $o->created_at->timezone(\App\Models\Event::timezone())->format('d/m/Y H:i') }}@if ($o->roomCode())<br>Hotel: <b>{{ $o->roomCode() }}</b>{{ $o->hotel_notified_at ? ' · avisado' : '' }}@endif</small></td>
                                <td data-label="Asistentes">{{ $o->quantity }} · <small>{{ $o->packagesLabel() }}<br>{{ $o->tickets->pluck('attendee_name')->implode(', ') }}</small></td>
                                <td data-label="Total">${{ number_format($o->total, 2) }}@if ($o->discount_label)<br><small>{{ $o->discount_label }}</small>@endif</td>
                                <td data-label="Estado">
                                    @if ($o->status === 'paid')<x-panel.badge tone="success">Pagada</x-panel.badge>
                                    @elseif ($o->status === 'pending')<x-panel.badge tone="warning" :dot="false">Por pagar</x-panel.badge>
                                    @elseif ($o->status === 'refunded')<x-panel.badge tone="neutral">Reembolsada</x-panel.badge>
                                    @else<x-panel.badge tone="neutral">Vencida</x-panel.badge>@endif
                                </td>
                                <td data-label="Acciones"><div class="hz-acts">@if ($o->status === 'paid')<x-panel.action icon="icon-mail" label="Reenviar confirmación" wire:click="resend({{ $o->id }})" />@if ($o->roomCode() && $current->hotelEmails())<x-panel.action icon="icon-send" label="Reenviar aviso al hotel" wire:click="resendHotel({{ $o->id }})" />@endif @endif</div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="hz-empty"><h3>Sin compras en este estado</h3><p>Cuando alguien compre, aparecerá aquí.</p></div>
            @endif
        </div></div>
    @else
        <div class="am-disputelist_wrap"><div class="am-disputelist">
            <div class="hz-empty"><h3>Aún no hay eventos</h3><p>Crea el primero en <a href="{{ route('admin.events') }}">Eventos</a>.</p></div>
        </div></div>
    @endif
</main>
