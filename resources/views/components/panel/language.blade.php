@if (!empty(setting('_general.enable_multi_language')) && !empty(setting('_general.multi_language_list')))
    @php
        $langs = getTranslatedLanguages();
        $current = app()->getLocale() ?: 'en';
    @endphp
    <div class="hz-pop">
        <button type="button" class="hz-langbtn" data-hz-pop-toggle aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Idioma') }}">
            <img src="{{ getLangFlag($current) }}" alt="" width="18" height="18">
            <span>{{ strtoupper($current) }}</span>
            <x-panel.icon name="chevron-down" size="14" />
        </button>
        <div class="hz-pop__panel hz-pop__panel--sm" role="menu">
            @foreach (setting('_general.multi_language_list') as $lang)
                <button type="button" role="menuitemradio" aria-checked="{{ $lang === $current ? 'true' : 'false' }}" class="hz-menu__item {{ $lang === $current ? 'is-current' : '' }}" data-hz-lang="{{ $lang }}" data-hz-lang-url="{{ route('switch-lang') }}">
                    <img src="{{ getLangFlag($lang) }}" alt="" width="18" height="18" style="border-radius:50%"> {{ $langs[$lang] ?? ucfirst($lang) }}
                    @if ($lang === $current)<x-panel.icon name="check" style="margin-left:auto" />@endif
                </button>
            @endforeach
        </div>
    </div>
@endif
