<div>
    <div class="am-dbbox_content am-profile-setting">
        <div class="am-dbbox_title" style="margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #f0f0f0;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,#ff9a9e,#fecfef); display:flex; align-items:center; justify-content:center;">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                </div>
                <div>
                    <h2 style="font-size:1.4rem; font-weight:800; color:#1a1a2e; margin:0;">Mis Sesiones en Vivo</h2>
                    <p style="font-size:.85rem; color:#888; margin:0;">Gestiona tus sesiones programadas, edita detalles o cancela eventos.</p>
                </div>
            </div>
            
            <div style="margin-top:20px; text-align:right;">
                <a href="{{ route('courses.tutor.schedule-live-stream') }}" class="am-btn am-btn-primary" style="background:#1a1a2e; border:none; padding:10px 20px; border-radius:12px;">+ Nueva Sesión</a>
            </div>
        </div>

        @if($liveStreams->isEmpty())
            <div style="text-align:center; padding:60px 20px; background:#fafafa; border-radius:16px; border:1px dashed #e5e5e5; margin-top:20px;">
                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#ccc" stroke-width="1.5" style="margin:0 auto 15px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <h3 style="font-size:1.1rem; color:#555; margin-bottom:8px;">No tienes sesiones en vivo registradas</h3>
                <p style="color:#999; font-size:.9rem;">Comienza programando tu primera clase sincrónica con tus estudiantes.</p>
            </div>
        @else
            <div style="margin-top:20px; background:#fff; border-radius:16px; border:1px solid #f0f0f0; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,.03);">
                <table style="width:100%; text-align:left; border-collapse:collapse;">
                    <thead style="background:#fafafa; border-bottom:2px solid #f0f0f0;">
                        <tr>
                            <th style="padding:16px 20px; font-size:.8rem; font-weight:700; color:#444;text-transform:uppercase; letter-spacing:.05em;">Curso</th>
                            <th style="padding:16px 20px; font-size:.8rem; font-weight:700; color:#444;text-transform:uppercase; letter-spacing:.05em;">Sesión</th>
                            <th style="padding:16px 20px; font-size:.8rem; font-weight:700; color:#444;text-transform:uppercase; letter-spacing:.05em;">Fecha / Hora</th>
                            <th style="padding:16px 20px; font-size:.8rem; font-weight:700; color:#444;text-transform:uppercase; letter-spacing:.05em;">Enlace</th>
                            <th style="padding:16px 20px; font-size:.8rem; font-weight:700; color:#444;text-transform:uppercase; letter-spacing:.05em;">Estado</th>
                            <th style="padding:16px 20px; font-size:.8rem; font-weight:700; color:#444;text-transform:uppercase; letter-spacing:.05em; text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($liveStreams as $stream)
                            <tr style="border-bottom:1px solid #f6f6f6; transition:background .2s;" onmouseenter="this.style.background='#fdfdfd'" onmouseleave="this.style.background=''">
                                <td style="padding:16px 20px;">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        @if($stream->course->image)
                                            <img src="{{ Storage::url($stream->course->image) }}" alt="curso" style="width:40px; height:40px; border-radius:8px; object-fit:cover;">
                                        @else
                                            <div style="width:40px; height:40px; border-radius:8px; background:#eee;"></div>
                                        @endif
                                        <div>
                                            <span style="display:block; font-weight:600; color:#222; font-size:.9rem;">{{ \Illuminate\Support\Str::limit($stream->course->title, 30) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:16px 20px;">
                                    <strong style="display:block; font-size:.95rem; color:#1a1a2e; margin-bottom:2px;">{{ $stream->title }}</strong>
                                    <span style="font-size:.8rem; color:#888;"><i class="am-icon-time-2"></i> {{ $stream->duration_minutes }} min</span>
                                </td>
                                <td style="padding:16px 20px;">
                                    <div style="background:#f4f6fa; padding:6px 12px; border-radius:8px; display:inline-block;">
                                        <div style="font-weight:700; color:#3b5998; font-size:.85rem;">{{ $stream->date_time?->translatedFormat('d M, Y') ?? 'N/A' }}</div>
                                        <div style="color:#555; font-size:.8rem; margin-top:2px;">{{ $stream->date_time?->format('h:i A') ?? '' }}</div>
                                    </div>
                                </td>
                                <td style="padding:16px 20px;">
                                    @if($stream->meeting_link)
                                        <a href="{{ $stream->meeting_link }}" target="_blank" style="color:#667eea; font-weight:600; font-size:.85rem; text-decoration:none; display:flex; align-items:center; gap:4px;">
                                            <i class="am-icon-link-01"></i> Abrir Sala
                                        </a>
                                    @else
                                        <span style="color:#bbb; font-size:.85rem;">-</span>
                                    @endif
                                </td>
                                <td style="padding:16px 20px;">
                                    @if($stream->status == \Modules\Courses\Models\CourseLiveStream::STATUS_SCHEDULED)
                                        <span style="background:#eafaf1; color:#27ae60; padding:4px 10px; border-radius:12px; font-size:.75rem; font-weight:700;">Programado</span>
                                    @elseif($stream->status == \Modules\Courses\Models\CourseLiveStream::STATUS_COMPLETED)
                                        <span style="background:#e8f4fd; color:#2980b9; padding:4px 10px; border-radius:12px; font-size:.75rem; font-weight:700;">Completado</span>
                                    @elseif($stream->status == \Modules\Courses\Models\CourseLiveStream::STATUS_CANCELLED)
                                        <span style="background:#fdeaea; color:#e74c3c; padding:4px 10px; border-radius:12px; font-size:.75rem; font-weight:700;">Cancelado</span>
                                    @endif
                                </td>
                                <td style="padding:16px 20px; text-align:right;">
                                    <button wire:click="startEdit({{ $stream->id }})" style="border:none; background:transparent; color:#667eea; cursor:pointer; font-size:1.1rem; padding:5px; margin-right:5px; transition:color .2s;" onmouseenter="this.style.color='#764ba2'" onmouseleave="this.style.color='#667eea'" title="Editar"><i class="am-icon-pencil-01"></i></button>
                                    <button wire:click="delete({{ $stream->id }})" wire:confirm="¿Estás seguro de que deseas eliminar esta sesión?" style="border:none; background:transparent; color:#e53e3e; cursor:pointer; font-size:1.1rem; padding:5px; transition:color .2s;" onmouseenter="this.style.color='#c53030'" onmouseleave="this.style.color='#e53e3e'" title="Eliminar"><i class="am-icon-trash-02"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Modal de Edición --}}
        @if($editingId)
        <div style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center; padding:20px;">
            <div style="background:#fff; border-radius:24px; width:100%; max-width:700px; max-height:90vh; overflow-y:auto; box-shadow:0 10px 40px rgba(0,0,0,.2);">
                <div style="padding:24px 30px; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="font-size:1.3rem; font-weight:800; color:#1a1a2e; margin:0;">Editar Sesión En Vivo</h3>
                    <button wire:click="cancelEdit" style="border:none; background:#f5f5f5; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#777;"><i class="am-icon-multiply-01"></i></button>
                </div>
                
                <form wire:submit="saveEdit" style="padding:30px;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        {{-- Course --}}
                        <div style="grid-column:1/-1;">
                            <label style="display:block; font-size:.85rem; font-weight:700; color:#444; margin-bottom:8px;">Curso Vinculado *</label>
                            <select wire:model="edit_course_id" style="width:100%; border:1px solid #ddd; border-radius:12px; padding:12px 16px; font-size:.9rem; color:#333; outline:none; background:#f9f9f9;">
                                <option value="">Selecciona un curso</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ mb_convert_case($course->title, MB_CASE_TITLE, "UTF-8") }}</option>
                                @endforeach
                            </select>
                            @error('edit_course_id') <span style="color:#e53e3e; font-size:.8rem; display:block; margin-top:5px;">{{ $message }}</span> @enderror
                        </div>

                        {{-- Title --}}
                        <div style="grid-column:1/-1;">
                            <label style="display:block; font-size:.85rem; font-weight:700; color:#444; margin-bottom:8px;">Título de la sesión *</label>
                            <input wire:model="edit_title" type="text" style="width:100%; border:1px solid #ddd; border-radius:12px; padding:12px 16px; font-size:.9rem; color:#333; outline:none; background:#fff;" placeholder="Ej: Clase de introducción, Tutoría Q&A">
                            @error('edit_title') <span style="color:#e53e3e; font-size:.8rem; display:block; margin-top:5px;">{{ $message }}</span> @enderror
                        </div>

                        {{-- Link --}}
                        <div style="grid-column:1/-1;">
                            <label style="display:block; font-size:.85rem; font-weight:700; color:#444; margin-bottom:8px;">Enlace de Reunión (Zoom, Meet, Teams)</label>
                            <input wire:model="edit_meeting_link" type="url" style="width:100%; border:1px solid #ddd; border-radius:12px; padding:12px 16px; font-size:.9rem; color:#333; outline:none; background:#fff;" placeholder="https://zoom.us/j/123456789">
                            @error('edit_meeting_link') <span style="color:#e53e3e; font-size:.8rem; display:block; margin-top:5px;">{{ $message }}</span> @enderror
                        </div>

                        {{-- Date/Time --}}
                        <div>
                            <label style="display:block; font-size:.85rem; font-weight:700; color:#444; margin-bottom:8px;">Fecha y Hora *</label>
                            <input wire:model="edit_date_time" type="datetime-local" style="width:100%; border:1px solid #ddd; border-radius:12px; padding:12px 16px; font-size:.9rem; color:#333; outline:none; background:#fff;">
                            @error('edit_date_time') <span style="color:#e53e3e; font-size:.8rem; display:block; margin-top:5px;">{{ $message }}</span> @enderror
                        </div>

                        {{-- Duration --}}
                        <div>
                            <label style="display:block; font-size:.85rem; font-weight:700; color:#444; margin-bottom:8px;">Duración (minutos)</label>
                            <input wire:model="edit_duration_minutes" type="number" min="1" style="width:100%; border:1px solid #ddd; border-radius:12px; padding:12px 16px; font-size:.9rem; color:#333; outline:none; background:#fff;">
                            @error('edit_duration_minutes') <span style="color:#e53e3e; font-size:.8rem; display:block; margin-top:5px;">{{ $message }}</span> @enderror
                        </div>

                        {{-- Status --}}
                        <div>
                            <label style="display:block; font-size:.85rem; font-weight:700; color:#444; margin-bottom:8px;">Estado</label>
                            <select wire:model="edit_status" style="width:100%; border:1px solid #ddd; border-radius:12px; padding:12px 16px; font-size:.9rem; color:#333; outline:none; background:#fff;">
                                <option value="1">Programado</option>
                                <option value="2">Completado</option>
                                <option value="3">Cancelado</option>
                            </select>
                            @error('edit_status') <span style="color:#e53e3e; font-size:.8rem; display:block; margin-top:5px;">{{ $message }}</span> @enderror
                        </div>
                        
                        {{-- Notify --}}
                        <div>
                            <label style="display:block; font-size:.85rem; font-weight:700; color:#444; margin-bottom:8px;">Notificar (horas antes)</label>
                            <input wire:model="edit_notify_hours_before" type="number" min="0" style="width:100%; border:1px solid #ddd; border-radius:12px; padding:12px 16px; font-size:.9rem; color:#333; outline:none; background:#fff;">
                            @error('edit_notify_hours_before') <span style="color:#e53e3e; font-size:.8rem; display:block; margin-top:5px;">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div style="margin-top:30px; display:flex; justify-content:flex-end; gap:12px; border-top:1px solid #f0f0f0; padding-top:20px;">
                        <button type="button" wire:click="cancelEdit" style="background:#fff; border:1px solid #ddd; color:#555; padding:12px 24px; border-radius:10px; font-weight:600; cursor:pointer;">Cancelar</button>
                        <button type="submit" style="background:linear-gradient(135deg,#667eea,#764ba2); border:none; color:#fff; padding:12px 30px; border-radius:10px; font-weight:700; cursor:pointer; box-shadow:0 6px 15px rgba(118,75,162,.3);">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
        
    </div>
</div>
