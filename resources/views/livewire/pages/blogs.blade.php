@php($first = (!$filtered && $blogs->isNotEmpty() && $blogs->currentPage() === 1) ? $blogs->first() : null)
<div class="ht-blog">
    <header class="ht-cat__head">
        <div class="ht-cat__wrap">
            <nav aria-label="Ruta de navegación" class="ht-cat__crumbs"><a href="{{ route('home') }}">Inicio</a> <span>/</span> <strong>Blog</strong></nav>
            <h1>{{ setting('_front_page_settings.blog_heading') ? html_entity_decode(strip_tags(setting('_front_page_settings.blog_heading'))) : 'Blog' }}</h1>
            <p>Guías, consejos y novedades para aprender mejor.</p>

            <div class="ht-cat__search" role="search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" wire:model.live.debounce.300ms="keyword" placeholder="Buscar artículos" aria-label="Buscar artículos" autocomplete="off">
                @if ($keyword !== '')<button type="button" class="ht-cat__clear" wire:click="$set('keyword', '')" aria-label="Borrar búsqueda">✕</button>@endif
            </div>

            @if ($categories->isNotEmpty())
                <div class="ht-chips" role="group" aria-label="Categorías">
                    <button type="button" class="ht-chip {{ !$activeCategory ? 'is-on' : '' }}" wire:click="setCategory('')" aria-pressed="{{ !$activeCategory ? 'true' : 'false' }}">Todos</button>
                    @foreach ($categories as $c)
                        <button type="button" class="ht-chip {{ $activeCategory?->id === $c->id ? 'is-on' : '' }}" wire:click="setCategory('{{ $c->slug }}')" wire:key="bc-{{ $c->id }}" aria-pressed="{{ $activeCategory?->id === $c->id ? 'true' : 'false' }}">{{ $c->name }} <em>{{ $c->posts_count }}</em></button>
                    @endforeach
                </div>
            @endif
        </div>
    </header>

    <div class="ht-cat__wrap ht-blog__body">
        <p class="ht-total" aria-live="polite" role="status">
            @if ($blogs->total())<strong>{{ $blogs->total() }}</strong> {{ $blogs->total() === 1 ? 'artículo' : 'artículos' }}@if ($filtered) con tus filtros <button type="button" class="ht-link" wire:click="clearFilters">Limpiar</button>@endif
            @else Sin resultados @endif
        </p>

        @if ($blogs->isNotEmpty())
            @if ($first)
                @include('livewire.pages.partials.blog-card', ['blog' => $first, 'featured' => true, 'eager' => true])
            @endif
            <div class="ht-bgrid" wire:loading.class="is-loading" wire:target="keyword, setCategory, clearFilters">
                @foreach ($blogs as $blog)
                    @continue($first && $blog->id === $first->id)
                    @include('livewire.pages.partials.blog-card', ['blog' => $blog, 'eager' => false])
                @endforeach
            </div>
            @if ($blogs->hasMorePages())
                <div class="ht-more">
                    <p>Mostrando {{ $blogs->count() }} de {{ $blogs->total() }}</p>
                    <button type="button" class="ht-btn-ghost" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore">
                        <span wire:loading.remove wire:target="loadMore">Cargar más artículos</span><span wire:loading wire:target="loadMore">Cargando…</span>
                    </button>
                </div>
            @endif
        @else
            <div class="ht-empty">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M5 4h11l3 3v13H5z"/><path d="M8 11h8M8 15h5"/></svg>
                <h2>No encontramos artículos</h2>
                <p>Prueba con otras palabras o elige una categoría distinta.</p>
                @if ($filtered)<button type="button" class="am-btn" wire:click="clearFilters">Ver todos los artículos</button>@endif
            </div>
        @endif
    </div>
</div>
