<main class="tb-main am-courses-system hz-bn hz-cs">
    <header class="hz-seo__head">
        <div>
            <h2>Estudio de contenido</h2>
            <p>Prepara material con IA a partir de tu texto o de una lección: resúmenes, preguntas de examen, descripciones y esquemas. Siempre lo revisas antes de usarlo; nada se publica solo.</p>
        </div>
    </header>

    @unless ($enabled)
        <div class="hz-ct__alert" role="status" data-testid="studio-off">La IA no está activada todavía. Pídele al administrador que active el tutor con IA en <strong>Ajustes › Tutor con IA</strong> (usa la misma clave de Google Gemini).</div>
    @endunless

    <div class="hz-cs__tools" role="radiogroup" aria-label="¿Qué quieres generar?">
        @foreach ($tools as $key => $t)
            <label @class(['hz-cs__tool', 'is-on' => $tool === $key]) wire:key="tool-{{ $key }}">
                <input type="radio" wire:model.live="tool" value="{{ $key }}">
                <strong>{{ $t['label'] }}</strong>
                <small>{{ $t['hint'] }}</small>
            </label>
        @endforeach
    </div>

    <form class="hz-en__form hz-bn__form" wire:submit="generate" data-testid="studio-form">
        <div class="hz-bn__row">
            <div>
                <label for="cs-course">Traer el texto de una lección (opcional)</label>
                <select id="cs-course" class="form-control" wire:model.live="courseId">
                    <option value="">— Elige un curso —</option>
                    @foreach ($courses as $c)<option value="{{ $c->id }}">{{ html_entity_decode($c->title) }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="cs-lesson">Lección</label>
                <select id="cs-lesson" class="form-control" wire:model.live="lessonId" @disabled($lessons->isEmpty())>
                    <option value="">— Elige una lección —</option>
                    @foreach ($lessons as $l)<option value="{{ $l->id }}">{{ html_entity_decode($l->title) }}</option>@endforeach
                </select>
            </div>
            @if ($tool === 'questions')
                <div>
                    <label for="cs-count">Número de preguntas</label>
                    <input id="cs-count" type="number" min="3" max="15" class="form-control" wire:model="count">
                </div>
            @endif
        </div>

        <label for="cs-text">{{ $tool === 'outline' ? 'Tema o notas del curso' : 'Texto o transcripción' }} <small>({{ mb_strlen($text) }} / {{ number_format($max) }} caracteres)</small></label>
        <textarea id="cs-text" rows="9" class="form-control" wire:model.live.debounce.500ms="text" maxlength="{{ $max }}" placeholder="Pega aquí el texto, la transcripción de tu video o tus notas…" data-testid="studio-text"></textarea>
        <small class="hz-en__err" role="alert" aria-live="polite">{{ $error }}</small>

        <div class="hz-en__formacts">
            <button type="submit" class="hz-btn hz-btn--sm" wire:loading.attr="disabled" wire:target="generate" @disabled(!$enabled) data-testid="studio-generate">
                <span wire:loading.remove wire:target="generate">Generar con IA</span><span wire:loading wire:target="generate">Generando…</span>
            </button>
        </div>
    </form>

    @if ($output)
        <section class="hz-card hz-dash__card" data-testid="studio-output">
            <header class="hz-dash__cardhead"><div><h2>{{ $tools[$tool]['label'] }}</h2><p>Revísalo y edítalo antes de usarlo.</p></div>
                <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" x-data x-on:click="navigator.clipboard.writeText($refs.out.innerText); $el.textContent = '¡Copiado!'">Copiar</button></header>
            <div class="hz-cs__out" x-ref="out" data-testid="studio-text-out">{{ $output }}</div>
        </section>
    @endif

    @if ($questions)
        <section class="hz-card hz-dash__card" data-testid="studio-questions">
            <header class="hz-dash__cardhead"><div><h2>Preguntas generadas ({{ count($questions) }})</h2><p>Quita las que no te sirvan. La respuesta correcta está marcada.</p></div></header>
            <ol class="hz-cs__qs">
                @foreach ($questions as $i => $q)
                    <li wire:key="q-{{ $i }}-{{ md5($q['text']) }}">
                        <strong>{{ $q['text'] }}</strong>
                        <ul>@foreach ($q['options'] as $oi => $o)<li @class(['is-correct' => $oi === $q['correct']])>{{ $o }}</li>@endforeach</ul>
                        @if ($q['explanation'])<small>{{ $q['explanation'] }}</small>@endif
                        <button type="button" class="hz-rp__link" wire:click="removeQuestion({{ $i }})">Quitar</button>
                    </li>
                @endforeach
            </ol>
            <form class="hz-bn__form" wire:submit="createQuiz">
                <div class="hz-bn__row">
                    <div><label for="cs-qt">Nombre del test</label><input id="cs-qt" class="form-control" wire:model="quizTitle" maxlength="160"><small class="hz-en__err">{{ $errors->first('quizTitle') }}</small></div>
                    <div><label for="cs-sec">Sección del curso</label>
                        <select id="cs-sec" class="form-control" wire:model="sectionId" @disabled($sections->isEmpty())>
                            <option value="">{{ $sections->isEmpty() ? 'Elige un curso arriba' : '— Elige una sección —' }}</option>
                            @foreach ($sections as $s)<option value="{{ $s->id }}">{{ html_entity_decode($s->title) }}</option>@endforeach
                        </select><small class="hz-en__err">{{ $errors->first('sectionId') }}</small></div>
                </div>
                <small class="hz-en__err">{{ $errors->first('questions') }}</small>
                <div class="hz-en__formacts"><button type="submit" class="hz-btn hz-btn--sm" data-testid="studio-create-quiz">Crear lección de test</button></div>
            </form>
        </section>
    @endif

    @if ($createdUrl)
        <div class="hz-ct__preview" data-testid="studio-created">✓ Test creado. <a href="{{ $createdUrl }}">Abrir el contenido del curso</a></div>
    @endif
</main>
