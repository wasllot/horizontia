<main class="tb-main am-courses-system hz-bn">
    <header class="hz-seo__head">
        <div>
            <h2>Comunidad <span class="hz-count">{{ $posts->count() }}</span></h2>
            <p>Mensajes reportados u ocultos de la comunidad de los cursos ({{ number_format($total) }} mensajes en total). Los instructores moderan su propio curso desde el aula; aquí ves lo que necesita revisión.</p>
        </div>
    </header>
    <div class="am-disputelist_wrap">
        <div class="am-disputelist">
            @if ($posts->isNotEmpty())
                <table class="tb-table" data-testid="moderation-table">
                    <thead><tr><th class="hz-col-main">Mensaje</th><th>Curso</th><th>Autor</th><th>Reportes</th><th>Estado</th><th><span class="hz-sr">Acciones</span></th></tr></thead>
                    <tbody>
                        @foreach ($posts as $p)
                            <tr wire:key="p-{{ $p->id }}">
                                <td data-label="Mensaje">@if ($p->title)<strong>{{ $p->title }}</strong><br>@endif<small>{{ \Illuminate\Support\Str::limit($p->body, 160) }}</small></td>
                                <td data-label="Curso"><a href="{{ route('courses.course-taking', ['slug' => $slugs[$p->course_id] ?? '-']) }}" target="_blank" rel="noopener">{{ html_entity_decode($courses[$p->course_id] ?? 'Curso eliminado') }}</a></td>
                                <td data-label="Autor">{{ \App\Services\CommunityService::displayName($p->author) }}</td>
                                <td data-label="Reportes">{{ $p->reports_count }}</td>
                                <td data-label="Estado">@if ($p->isHidden())<x-panel.badge tone="neutral">Oculto</x-panel.badge>@else<x-panel.badge tone="warning" :dot="false">Reportado</x-panel.badge>@endif</td>
                                <td data-label="Acciones">
                                    <div class="hz-acts">
                                        @if ($p->isHidden())
                                            <x-panel.action icon="icon-rotate-ccw" label="Mostrar de nuevo" wire:click="restore({{ $p->id }})" />
                                        @else
                                            <x-panel.action icon="icon-check" label="Descartar reporte" wire:click="dismiss({{ $p->id }})" />
                                            <x-panel.action icon="icon-eye-off" label="Ocultar" wire:click="hide({{ $p->id }})" />
                                        @endif
                                        <x-panel.action icon="icon-trash-2" label="Borrar" tone="danger" wire:click="delete({{ $p->id }})" wire:confirm="¿Borrar este mensaje (y sus respuestas si es una pregunta)?" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="hz-empty"><h3>Nada que revisar</h3><p>Cuando un alumno reporte un mensaje aparecerá aquí.</p></div>
            @endif
        </div>
    </div>
</main>
