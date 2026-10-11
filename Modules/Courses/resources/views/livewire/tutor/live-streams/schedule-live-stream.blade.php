<div class="cr-all-courses">
    <div class="hz-pagehead">
        <div>
            <h2>{{ __('Programar sesión en vivo') }}</h2>
            <p>{{ __('Crea y programa una transmisión en vivo para tus estudiantes inscritos.') }}</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="hz-callout hz-callout--success" role="status" style="margin-bottom: 18px">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <p><strong>{{ __('¡Listo!') }}</strong> {{ session('success') }}</p>
        </div>
    @endif

    <form wire:submit.prevent="save" class="am-themeform hz-panel">
        <div class="hz-fgrid">
            <div class="hz-field is-wide @error('course_id') am-invalid @enderror">
                <x-input-label for="live_course_id" class="am-important" :value="__('Curso')" />
                <select id="live_course_id" class="form-select" wire:model="course_id">
                    <option value="">{{ __('Selecciona un curso') }}</option>
                    @forelse ($courses as $course)
                        <option value="{{ $course->id }}">{{ html_entity_decode($course->title) }}</option>
                    @empty
                        <option value="" disabled>{{ __('No tienes cursos creados aún.') }}</option>
                    @endforelse
                </select>
                <x-input-error field_name="course_id" />
            </div>

            <div class="hz-field is-wide @error('title') am-invalid @enderror">
                <x-input-label for="live_title" class="am-important" :value="__('Título de la sesión')" />
                <x-text-input id="live_title" wire:model="title" type="text" :placeholder="__('Ej. Clase en vivo: introducción al módulo')" />
                <x-input-error field_name="title" />
            </div>

            <div class="hz-field @error('date_time') am-invalid @enderror">
                <x-input-label for="live_date_time" class="am-important" :value="__('Fecha y hora')" />
                <x-text-input id="live_date_time" wire:model="date_time" type="datetime-local" />
                <x-input-error field_name="date_time" />
            </div>

            <div class="hz-field @error('duration_minutes') am-invalid @enderror">
                <x-input-label for="live_duration" :value="__('Duración (minutos)')" />
                <x-text-input id="live_duration" wire:model="duration_minutes" type="number" min="1" placeholder="60" />
                <x-input-error field_name="duration_minutes" />
            </div>

            <div class="hz-field hz-field--icon is-wide @error('meeting_link') am-invalid @enderror">
                <x-input-label for="live_meeting_link" :value="__('Enlace de la reunión (Zoom, Meet, Teams…)')" />
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                <x-text-input id="live_meeting_link" wire:model="meeting_link" type="url" placeholder="https://meet.google.com/…" />
                <x-input-error field_name="meeting_link" />
            </div>

            <div class="hz-field @error('notify_hours_before') am-invalid @enderror">
                <x-input-label for="live_notify" :value="__('Enviar recordatorio por correo')" />
                <select id="live_notify" class="form-select" wire:model="notify_hours_before">
                    <option value="1">{{ __('1 hora antes') }}</option>
                    <option value="2">{{ __('2 horas antes') }}</option>
                    <option value="6">{{ __('6 horas antes') }}</option>
                    <option value="12">{{ __('12 horas antes') }}</option>
                    <option value="24">{{ __('24 horas antes (recomendado)') }}</option>
                    <option value="48">{{ __('48 horas antes') }}</option>
                </select>
                <x-input-error field_name="notify_hours_before" />
            </div>

            <div class="hz-field is-wide @error('description') am-invalid @enderror">
                <x-input-label for="live_description" :value="__('Descripción (opcional)')" />
                <textarea id="live_description" wire:model="description" rows="4" class="form-control" placeholder="{{ __('Describe los temas que se abordarán en esta sesión en vivo…') }}"></textarea>
                <x-input-error field_name="description" />
            </div>

            <div class="hz-callout hz-callout--warning is-wide">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p>{{ __('Al guardar, todos los estudiantes inscritos recibirán un correo de notificación con el enlace de la sesión.') }}</p>
            </div>
        </div>

        <div class="am-form-btns" style="margin-top: 20px">
            <button type="submit" class="hz-btn" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ __('Programar sesión en vivo') }}</span>
                <span wire:loading wire:target="save">{{ __('Guardando…') }}</span>
            </button>
        </div>
    </form>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/courses/css/main.css') }}">
@endpush
