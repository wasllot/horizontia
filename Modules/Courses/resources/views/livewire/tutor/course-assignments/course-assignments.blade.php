<div>
    <div class="am-profile-setting">
        <div class="am-profile-setting_head">
            <h3>{{ __('courses::courses.assignments') ?? 'Tareas / Assignments' }}</h3>
        </div>
        <div class="am-profile-setting_content">
            <div class="am-filter-wrap mb-4 d-flex justify-content-between">
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Buscar por alumno o tarea..." style="max-width: 300px;">
                <select wire:model.live="filterStatus" class="form-control" style="max-width: 200px;">
                    <option value="">Todos</option>
                    <option value="submitted">Pendientes de calificar</option>
                    <option value="graded">Calificados</option>
                </select>
            </div>

            <div class="table-responsive">
                <table class="table am-table">
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Curso / Tarea</th>
                            <th>Archivo</th>
                            <th>Estado</th>
                            <th>Puntuación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submissions as $submission)
                            <tr>
                                <td>{{ $submission->user->profile->full_name ?? 'Alumno' }}</td>
                                <td>
                                    <strong>{{ $submission->curriculum->title ?? '' }}</strong><br>
                                    <small>{{ $submission->curriculum->section->course->title ?? '' }}</small>
                                </td>
                                <td>
                                    @if($submission->file_path)
                                        <a href="{{ Storage::url($submission->file_path) }}" target="_blank" class="am-btn am-btn-sm"><i class="am-icon-download"></i> Descargar</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($submission->status == 'submitted')
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @else
                                        <span class="badge bg-success">Calificado</span>
                                    @endif
                                </td>
                                <td>{{ $submission->score !== null ? $submission->score . '/100' : '-' }}</td>
                                <td>
                                    <button wire:click="openGradingModal({{ $submission->id }})" class="am-btn am-btn-sm" style="min-width: 100px;">
                                        {{ $submission->status == 'submitted' ? 'Calificar' : 'Editar Calificación' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No hay tareas enviadas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $submissions->links() }}
            </div>
        </div>
    </div>

    <!-- Modal para calificar -->
    <div wire:ignore.self class="modal fade" id="gradingModal" tabindex="-1" aria-labelledby="gradingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gradingModalLabel">Calificar Tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="closeGradingModal()"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group mb-3">
                            <label class="form-label">Calificación (0 - 100)</label>
                            <input type="number" wire:model="gradeScore" class="form-control" min="0" max="100">
                            @error('gradeScore') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Feedback / Comentario</label>
                            <textarea wire:model="gradeFeedback" class="form-control" rows="4"></textarea>
                            @error('gradeFeedback') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="closeGradingModal()">Cancelar</button>
                    <button type="button" class="am-btn" wire:click.prevent="saveGrade()">Guardar Calificación</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.addEventListener('open-grading-modal', event => {
        var myModal = new bootstrap.Modal(document.getElementById('gradingModal'));
        myModal.show();
    });
    window.addEventListener('close-grading-modal', event => {
        var myModalEl = document.getElementById('gradingModal');
        var modal = bootstrap.Modal.getInstance(myModalEl);
        if (modal) {
            modal.hide();
        }
    });
</script>
@endpush
