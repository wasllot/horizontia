{{-- Con un solo idioma no hay nada que elegir: el selector solo aparece si hay al menos dos. --}}
@if(!empty(setting('_general.enable_multi_language')))
    @if(is_array(setting('_general.multi_language_list')) && count(array_filter(setting('_general.multi_language_list'))) > 1)
        @php
            $translatedLangs = getTranslatedLanguages();
            $selectedLang = app()->getLocale() ?? 'en';
        @endphp
        <form class="am-switch-language am-multi-lang" action="{{ route('switch-lang') }}" method="POST">
            @csrf
            <div>
                <input type="hidden" name="am-locale">
                <div class="am-language-select">
                    <a href="javascript:void(0);" class="am-lang-anchor">
                        <img src="{{ getLangFlag($selectedLang) }}" alt="{{ $selectedLang }}">
                        {{ $translatedLangs[$selectedLang] ?? ucfirst($selectedLang) }}<i class="am-icon-chevron-down"></i>
                    </a>
                    <ul class="sub-menutwo locale-menu">
                        @foreach(setting('_general.multi_language_list') as $lang)
                            <li data-lang="{{ $lang }}" class="{{ $selectedLang == $lang ? 'active' : '' }}">
                                <span><img src="{{ getLangFlag($lang) }}" alt="{{ $lang }}">{{ $translatedLangs[$lang] ?? ucfirst($lang) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </form>
    @endif
@endif