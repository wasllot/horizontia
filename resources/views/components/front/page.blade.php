@props(['title', 'description' => null, 'updated' => null])

<x-guest-layout :title="$title">
    <x-front.header />

    <div class="am-breadcrumb-area" style="background-color:#f9f9f9; padding:40px 0;">
        <div class="container">
            <h1 style="font-size:2.25rem; font-weight:800; color:#14213d; margin-bottom:10px;">{{ $title }}</h1>
            <ul class="am-breadcrumb" style="list-style:none; padding:0; margin:0; display:flex; gap:10px; align-items:center;">
                <li><a href="{{ route('home') }}" style="color:#666; text-decoration:none;">Inicio</a></li>
                <li style="color:#ccc;">/</li>
                <li style="color:#b8860b; font-weight:600;">{{ $title }}</li>
            </ul>
            @if ($description)
                <p style="color:#555; margin:14px 0 0; max-width:720px;">{{ $description }}</p>
            @endif
        </div>
    </div>

    <section style="padding:48px 0 72px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <article class="ht-prose">
                        {{ $slot }}
                        @if ($updated)
                            <p class="ht-prose__meta">Última actualización: {{ \Carbon\Carbon::parse($updated)->locale('es')->translatedFormat('j \d\e F \d\e Y') }}</p>
                        @endif
                    </article>
                </div>
            </div>
        </div>
    </section>

    <style>
        .ht-prose { color:#2b3445; font-size:1.0625rem; line-height:1.75; }
        .ht-prose h2 { color:#14213d; font-size:1.4rem; font-weight:700; margin:2.2rem 0 .8rem; }
        .ht-prose h3 { color:#14213d; font-size:1.15rem; font-weight:700; margin:1.6rem 0 .5rem; }
        .ht-prose p, .ht-prose ul, .ht-prose ol { margin-bottom:1rem; }
        .ht-prose ul, .ht-prose ol { padding-left:1.4rem; }
        .ht-prose li { margin-bottom:.4rem; }
        .ht-prose a { color:#0f3460; text-decoration:underline; }
        .ht-prose__meta { margin-top:2.5rem; padding-top:1rem; border-top:1px solid #e5e7eb; color:#6b7280; font-size:.9rem; }
        .ht-prose .ht-callout { background:#fff8dc; border-left:4px solid #fed304; padding:14px 18px; border-radius:6px; margin:1.4rem 0; }
    </style>

    <x-front.footer />
</x-guest-layout>
