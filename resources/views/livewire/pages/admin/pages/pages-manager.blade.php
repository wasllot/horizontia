<div class="hz-pages">
    <header class="hz-seo__head">
        <div>
            <h2>Páginas</h2>
            <p>Crea y edita las páginas informativas, legales y de venta con el editor visual: arrastra bloques, ajusta estilos y publica.</p>
        </div>
        <a class="hz-btn hz-btn--secondary" href="{{ route('admin.page-banners') }}">Banners de páginas</a>
        <button type="button" class="hz-btn hz-btn--primary" wire:click="create" wire:loading.attr="disabled" wire:target="create">+ Nueva página</button>
    </header>

    <div class="hz-pages__bar">
        <input type="search" class="form-control" wire:model.live.debounce.250ms="search" placeholder="Buscar por nombre o URL…" aria-label="Buscar páginas">
        <nav class="hz-tabs hz-tabs--sm" aria-label="Filtro">
            @foreach (['all' => 'Todas', 'published' => 'Publicadas', 'draft' => 'Borradores'] as $key => $label)
                <button type="button" class="hz-tabs__tab {{ $filter === $key ? 'is-active' : '' }}" wire:click="$set('filter', '{{ $key }}')">{{ $label }}</button>
            @endforeach
        </nav>
    </div>

    <div class="table-responsive">
        <table>
            <thead><tr><th>Página</th><th>URL</th><th>Estado</th><th>Editada</th><th style="width:1%"></th></tr></thead>
            <tbody>
            @forelse ($pages as $p)
                @php($isSystem = in_array($p->slug, $system, true))
                <tr wire:key="page-{{ $p->id }}">
                    <td>
                        <strong>{{ $p->name }}</strong>
                        @if ($isSystem)<span class="hz-badge hz-badge--neutral" title="Usada por rutas y menús del sitio">Sistema</span>@endif
                        @unless ($p->gjs_html !== null)<span class="hz-badge hz-badge--warning" title="Se abrirá en el editor visual al editarla por primera vez">Constructor antiguo</span>@endunless
                    </td>
                    <td><a href="{{ url($p->slug) }}" target="_blank" rel="noopener">/{{ $p->slug }}</a></td>
                    <td><span class="hz-badge {{ $p->status === 'published' ? 'hz-badge--success' : 'hz-badge--neutral' }}">{{ $p->status === 'published' ? 'Publicada' : 'Borrador' }}</span></td>
                    <td>{{ ($p->edited_at ?? $p->updated_at)?->locale('es')->diffForHumans() }}</td>
                    <td>
                        <div class="hz-pages__actions">
                            <a class="hz-btn hz-btn--primary hz-btn--sm" href="{{ route('admin.pages.editor', $p->id) }}" data-no-spa>Editar</a>
                            <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm" wire:click="togglePublish({{ $p->id }})">{{ $p->status === 'published' ? 'Despublicar' : 'Publicar' }}</button>
                            <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm" wire:click="duplicate({{ $p->id }})">Duplicar</button>
                            @unless ($isSystem)
                                <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm hz-btn--danger-outline" wire:click="delete({{ $p->id }})" wire:confirm="¿Eliminar «{{ $p->name }}»? No se puede deshacer.">Eliminar</button>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="hz-pages__empty">No hay páginas todavía. Crea la primera con «Nueva página».</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
