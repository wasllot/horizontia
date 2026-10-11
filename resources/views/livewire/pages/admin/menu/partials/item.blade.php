@php($it = $node['item'])
<li class="hz-mb__item" data-id="{{ $it->id }}" wire:key="item-{{ $it->id }}">
    <div class="hz-mb__row">
        <span class="hz-mb__handle" title="Arrastrar" aria-label="Arrastrar para ordenar">⋮⋮</span>
        @if ($editingId === $it->id)
            <form class="hz-mb__edit" wire:submit="saveItem">
                <input type="text" class="form-control" wire:model="editLabel" maxlength="60" aria-label="Texto" autofocus>
                @if ($it->type !== 'route')
                    <input type="text" class="form-control" wire:model="editUrl" maxlength="500" aria-label="Enlace">
                @endif
                <button type="submit" class="am-btn">Guardar</button>
                <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm" wire:click="cancelEdit">Cancelar</button>
                @error('editLabel') <span class="hz-cp__err">{{ $message }}</span> @enderror
                @error('editUrl') <span class="hz-cp__err">{{ $message }}</span> @enderror
            </form>
        @else
            <div class="hz-mb__label">
                <strong>{{ $it->label }}</strong>
                <small>{{ $node['url'] === '#' ? 'sin enlace (solo agrupa)' : (\Illuminate\Support\Str::after($node['url'], url('/')) ?: '/') }}@if (str_contains((string) $it->class, '_blank')) · pestaña nueva @endif</small>
            </div>
            <div class="hz-mb__actions">
                <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm" wire:click="toggleBlank({{ $it->id }})" title="Abrir en pestaña nueva" aria-pressed="{{ str_contains((string) $it->class, '_blank') ? 'true' : 'false' }}">↗</button>
                <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm" wire:click="edit({{ $it->id }})">Editar</button>
                <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm hz-btn--danger-outline" wire:click="removeItem({{ $it->id }})" wire:confirm="¿Quitar «{{ $it->label }}» del menú?" aria-label="Quitar">✕</button>
            </div>
        @endif
    </div>
    @if ($depth === 0)
        <ul class="hz-mb__list hz-mb__sub" data-sortable="sub">
            @foreach ($node['children'] as $child)
                @include('livewire.pages.admin.menu.partials.item', ['node' => $child + ['children' => collect()], 'depth' => 1])
            @endforeach
        </ul>
    @endif
</li>
