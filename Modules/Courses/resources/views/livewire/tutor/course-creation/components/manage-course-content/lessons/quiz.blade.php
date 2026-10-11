<div class="cr-quiz" x-data="crQuizBuilder(@js($content['quiz']))" wire:key="quiz-{{ $lesson->id }}">
    @php $quizErrors = collect($errors->keys())->filter(fn ($k) => str_starts_with($k, 'content.quiz'))->map(fn ($k) => $errors->first($k))->unique(); @endphp
    @if ($quizErrors->isNotEmpty())
        <div class="cr-callout cr-callout--err" role="alert"><x-courses::icon name="alert" :size="20" /><div>@foreach ($quizErrors as $msg)<p>{{ $msg }}</p>@endforeach</div></div>
    @endif

    <div class="cr-quiz__settings">
        <div class="cr-field">
            <label for="quiz-pass">{{ __('Puntaje mínimo para aprobar') }}</label>
            <div class="cr-input-suffix"><input id="quiz-pass" type="number" min="0" max="100" class="form-control" x-model.number="quiz.pass_score"><span>%</span></div>
        </div>
        <div class="cr-field">
            <label for="quiz-attempts">{{ __('Intentos permitidos') }}</label>
            <select id="quiz-attempts" class="form-select" x-model.number="quiz.max_attempts">
                <option value="0">{{ __('Ilimitados') }}</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="5">5</option>
            </select>
        </div>
        <label class="cr-switch cr-switch--inline"><input type="checkbox" class="cr-toggle" x-model="quiz.show_answers"><span>{{ __('Mostrar las respuestas correctas al terminar') }}</span></label>
    </div>

    <ol class="cr-qlist">
        <template x-for="(q, qi) in quiz.questions" :key="q.id">
            <li class="cr-q">
                <header class="cr-q__head">
                    <span class="cr-q__num" x-text="qi + 1"></span>
                    <input type="text" class="form-control cr-q__text" x-model="q.text" placeholder="{{ __('Escribe la pregunta…') }}" maxlength="500" :aria-label="'{{ __('Pregunta') }} ' + (qi + 1)">
                    <div class="cr-q__tools">
                        <button type="button" class="cr-iconbtn" x-on:click="move(qi, -1)" :disabled="qi === 0" title="{{ __('Subir') }}" aria-label="{{ __('Subir pregunta') }}"><x-courses::icon name="arrow-up" :size="15" /></button>
                        <button type="button" class="cr-iconbtn" x-on:click="move(qi, 1)" :disabled="qi === quiz.questions.length - 1" title="{{ __('Bajar') }}" aria-label="{{ __('Bajar pregunta') }}"><x-courses::icon name="arrow-down" :size="15" /></button>
                        <button type="button" class="cr-iconbtn" x-on:click="duplicate(qi)" title="{{ __('Duplicar') }}" aria-label="{{ __('Duplicar pregunta') }}"><x-courses::icon name="copy" :size="15" /></button>
                        <button type="button" class="cr-iconbtn cr-iconbtn--danger" x-on:click="remove(qi)" title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar pregunta') }}"><x-courses::icon name="trash" :size="15" /></button>
                    </div>
                </header>
                <div class="cr-q__type" role="radiogroup" aria-label="{{ __('Tipo de pregunta') }}">
                    <button type="button" class="cr-chip" :class="{ 'is-active': q.type === 'single' }" role="radio" :aria-checked="q.type === 'single'" x-on:click="setType(q, 'single')">{{ __('Una respuesta') }}</button>
                    <button type="button" class="cr-chip" :class="{ 'is-active': q.type === 'multiple' }" role="radio" :aria-checked="q.type === 'multiple'" x-on:click="setType(q, 'multiple')">{{ __('Varias respuestas') }}</button>
                    <button type="button" class="cr-chip" :class="{ 'is-active': q.type === 'true_false' }" role="radio" :aria-checked="q.type === 'true_false'" x-on:click="setType(q, 'true_false')">{{ __('Verdadero / Falso') }}</button>
                </div>
                <ul class="cr-opts">
                    <template x-for="(opt, oi) in q.options" :key="oi">
                        <li class="cr-opt" :class="{ 'is-correct': isCorrect(q, oi) }">
                            <label class="cr-opt__mark" :title="'{{ __('Marcar como correcta') }}'">
                                <input :type="q.type === 'multiple' ? 'checkbox' : 'radio'" :name="'ok-' + q.id" :checked="isCorrect(q, oi)" x-on:change="toggleCorrect(q, oi)" :aria-label="'{{ __('Respuesta correcta') }}: ' + opt">
                            </label>
                            <input type="text" class="form-control" x-model="q.options[oi]" :readonly="q.type === 'true_false'" placeholder="{{ __('Opción') }}" maxlength="300" :aria-label="'{{ __('Opción') }} ' + (oi + 1)">
                            <button type="button" class="cr-iconbtn" x-show="q.type !== 'true_false' && q.options.length > 2" x-on:click="removeOption(q, oi)" aria-label="{{ __('Quitar opción') }}"><x-courses::icon name="x" :size="14" /></button>
                        </li>
                    </template>
                </ul>
                <div class="cr-q__foot">
                    <button type="button" class="cr-add" x-show="q.type !== 'true_false' && q.options.length < 8" x-on:click="addOption(q)"><x-courses::icon name="plus" :size="14" /> {{ __('Añadir opción') }}</button>
                    <details class="cr-more" :open="!!q.explanation">
                        <summary>{{ __('Explicación (se muestra tras responder)') }}</summary>
                        <textarea class="form-control" rows="2" x-model="q.explanation" maxlength="500" placeholder="{{ __('Por qué esta es la respuesta correcta…') }}"></textarea>
                    </details>
                </div>
            </li>
        </template>
    </ol>

    <p class="cr-hint" x-show="quiz.questions.length === 0">{{ __('Aún no hay preguntas. Añade la primera para crear el test.') }}</p>
    <button type="button" class="cr-add cr-add--block" x-on:click="add()"><x-courses::icon name="plus" :size="16" /> {{ __('Añadir pregunta') }}</button>
    <p class="cr-hint"><x-courses::icon name="check" :size="14" /> {{ __('Las respuestas correctas nunca se envían al navegador del alumno: la calificación se calcula en el servidor.') }}</p>
</div>
