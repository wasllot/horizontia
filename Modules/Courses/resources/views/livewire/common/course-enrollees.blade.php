<main class="tb-main am-courses-system hz-en">
    <header class="hz-seo__head">
        <div>
            <a class="hz-en__back" href="{{ $backUrl }}">← Cursos</a>
            <h2>Inscritos · {{ html_entity_decode($course->title) }} <span class="hz-count">{{ $total }}</span></h2>
            <p>{{ $course->isOnsite() ? 'Quién compró cada fecha del curso presencial. Puedes moverlos de fecha, avisar de cambios o cancelar una fecha (se les avisa por correo).' : 'Personas inscritas en este curso.' }}</p>
        </div>
        <div class="hz-en__tools">
            <a class="hz-btn hz-btn--secondary hz-btn--sm" href="{{ $csvUrl }}" data-testid="enrollees-csv"><i class="icon-download" aria-hidden="true"></i> Descargar CSV</a>
            <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm" onclick="window.print()"><i class="icon-printer" aria-hidden="true"></i> Imprimir</button>
        </div>
    </header>

    @if ($course->isOnsite())
        <section class="hz-en__dates" aria-label="Fechas del curso">
            <button type="button" class="hz-en__date {{ $dateFilter === '' ? 'is-active' : '' }}" wire:click="$set('dateFilter', '')">
                <strong>Todas las fechas</strong><span>{{ $total }} inscritos</span>
            </button>
            @foreach ($dates as $d)
                @php
                    $over = in_array($d->id, $oversold, true);
                    $full = $d->capacity !== null && $d->taken >= $d->capacity;
                @endphp
                <div class="hz-en__date {{ $dateFilter === (string) $d->id ? 'is-active' : '' }} {{ $d->isCancelled() ? 'is-cancelled' : '' }}" wire:key="date-{{ $d->id }}" data-testid="date-card">
                    <button type="button" class="hz-en__pick" wire:click="$set('dateFilter', '{{ $d->id }}')">
                        <strong>{{ $d->startsLocal()->locale('es')->translatedFormat('D j M Y, H:i') }}</strong>
                        <span>{{ $d->venue_name }} · {{ $d->city }}</span>
                        <span class="hz-en__seats">{{ $d->taken }}{{ $d->capacity !== null ? ' / ' . $d->capacity : '' }} inscritos
                            @if ($d->isCancelled()) <x-panel.badge tone="danger" :dot="false">Cancelada</x-panel.badge>
                            @elseif ($over) <x-panel.badge tone="danger" :dot="false">Sobrecupo</x-panel.badge>
                            @elseif ($full) <x-panel.badge tone="warning" :dot="false">Agotada</x-panel.badge>
                            @elseif ($d->starts_at->isPast()) <x-panel.badge tone="neutral" :dot="false">Finalizada</x-panel.badge>
                            @endif
                        </span>
                    </button>
                    @unless ($d->isCancelled())
                        <div class="hz-en__dateacts">
                            <button type="button" class="hz-en__act" wire:click="openNotice({{ $d->id }})" @disabled(!$d->taken)>Avisar cambio</button>
                            <button type="button" class="hz-en__act hz-en__act--danger" wire:click="openCancel({{ $d->id }})">Cancelar fecha</button>
                        </div>
                    @endunless
                </div>
            @endforeach
            @if ($withoutDate)
                <button type="button" class="hz-en__date {{ $dateFilter === 'sin' ? 'is-active' : '' }}" wire:click="$set('dateFilter', 'sin')">
                    <strong>Sin fecha asignada</strong><span>{{ $withoutDate }} inscritos</span>
                </button>
            @endif
        </section>

        @if ($noticeFor)
            <form class="hz-en__form" wire:submit="sendNotice" data-testid="notice-form">
                <label for="en-notice">Mensaje para los inscritos de esta fecha</label>
                <textarea id="en-notice" class="form-control" rows="3" wire:model="notice" maxlength="500" placeholder="Ej. La sede cambió a la sala B. Los datos de abajo ya están actualizados."></textarea>
                @error('notice') <small class="hz-en__err">{{ $message }}</small> @enderror
                <small>Se envía un correo con tu mensaje y los datos actuales de la fecha (horario, sede, cómo llegar y calendario).</small>
                <div class="hz-en__formacts">
                    <button type="submit" class="hz-btn hz-btn--sm" wire:loading.attr="disabled">Enviar aviso</button>
                    <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="closeForms">Cerrar</button>
                </div>
            </form>
        @endif

        @if ($cancelFor)
            <form class="hz-en__form hz-en__form--danger" wire:submit="cancelDate" data-testid="cancel-form">
                <strong>¿Cancelar esta fecha?</strong>
                <p>Dejará de venderse y se avisará por correo a quienes la compraron. El reembolso se hace a mano desde Mercado Pago; también puedes mover a cada persona a otra fecha antes de cancelar.</p>
                <label for="en-cancel">Mensaje para los inscritos (opcional)</label>
                <textarea id="en-cancel" class="form-control" rows="2" wire:model="cancelNote" maxlength="500"></textarea>
                <div class="hz-en__formacts">
                    <button type="submit" class="hz-btn hz-btn--danger hz-btn--sm" wire:loading.attr="disabled">Sí, cancelar y avisar</button>
                    <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="closeForms">No, volver</button>
                </div>
            </form>
        @endif
    @endif

    <form class="hz-toolbar" onsubmit="return false">
        <div class="hz-toolbar__search">
            <i class="icon-search" aria-hidden="true"></i>
            <input type="search" class="form-control" wire:model.live.debounce.400ms="search" autocomplete="off" placeholder="Buscar por nombre o correo…" aria-label="Buscar inscrito">
        </div>
    </form>

    <div class="am-disputelist_wrap">
        <div class="am-disputelist am-custom-scrollbar-y">
            @if ($rows->isNotEmpty())
                <table class="tb-table" data-testid="enrollees-table">
                    <thead>
                        <tr>
                            <th class="hz-col-main">Nombre</th>
                            <th>Correo</th>
                            <th>Teléfono</th>
                            @if ($course->isOnsite()) <th>Fecha del curso</th> @endif
                            <th>Compra</th>
                            @if ($course->isOnsite()) <th class="hz-en__noprint"><span class="hz-sr">Mover</span></th> @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $e)
                            @php $p = $e->student; $s = $e->session; @endphp
                            <tr wire:key="enrollee-{{ $e->id }}">
                                <td data-label="Nombre"><strong>{{ trim(($p?->first_name ?? '') . ' ' . ($p?->last_name ?? '')) ?: '—' }}</strong></td>
                                <td data-label="Correo">{{ $p?->user?->email ?? '—' }}</td>
                                <td data-label="Teléfono">{{ $p?->phone_number ?: '—' }}</td>
                                @if ($course->isOnsite())
                                    <td data-label="Fecha del curso">
                                        @if ($s)
                                            <span class="hz-twoline"><strong>{{ $s->startsLocal()->locale('es')->translatedFormat('j M Y, H:i') }}</strong><small>{{ $s->city }}@if($s->isCancelled()) · cancelada @endif</small></span>
                                        @else
                                            <em>Sin fecha</em>
                                        @endif
                                    </td>
                                @endif
                                <td data-label="Compra">{{ optional($e->created_at)->format('d/m/Y') }}</td>
                                @if ($course->isOnsite())
                                    <td class="hz-en__noprint" data-label="Mover">
                                        <select class="form-control hz-en__move" aria-label="Mover a otra fecha" wire:change="move({{ $e->id }}, $event.target.value)">
                                            <option value="">Mover a…</option>
                                            @foreach ($dates as $d)
                                                @continue($d->isCancelled() || $d->id === $e->session_id)
                                                <option value="{{ $d->id }}">{{ $d->startsLocal()->locale('es')->translatedFormat('j M, H:i') }} · {{ $d->city }}{{ $d->capacity !== null && $d->taken >= $d->capacity ? ' (llena)' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="hz-empty"><h3>Sin inscritos</h3><p>{{ trim($search) !== '' || $dateFilter !== '' ? 'No hay inscritos con ese filtro.' : 'Cuando alguien compre este curso aparecerá aquí.' }}</p></div>
            @endif
        </div>
    </div>
</main>
