<main class="tb-main am-courses-system hz-bn">
    <header class="hz-seo__head">
        <div>
            <h2>Solicitudes de acceso <span class="hz-count">{{ $counts['pending'] ?? 0 }}</span></h2>
            <p>Nadie se registra solo: quien pide acceso en <a href="{{ route('access.request') }}" target="_blank" rel="noopener">/solicitar-acceso</a> aparece aquí. Al aprobar se crea su cuenta (estudiante o instructor) y recibe un enlace de un solo uso para elegir su contraseña.</p>
        </div>
        <input type="search" class="form-control" style="max-width: 260px" wire:model.live.debounce.300ms="search" placeholder="Buscar nombre o correo…" aria-label="Buscar solicitudes">
    </header>

    <div class="hz-tabs" role="tablist" aria-label="Estado">
        @foreach (['pending' => 'Pendientes', 'approved' => 'Aprobadas', 'rejected' => 'Rechazadas', 'all' => 'Todas'] as $k => $label)
            <button type="button" role="tab" aria-selected="{{ $filter === $k ? 'true' : 'false' }}" @class(['hz-tabs__tab', 'is-active' => $filter === $k]) wire:click="setFilter('{{ $k }}')" data-testid="filter-{{ $k }}">{{ $label }}@if ($k !== 'all' && ($counts[$k] ?? 0)) <small>({{ $counts[$k] }})</small>@endif</button>
        @endforeach
    </div>

    @if ($rejectingId)
        <form class="hz-en__form" wire:submit="reject" wire:key="reject-form" data-testid="reject-form">
            <strong>Rechazar solicitud</strong>
            <label for="ar-note">Motivo (opcional; se envía a la persona)</label>
            <input id="ar-note" class="form-control" wire:model="note" maxlength="300" placeholder="Ej. Por ahora solo aceptamos instructores con experiencia comprobable">
            <div class="hz-en__formacts">
                <button type="submit" class="hz-btn hz-btn--sm">Rechazar y avisar</button>
                <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="cancelReject">Cancelar</button>
            </div>
        </form>
    @endif

    <div class="am-disputelist_wrap">
        <div class="am-disputelist">
            @if ($requests->isNotEmpty())
                <table class="tb-table" data-testid="requests-table">
                    <thead><tr><th class="hz-col-main">Persona</th><th>Quiere ser</th><th>Recibida</th><th>Estado</th><th><span class="hz-sr">Acciones</span></th></tr></thead>
                    <tbody>
                        @foreach ($requests as $r)
                            <tr wire:key="r-{{ $r->id }}">
                                <td data-label="Persona">
                                    <strong>{{ $r->name }}</strong><br><small>{{ $r->email }}@if ($r->phone) · {{ $r->phone }}@endif</small>
                                    @if ($r->message || $r->link)
                                        <br><button type="button" class="hz-rp__link" wire:click="toggle({{ $r->id }})">{{ $openId === $r->id ? 'Ocultar detalle' : 'Ver lo que escribió' }}</button>
                                        @if ($openId === $r->id)
                                            <div class="hz-ar__detail">@if ($r->message)<p>{{ $r->message }}</p>@endif @if ($r->link)<p><a href="{{ $r->link }}" target="_blank" rel="noopener nofollow">{{ $r->link }}</a></p>@endif</div>
                                        @endif
                                    @endif
                                </td>
                                <td data-label="Quiere ser">{{ $r->typeLabel() }}</td>
                                <td data-label="Recibida">{{ $r->created_at->locale('es')->diffForHumans() }}</td>
                                <td data-label="Estado">
                                    @if ($r->status === 'pending')<x-panel.badge tone="warning" :dot="false">Pendiente</x-panel.badge>
                                    @elseif ($r->status === 'approved')<x-panel.badge tone="success">Aprobada</x-panel.badge>@if ($r->activated_at)<br><small>Ya activó su cuenta</small>@else<br><small>Aún no activa su cuenta</small>@endif
                                    @else<x-panel.badge tone="neutral">Rechazada</x-panel.badge>@endif
                                </td>
                                <td data-label="Acciones">
                                    <div class="hz-acts">
                                        @if ($r->status === 'pending')
                                            <x-panel.action icon="icon-check" label="Aprobar y crear la cuenta" wire:click="approve({{ $r->id }})" wire:confirm="¿Aprobar a {{ $r->name }} como {{ $r->type === 'tutor' ? 'instructor' : 'estudiante' }}? Se crea su cuenta y se le envía el enlace para elegir contraseña." />
                                            <x-panel.action icon="icon-x" label="Rechazar" tone="danger" wire:click="askReject({{ $r->id }})" />
                                        @else
                                            <x-panel.action icon="icon-trash-2" label="Borrar de la lista" tone="danger" wire:click="delete({{ $r->id }})" wire:confirm="¿Borrar esta solicitud de la lista? La cuenta (si existe) no se toca." />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $requests->links() }}
            @else
                <div class="hz-empty"><h3>{{ $filter === 'pending' ? 'No hay solicitudes pendientes' : 'Sin resultados' }}</h3><p>Cuando alguien pida acceso aparecerá aquí y te llegará un correo.</p></div>
            @endif
        </div>
    </div>
</main>
