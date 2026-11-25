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
                                <td data-label="Compra"><strong>#{{ $o->id }} · {{ $o->name }}</strong><br><small>{{ $o->email }} · {{ $o->phone }}<br>{{ $o->created_at->timezone(\App\Models\Event::timezone())->format('d/m/Y H:i') }}@if ($o->confirmationKey())<br>Clave: <b>{{ $o->confirmationKey() }}</b>{{ $o->hotel_notified_at ? ' · hotel avisado' : '' }}@endif @if ($o->status === 'paid')<br>Correo al cliente: @if ($o->confirmation_sent_at)<b data-testid="mail-ok" style="color:var(--hz-success)">enviado</b>@else<b data-testid="mail-missing" style="color:var(--hz-danger)">SIN ENVIAR · reenvíalo con el sobre</b>@endif @endif</small></td>
                                <td data-label="Asistentes">{{ $o->quantity }} · <small>{{ $o->packagesLabel() }}<br>{{ $o->tickets->pluck('attendee_name')->implode(', ') }}</small></td>
                                <td data-label="Total">${{ number_format($o->total, 2) }}@if ($o->discount_label)<br><small>{{ $o->discount_label }}</small>@endif</td>
                                <td data-label="Estado">
                                    @if ($o->status === 'paid')<x-panel.badge tone="success">Pagada</x-panel.badge>
                                    @elseif ($o->status === 'pending')<x-panel.badge tone="warning" :dot="false">Por pagar</x-panel.badge>
                                    @elseif ($o->status === 'refunded')<x-panel.badge tone="neutral">Reembolsada</x-panel.badge>
                                    @else<x-panel.badge tone="neutral">Vencida</x-panel.badge>@endif
                                </td>
                                <td data-label="Acciones"><div class="hz-acts">@if ($o->status === 'paid')<x-panel.action icon="icon-mail" label="Reenviar boletos por correo" data-testid="resend-{{ $o->id }}" wire:click="openResend({{ $o->id }})" /><x-panel.action icon="icon-link" label="Copiar enlace de los boletos (para enviarlo por WhatsApp)" data-testid="copy-link-{{ $o->id }}" x-on:click="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($current->url('gracias') . '?order=' . $o->code) }}).then(() => showAlert({ type: 'success', message: 'Enlace copiado. Pégalo en WhatsApp o en el correo.', autoclose: 4000 }), () => window.prompt('Copia este enlace (Ctrl+C):', {{ \Illuminate\Support\Js::from($current->url('gracias') . '?order=' . $o->code) }}))" />@if ($o->roomCode() && $current->hotelEmails())<x-panel.action icon="icon-send" label="Reenviar aviso al hotel" wire:click="resendHotel({{ $o->id }})" />@endif @endif</div></td>
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
    {{-- Ventana: reenviar boletos (correo de destino editable, error dentro de la ventana) --}}
    @if ($sendingOrder)
        @php $sendLink = $current->url('gracias') . '?order=' . $sendingOrder->code; @endphp
        <div class="hz-overlay" wire:click.self="cancelResend" x-data x-init="$nextTick(() => { $refs.to?.focus(); $refs.to?.select(); })" @keydown.escape.window="$wire.cancelResend()" data-testid="resend-dialog">
            <form class="hz-dialog" style="max-width:520px" role="dialog" aria-modal="true" aria-labelledby="resend-title" wire:submit="sendResend" novalidate>
                <div class="hz-dialog__head">
                    <h3 id="resend-title">Reenviar boletos</h3>
                    <button type="button" class="hz-drawer__close" wire:click="cancelResend" aria-label="Cerrar">✕</button>
                </div>
                <div class="hz-dialog__body">
                    <p style="margin:0 0 16px;color:var(--hz-muted);font-size:var(--hz-fs-md)">Compra #{{ $sendingOrder->id }} · {{ $sendingOrder->name }} · {{ $sendingOrder->quantity }} {{ $sendingOrder->quantity === 1 ? 'persona' : 'personas' }}.<br>Se envía el correo con el QR y la clave de cada asistente.</p>
                    <div class="hz-field">
                        <label for="resend-to">Enviar a</label>
                        <input id="resend-to" x-ref="to" type="email" class="form-control" wire:model="sendTo" autocomplete="off" inputmode="email" placeholder="correo@ejemplo.com" data-testid="resend-to">
                        <small class="hz-error" data-testid="resend-to-error">{{ $errors->first('sendTo') }}</small>
                        <small style="display:block;margin-top:6px;color:var(--hz-muted)">Si lo cambias, solo se envía a esa dirección; la compra conserva el correo del comprador.</small>
                    </div>
                    <div class="hz-callout hz-callout--warning" role="alert" data-testid="resend-error" style="margin-top:14px;{{ $sendError ? '' : 'display:none' }}"><p>{{ $sendError }}</p></div>
                </div>
                <div class="hz-dialog__foot">
                    <button type="button" class="hz-btn hz-btn--ghost hz-dialog__start" x-on:click="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($sendLink) }}).then(() => showAlert({ type: 'success', message: 'Enlace copiado. Pégalo en WhatsApp o en el correo.', autoclose: 4000 }), () => window.prompt('Copia este enlace (Ctrl+C):', {{ \Illuminate\Support\Js::from($sendLink) }}))" data-testid="resend-copy">Copiar enlace</button>
                    <button type="button" class="hz-btn hz-btn--secondary" wire:click="cancelResend">Cancelar</button>
                    <button type="submit" class="hz-btn" wire:loading.attr="disabled" wire:target="sendResend" data-testid="resend-submit"><span wire:loading.remove wire:target="sendResend">Enviar boletos</span><span wire:loading wire:target="sendResend">Enviando…</span></button>
                </div>
            </form>
        </div>
    @endif
</main>
