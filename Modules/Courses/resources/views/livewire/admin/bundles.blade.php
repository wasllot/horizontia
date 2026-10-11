<main class="tb-main am-courses-system hz-bn">
    <header class="hz-seo__head">
        <div>
            <h2>Paquetes <span class="hz-count">{{ $bundles->count() }}</span></h2>
            <p>Varios cursos juntos a un precio menor. Se compran como sus cursos individuales con el descuento repartido, así que inscripciones, recibos y reembolsos siguen funcionando por curso. Solo cursos publicados y en línea.</p>
        </div>
        <button type="button" class="hz-btn hz-btn--sm" wire:click="create" data-testid="bundle-new"><i class="icon-plus" aria-hidden="true"></i> Nuevo paquete</button>
    </header>

    @if ($formOpen)
        <form class="hz-en__form hz-bn__form" wire:key="bundle-form" wire:submit="save" data-testid="bundle-form">
            <strong>{{ $editingId ? 'Editar paquete' : 'Nuevo paquete' }}</strong>
            <label for="bn-title">Nombre</label>
            <input id="bn-title" class="form-control" wire:model="title" maxlength="160" placeholder="Ej. Ruta de ventas completa">
            <small class="hz-en__err" aria-live="polite">{{ $errors->first('title') }}</small>
            <label for="bn-sub">Descripción corta (opcional)</label>
            <input id="bn-sub" class="form-control" wire:model="subtitle" maxlength="255">
            <label for="bn-desc">Descripción (opcional)</label>
            <textarea id="bn-desc" class="form-control" rows="3" wire:model="description" maxlength="5000"></textarea>

            <label>Cursos incluidos <small>({{ $selected->count() }} elegidos · por separado suman {!! formatAmount($listSelected, true) !!})</small></label>
            <input type="search" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Buscar curso…" aria-label="Buscar curso">
            <div class="hz-bn__picker">
                @foreach ($eligible as $c)
                    <label class="hz-bn__opt {{ $selected->contains($c->id) ? 'is-on' : '' }}" wire:key="opt-{{ $c->id }}">
                        <input type="checkbox" value="{{ $c->id }}" wire:model.live="courseIds">
                        <span>{{ html_entity_decode($c->title) }}</span>
                        <em>{!! formatAmount($c->pricing->final_price, true) !!}</em>
                    </label>
                @endforeach
                @if ($eligible->isEmpty())<p class="hz-cp__empty" wire:key="no-eligible">No hay cursos publicados y en línea con precio.</p>@endif
            </div>
            <small class="hz-en__err" aria-live="polite">{{ $errors->first('courseIds') }}</small>

            <div class="hz-bn__row">
                <div>
                    <label for="bn-price">Precio del paquete (MXN)</label>
                    <input id="bn-price" type="number" step="0.01" min="1" class="form-control" wire:model.live.debounce.300ms="price" placeholder="0.00">
                    <small class="hz-en__err" aria-live="polite">{{ $errors->first('price') }}</small>
                    <small class="hz-bn__save">@if ($price !== '' && $listSelected > 0 && (float) $price < $listSelected)Ahorro para el alumno: {{ (int) round(($listSelected - (float) $price) / $listSelected * 100) }}%@endif</small>
                </div>
                <div>
                    <label for="bn-status">Estado</label>
                    <select id="bn-status" class="form-control" wire:model="status">
                        <option value="draft">Borrador (no se vende)</option>
                        <option value="active">Publicado</option>
                    </select>
                </div>
            </div>
            <div class="hz-en__formacts">
                <button type="submit" class="hz-btn hz-btn--sm" wire:loading.attr="disabled">Guardar paquete</button>
                <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="closeForm">Cancelar</button>
            </div>
        </form>
    @endif

    <div class="am-disputelist_wrap" wire:key="bundle-list">
        <div class="am-disputelist">
            @if ($bundles->isNotEmpty())
                <table class="tb-table" data-testid="bundles-table">
                    <thead><tr><th class="hz-col-main">Paquete</th><th>Cursos</th><th>Precio</th><th>Ahorro</th><th>Estado</th><th><span class="hz-sr">Acciones</span></th></tr></thead>
                    <tbody>
                        @foreach ($bundles as $b)
                            @php $sellable = $service->isSellable($b); @endphp
                            <tr wire:key="bundle-{{ $b->id }}">
                                <td data-label="Paquete"><strong>{{ $b->title }}</strong><br><small>/paquete/{{ $b->slug }}</small></td>
                                <td data-label="Cursos">{{ $b->courses->count() }}</td>
                                <td data-label="Precio">{!! formatAmount($b->price, true) !!}<br><small><s>{!! formatAmount($service->listPrice($b), true) !!}</s></small></td>
                                <td data-label="Ahorro">{{ $service->savingsPercent($b) }}%</td>
                                <td data-label="Estado">
                                    @if ($b->isActive() && $sellable)<x-panel.badge tone="success">Publicado</x-panel.badge>
                                    @elseif ($b->isActive())<x-panel.badge tone="warning" :dot="false">Publicado, no vendible</x-panel.badge>
                                    @else<x-panel.badge tone="neutral">Borrador</x-panel.badge>@endif
                                </td>
                                <td data-label="Acciones">
                                    <div class="hz-acts">
                                        <a class="hz-act" href="{{ route('courses.bundles.show', ['slug' => $b->slug]) }}" target="_blank" rel="noopener" title="Ver" aria-label="Ver paquete"><i class="icon-eye" aria-hidden="true"></i></a>
                                        <x-panel.action icon="icon-edit" label="Editar" wire:click="edit({{ $b->id }})" />
                                        <x-panel.action icon="icon-power" :label="$b->isActive() ? 'Pasar a borrador' : 'Publicar'" wire:click="toggleStatus({{ $b->id }})" />
                                        <x-panel.action icon="icon-trash-2" label="Eliminar" tone="danger" wire:click="delete({{ $b->id }})" wire:confirm="¿Eliminar este paquete? Los cursos no se tocan; se quitará de los carritos." />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="hz-empty"><h3>Aún no hay paquetes</h3><p>Crea el primero con 2 o más cursos y un precio menor que la suma.</p></div>
            @endif
        </div>
    </div>
</main>
