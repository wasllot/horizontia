@php
    $stream = $lesson->liveStream;
    $phase = $stream ? \Modules\Courses\Services\LiveSessionService::phase($stream) : null;
    $phases = ['upcoming' => ['Programada', 'ok'], 'soon' => ['Por comenzar', 'warn'], 'live' => ['En curso', 'ok'], 'ended' => ['Finalizada', 'muted'], 'cancelled' => ['Cancelada', 'muted']];
@endphp
<div class="cr-live">
    @if ($stream)
        <div class="cr-callout cr-callout--{{ $phases[$phase][1] }}">
            <x-courses::icon name="calendar" :size="20" />
            <div><strong>{{ __($phases[$phase][0]) }} · {{ $stream->date_time->translatedFormat('l j \d\e F, H:i') }}</strong>
                <small>{{ __('Se enviará un recordatorio :h h antes a los alumnos inscritos.', ['h' => $stream->notify_hours_before ?: 0]) }}</small></div>
        </div>
    @endif
    <div class="cr-grid2">
        <div class="cr-field">
            <label for="live-date">{{ __('Fecha y hora') }} <span class="cr-req">*</span></label>
            <input id="live-date" type="datetime-local" class="form-control" wire:model="content.live.date_time">
            @error('content.live.date_time')<p class="cr-err">{{ $message }}</p>@enderror
        </div>
        <div class="cr-field">
            <label for="live-dur">{{ __('Duración (minutos)') }} <span class="cr-req">*</span></label>
            <input id="live-dur" type="number" min="5" max="720" class="form-control" wire:model="content.live.duration_minutes">
            @error('content.live.duration_minutes')<p class="cr-err">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="cr-field">
        <label for="live-link">{{ __('Enlace de la reunión') }} <span class="cr-req">*</span></label>
        <div class="cr-input-icon"><x-courses::icon name="link" :size="16" />
            <input id="live-link" type="url" class="form-control" placeholder="https://meet.google.com/…  ·  https://zoom.us/j/…" wire:model.live.blur="content.live.meeting_link" x-on:paste="$nextTick(() => $el.blur())" x-on:keydown.enter.prevent="$el.blur()" autocomplete="off">
        </div>
        @error('content.live.meeting_link')<p class="cr-err">{{ $message }}</p>@enderror
        @php $prov = \Modules\Courses\Services\LiveSessionService::providerFor($content['live']['meeting_link'] ?? null); @endphp
        @if ($prov)<p class="cr-hint">{{ __('Detectado:') }} <strong>{{ \Modules\Courses\Services\LiveSessionService::PROVIDERS[$prov] }}</strong>. {{ __('El botón «Unirse» se habilita 15 minutos antes del inicio.') }}</p>@endif
    </div>
    <div class="cr-grid2">
        <div class="cr-field">
            <label for="live-notify">{{ __('Recordatorio por correo') }}</label>
            <select id="live-notify" class="form-select" wire:model="content.live.notify_hours_before">
                @foreach ([0 => 'Sin recordatorio', 1 => '1 hora antes', 2 => '2 horas antes', 6 => '6 horas antes', 12 => '12 horas antes', 24 => '24 horas antes', 48 => '48 horas antes'] as $h => $l)
                    <option value="{{ $h }}">{{ __($l) }}</option>
                @endforeach
            </select>
        </div>
        <div class="cr-field">
            <label for="live-rec">{{ __('Grabación (opcional)') }}</label>
            <input id="live-rec" type="url" class="form-control" placeholder="https://…" wire:model="content.live.recording_url">
            @error('content.live.recording_url')<p class="cr-err">{{ $message }}</p>@enderror
            <p class="cr-hint">{{ __('Añádela después de la sesión: los alumnos la verán en la lección.') }}</p>
        </div>
    </div>
    <div class="cr-field">
        <label for="live-desc">{{ __('Temario o indicaciones para el alumno') }}</label>
        <textarea id="live-desc" class="form-control" rows="3" wire:model="content.live.description" maxlength="2000" placeholder="{{ __('Qué se verá en la sesión, qué deben preparar…') }}"></textarea>
    </div>
    <p class="cr-hint"><x-courses::icon name="alert" :size="14" /> {{ $enrolled > 0 ? trans_choice('Al guardar se avisará por correo a :count alumno inscrito.|Al guardar se avisará por correo a los :count alumnos inscritos.', $enrolled) : __('Todavía no hay alumnos inscritos: se les avisará cuando se inscriban y cuando se acerque la fecha.') }}</p>
</div>
