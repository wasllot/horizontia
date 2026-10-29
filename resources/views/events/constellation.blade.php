{{-- Constelación decorativa de línea dorada que se dibuja al entrar a la vista. $kind: gem | orion --}}
@if (($kind ?? 'gem') === 'gem')
<svg class="ev-const ev-const--gem" viewBox="0 0 520 520" aria-hidden="true" focusable="false">
    <g class="ev-const__lines"><path pathLength="1" d="M210 60L200 140L180 230L150 330L110 366"/><path pathLength="1" d="M300 80L290 150L280 240L300 340L342 368"/><path pathLength="1" d="M200 140L290 150"/><path pathLength="1" d="M180 230L280 240"/><path pathLength="1" d="M200 140L132 118"/><path pathLength="1" d="M210 60L300 80"/></g>
    <g class="ev-const__stars"><circle cx="210" cy="60" r="7"/><circle cx="300" cy="80" r="8"/><circle cx="200" cy="140" r="4"/><circle cx="290" cy="150" r="4"/><circle cx="180" cy="230" r="4"/><circle cx="280" cy="240" r="4"/><circle cx="150" cy="330" r="4"/><circle cx="300" cy="340" r="4"/><circle cx="110" cy="366" r="3"/><circle cx="342" cy="368" r="3"/><circle cx="132" cy="118" r="3"/></g>
    <g class="ev-const__names"><text x="170" y="52">Cástor</text><text x="312" y="76">Pólux</text></g>
</svg>
@else
<svg class="ev-const ev-const--orion" viewBox="0 0 520 520" aria-hidden="true" focusable="false">
    <g class="ev-const__lines"><path pathLength="1" d="M270 52L190 110L228 214L252 222L286 226L322 218L360 112L270 52"/><path pathLength="1" d="M228 214L200 340"/><path pathLength="1" d="M322 218L368 350"/><path pathLength="1" d="M252 222L254 300"/><path pathLength="1" d="M286 226L282 300"/><path pathLength="1" d="M200 340L254 300L282 300L368 350"/><path pathLength="1" d="M360 112L420 90L440 130"/></g>
    <g class="ev-const__stars"><circle cx="270" cy="52" r="4"/><circle cx="190" cy="110" r="9"/><circle cx="360" cy="112" r="6"/><circle cx="228" cy="214" r="5"/><circle cx="252" cy="222" r="5"/><circle cx="286" cy="226" r="5"/><circle cx="322" cy="218" r="5"/><circle cx="200" cy="340" r="5"/><circle cx="368" cy="350" r="8"/><circle cx="254" cy="300" r="3"/><circle cx="282" cy="300" r="3"/><circle cx="420" cy="90" r="3"/><circle cx="440" cy="130" r="3"/></g>
    <g class="ev-const__names"><text x="130" y="104">Betelgeuse</text><text x="372" y="356">Rigel</text></g>
</svg>
@endif
