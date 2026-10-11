<div class="hz-com" data-testid="community">
    <style>
        .hz-com__bar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 14px; }
        .hz-com__bar input[type=search] { flex: 1 1 200px; border: 1.5px solid #dfe3ec; border-radius: 10px; padding: 9px 12px; }
        .hz-com__chip { border: 1px solid #dfe3ec; background: #fff; border-radius: 999px; padding: 5px 12px; cursor: pointer; font-weight: 600; font-size: .85rem; color: #5b6478; }
        .hz-com__chip.is-on { background: #14213d; border-color: #14213d; color: #fff; }
        .hz-com__btn { border: 0; border-radius: 10px; padding: 9px 16px; background: #fed304; color: #14213d; font-weight: 700; cursor: pointer; }
        .hz-com__btn--ghost { background: #eef0f6; }
        .hz-com__list { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
        .hz-com__item { padding: 12px 14px; border: 1px solid #e3e6ee; border-radius: 12px; background: #fff; cursor: pointer; }
        .hz-com__item h4 { margin: 0 0 4px; font-size: 1rem; }
        .hz-com__meta { display: flex; flex-wrap: wrap; gap: 6px 12px; color: #5b6478; font-size: .82rem; align-items: center; }
        .hz-com__tag { padding: 1px 8px; border-radius: 999px; background: #fff3bf; color: #6b5300; font-weight: 700; font-size: .75rem; }
        .hz-com__tag--ok { background: #d9f0e1; color: #1b6b3a; }
        .hz-com__tag--inst { background: #14213d; color: #fff; }
        .hz-com__post { padding: 14px 16px; border: 1px solid #e3e6ee; border-radius: 12px; background: #fff; margin-bottom: 10px; }
        .hz-com__post.is-answer { border-color: #1b6b3a; background: #f3faf5; }
        .hz-com__text { white-space: pre-wrap; word-break: break-word; margin: 8px 0; }
        .hz-com__acts { display: flex; flex-wrap: wrap; gap: 4px 14px; }
        .hz-com__acts button { border: 0; background: none; color: #5b6478; cursor: pointer; font-size: .82rem; padding: 2px 0; }
        .hz-com__acts button:hover { color: #14213d; text-decoration: underline; }
        .hz-com__form input, .hz-com__form textarea, .hz-com__form select { width: 100%; border: 1.5px solid #dfe3ec; border-radius: 10px; padding: 9px 12px; font: inherit; margin-bottom: 4px; }
        .hz-com__err { color: #b42318; font-size: .85rem; min-height: 1.1em; display: block; }
        .hz-com__empty { color: #5b6478; padding: 18px 0; }
    </style>

    @if ($mode === 'new')
        <form class="hz-com__form" wire:submit="ask" data-testid="community-new">
            <h3>Hacer una pregunta</h3>
            <label for="cm-title">Título</label>
            <input id="cm-title" wire:model="title" maxlength="140" placeholder="Ej. ¿Cómo aplico el método de la lección 3?">
            <small class="hz-com__err" aria-live="polite">{{ $errors->first('title') }}</small>
            <label for="cm-body">Tu duda</label>
            <textarea id="cm-body" rows="5" wire:model="body" maxlength="3000" placeholder="Cuéntanos con detalle qué intentaste y qué no te quedó claro."></textarea>
            <small class="hz-com__err" aria-live="polite">{{ $errors->first('body') }}</small>
            <label for="cm-lesson">¿De qué lección? (opcional)</label>
            <select id="cm-lesson" wire:model="lessonId"><option value="">Del curso en general</option>@foreach ($lessons as $l)<option value="{{ $l->id }}">{{ html_entity_decode($l->title) }}</option>@endforeach</select>
            <div class="hz-com__bar" style="margin-top:10px">
                <button type="submit" class="hz-com__btn" wire:loading.attr="disabled" wire:target="ask" data-testid="community-publish">Publicar pregunta</button>
                <button type="button" class="hz-com__btn hz-com__btn--ghost" wire:click="back">Cancelar</button>
            </div>
        </form>
    @elseif ($mode === 'thread' && isset($root))
        <div data-testid="community-thread">
            <button type="button" class="hz-com__btn hz-com__btn--ghost" wire:click="back">← Todas las preguntas</button>
            <article class="hz-com__post" style="margin-top:12px">
                <h3 style="margin:0 0 4px">{{ $root->title }}</h3>
                <div class="hz-com__meta">
                    <strong>{{ $service::displayName($root->author) }}</strong>
                    @if ((int) $root->user_id === (int) $course->instructor_id)<span class="hz-com__tag hz-com__tag--inst">Instructor</span>@endif
                    <span>{{ $root->created_at->locale('es')->diffForHumans() }}</span>
                    @if ($root->pinned)<span class="hz-com__tag">📌 Fijada</span>@endif
                    @if ($lessonTitle)<span>Lección: {{ html_entity_decode($lessonTitle) }}</span>@endif
                </div>
                <p class="hz-com__text">{{ $root->body }}</p>
                @include('livewire.frontend.partials.community-actions', ['post' => $root, 'liked' => $liked, 'isRoot' => true])
            </article>

            <h4>{{ $replies->count() }} {{ $replies->count() === 1 ? 'respuesta' : 'respuestas' }}</h4>
            @foreach ($replies as $r)
                <article class="hz-com__post {{ $r->is_answer ? 'is-answer' : '' }}" wire:key="reply-{{ $r->id }}" data-testid="community-reply">
                    <div class="hz-com__meta">
                        <strong>{{ $service::displayName($r->author) }}</strong>
                        @if ((int) $r->user_id === (int) $course->instructor_id)<span class="hz-com__tag hz-com__tag--inst">Instructor</span>@endif
                        @if ($r->is_answer)<span class="hz-com__tag hz-com__tag--ok">✓ Solución</span>@endif
                        <span>{{ $r->created_at->locale('es')->diffForHumans() }}</span>
                    </div>
                    <p class="hz-com__text">{{ $r->body }}</p>
                    @include('livewire.frontend.partials.community-actions', ['post' => $r, 'liked' => $liked, 'isRoot' => false, 'canMark' => $moderator || (int) $root->user_id === (int) $me->id])
                </article>
            @endforeach

            <form class="hz-com__form" wire:submit="reply" data-testid="community-reply-form">
                <label for="cm-reply">Tu respuesta</label>
                <textarea id="cm-reply" rows="3" wire:model="replyBody" maxlength="3000" placeholder="Escribe tu respuesta…"></textarea>
                <small class="hz-com__err" aria-live="polite">{{ $errors->first('replyBody') }}</small>
                <button type="submit" class="hz-com__btn" wire:loading.attr="disabled" wire:target="reply" data-testid="community-send">Responder</button>
            </form>
        </div>
    @else
        <div class="hz-com__bar">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar en las preguntas…" aria-label="Buscar en la comunidad">
            <button type="button" class="hz-com__btn" wire:click="startNew" data-testid="community-ask">Hacer una pregunta</button>
        </div>
        <div class="hz-com__bar" role="group" aria-label="Filtrar">
            @foreach (['all' => 'Todas', 'unanswered' => 'Sin responder', 'solved' => 'Resueltas'] as $k => $label)
                <button type="button" class="hz-com__chip {{ $filter === $k ? 'is-on' : '' }}" wire:click="setFilter('{{ $k }}')" aria-pressed="{{ $filter === $k ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
        @if ($posts->isEmpty())
            <p class="hz-com__empty" data-testid="community-empty">{{ $search !== '' || $filter !== 'all' ? 'No hay preguntas con ese filtro.' : 'Aún no hay preguntas. ¡Sé la primera persona en preguntar!' }}</p>
        @else
            <ul class="hz-com__list" data-testid="community-list">
                @foreach ($posts as $p)
                    <li class="hz-com__item" wire:key="post-{{ $p->id }}" wire:click="open({{ $p->id }})" role="button" tabindex="0" x-on:keydown.enter="$wire.open({{ $p->id }})">
                        <h4>{{ $p->title }}</h4>
                        <div class="hz-com__meta">
                            <span>{{ $service::displayName($p->author) }}</span>
                            @if ((int) $p->user_id === (int) $course->instructor_id)<span class="hz-com__tag hz-com__tag--inst">Instructor</span>@endif
                            <span>{{ $p->replies_count }} {{ $p->replies_count === 1 ? 'respuesta' : 'respuestas' }}</span>
                            @if ($p->solved)<span class="hz-com__tag hz-com__tag--ok">✓ Resuelta</span>@endif
                            @if ($p->pinned)<span class="hz-com__tag">📌 Fijada</span>@endif
                            <span>{{ $p->last_activity_at?->locale('es')->diffForHumans() }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if ($total > $posts->count())
                <button type="button" class="hz-com__btn hz-com__btn--ghost" style="margin-top:12px" wire:click="more">Ver más preguntas</button>
            @endif
        @endif
    @endif
</div>
