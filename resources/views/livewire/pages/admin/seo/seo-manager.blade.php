<div class="hz-seo">
    <header class="hz-seo__head">
        <div>
            <h2>SEO de la plataforma</h2>
            <p>Define cómo aparece cada URL en Google y en redes sociales. Si dejas un campo vacío se usa el valor automático de la página.</p>
        </div>
        <div class="hz-seo__stats">
            <span><strong>{{ $total }}</strong> URLs</span>
            <span><strong>{{ $customCount }}</strong> personalizadas</span>
            <span><strong>{{ $rules->count() }}</strong> reglas</span>
        </div>
    </header>

    <nav class="hz-tabs" role="tablist">
        @foreach (['urls' => 'URLs y páginas', 'rules' => 'Reglas por patrón', 'global' => 'Ajustes globales'] as $key => $label)
            <button type="button" role="tab" class="hz-tabs__tab {{ $tab === $key ? 'is-active' : '' }}" wire:click="$set('tab', '{{ $key }}')">{{ $label }}</button>
        @endforeach
    </nav>

    @if ($tab === 'urls')
        <div class="hz-seo__filters">
            <input type="search" class="form-control" placeholder="Buscar por nombre o URL…" wire:model.live.debounce.250ms="search" aria-label="Buscar">
            <select class="form-control" wire:model.live="type" aria-label="Tipo">
                <option value="all">Todas</option>
                <option value="static">Páginas fijas</option>
                <option value="pages">Páginas del constructor</option>
                <option value="courses">Cursos</option>
                <option value="blog">Blog</option>
            </select>
        </div>

        <div class="hz-seo__list" wire:loading.class="is-loading">
            @forelse ($rows as $row)
                <button type="button" class="hz-seo__row {{ $editing === $row['path'] ? 'is-selected' : '' }}" wire:click="edit(@js($row['path']))" wire:key="seo-{{ $row['type'] }}-{{ $row['path'] }}">
                    <span class="hz-seo__type hz-seo__type--{{ $row['type'] }}">{{ ['static' => 'Fija', 'pages' => 'Página', 'courses' => 'Curso', 'blog' => 'Blog'][$row['type']] }}</span>
                    <span class="hz-seo__name"><strong>{{ $row['label'] }}</strong><small>/{{ ltrim($row['path'], '/') }}</small></span>
                    <span class="hz-seo__state {{ $row['custom'] ? 'is-custom' : '' }}">{{ $row['custom'] ? ($row['active'] ? 'Personalizado' : 'Desactivado') : 'Automático' }}</span>
                    <span class="hz-seo__go" aria-hidden="true">Editar</span>
                </button>
            @empty
                <p class="hz-seo__empty">No hay URLs que coincidan con la búsqueda.</p>
            @endforelse
        </div>

        @if ($pages > 1)
            <div class="hz-seo__pager">
                <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm" wire:click="goTo({{ $page - 1 }})" @disabled($page <= 1)>Anterior</button>
                <span>Página {{ $page }} de {{ $pages }}</span>
                <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm" wire:click="goTo({{ $page + 1 }})" @disabled($page >= $pages)>Siguiente</button>
            </div>
        @endif
    @elseif ($tab === 'rules')
        <p class="hz-seo__hint">Una regla aplica SEO a muchas URLs a la vez. Ejemplos: <code>curso/*</code> (todas las fichas de curso), <code>blog/*</code>, o el nombre de una ruta como <code>courses.search-courses</code>. Una URL con SEO propio siempre gana sobre la regla.</p>
        <form class="hz-seo__newrule" wire:submit="addRule">
            <select class="form-control" wire:model="newRuleType"><option value="pattern">Patrón de URL</option><option value="route">Nombre de ruta</option><option value="path">URL exacta</option></select>
            <input type="text" class="form-control" placeholder="curso/*" wire:model="newRule">
            <button class="am-btn" type="submit">Crear regla</button>
        </form>
        @error('newRule') <p class="hz-seo__error">{{ $message }}</p> @enderror
        <div class="hz-seo__list">
            @forelse ($rules as $rule)
                <button type="button" class="hz-seo__row" wire:click="edit(@js($rule->match_value), '{{ $rule->match_type }}')" wire:key="rule-{{ $rule->id }}">
                    <span class="hz-seo__type hz-seo__type--static">{{ $rule->match_type === 'route' ? 'Ruta' : 'Patrón' }}</span>
                    <span class="hz-seo__name"><strong>{{ $rule->match_value }}</strong><small>{{ $rule->title ?: 'Sin título' }}</small></span>
                    <span class="hz-seo__state {{ $rule->is_active ? 'is-custom' : '' }}">{{ $rule->is_active ? 'Activa' : 'Desactivada' }}</span>
                    <span class="hz-seo__go" aria-hidden="true">Editar</span>
                </button>
            @empty
                <p class="hz-seo__empty">Aún no hay reglas por patrón.</p>
            @endforelse
        </div>
    @else
        <form class="hz-seo__global" wire:submit="saveGlobal">
            <label>Descripción por defecto
                <textarea class="form-control" rows="3" wire:model="defaultDescription" placeholder="Se usa cuando una página no tiene descripción propia (máx. 160 recomendados)"></textarea>
            </label>
            <label>Imagen social por defecto (URL o ruta)
                <input type="text" class="form-control" wire:model="defaultImage" placeholder="https://…/imagen-1200x630.jpg">
            </label>
            <button class="am-btn" type="submit">Guardar ajustes</button>
        </form>
    @endif

    {{-- Panel de edición --}}
    @if ($editing !== null)
        <aside class="hz-drawer" role="dialog" aria-label="Editar SEO" x-data x-init="$nextTick(() => $el.querySelector('input,textarea')?.focus())" @keydown.escape.window="$wire.cancel()">
            <div class="hz-drawer__backdrop" wire:click="cancel"></div>
            <div class="hz-drawer__panel">
                <header class="hz-drawer__head">
                    <div><strong>{{ $matchType === 'route' ? 'Ruta' : ($matchType === 'pattern' ? 'Patrón' : 'URL') }}</strong><code>{{ $editing }}</code></div>
                    <button type="button" class="hz-drawer__close" wire:click="cancel" aria-label="Cerrar">✕</button>
                </header>

                <div class="hz-serp" aria-label="Vista previa en Google">
                    <small>{{ $auto['url'] }}</small>
                    <h4>{{ \Illuminate\Support\Str::limit($title ?: ($auto['title'] ?: 'Título de la página'), 60) }} | {{ setting('_general.site_name') ?: 'Horizontia' }}</h4>
                    <p>{{ \Illuminate\Support\Str::limit($description ?: ($auto['description'] ?: (setting('_seo.default_description') ?: 'Descripción de la página que aparecerá bajo el título.')), 160) }}</p>
                </div>

                <form wire:submit="save" class="hz-drawer__form">
                    <label>Título SEO
                        <input type="text" class="form-control" wire:model.live.debounce.200ms="title" placeholder="{{ $auto['title'] ?: 'Automático' }}" maxlength="120">
                        <span class="hz-count {{ strlen($title) > 60 ? 'is-over' : (strlen($title) >= 30 ? 'is-good' : '') }}">{{ strlen($title) }}/60</span>
                        @error('title') <em class="hz-seo__error">{{ $message }}</em> @enderror
                    </label>
                    <label>Meta descripción
                        <textarea class="form-control" rows="3" wire:model.live.debounce.200ms="description" placeholder="{{ $auto['description'] ?: 'Automática' }}" maxlength="320"></textarea>
                        <span class="hz-count {{ strlen($description) > 160 ? 'is-over' : (strlen($description) >= 70 ? 'is-good' : '') }}">{{ strlen($description) }}/160</span>
                        @error('description') <em class="hz-seo__error">{{ $message }}</em> @enderror
                    </label>
                    <label>Palabras clave <small>(opcional)</small>
                        <input type="text" class="form-control" wire:model="keywords" placeholder="marketing digital, curso en línea">
                    </label>
                    <div class="hz-drawer__grid">
                        <label>Indexación
                            <select class="form-control" wire:model="robots">
                                <option value="">Automática</option>
                                <option value="index, follow">Indexar y seguir enlaces</option>
                                <option value="noindex, follow">No indexar (seguir enlaces)</option>
                                <option value="noindex, nofollow">No indexar ni seguir</option>
                            </select>
                        </label>
                        <label>URL canónica
                            <input type="url" class="form-control" wire:model="canonical" placeholder="https://…">
                            @error('canonical') <em class="hz-seo__error">{{ $message }}</em> @enderror
                        </label>
                    </div>
                    <label>Imagen para redes (1200×630)
                        <input type="text" class="form-control" wire:model="ogImage" placeholder="https://… o /images/…">
                    </label>
                    <details class="hz-drawer__adv">
                        <summary>Datos estructurados (JSON-LD)</summary>
                        <textarea class="form-control" rows="6" wire:model="schema" placeholder='{"@@context":"https://schema.org","@@type":"Course","name":"…"}'></textarea>
                        @error('schema') <em class="hz-seo__error">{{ $message }}</em> @enderror
                    </details>
                    <label class="hz-drawer__switch"><input type="checkbox" wire:model="isActive"> Aplicar esta configuración</label>
                    <footer class="hz-drawer__foot">
                        <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm" wire:click="remove" wire:confirm="¿Volver al SEO automático de esta URL?">Restaurar automático</button>
                        <button type="submit" class="am-btn" wire:loading.attr="disabled">Guardar</button>
                    </footer>
                </form>
            </div>
        </aside>
    @endif
</div>
