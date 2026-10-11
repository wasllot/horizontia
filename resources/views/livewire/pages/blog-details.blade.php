@php
    $url = route('blog-details', $blog->slug);
    $cat = $blog->categories->first();
    $author = $blog->author?->profile;
    $hasImage = !empty($blog->image) && mediaExists($blog->image);
    $date = $blog->updated_at->locale('es')->translatedFormat('j \d\e F \d\e Y');
@endphp
<article class="ht-post">
    <header class="ht-post__head">
        <div class="ht-post__wrap">
            <nav aria-label="Ruta de navegación" class="ht-cat__crumbs">
                <a href="{{ route('home') }}">Inicio</a> <span>/</span> <a href="{{ route('blogs') }}">Blog</a>
                @if ($cat)<span>/</span> <a href="{{ route('blogs', ['category' => $cat->slug]) }}">{{ $cat->name }}</a>@endif
            </nav>
            <div class="ht-post__cats">
                @foreach ($blog->categories as $c)<a href="{{ route('blogs', ['category' => $c->slug]) }}" class="ht-bcard__cat">{{ $c->name }}</a>@endforeach
            </div>
            <h1>{{ $blog->title }}</h1>
            <div class="ht-post__meta">
                @if ($author?->short_name)
                    <span class="ht-post__author">
                        @if (!empty($author->image) && mediaExists($author->image))<img src="{{ mediaThumb($author->image, 80) }}" alt="" width="32" height="32">@else<span class="ht-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($author->short_name, 0, 1)) }}</span>@endif
                        {{ $author->short_name }}
                    </span>
                @endif
                <time datetime="{{ $blog->updated_at->toDateString() }}">{{ $date }}</time>
                <span>{{ $blog->reading_minutes }} min de lectura</span>
            </div>
        </div>
    </header>

    <div class="ht-post__wrap">
        @if ($hasImage)
            <figure class="ht-post__cover"><img src="{{ mediaThumb($blog->image, 1200) }}" alt="{{ $blog->title }}" width="1200" height="630" fetchpriority="high"></figure>
        @endif

        <div class="ht-post__layout">
            <div class="ht-post__content ht-prose">{!! $blog->description !!}</div>

            <aside class="ht-share" aria-label="Compartir" x-data="{ copied: false }">
                <span>Compartir</span>
                <a href="https://wa.me/?text={{ urlencode($blog->title . ' ' . $url) }}" target="_blank" rel="noopener noreferrer">WhatsApp</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($url) }}" target="_blank" rel="noopener noreferrer">LinkedIn</a>
                <a href="https://twitter.com/intent/tweet?text={{ urlencode($blog->title) }}&url={{ urlencode($url) }}" target="_blank" rel="noopener noreferrer">X</a>
                <a href="mailto:?subject={{ rawurlencode($blog->title) }}&body={{ rawurlencode($url) }}">Correo</a>
                <button type="button" @click="navigator.clipboard?.writeText(@js($url)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" x-text="copied ? '¡Enlace copiado!' : 'Copiar enlace'">Copiar enlace</button>
            </aside>
        </div>

        @if ($blog->tags->isNotEmpty())
            <ul class="ht-post__tags" aria-label="Etiquetas">
                @foreach ($blog->tags as $tag)<li>#{{ $tag->name }}</li>@endforeach
            </ul>
        @endif
    </div>

    @if ($courses->isNotEmpty())
        <section class="ht-post__courses">
            <div class="ht-cat__wrap">
                <h2>Sigue aprendiendo con nuestros cursos</h2>
                <div class="ht-courses__grid">
                    @foreach ($courses as $course)
                        <a class="ht-course-card" href="{{ route('courses.course-detail', $course->slug) }}">
                            @if (!empty($course->thumbnail?->path))<img loading="lazy" decoding="async" width="640" height="360" src="{{ mediaThumb($course->thumbnail->path, 640) }}" alt="">@endif
                            <div class="ht-course-card__body"><h3>{{ html_entity_decode($course->title) }}</h3>
                                <span class="ht-course-card__price">{{ isPaidSystem() && (float) ($course->pricing?->final_price ?? 0) > 0 ? formatAmount($course->pricing->final_price) : 'Gratis' }}</span></div>
                        </a>
                    @endforeach
                </div>
                <p class="ht-courses__more"><a class="am-btn" href="{{ route('courses.search-courses') }}">Ver todos los cursos</a></p>
            </div>
        </section>
    @endif

    @if ($relatedBlogs->isNotEmpty())
        <section class="ht-post__related">
            <div class="ht-cat__wrap">
                <h2>Artículos relacionados</h2>
                <div class="ht-bgrid">
                    @foreach ($relatedBlogs as $related)
                        @include('livewire.pages.partials.blog-card', ['blog' => $related, 'eager' => false])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <script type="application/ld+json">{!! json_encode(array_filter([
        '@context' => 'https://schema.org', '@type' => 'BlogPosting',
        'headline' => $blog->title, 'description' => $blog->meta_description ?: $blog->excerpt(28),
        'mainEntityOfPage' => $url, 'url' => $url,
        'image' => $hasImage ? [url(mediaUrl($blog->image))] : null,
        'datePublished' => $blog->created_at?->toIso8601String(), 'dateModified' => $blog->updated_at?->toIso8601String(),
        'author' => $author?->short_name ? ['@type' => 'Person', 'name' => $author->short_name] : null,
        'publisher' => ['@type' => 'Organization', 'name' => config('academy.legal_name'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/nuevo/logo-horizontia.png')]],
        'inLanguage' => 'es-MX', 'wordCount' => str_word_count(strip_tags((string) $blog->description)),
    ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_values(array_filter([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blogs')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $blog->title, 'item' => $url],
    ]))], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
</article>
