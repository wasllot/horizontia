@php
    // The lesson arrives student-safe from CourseTaking::activeFrom(): questions and options only, never the answers.
    $quizData = json_decode($activeCurriculum['article_content'] ?? '{}', true) ?: ['questions' => []];
    $submission = \Modules\Courses\Models\AssignmentSubmission::where('curriculum_id', $activeCurriculum['id'])->where('user_id', auth()->id())->first();
    $quizState = ['score' => $submission?->score, 'passed' => (bool) $submission?->passed_at, 'attempts' => (int) ($submission->attempts ?? ($submission ? 1 : 0))];
    $nextLessonId = $curriculumOrder[$activeCurriculum['id']] ?? null;
@endphp
<div class="cr-lp cr-quiz2" x-data="crQuiz(@js($quizData), @js($quizState), @js($nextLessonId))" wire:key="quiz-{{ $activeCurriculum['id'] }}" data-testid="quiz">
    @if (empty($quizData['questions']))
        <div class="cr-lp__empty"><strong>{{ __('Este test aún no tiene preguntas.') }}</strong></div>
    @else
        <header class="cr-lp__head">
            <div>
                <h3>{{ $activeCurriculum['title'] ?? __('Test') }}</h3>
                <p>{{ count($quizData['questions']) }} {{ count($quizData['questions']) === 1 ? __('pregunta') : __('preguntas') }} · {{ __('Mínimo para aprobar') }}: {{ $quizData['pass_score'] }}%
                    @if (($quizData['max_attempts'] ?? 0) > 0) · {{ __('Intentos') }}: <span x-text="attempts + '/' + quiz.max_attempts"></span>@endif</p>
            </div>
        </header>

        {{-- Estado previo / resultado --}}
        <section class="cr-lp__panel" x-show="mode === 'intro'" x-cloak>
            <template x-if="score !== null">
                <div class="cr-quiz2__last" :class="passed ? 'is-pass' : 'is-fail'">
                    <strong x-text="passed ? '{{ __('Aprobaste este test') }}' : '{{ __('Aún no aprobaste este test') }}'"></strong>
                    <span>{{ __('Tu último resultado') }}: <b x-text="score + '%'"></b></span>
                </div>
            </template>
            <div class="cr-lp__actions">
                <button type="button" class="am-btn" x-on:click="start()" x-show="canRetry" data-testid="quiz-start" x-text="attempts > 0 ? '{{ __('Volver a intentar') }}' : '{{ __('Empezar test') }}'"></button>
                <p class="cr-lp__note" x-show="!canRetry">{{ __('Ya usaste todos los intentos permitidos.') }}</p>
                @if ($nextLessonId)
                    <a href="javascript:void(0);" class="am-btn cr-lp__ghost" wire:click.prevent="nextCurriculum({{ $nextLessonId }})">{{ __('Siguiente lección') }}</a>
                @endif
            </div>
        </section>

        {{-- Respondiendo --}}
        <section class="cr-lp__panel" x-show="mode === 'taking'" x-cloak>
            <div class="cr-quiz2__bar" role="progressbar" :aria-valuenow="answered" aria-valuemin="0" :aria-valuemax="quiz.questions.length"><span :style="'width:' + (answered / quiz.questions.length * 100) + '%'"></span></div>
            <ol class="cr-quiz2__qs">
                <template x-for="(q, qi) in quiz.questions" :key="q.id">
                    <li class="cr-quiz2__q" :data-testid="'q-' + q.id">
                        <p class="cr-quiz2__qtext"><span x-text="qi + 1"></span> <b x-text="q.text"></b></p>
                        <small x-show="q.multiple">{{ __('Elige todas las que correspondan') }}</small>
                        <div class="cr-quiz2__opts">
                            <template x-for="(opt, oi) in q.options" :key="oi">
                                <label class="cr-quiz2__opt" :class="{ 'is-on': isOn(q, oi), 'is-ok': reviewState(q, oi) === 'ok', 'is-bad': reviewState(q, oi) === 'bad' }">
                                    <input :type="q.multiple ? 'checkbox' : 'radio'" :name="'q' + q.id" :checked="isOn(q, oi)" x-on:change="choose(q, oi)" :disabled="mode === 'result'">
                                    <span x-text="opt"></span>
                                </label>
                            </template>
                        </div>
                    </li>
                </template>
            </ol>
            <div class="cr-lp__actions">
                <button type="button" class="am-btn" x-on:click="submit()" :disabled="loading || answered < quiz.questions.length" data-testid="quiz-submit">
                    <span x-show="!loading && answered < quiz.questions.length">{{ __('Responde todas las preguntas') }}</span>
                    <span x-show="!loading && answered >= quiz.questions.length">{{ __('Enviar respuestas') }}</span>
                    <span x-show="loading">{{ __('Calificando…') }}</span>
                </button>
            </div>
            <p class="cr-lp__err" x-show="error" x-text="error" role="alert"></p>
        </section>

        {{-- Resultado --}}
        <section class="cr-lp__panel" x-show="mode === 'result'" x-cloak data-testid="quiz-result">
            <div class="cr-quiz2__score" :class="passed ? 'is-pass' : 'is-fail'">
                <div class="cr-quiz2__ring"><b x-text="score + '%'"></b></div>
                <div>
                    <h4 x-text="passed ? '{{ __('¡Felicidades, aprobaste!') }}' : '{{ __('Inténtalo de nuevo') }}'"></h4>
                    <p><span x-text="result.correct"></span> / <span x-text="result.total"></span> {{ __('respuestas correctas') }} · {{ __('mínimo') }} {{ $quizData['pass_score'] }}%</p>
                </div>
            </div>
            <template x-if="review">
                <ol class="cr-quiz2__qs">
                    <template x-for="(q, qi) in quiz.questions" :key="q.id">
                        <li class="cr-quiz2__q" :class="result.results[q.id] && result.results[q.id].correct ? 'is-right' : 'is-wrong'">
                            <p class="cr-quiz2__qtext"><span x-text="qi + 1"></span> <b x-text="q.text"></b></p>
                            <div class="cr-quiz2__opts">
                                <template x-for="(opt, oi) in q.options" :key="oi">
                                    <div class="cr-quiz2__opt" :class="{ 'is-on': isOn(q, oi), 'is-ok': reviewState(q, oi) === 'ok', 'is-bad': reviewState(q, oi) === 'bad' }"><span x-text="opt"></span></div>
                                </template>
                            </div>
                            <p class="cr-quiz2__why" x-show="result.results[q.id] && result.results[q.id].explanation" x-text="result.results[q.id] && result.results[q.id].explanation"></p>
                        </li>
                    </template>
                </ol>
            </template>
            <div class="cr-lp__actions">
                <button type="button" class="am-btn" x-show="canRetry && !passed" x-on:click="start()">{{ __('Volver a intentar') }}</button>
                @if ($nextLessonId)
                    <a href="javascript:void(0);" class="am-btn" wire:click.prevent="nextCurriculum({{ $nextLessonId }})">{{ __('Siguiente lección') }}</a>
                @endif
            </div>
        </section>
    @endif
</div>
