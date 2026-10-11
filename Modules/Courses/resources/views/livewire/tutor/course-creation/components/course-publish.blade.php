@php
    $labels = ['draft' => ['Borrador', 'neutral'], 'under_review' => ['En revisión', 'warn'], 'need_revision' => ['Requiere cambios', 'warn'], 'active' => ['Publicado', 'ok'], 'inactive' => ['Inactivo', 'neutral']];
    [$statusLabel, $statusTone] = $labels[$course->status] ?? ['Borrador', 'neutral'];
    $required = collect($checks)->where('required', true);
    $tips = collect($checks)->where('required', false)->where('ok', false);
@endphp
<div class="cr-course-box cr-publish" wire:init="$refresh">
    @if ($done)
        <div class="cr-publish__done" data-testid="publish-done">
            <span class="cr-publish__check"><x-courses::icon name="check" :size="40" /></span>
            <h2>{{ $done === 'published' ? __('¡Tu curso ya está publicado!') : ($done === 'review' ? __('¡Tu curso se envió a revisión!') : __('Guardado como borrador')) }}</h2>
            <p>{{ $done === 'published' ? __('Ya aparece en el catálogo y los estudiantes pueden inscribirse.') : ($done === 'review' ? __('El equipo lo revisará y te avisaremos cuando esté publicado. Mientras tanto puedes seguir editándolo.') : __('Puedes volver a editarlo cuando quieras.')) }}</p>
            <div class="cr-publish__actions">
                <a href="{{ $dashboardUrl }}" class="am-btn">{{ __('courses::courses.go_to_dashboard') }}</a>
                @if ($done === 'published')<a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="am-white-btn">{{ __('Ver el curso en el sitio') }}</a>@endif
            </div>
        </div>
    @else
        <div class="cr-content-box cr-publish__head">
            <div>
                <h2>{{ __('Revisa y publica tu curso') }}</h2>
                <p>{{ __('Comprueba que todo lo importante está listo. Los puntos marcados como obligatorios deben cumplirse para publicar.') }}</p>
            </div>
            <span class="cr-pill cr-pill--{{ $statusTone }}">{{ __($statusLabel) }}</span>
        </div>

        <div class="cr-publish__grid">
            <section class="cr-publish__card" aria-labelledby="pub-req">
                <h3 id="pub-req">{{ __('Obligatorio') }} <small>{{ $required->where('ok', true)->count() }}/{{ $required->count() }}</small></h3>
                <ul class="cr-checks">
                    @foreach ($required as $c)
                        <li class="{{ $c['ok'] ? 'is-ok' : 'is-bad' }}" data-testid="check-{{ $c['key'] }}">
                            <span class="cr-checks__mark"><x-courses::icon :name="$c['ok'] ? 'check' : 'alert'" :size="18" /></span>
                            <span class="cr-checks__text"><strong>{{ __($c['label']) }}</strong>@unless ($c['ok'])<small>{{ __($c['hint']) }}</small>@endunless</span>
                            @unless ($c['ok'])<a class="cr-btn cr-btn--sm" href="{{ courseEditUrl($c['tab'], $courseId) }}">{{ __('Corregir') }}</a>@endunless
                        </li>
                    @endforeach
                </ul>
            </section>
            <section class="cr-publish__card" aria-labelledby="pub-tips">
                <h3 id="pub-tips">{{ __('Recomendado') }}</h3>
                @if ($tips->isEmpty())
                    <p class="cr-hint">{{ __('Tienes todo lo recomendado. ¡Excelente trabajo!') }}</p>
                @else
                    <ul class="cr-checks cr-checks--soft">
                        @foreach ($tips as $c)
                            <li>
                                <span class="cr-checks__mark"><x-courses::icon name="plus" :size="16" /></span>
                                <span class="cr-checks__text"><strong>{{ __($c['label']) }}</strong><small>{{ __($c['hint']) }}</small></span>
                                <a class="cr-btn cr-btn--sm" href="{{ courseEditUrl($c['tab'], $courseId) }}">{{ __('Añadir') }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif

    @unless ($done)
        <div class="am-themeform_footer">
            <a href="{{ courseEditUrl('noticeboard', $courseId) }}" class="am-white-btn"><x-courses::icon name="arrow-left" :size="16" /> {{ __('courses::courses.back') }}</a>
            @if ($course->status !== 'draft' && $course->status !== 'need_revision' || $isAdmin)
                <button type="button" class="cr-btn" wire:click="backToDraft" wire:confirm="{{ __('¿Pasar el curso a borrador? Dejará de verse en el catálogo.') }}">{{ __('Guardar como borrador') }}</button>
            @endif
            <button type="button" class="am-btn" wire:click="publish" wire:loading.attr="disabled" wire:target="publish" @disabled(!$ready) data-testid="publish-btn">
                @if ($isAdmin){{ $course->status === 'active' ? __('Guardar y mantener publicado') : __('Publicar curso') }}@else{{ in_array($course->status, ['active', 'under_review'], true) ? __('Listo, guardar cambios') : __('Enviar a revisión') }}@endif
            </button>
        </div>
    @endunless
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/courses/css/main.css') }}">
@endpush
