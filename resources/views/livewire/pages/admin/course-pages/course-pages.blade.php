<div class="hz-cp">
    <header class="hz-seo__head">
        <div>
            <h2>Páginas de curso</h2>
            <p>Además de la página de venta, un curso puede tener páginas informativas (programa, instructor, preguntas, requisitos…) hechas con el <a href="{{ route('admin.pages') }}">constructor de páginas</a>.</p>
        </div>
        <a class="hz-btn hz-btn--sm" href="{{ route('admin.pages') }}"><i class="icon-plus" aria-hidden="true"></i> Crear página</a>
    </header>

    <div class="hz-cp__layout">
        <aside class="hz-cp__courses" aria-label="Cursos">
            <div class="hz-cp__find">
                <div class="hz-toolbar__search">
                    <i class="icon-search" aria-hidden="true"></i>
                    <input type="search" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Buscar curso…" aria-label="Buscar curso" autocomplete="off">
                </div>
                <label class="hz-cp__check">
                    <input type="checkbox" wire:model.live="onlyPublished"> <span>Solo cursos publicados</span>
                </label>
            </div>
            <ul class="hz-cp__list">
                @forelse ($courses as $c)
                    @php
                        $statusName = array_flip(\Modules\Courses\Models\Course::STATUSES)[$c->getRawOriginal('status')] ?? 'draft';
                        $tone = ['active' => 'success', 'under_review' => 'warning', 'need_revision' => 'danger'][$statusName] ?? 'neutral';
                    @endphp
                    <li wire:key="cp-course-{{ $c->id }}">
                        <button type="button" class="hz-cp__course {{ $courseId === $c->id ? 'is-active' : '' }}" wire:click="selectCourse({{ $c->id }})" @if($courseId === $c->id) aria-current="true" @endif>
                            <span class="hz-cp__title">{{ html_entity_decode($c->title) }}</span>
                            <span class="hz-cp__meta">
                                {{ $c->instructor?->profile?->full_name ?? '—' }}
                                · <x-panel.badge :tone="$tone" :dot="false">{{ __('courses::courses.' . $statusName) }}</x-panel.badge>
                            </span>
                            <span class="hz-cp__count" title="Páginas informativas">{{ $c->info_pages_count }}</span>
                        </button>
                    </li>
                @empty
                    <li class="hz-cp__empty">No hay cursos con ese filtro.</li>
                @endforelse
            </ul>
        </aside>

        <div class="hz-cp__detail">
            @if ($course)
                <section class="hz-cp__card">
                    <div class="hz-cp__cardhead">
                        <div>
                            <h3>{{ html_entity_decode($course->title) }}</h3>
                            <a class="hz-cp__link" href="{{ route('courses.course-detail', ['slug' => $course->slug]) }}" target="_blank" rel="noopener">Ver página de venta ↗</a>
                        </div>
                    </div>
                    <h4 class="hz-cp__sub">Páginas informativas</h4>
                    @forelse ($links as $link)
                        <div class="hz-cp__row" wire:key="link-{{ $link->id }}">
                            <div>
                                <strong>{{ $link->label }}</strong>
                                <small>
                                    @if ($link->page?->status === 'published')
                                        <a href="{{ route('courses.info-page', [$course->slug, $link->page->slug]) }}" target="_blank" rel="noopener">/curso/{{ $course->slug }}/informacion/{{ $link->page->slug }} ↗</a>
                                    @else
                                        /curso/{{ $course->slug }}/informacion/{{ $link->page?->slug }} · <em>borrador: publícala para que se vea</em>
                                    @endif
                                </small>
                            </div>
                            <div class="hz-acts">
                                <button type="button" class="hz-act" wire:click="move({{ $link->id }}, 'up')" @disabled($loop->first) title="Subir" aria-label="Subir"><i class="icon-arrow-up" aria-hidden="true"></i></button>
                                <button type="button" class="hz-act" wire:click="move({{ $link->id }}, 'down')" @disabled($loop->last) title="Bajar" aria-label="Bajar"><i class="icon-arrow-down" aria-hidden="true"></i></button>
                                <a class="hz-act" href="{{ route('admin.pages.editor', $link->page_id) }}" title="Editar" aria-label="Editar página"><i class="icon-edit" aria-hidden="true"></i></a>
                                <button type="button" class="hz-act hz-act--danger" wire:click="detach({{ $link->id }})" wire:confirm="¿Retirar esta página del curso?" title="Quitar" aria-label="Quitar del curso"><i class="icon-trash-2" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    @empty
                        <p class="hz-cp__empty">Este curso aún no tiene páginas informativas. Asocia una desde el panel de abajo.</p>
                    @endforelse
                </section>

                <section class="hz-cp__card">
                    <h3>Asociar una página</h3>
                    <form wire:submit="attach" class="hz-cp__form">
                        <div>
                            <label for="cp-page">Página del constructor</label>
                            <select id="cp-page" class="form-control" wire:model.live="pageId">
                                <option value="">Elige una página…</option>
                                @foreach ($pages as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->slug }})</option>
                                @endforeach
                            </select>
                            @error('pageId') <span class="hz-cp__err">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="cp-label">Etiqueta en el menú del curso</label>
                            <input id="cp-label" type="text" class="form-control" wire:model="label" maxlength="120" placeholder="Ej.: Programa detallado">
                            @error('label') <span class="hz-cp__err">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="hz-btn hz-btn--sm" wire:loading.attr="disabled" wire:target="attach">Asociar</button>
                    </form>
                    <p class="hz-cp__hint">Tip: dentro de la página puedes usar el bloque «Datos y compra del curso» para mostrar precio y botón de compra.</p>
                </section>
            @else
                <div class="hz-cp__placeholder">
                    <span aria-hidden="true"><i class="icon-file-text"></i></span>
                    <h3>Elige un curso</h3>
                    <p>Selecciónalo en la lista para ver y ordenar sus páginas informativas.</p>
                </div>
            @endif
        </div>
    </div>
</div>
