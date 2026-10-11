@php
    $multiLang = collect($languages)->filter(fn ($l) => $l->active_courses_count > 0)->count() > 1;
    $showLevels = collect($levels)->filter(fn ($l) => $l['courses_count'] > 0)->isNotEmpty();
    $filterCount = count($applied);
@endphp
<div class="ht-cat" x-data="{ drawer: false }" @keydown.escape.window="drawer = false">
    <header class="ht-cat__head">
        <div class="ht-cat__wrap">
            <nav aria-label="Ruta de navegación" class="ht-cat__crumbs"><a href="{{ route('home') }}">Inicio</a> <span>/</span> <strong>Cursos</strong></nav>
            <h1>Cursos en línea</h1>
            <p>Aprende a tu ritmo con lecciones en video, ejercicios y certificado.</p>

            <div class="ht-cat__search" role="search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" wire:model.live.debounce.300ms="filters.keyword" placeholder="¿Qué quieres aprender hoy?" aria-label="Buscar cursos" autocomplete="off">
                @if (!empty($filters['keyword']))
                    <button type="button" class="ht-cat__clear" wire:click="removeFilter('keyword')" aria-label="Borrar búsqueda">✕</button>
                @endif
            </div>

            @if ($categories->isNotEmpty())
                <div class="ht-chips" role="group" aria-label="Categorías">
                    <button type="button" class="ht-chip {{ empty($searchCategories) ? 'is-on' : '' }}" wire:click="clearCategories" aria-pressed="{{ empty($searchCategories) ? 'true' : 'false' }}">Todos</button>
                    @foreach ($categories as $category)
                        @php($on = in_array((int) $category->id, array_map('intval', (array) $searchCategories), true))
                        <button type="button" class="ht-chip {{ $on ? 'is-on' : '' }}" wire:click="toggleCategory({{ $category->id }})" wire:key="cat-{{ $category->id }}" aria-pressed="{{ $on ? 'true' : 'false' }}">
                            {{ $category->name }} <em>{{ $category->active_courses_count }}</em>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </header>

    <div class="ht-cat__wrap ht-cat__layout">
        <div class="ht-cat__overlay" x-show="drawer" x-transition.opacity @click="drawer = false" x-cloak></div>

        <aside class="ht-filters" :class="{ 'is-open': drawer }" aria-label="Filtros">
            <div class="ht-filters__top">
                <h2>Filtros</h2>
                <button type="button" class="ht-filters__close" @click="drawer = false" aria-label="Cerrar filtros">✕</button>
            </div>

            <fieldset class="ht-fgroup">
                <legend>Precio</legend>
                @foreach (['all' => 'Todos', 'free' => 'Gratis', 'paid' => 'De pago'] as $val => $label)
                    <label class="ht-check"><input type="radio" name="pricing" value="{{ $val }}" wire:model.live="filters.pricing_type"> <span>{{ $label }}</span></label>
                @endforeach
            </fieldset>

            @if ($showLevels)
                <fieldset class="ht-fgroup">
                    <legend>Nivel</legend>
                    @foreach ($levels as $name => $level)
                        @continue($level['courses_count'] < 1)
                        <label class="ht-check"><input type="checkbox" value="{{ $level['name'] }}" wire:model.live="filters.levels"> <span>{{ $name === 'all' ? 'Todos los niveles' : __('courses::courses.' . $name) }}</span> <em>{{ $level['courses_count'] }}</em></label>
                    @endforeach
                </fieldset>
            @endif

            <fieldset class="ht-fgroup">
                <legend>Duración</legend>
                @foreach ($durationCounts as $range => $count)
                    @continue($count < 1)
                    <label class="ht-check"><input type="checkbox" value="{{ $range }}" wire:model.live="filters.duration"> <span>{{ \Modules\Courses\Livewire\Pages\Search\SearchCourses::durationLabel($range) }}</span> <em>{{ $count }}</em></label>
                @endforeach
            </fieldset>

            <fieldset class="ht-fgroup">
                <legend>Calificación</legend>
                <label class="ht-check"><input type="checkbox" wire:click="toggleRating" @checked(!empty($filters['avg_rating']))> <span>★ 4 estrellas o más</span></label>
            </fieldset>

            @if ($multiLang)
                <fieldset class="ht-fgroup">
                    <legend>Idioma</legend>
                    @foreach ($languages as $language)
                        @continue($language->active_courses_count < 1)
                        <label class="ht-check"><input type="checkbox" value="{{ $language->id }}" wire:model.live="searchLanguages"> <span>{{ languageLabel($language->name) }}</span> <em>{{ $language->active_courses_count }}</em></label>
                    @endforeach
                </fieldset>
            @endif

            <div class="ht-filters__foot">
                <button type="button" class="am-btn" @click="drawer = false">Ver {{ $courses->total() }} {{ $courses->total() === 1 ? 'curso' : 'cursos' }}</button>
                @if ($filterCount)<button type="button" class="ht-link" wire:click="resetFilters">Limpiar filtros</button>@endif
            </div>
        </aside>

        <main class="ht-results">
            @if ($continue->isNotEmpty())
                <section class="ht-continue" aria-label="Continúa donde lo dejaste">
                    <h2>Continúa donde lo dejaste</h2>
                    <div class="ht-continue__row">
                        @foreach ($continue as $mine)
                            <a class="ht-continue__item" href="{{ route('courses.course-taking', $mine->slug) }}" wire:key="cont-{{ $mine->id }}">
                                @include('courses::livewire.search.partials.thumb', ['course' => $mine, 'eager' => true])
                                <span>{{ html_entity_decode($mine->title) }}</span>
                                <strong>Continuar →</strong>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="ht-toolbar">
                <p class="ht-total" aria-live="polite" role="status">
                    @if ($courses->total() > 0)
                        <strong>{{ number_format($courses->total()) }}</strong> {{ $courses->total() === 1 ? 'curso' : 'cursos' }}
                        @if ($filterCount) con tus filtros @else disponibles @endif
                    @else
                        Sin resultados
                    @endif
                </p>
                <div class="ht-toolbar__ctrl">
                    <button type="button" class="ht-btn-ghost ht-toolbar__filters" @click="drawer = true">
                        Filtros @if ($filterCount)<span class="ht-badge">{{ $filterCount }}</span>@endif
                    </button>
                    <label class="ht-sort"><span>Ordenar por</span>
                        <select wire:model.live="filters.sort" aria-label="Ordenar cursos">
                            @foreach ($sorts as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </label>
                </div>
            </div>

            @if ($applied)
                <div class="ht-applied" aria-label="Filtros aplicados">
                    @foreach ($applied as $chip)
                        <button type="button" class="ht-applied__chip" wire:click="removeFilter('{{ $chip['type'] }}', '{{ $chip['value'] }}')" wire:key="app-{{ $chip['type'] }}-{{ $chip['value'] }}" aria-label="Quitar filtro {{ $chip['label'] }}">{{ $chip['label'] }} <span aria-hidden="true">✕</span></button>
                    @endforeach
                    <button type="button" class="ht-link" wire:click="resetFilters">Limpiar todo</button>
                </div>
            @endif

            @if ($courses->isNotEmpty())
                <div class="ht-grid" wire:loading.class="is-loading" wire:target="filters, searchCategories, searchLanguages, toggleCategory, clearCategories, removeFilter, resetFilters, toggleRating">
                    @foreach ($courses as $course)
                        @include('courses::livewire.search.partials.card', ['course' => $course, 'eager' => $loop->index < 3, 'multiLang' => $multiLang])
                    @endforeach
                </div>

                <div class="ht-more">
                    <p>Mostrando {{ $courses->count() }} de {{ $courses->total() }}</p>
                    @if ($courses->hasMorePages())
                        <button type="button" class="ht-btn-ghost" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore">
                            <span wire:loading.remove wire:target="loadMore">Cargar más cursos</span>
                            <span wire:loading wire:target="loadMore">Cargando…</span>
                        </button>
                    @endif
                </div>

                <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $courses->values()->map(fn ($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => route('courses.course-detail', $c->slug), 'name' => html_entity_decode($c->title)])->all()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
            @else
                <div class="ht-empty">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M8.5 11h5"/></svg>
                    <h2>No encontramos cursos con esos filtros</h2>
                    @if (!empty($suggestion))
                        <p data-testid="search-suggestion">¿Quisiste decir <button type="button" class="ht-link" wire:click="useSuggestion(@js($suggestion))"><strong>{{ $suggestion }}</strong></button>?</p>
                    @endif
                    <p>Prueba quitando alguno, revisa la ortografía o explora una categoría.</p>
                    @if ($filterCount)<button type="button" class="am-btn" wire:click="resetFilters">Quitar todos los filtros</button>@endif
                    @if ($categories->isNotEmpty())
                        <div class="ht-chips ht-chips--center">
                            @foreach ($categories->take(6) as $category)
                                <button type="button" class="ht-chip" wire:click="resetFilters" x-on:click="$wire.toggleCategory({{ $category->id }})">{{ $category->name }}</button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </main>
    </div>
</div>
