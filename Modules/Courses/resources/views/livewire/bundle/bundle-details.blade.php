<div>
<link rel="stylesheet" href="{{ asset('css/bundles.css') }}?v={{ @filemtime(public_path('css/bundles.css')) }}">
<section class="bn-page">
    <div class="bn-wrap">
        <header class="bn-head">
            <span class="bn-tag">Paquete de {{ $bundle->courses->count() }} cursos</span>
            <h1>{{ $bundle->title }}</h1>
            @if ($bundle->subtitle)<p class="bn-sub">{{ $bundle->subtitle }}</p>@endif
            @unless ($bundle->isActive())<p class="bn-draft">Borrador: solo lo ves tú como administrador.</p>@endunless
        </header>

        <div class="bn-layout">
            <div class="bn-main">
                @if ($bundle->description)<div class="bn-desc">{!! nl2br(e($bundle->description)) !!}</div>@endif
                <h2>Incluye</h2>
                <ul class="bn-courses">
                    @foreach ($bundle->courses as $c)
                        @php $has = !$remaining->contains('id', $c->id); @endphp
                        <li class="bn-course" data-testid="bundle-course">
                            <a class="bn-course__img" href="{{ route('courses.course-detail', ['slug' => $c->slug]) }}">
                                <img src="{{ !empty($c->thumbnail?->path) ? mediaThumb($c->thumbnail->path, 320) : asset('modules/courses/images/course.png') }}" alt="" loading="lazy" width="160" height="90">
                            </a>
                            <div class="bn-course__body">
                                <a href="{{ route('courses.course-detail', ['slug' => $c->slug]) }}"><strong>{{ html_entity_decode($c->title) }}</strong></a>
                                <small>{{ $c->instructor?->profile?->full_name }}@if($c->category) · {{ $c->category->name }}@endif</small>
                            </div>
                            <span class="bn-course__price">@if ($has)<em>Ya lo tienes</em>@else {!! formatAmount($c->pricing->final_price, true) !!}@endif</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <aside class="bn-buy" aria-label="Comprar paquete">
                @if ($listPrice > $bundle->price)<s class="bn-list">{!! formatAmount($listPrice, true) !!}</s>@endif
                <strong class="bn-price" data-testid="bundle-price">{!! formatAmount($owned ? $yourPrice : $bundle->price, true) !!}</strong>
                @if ($savingsPercent > 0)<span class="bn-save">Ahorras {{ $savingsPercent }}% frente a comprar cada curso por separado</span>@endif
                @if ($owned && $remaining->isNotEmpty())<p class="bn-note">Ya tienes {{ $owned }} de estos cursos: pagas solo los {{ $remaining->count() }} que te faltan, con el mismo descuento.</p>@endif

                @if ($remaining->isEmpty())
                    <a class="bn-btn" href="{{ route('courses.course-list') }}">Ir a mis cursos</a>
                @elseif (!$sellable)
                    <span class="bn-btn bn-btn--off">No disponible por ahora</span>
                @elseif ($inCart)
                    <a class="bn-btn" href="{{ route('checkout') }}">Ir al pago</a>
                @else
                    <button class="bn-btn" wire:click="buyNow" wire:loading.attr="disabled" data-testid="bundle-buy">Comprar paquete</button>
                    <button class="bn-btn bn-btn--ghost" wire:click="addToCart" wire:loading.attr="disabled">Añadir al carrito</button>
                @endif
                <ul class="bn-trust">
                    <li>Acceso inmediato a todos los cursos</li>
                    <li>Garantía de reembolso de {{ config('academy.refund_days') }} días</li>
                </ul>
            </aside>
        </div>
    </div>
</section>
</div>
