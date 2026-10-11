<div class="cr-course-box">
    <div class="cr-content-box">
        <h1>Fechas y sede</h1>
        <p>Habilita las fechas en las que se imparte el curso. Quien compre elegirá una. Las horas son {{ $tzLabel }}.</p>
    </div>

    <div class="cr-dates">
        @forelse ($sessions as $s)
            @php
                $past = $s->starts_at->isPast();
                $left = $s->capacity === null ? null : max(0, $s->capacity - $s->taken);
            @endphp
            <article class="cr-date {{ $s->isCancelled() ? 'is-cancelled' : '' }} {{ $editingId === $s->id ? 'is-editing' : '' }}" wire:key="session-{{ $s->id }}">
                <div class="cr-date__when">
                    <strong>{{ $s->startsLocal()->locale('es')->translatedFormat('D j M Y') }}</strong>
                    <span>{{ $s->startsLocal()->format('H:i') }}@if($s->endsLocal())–{{ $s->endsLocal()->format('H:i') }}@endif</span>
                </div>
                <div class="cr-date__where">
                    <strong>{{ $s->venue_name }}</strong>
                    <span>{{ $s->address }} · {{ $s->city }}</span>
                    @if ($s->schedule_note)<small>{{ $s->schedule_note }}</small>@endif
                </div>
                <div class="cr-date__seats">
                    <strong>{{ $s->taken }}{{ $s->capacity !== null ? ' / ' . $s->capacity : '' }}</strong>
                    <span>{{ $s->capacity === null ? 'inscritos · sin límite' : ($left === 0 ? 'agotada' : $left . ' libres') }}</span>
                </div>
                <div class="cr-date__state">
                    @if ($s->isCancelled())<span class="cr-tag cr-tag--bad">Cancelada</span>
                    @elseif ($past)<span class="cr-tag">Finalizada</span>
                    @elseif ($s->isSoldOut())<span class="cr-tag cr-tag--warn">Agotada</span>
                    @else<span class="cr-tag cr-tag--ok">Disponible</span>@endif
                </div>
                <div class="cr-date__actions">
                    @if ($s->taken)<a class="cr-btn cr-btn--sm" href="{{ $course->enrolleesUrl($s->id) }}" data-testid="date-enrollees">Inscritos ({{ $s->taken }})</a>@endif
                    <button type="button" class="cr-btn cr-btn--sm" wire:click="edit({{ $s->id }})">Editar</button>
                    <button type="button" class="cr-btn cr-btn--sm" wire:click="duplicate({{ $s->id }})" title="Copia esta fecha una semana después">Duplicar</button>
                    @if ($s->isCancelled())
                        <button type="button" class="cr-btn cr-btn--sm" wire:click="restoreSession({{ $s->id }})">Reactivar</button>
                    @else
                        <button type="button" class="cr-btn cr-btn--sm" wire:click="cancelSession({{ $s->id }})" wire:confirm="¿Cancelar esta fecha? Dejará de venderse{{ $s->taken ? ' y tiene ' . $s->taken . ' inscritos a los que deberás avisar' : '' }}.">Cancelar</button>
                    @endif
                    @if (!$s->taken)
                        <button type="button" class="cr-btn cr-btn--sm cr-btn--danger" wire:click="delete({{ $s->id }})" wire:confirm="¿Eliminar esta fecha?">Eliminar</button>
                    @endif
                </div>
            </article>
        @empty
            <div class="cr-dates__empty">
                <strong>Aún no hay fechas</strong>
                <span>Añade la primera para que el curso pueda venderse.</span>
            </div>
        @endforelse
    </div>

    @unless ($formOpen)
        <button type="button" class="cr-btn cr-btn--primary cr-dates__add" wire:click="add"><x-courses::icon name="plus" :size="15" /> Añadir fecha</button>
    @else
        <form class="cr-dateform" wire:submit="save">
            <h3>{{ $editingId ? 'Editar fecha' : 'Nueva fecha' }}</h3>
            <div class="cr-dateform__grid">
                <div class="form-group @error('form.starts_at') cr-invalid @enderror">
                    <label for="ds-start" class="am-important">Inicio</label>
                    <input id="ds-start" type="datetime-local" class="form-control" wire:model="form.starts_at">
                    @error('form.starts_at')<p class="cr-err">{{ $message }}</p>@enderror
                </div>
                <div class="form-group @error('form.ends_at') cr-invalid @enderror">
                    <label for="ds-end">Fin</label>
                    <input id="ds-end" type="datetime-local" class="form-control" wire:model="form.ends_at">
                    @error('form.ends_at')<p class="cr-err">{{ $message }}</p>@enderror
                </div>
                <div class="form-group cr-span2">
                    <label for="ds-note">Horario (texto libre)</label>
                    <input id="ds-note" type="text" class="form-control" maxlength="160" wire:model="form.schedule_note" placeholder="Ej.: Sábados de 9:00 a 13:00 durante 4 semanas">
                </div>
                <div class="form-group @error('form.venue_name') cr-invalid @enderror">
                    <label for="ds-venue" class="am-important">Sede</label>
                    <input id="ds-venue" type="text" class="form-control" maxlength="160" wire:model="form.venue_name" placeholder="Nombre del lugar">
                    @error('form.venue_name')<p class="cr-err">{{ $message }}</p>@enderror
                </div>
                <div class="form-group @error('form.city') cr-invalid @enderror">
                    <label for="ds-city" class="am-important">Ciudad</label>
                    <input id="ds-city" type="text" class="form-control" maxlength="120" wire:model="form.city">
                    @error('form.city')<p class="cr-err">{{ $message }}</p>@enderror
                </div>
                <div class="form-group cr-span2 @error('form.address') cr-invalid @enderror">
                    <label for="ds-address" class="am-important">Dirección</label>
                    <input id="ds-address" type="text" class="form-control" maxlength="255" wire:model="form.address" placeholder="Calle, número, colonia, CP">
                    @error('form.address')<p class="cr-err">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label for="ds-state">Estado</label>
                    <input id="ds-state" type="text" class="form-control" maxlength="120" wire:model="form.state">
                </div>
                <div class="form-group @error('form.maps_url') cr-invalid @enderror">
                    <label for="ds-maps">Enlace del mapa (opcional)</label>
                    <input id="ds-maps" type="url" class="form-control" wire:model="form.maps_url" placeholder="https://maps.app.goo.gl/…">
                    @error('form.maps_url')<p class="cr-err">{{ $message }}</p>@enderror
                </div>
                <div class="form-group cr-span2">
                    <label for="ds-notes">Cómo llegar / indicaciones (opcional)</label>
                    <textarea id="ds-notes" class="form-control" rows="3" maxlength="2000" wire:model="form.venue_notes" placeholder="Estacionamiento, referencias, qué llevar…"></textarea>
                </div>
                <div class="form-group @error('form.capacity') cr-invalid @enderror">
                    <label for="ds-cap">Cupo (opcional)</label>
                    <input id="ds-cap" type="number" min="1" class="form-control" wire:model="form.capacity" placeholder="Sin límite">
                    @error('form.capacity')<p class="cr-err">{{ $message }}</p>@enderror
                </div>
                <div class="form-group @error('form.sales_close_at') cr-invalid @enderror">
                    <label for="ds-close">Cierre de ventas (opcional)</label>
                    <input id="ds-close" type="datetime-local" class="form-control" wire:model="form.sales_close_at">
                    <small class="cr-hint">Si lo dejas vacío, se vende hasta que empiece.</small>
                    @error('form.sales_close_at')<p class="cr-err">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="cr-dateform__actions">
                <button type="button" class="cr-btn" wire:click="closeForm">Cancelar</button>
                <button type="submit" class="cr-btn cr-btn--primary" wire:loading.attr="disabled" wire:target="save">Guardar fecha</button>
            </div>
        </form>
    @endunless

    <div class="am-themeform_footer">
        <a href="{{ courseEditUrl('media', $courseId) }}" class="am-white-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                <path d="M10.5 4.5L6 9L10.5 13.5" stroke="#585858" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            {{ __('courses::courses.back') }}
        </a>
        <button type="button" wire:click="next" class="am-btn">{{ __('courses::courses.save_continue') }}</button>
    </div>
</div>
