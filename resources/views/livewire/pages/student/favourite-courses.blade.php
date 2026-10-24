<div class="ht-fav" data-testid="favourites">
    <style>
        .ht-fav h1 { margin: 0 0 4px; font-size: 1.6rem; color: #14213d; }
        .ht-fav__sub { margin: 0 0 20px; color: #5b6478; }
        .ht-fav__grid { display: grid; gap: 18px; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
        .ht-fav__card { display: flex; flex-direction: column; border: 1px solid #e6eaf2; border-radius: 16px; background: #fff; overflow: hidden; transition: box-shadow .2s, transform .2s; }
        .ht-fav__card:hover { box-shadow: 0 14px 30px rgba(20, 33, 61, .1); transform: translateY(-3px); }
        .ht-fav__img { position: relative; aspect-ratio: 16 / 9; background: #14213d; }
        .ht-fav__img img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .ht-fav__heart { position: absolute; top: 10px; right: 10px; width: 36px; height: 36px; border: 0; border-radius: 50%; background: #fff; color: #d92d20; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0, 0, 0, .2); }
        .ht-fav__body { padding: 14px 16px 16px; display: grid; gap: 6px; flex: 1; align-content: start; }
        .ht-fav__body h2 { margin: 0; font-size: 1.02rem; line-height: 1.35; }
        .ht-fav__body h2 a { color: #14213d; text-decoration: none; }
        .ht-fav__body small { color: #5b6478; }
        .ht-fav__foot { margin-top: auto; padding-top: 8px; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .ht-fav__price { font-weight: 800; color: #14213d; }
        .ht-fav__price.is-free { color: #14703b; }
        .ht-fav a.ht-fav__btn { padding: 7px 14px; border-radius: 9px; background: #fed304; color: #14213d; font-weight: 700; font-size: .86rem; text-decoration: none; }
        .ht-fav__empty { padding: 36px 24px; text-align: center; border: 1.5px dashed #c5cde0; border-radius: 16px; color: #5b6478; }
        .ht-fav__empty a { color: #1b4fc4; font-weight: 700; text-decoration: underline; }
    </style>

    <h1>Mis favoritos</h1>
    <p class="ht-fav__sub">Los cursos que marcaste con el corazón para no perderlos.</p>

    @if ($courses->isEmpty())
        <div class="ht-fav__empty" data-testid="favourites-empty">
            <p style="margin: 0 0 6px"><strong>Aún no tienes favoritos.</strong></p>
            <p style="margin: 0">Marca un curso con el corazón y aparecerá aquí. <a href="{{ route('courses.search-courses') }}">Explorar el catálogo</a></p>
        </div>
    @else
        <div class="ht-fav__grid">
            @foreach ($courses as $c)
                @php $price = $c->pricing; $free = empty($price?->final_price) || (float) $price->final_price <= 0; @endphp
                <article class="ht-fav__card" wire:key="fav-{{ $c->id }}" data-testid="fav-{{ $c->id }}">
                    <div class="ht-fav__img">
                        <img src="{{ !empty($c->thumbnail?->path) ? mediaThumb($c->thumbnail->path, 640) : asset('modules/courses/images/course-placeholder.png') }}" alt="" loading="lazy" width="360" height="200">
                        <button type="button" class="ht-fav__heart" wire:click="remove({{ $c->id }})" aria-label="Quitar «{{ html_entity_decode($c->title) }}» de favoritos" title="Quitar de favoritos" data-testid="fav-remove-{{ $c->id }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 8.4a4.3 4.3 0 0 1 7.5 2.4C19.5 16.4 12 21 12 21z"/></svg>
                        </button>
                    </div>
                    <div class="ht-fav__body">
                        <small>{{ $c->category?->name }}</small>
                        <h2><a href="{{ route('courses.course-detail', ['slug' => $c->slug]) }}">{{ html_entity_decode($c->title) }}</a></h2>
                        <small>{{ $c->instructor?->profile?->full_name }}</small>
                        <div class="ht-fav__foot">
                            <span class="ht-fav__price {{ $free ? 'is-free' : '' }}">{!! $free ? 'Gratis' : formatAmount($price->final_price) !!}</span>
                            <a class="ht-fav__btn" href="{{ route('courses.course-detail', ['slug' => $c->slug]) }}">Ver curso</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
