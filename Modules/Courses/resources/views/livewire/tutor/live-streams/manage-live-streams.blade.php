<div>
    <div class="hz-pagehead">
        <div>
            <h2>{{ __('Mis sesiones en vivo') }}</h2>
            <p>{{ __('Gestiona tus sesiones programadas, edita detalles o cancela eventos.') }}</p>
        </div>
        <div class="hz-pagehead__actions">
            <a href="{{ route('courses.tutor.schedule-live-stream') }}" class="hz-btn hz-btn--sm"><i class="am-icon-plus-02" aria-hidden="true"></i> {{ __('Nueva sesión') }}</a>
        </div>
    </div>

    @if ($liveStreams->isEmpty())
        <div class="hz-empty">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <h3>{{ __('No tienes sesiones en vivo registradas') }}</h3>
            <p>{{ __('Comienza programando tu primera clase sincrónica con tus estudiantes.') }}</p>
        </div>
    @else
        <div class="am-table-area">
            <table class="am-table">
                <thead>
                    <tr>
                        <th>{{ __('Curso') }}</th>
                        <th>{{ __('Sesión') }}</th>
                        <th>{{ __('Fecha y hora') }}</th>
                        <th>{{ __('Enlace') }}</th>
                        <th>{{ __('Estado') }}</th>
                        <th>{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($liveStreams as $stream)
                        <tr wire:key="stream-{{ $stream->id }}">
                            <td data-label="{{ __('Curso') }}">
                                <div class="hz-cell-flex">
                                    @if ($stream->course->image)
                                        <img class="hz-thumb" src="{{ mediaUrl($stream->course->image) }}" alt="" loading="lazy" width="40" height="40">
                                    @else
                                        <span class="hz-thumb" aria-hidden="true"></span>
                                    @endif
                                    <span>{{ \Illuminate\Support\Str::limit($stream->course->title, 40) }}</span>
                                </div>
                            </td>
                            <td data-label="{{ __('Sesión') }}">
                                <strong>{{ $stream->title }}</strong>
                                <span class="hz-cell-sub">{{ $stream->duration_minutes }} min</span>
                            </td>
                            <td data-label="{{ __('Fecha y hora') }}">
                                <div class="hz-datecell">
                                    <strong>{{ $stream->date_time?->translatedFormat('d M Y') ?? '—' }}</strong>
                                    <small>{{ $stream->date_time?->format('H:i') ?? '' }}</small>
                                </div>
                            </td>
                            <td data-label="{{ __('Enlace') }}">
                                @if ($stream->meeting_link)
                                    <a class="hz-link" href="{{ $stream->meeting_link }}" target="_blank" rel="noopener"><i class="am-icon-link-01" aria-hidden="true"></i> {{ __('Abrir sala') }}</a>
                                @else
                                    <span class="hz-cell-sub">—</span>
                                @endif
                            </td>
                            <td data-label="{{ __('Estado') }}">
                                @if ($stream->status == \Modules\Courses\Models\CourseLiveStream::STATUS_SCHEDULED)
                                    <x-panel.badge tone="success">{{ __('Programado') }}</x-panel.badge>
                                @elseif ($stream->status == \Modules\Courses\Models\CourseLiveStream::STATUS_COMPLETED)
                                    <x-panel.badge tone="info">{{ __('Completado') }}</x-panel.badge>
                                @elseif ($stream->status == \Modules\Courses\Models\CourseLiveStream::STATUS_CANCELLED)
                                    <x-panel.badge tone="danger">{{ __('Cancelado') }}</x-panel.badge>
                                @endif
                            </td>
                            <td data-label="{{ __('Acciones') }}">
                                <div class="hz-acts">
                                    <x-panel.action icon="am-icon-pencil-01" :label="__('Editar')" wire:click="startEdit({{ $stream->id }})" />
                                    <x-panel.action icon="am-icon-trash-01" tone="danger" :label="__('Eliminar')" wire:click="delete({{ $stream->id }})" wire:confirm="{{ __('¿Estás seguro de que deseas eliminar esta sesión?') }}" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($editingId)
        <div class="hz-overlay" wire:key="edit-stream" wire:keydown.escape.window="cancelEdit">
            <div class="hz-dialog" role="dialog" aria-modal="true" aria-labelledby="hz-edit-stream-title">
                <div class="hz-dialog__head">
                    <h3 id="hz-edit-stream-title">{{ __('Editar sesión en vivo') }}</h3>
                    <button type="button" class="hz-iconbtn" wire:click="cancelEdit" aria-label="{{ __('Cerrar') }}"><i class="am-icon-multiply-01" aria-hidden="true"></i></button>
                </div>
                <form wire:submit="saveEdit">
                    <div class="hz-dialog__body">
                        <div class="hz-fgrid">
                            <div class="hz-field is-wide">
                                <label for="edit_course_id">{{ __('Curso vinculado') }} *</label>
                                <select id="edit_course_id" class="form-select" wire:model="edit_course_id">
                                    <option value="">{{ __('Selecciona un curso') }}</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}">{{ mb_convert_case($course->title, MB_CASE_TITLE, 'UTF-8') }}</option>
                                    @endforeach
                                </select>
                                @error('edit_course_id') <span class="hz-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="hz-field is-wide">
                                <label for="edit_title">{{ __('Título de la sesión') }} *</label>
                                <input id="edit_title" class="form-control" wire:model="edit_title" type="text" placeholder="{{ __('Ej: Clase de introducción, tutoría de dudas') }}">
                                @error('edit_title') <span class="hz-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="hz-field is-wide">
                                <label for="edit_meeting_link">{{ __('Enlace de la reunión (Zoom, Meet, Teams)') }}</label>
                                <input id="edit_meeting_link" class="form-control" wire:model="edit_meeting_link" type="url" placeholder="https://zoom.us/j/123456789">
                                @error('edit_meeting_link') <span class="hz-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="hz-field">
                                <label for="edit_date_time">{{ __('Fecha y hora') }} *</label>
                                <input id="edit_date_time" class="form-control" wire:model="edit_date_time" type="datetime-local">
                                @error('edit_date_time') <span class="hz-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="hz-field">
                                <label for="edit_duration_minutes">{{ __('Duración (minutos)') }}</label>
                                <input id="edit_duration_minutes" class="form-control" wire:model="edit_duration_minutes" type="number" min="1">
                                @error('edit_duration_minutes') <span class="hz-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="hz-field">
                                <label for="edit_status">{{ __('Estado') }}</label>
                                <select id="edit_status" class="form-select" wire:model="edit_status">
                                    <option value="1">{{ __('Programado') }}</option>
                                    <option value="2">{{ __('Completado') }}</option>
                                    <option value="3">{{ __('Cancelado') }}</option>
                                </select>
                                @error('edit_status') <span class="hz-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="hz-field">
                                <label for="edit_notify_hours_before">{{ __('Notificar (horas antes)') }}</label>
                                <input id="edit_notify_hours_before" class="form-control" wire:model="edit_notify_hours_before" type="number" min="0">
                                @error('edit_notify_hours_before') <span class="hz-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="hz-dialog__foot">
                        <button type="button" class="hz-btn hz-btn--secondary" wire:click="cancelEdit">{{ __('Cancelar') }}</button>
                        <button type="submit" class="hz-btn" wire:loading.attr="disabled" wire:target="saveEdit">{{ __('Guardar cambios') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
