@php $live = $activeCurriculum['live'] ?? null; @endphp
<div class="cr-lp cr-live2" wire:key="live-{{ $activeCurriculum['id'] }}" data-testid="live-lesson"
     x-data="crLive(@js($live))">
    @if (!$live)
        <div class="cr-lp__empty"><strong>{{ __('La sesión en vivo aún no tiene fecha.') }}</strong><p>{{ __('Tu instructor la programará pronto.') }}</p></div>
    @else
        <header class="cr-lp__head">
            <div>
                <span class="cr-live2__badge" :class="'is-' + phase" x-text="label"></span>
                <h3>{{ $live['title'] }}</h3>
                <p>{{ \Carbon\Carbon::parse($live['starts_at'])->translatedFormat('l j \d\e F, H:i') }} · {{ $live['duration_minutes'] }} min</p>
            </div>
        </header>
        <section class="cr-lp__panel">
            <div class="cr-live2__count" x-show="phase === 'upcoming'" x-cloak aria-live="polite">
                <template x-for="u in units" :key="u.k"><div><b x-text="u.v"></b><small x-text="u.l"></small></div></template>
            </div>
            @if (!empty($live['description']))<p class="cr-live2__desc">{{ $live['description'] }}</p>@endif
            <div class="cr-lp__actions">
                <template x-if="joinUrl">
                    <a :href="joinUrl" target="_blank" rel="noopener noreferrer" class="am-btn cr-live2__join" data-testid="live-join" x-on:click="$wire.markAttended()">{{ __('Unirme a la sesión') }}</a>
                </template>
                <button type="button" class="am-btn" disabled x-show="!joinUrl && (phase === 'upcoming')" x-cloak>{{ __('Se habilita 15 minutos antes') }}</button>
                <template x-if="recording">
                    <a :href="recording" target="_blank" rel="noopener noreferrer" class="am-btn" data-testid="live-recording">{{ __('Ver grabación') }}</a>
                </template>
                <a href="{{ $live['ics_url'] }}" class="am-btn cr-lp__ghost" x-show="phase !== 'ended'" data-testid="live-ics">{{ __('Añadir al calendario') }}</a>
                @if (!empty($curriculumOrder[$activeCurriculum['id']]))
                    <a href="javascript:void(0);" class="am-btn cr-lp__ghost" wire:click.prevent="nextCurriculum({{ $curriculumOrder[$activeCurriculum['id']] }})">{{ __('Siguiente lección') }}</a>
                @endif
            </div>
            <p class="cr-lp__note" x-show="phase === 'ended' && !recording" x-cloak>{{ __('La sesión terminó. Si hay grabación, aparecerá aquí.') }}</p>
        </section>
    @endif
</div>
