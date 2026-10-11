<main class="tb-main am-courses-system hz-bn">
    <header class="hz-seo__head">
        <div>
            <h2>Rutas de aprendizaje <span class="hz-count">{{ $paths->count() }}</span></h2>
            <p>Cursos en un orden recomendado (público en <a href="{{ route('paths.index') }}" target="_blank" rel="noopener">/rutas</a>). Cada persona ve su avance y, al terminar todos los cursos, recibe un certificado de la ruta. Los cursos se compran por separado o en un paquete.</p>
        </div>
        <button type="button" class="hz-btn hz-btn--sm" wire:click="create" data-testid="path-new"><i class="icon-plus" aria-hidden="true"></i> Nueva ruta</button>
    </header>

    @if ($formOpen)
        <form class="hz-en__form hz-bn__form" wire:key="path-form" wire:submit="save" data-testid="path-form">
            <strong>{{ $editingId ? 'Editar ruta' : 'Nueva ruta' }}</strong>
            <label for="lp-title">Nombre</label>
            <input id="lp-title" class="form-control" wire:model="title" maxlength="160" placeholder="Ej. De cero a vendedor profesional">
            <small class="hz-en__err" aria-live="polite">{{ $errors->first('title') }}</small>
            <label for="lp-sub">Descripción corta (opcional)</label>
            <input id="lp-sub" class="form-control" wire:model="subtitle" maxlength="255">
            <label for="lp-desc">Descripción (opcional)</label>
            <textarea id="lp-desc" class="form-control" rows="3" wire:model="description" maxlength="5000"></textarea>

            @if ($selected->isNotEmpty())
                <label>Orden de la ruta <small>({{ $selected->count() }} cursos)</small></label>
                <ol class="hz-lp__order" data-testid="path-order">
                    @foreach ($selected as $i => $c)
                        <li wire:key="ord-{{ $c->id }}">
                            <span>{{ html_entity_decode($c->title) }}</span>
                            <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="move({{ $c->id }}, 'up')" @disabled($loop->first) aria-label="Subir">↑</button>
                            <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="move({{ $c->id }}, 'down')" @disabled($loop->last) aria-label="Bajar">↓</button>
                        </li>
                    @endforeach
                </ol>
            @endif

            <label>Cursos disponibles</label>
            <input type="search" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Buscar curso…" aria-label="Buscar curso">
            <div class="hz-bn__picker">
                @foreach ($eligible as $c)
                    <label class="hz-bn__opt {{ $selected->contains('id', $c->id) ? 'is-on' : '' }}" wire:key="opt-{{ $c->id }}">
                        <input type="checkbox" value="{{ $c->id }}" wire:model.live="courseIds">
                        <span>{{ html_entity_decode($c->title) }}</span>
                    </label>
                @endforeach
                @if ($eligible->isEmpty())<p class="hz-cp__empty" wire:key="no-eligible">No hay cursos publicados y en línea.</p>@endif
            </div>
            <small class="hz-en__err" aria-live="polite">{{ $errors->first('courseIds') }}</small>

            <label for="lp-status">Estado</label>
            <select id="lp-status" class="form-control" wire:model="status">
                <option value="draft">Borrador (no se muestra)</option>
                <option value="active">Publicada</option>
            </select>
            <div class="hz-en__formacts">
                <button type="submit" class="hz-btn hz-btn--sm" wire:loading.attr="disabled">Guardar ruta</button>
                <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="closeForm">Cancelar</button>
            </div>
        </form>
    @endif

    <div class="am-disputelist_wrap" wire:key="path-list">
        <div class="am-disputelist">
            @if ($paths->isNotEmpty())
                <table class="tb-table" data-testid="paths-table">
                    <thead><tr><th class="hz-col-main">Ruta</th><th>Cursos</th><th>Estado</th><th><span class="hz-sr">Acciones</span></th></tr></thead>
                    <tbody>
                        @foreach ($paths as $p)
                            <tr wire:key="path-{{ $p->id }}">
                                <td data-label="Ruta"><strong>{{ $p->title }}</strong><br><small>/ruta/{{ $p->slug }}</small></td>
                                <td data-label="Cursos">{{ $p->courses->count() }}</td>
                                <td data-label="Estado">
                                    @if ($p->isActive() && $service->isPublic($p))<x-panel.badge tone="success">Publicada</x-panel.badge>
                                    @elseif ($p->isActive())<x-panel.badge tone="warning" :dot="false">Publicada, sin cursos suficientes</x-panel.badge>
                                    @else<x-panel.badge tone="neutral">Borrador</x-panel.badge>@endif
                                </td>
                                <td data-label="Acciones">
                                    <div class="hz-acts">
                                        <a class="hz-act" href="{{ route('paths.show', ['slug' => $p->slug]) }}" target="_blank" rel="noopener" title="Ver" aria-label="Ver ruta"><i class="icon-eye" aria-hidden="true"></i></a>
                                        <x-panel.action icon="icon-edit" label="Editar" wire:click="edit({{ $p->id }})" />
                                        <x-panel.action icon="icon-power" :label="$p->isActive() ? 'Pasar a borrador' : 'Publicar'" wire:click="toggleStatus({{ $p->id }})" />
                                        <x-panel.action icon="icon-trash-2" label="Eliminar" tone="danger" wire:click="delete({{ $p->id }})" wire:confirm="¿Eliminar esta ruta? Los cursos no se tocan." />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="hz-empty"><h3>Aún no hay rutas</h3><p>Crea la primera con 2 o más cursos en el orden en que conviene estudiarlos.</p></div>
            @endif
        </div>
    </div>
</main>
