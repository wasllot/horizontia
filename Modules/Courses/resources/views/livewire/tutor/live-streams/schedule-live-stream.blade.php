<div class="cr-all-courses">

    {{-- Header --}}
    <div class="cr-page-heading">
        <div class="cr-headingbox">
            <h2>Programar Sesión "En Vivo"</h2>
            <p>Crea y programa una transmisión en vivo para tus estudiantes inscritos.</p>
        </div>
    </div>

    {{-- Card principal --}}
    <div style="max-width:780px; background:#fff; border-radius:16px; box-shadow:0 2px 16px rgba(0,0,0,.07); overflow:hidden; margin-top:8px;">

        {{-- Banner superior --}}
        <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); padding: 28px 32px; display:flex; align-items:center; gap:18px;">
            <div style="background:rgba(255,255,255,.12); border-radius:12px; width:54px; height:54px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <h3 style="color:#fff; font-size:1.1rem; font-weight:700; margin:0 0 4px;">Nueva Sesión En Vivo</h3>
                <p style="color:rgba(255,255,255,.65); font-size:.85rem; margin:0;">Los estudiantes inscritos recibirán un correo de notificación automáticamente.</p>
            </div>
        </div>

        {{-- Formulario --}}
        <div style="padding:32px;">

            @if(session()->has('success'))
                <div style="background:#edfaf4; border:1px solid #34c77b; color:#166534; padding:12px 16px; border-radius:10px; margin-bottom:24px; display:flex; align-items:center; gap:10px; font-size:.9rem;">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#34c77b" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span><strong>¡Listo!</strong> {{ session('success') }}</span>
                </div>
            @endif

            <form wire:submit.prevent="save" class="am-themeform">

                {{-- Seleccionar Curso --}}
                <div class="form-group @error('course_id') am-invalid @enderror">
                    <x-input-label for="live_course_id" class="am-important" value="Seleccionar Curso" />
                    <div class="am-select">
                        <select id="live_course_id" wire:model="course_id">
                            <option value="">— Seleccione un curso —</option>
                            @forelse($courses as $course)
                                <option value="{{ $course->id }}">{{ html_entity_decode($course->title) }}</option>
                            @empty
                                <option value="" disabled>No tienes cursos creados aún.</option>
                            @endforelse
                        </select>
                    </div>
                    <x-input-error field_name="course_id" />
                </div>

                {{-- Título de la sesión --}}
                <div class="form-group @error('title') am-invalid @enderror">
                    <x-input-label for="live_title" class="am-important" value="Título de la Sesión" />
                    <x-text-input id="live_title" wire:model="title" type="text" placeholder="Ej. Clase en vivo: Introducción al módulo..." />
                    <x-input-error field_name="title" />
                </div>

                {{-- Fecha + Duración (2 columnas) --}}
                <div class="form-group-two-wrap">
                    <div class="form-group @error('date_time') am-invalid @enderror">
                        <x-input-label for="live_date_time" class="am-important" value="Fecha y Hora" />
                        <x-text-input id="live_date_time" wire:model="date_time" type="datetime-local" />
                        <x-input-error field_name="date_time" />
                    </div>
                    <div class="form-group @error('duration_minutes') am-invalid @enderror">
                        <x-input-label for="live_duration" value="Duración (minutos)" />
                        <x-text-input id="live_duration" wire:model="duration_minutes" type="number" min="1" placeholder="60" />
                        <x-input-error field_name="duration_minutes" />
                    </div>
                </div>

                {{-- Enlace de reunión --}}
                <div class="form-group @error('meeting_link') am-invalid @enderror">
                    <x-input-label for="live_meeting_link" value="Enlace de la Reunión (Zoom, Meet, Teams...)" />
                    <div style="position:relative;">
                        <div style="position:absolute; left:14px; top:50%; transform:translateY(-50%); pointer-events:none;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#aaa" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        </div>
                        <x-text-input id="live_meeting_link" wire:model="meeting_link" type="url" placeholder="https://meet.google.com/..." style="padding-left:40px;" />
                    </div>
                    <x-input-error field_name="meeting_link" />
                </div>

                {{-- Divider --}}
                <div style="border-top:1px dashed #e8e8e8; margin:8px 0 24px;"></div>

                {{-- Recordatorio --}}
                <div class="form-group @error('notify_hours_before') am-invalid @enderror">
                    <x-input-label for="live_notify" value="⏰ Enviar recordatorio por correo" />
                    <div class="am-select">
                        <select id="live_notify" wire:model="notify_hours_before">
                            <option value="1">1 hora antes</option>
                            <option value="2">2 horas antes</option>
                            <option value="6">6 horas antes</option>
                            <option value="12">12 horas antes</option>
                            <option value="24">24 horas antes (recomendado)</option>
                            <option value="48">48 horas antes</option>
                        </select>
                    </div>
                    <x-input-error field_name="notify_hours_before" />
                </div>

                {{-- Descripción --}}
                <div class="form-group @error('description') am-invalid @enderror">
                    <x-input-label for="live_description" value="Descripción (opcional)" />
                    <textarea id="live_description" wire:model="description" rows="4"
                        class="form-control"
                        placeholder="Describe los temas que se abordarán en esta sesión en vivo..."></textarea>
                    <x-input-error field_name="description" />
                </div>

                {{-- Info box --}}
                <div style="background:#fffbeb; border:1px solid #f6d860; border-radius:10px; padding:12px 16px; margin-bottom:24px; display:flex; gap:10px; align-items:flex-start; font-size:.85rem; color:#92400e;">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#d97706" stroke-width="2" style="flex-shrink:0; margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Al guardar, todos los estudiantes inscritos recibirán un correo de notificación con el enlace de la sesión.</span>
                </div>

                {{-- Botones --}}
                <div class="form-group am-form-btns">
                    <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="am-btn">
                        <span wire:loading.remove wire:target="save" style="display:flex; align-items:center; gap:8px;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Programar Sesión En Vivo
                        </span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/courses/css/main.css') }}">
@endpush
