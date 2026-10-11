<div class="hz-mb">
    <header class="hz-seo__head">
        <div>
            <h2>Menús del sitio</h2>
            <p>Arrastra para ordenar; suelta un enlace sobre otro para crear un submenú. Los cambios se guardan solos y se ven de inmediato en el sitio.</p>
        </div>
        <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm" wire:click="startCreating">+ Nuevo menú</button>
    </header>

    <nav class="hz-tabs" role="tablist" aria-label="Menús">
        @foreach ($menus as $m)
            <button type="button" role="tab" class="hz-tabs__tab {{ $menuId === $m->id ? 'is-active' : '' }}" wire:click="select({{ $m->id }})" wire:key="tab-{{ $m->id }}">
                {{ $m->name }} <small class="hz-mb__loc">{{ $m->location === 'header' ? 'encabezado' : 'pie' }}</small>
            </button>
        @endforeach
    </nav>

    @if ($creating)
        <form class="hz-mb__card hz-mb__create" wire:submit="createMenu">
            <input type="text" class="form-control" wire:model="newMenuName" placeholder="Nombre del menú" maxlength="60" aria-label="Nombre del menú" autofocus>
            <select class="form-control" wire:model="newMenuLocation" aria-label="Ubicación">
                <option value="header">Encabezado (barra superior)</option>
                <option value="footer">Pie de página (una columna)</option>
            </select>
            <button type="submit" class="am-btn">Crear</button>
            <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm" wire:click="$set('creating', false)">Cancelar</button>
            @error('newMenuName') <span class="hz-cp__err">{{ $message }}</span> @enderror
        </form>
    @endif

    @if ($menuId)
        <div class="hz-mb__grid">
            <section class="hz-mb__card">
                <form class="hz-mb__meta" wire:submit="saveMenu">
                    <input type="text" class="form-control" wire:model="menuName" maxlength="60" aria-label="Nombre">
                    <select class="form-control" wire:model="menuLocation" aria-label="Ubicación">
                        <option value="header">Encabezado</option>
                        <option value="footer">Pie de página</option>
                    </select>
                    <button type="submit" class="hz-btn hz-btn--secondary hz-btn--sm">Guardar</button>
                    <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm hz-btn--danger-outline" wire:click="deleteMenu" wire:confirm="¿Eliminar este menú y todos sus enlaces?">Eliminar</button>
                </form>
                @error('menuName') <span class="hz-cp__err">{{ $message }}</span> @enderror

                <div class="hz-mb__preview" aria-label="Vista previa">
                    <span class="hz-mb__preview-label">Vista previa</span>
                    @forelse ($tree as $node)
                        <span class="hz-mb__chip">{{ $node['item']->label }}@if ($node['children']->isNotEmpty()) <i aria-hidden="true">▾</i>@endif</span>
                    @empty
                        <em>Sin enlaces todavía</em>
                    @endforelse
                </div>

                <ul class="hz-mb__list" data-sortable="root" wire:key="root-{{ $menuId }}">
                    @foreach ($tree as $node)
                        @include('livewire.pages.admin.menu.partials.item', ['node' => $node, 'depth' => 0])
                    @endforeach
                </ul>
                @if ($tree->isEmpty())
                    <p class="hz-mb__empty">Agrega enlaces desde el panel de la derecha.</p>
                @endif
            </section>

            <aside class="hz-mb__card hz-mb__add" x-data="{ tab: 'platform' }">
                <h3>Agregar enlaces</h3>
                <div class="hz-tabs hz-tabs--sm" role="tablist">
                    @foreach (['platform' => 'Plataforma', 'pages' => 'Páginas', 'courses' => 'Cursos', 'custom' => 'Enlace'] as $key => $label)
                        <button type="button" role="tab" class="hz-tabs__tab" :class="{ 'is-active': tab === '{{ $key }}' }" @click="tab = '{{ $key }}'">{{ $label }}</button>
                    @endforeach
                </div>

                <div x-show="tab === 'platform'" x-transition.opacity>
                    <div class="hz-mb__checks">
                        @foreach ($platformLinks as $route => $label)
                            <label><input type="checkbox" value="{{ $route }}" wire:model="platformChoices"> {{ $label }}</label>
                        @endforeach
                    </div>
                    <button type="button" class="am-btn" wire:click="addPlatform" @disabled(empty($platformChoices))>Agregar al menú</button>
                </div>

                <div x-show="tab === 'pages'" x-cloak x-transition.opacity>
                    @if ($pages->isEmpty())
                        <p class="hz-mb__empty">Aún no hay páginas publicadas. <a href="{{ route('admin.pages') }}">Crear una página</a></p>
                    @else
                        <div class="hz-mb__checks">
                            @foreach ($pages as $p)
                                <label><input type="checkbox" value="{{ $p->id }}" wire:model="pageChoices"> {{ $p->name }} <small>/{{ $p->slug }}</small></label>
                            @endforeach
                        </div>
                        <button type="button" class="am-btn" wire:click="addPages" @disabled(empty($pageChoices))>Agregar al menú</button>
                    @endif
                </div>

                <div x-show="tab === 'courses'" x-cloak x-transition.opacity>
                    <select class="form-control" wire:model="courseChoice" aria-label="Curso">
                        <option value="">Elige un curso…</option>
                        @foreach ($courses as $c)
                            <option value="{{ $c->id }}">{{ $c->title }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="am-btn" wire:click="addCourse" @disabled(!$courseChoice)>Agregar al menú</button>
                </div>

                <form x-show="tab === 'custom'" x-cloak x-transition.opacity wire:submit="addCustom">
                    <label for="mb-label">Texto</label>
                    <input id="mb-label" type="text" class="form-control" wire:model="customLabel" maxlength="60" placeholder="Ej.: Blog">
                    @error('customLabel') <span class="hz-cp__err">{{ $message }}</span> @enderror
                    <label for="mb-url">Enlace</label>
                    <input id="mb-url" type="text" class="form-control" wire:model="customUrl" maxlength="500" placeholder="/blog  ·  https://…  ·  mailto:…">
                    @error('customUrl') <span class="hz-cp__err">{{ $message }}</span> @enderror
                    <button type="submit" class="am-btn">Agregar al menú</button>
                </form>
            </aside>
        </div>
    @else
        <p class="hz-mb__empty">Crea tu primer menú con «Nuevo menú».</p>
    @endif

    @assets
        <script src="{{ asset('vendor/pagebuilder/js/Sortable.min.js') }}"></script>
    @endassets

    @script
    <script>
        const serialize = (ul) => [...ul.children].filter(li => li.dataset.id).map(li => {
            const sub = li.querySelector(':scope > ul');
            return { id: li.dataset.id, children: sub ? serialize(sub) : [] };
        });

        const init = () => {
            document.querySelectorAll('.hz-mb__list').forEach((ul) => {
                if (ul._sortable) return;
                ul._sortable = Sortable.create(ul, {
                    group: 'menu', handle: '.hz-mb__handle', animation: 160, fallbackOnBody: true, swapThreshold: .65, ghostClass: 'hz-mb__ghost',
                    // One level of dropdown only: an item that already has children cannot become a child.
                    onMove: (e) => !(e.to.dataset.sortable === 'sub' && e.dragged.querySelector(':scope > ul > li')),
                    onEnd: () => $wire.reorder(serialize(document.querySelector('.hz-mb__list[data-sortable="root"]'))),
                });
            });
        };
        init();
        Livewire.hook('morphed', () => queueMicrotask(init));
    </script>
    @endscript
</div>
