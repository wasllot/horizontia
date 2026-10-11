<div class="ht-notes" data-testid="lesson-notes" x-data="{ at: null, sync() { this.at = window.crLessonVideo ? window.crLessonVideo.time() : null; } }" x-init="sync(); setInterval(() => sync(), 1000)">
    <style>
        .ht-notes__form textarea { width: 100%; border: 1.5px solid #dfe3ec; border-radius: 10px; padding: 10px 12px; font: inherit; resize: vertical; }
        .ht-notes__bar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 8px; }
        .ht-notes__bar button { border: 0; border-radius: 10px; padding: 9px 16px; font-weight: 700; cursor: pointer; }
        .ht-notes__save { background: #fed304; color: #14213d; }
        .ht-notes__mark { background: #eef0f6; color: #14213d; }
        .ht-notes__list { list-style: none; margin: 18px 0 0; padding: 0; display: grid; gap: 10px; }
        .ht-notes__item { display: grid; grid-template-columns: auto 1fr auto; gap: 10px; align-items: start; padding: 10px 12px; border: 1px solid #e3e6ee; border-radius: 10px; background: #fff; }
        .ht-notes__time { border: 0; border-radius: 8px; padding: 3px 9px; background: #14213d; color: #fff; font-weight: 700; font-size: .82rem; cursor: pointer; }
        .ht-notes__time[disabled] { background: #eceef4; color: #5b6478; cursor: default; }
        .ht-notes__item p { margin: 0; white-space: pre-wrap; word-break: break-word; }
        .ht-notes__item .is-mark { color: #8a6d00; font-weight: 600; }
        .ht-notes__del { border: 0; background: none; color: #8a93a8; cursor: pointer; font-size: 1rem; }
        .ht-notes__empty { color: #5b6478; margin: 14px 0 0; }
        .ht-notes__err { color: #b42318; font-size: .85rem; min-height: 1.1em; display: block; }
    </style>

    <form class="ht-notes__form" wire:submit.prevent="add(at)">
        <label for="note-body-{{ $curriculumId }}" class="visually-hidden" style="position:absolute;left:-9999px">Nueva nota</label>
        <textarea id="note-body-{{ $curriculumId }}" rows="3" wire:model="body" maxlength="2000" placeholder="Escribe una nota sobre esta lección…"></textarea>
        <small class="ht-notes__err" aria-live="polite">{{ $errors->first('body') }}</small>
        <div class="ht-notes__bar">
            <button type="submit" class="ht-notes__save" wire:loading.attr="disabled" wire:target="add" data-testid="note-save">
                Guardar nota<span x-show="at !== null" x-cloak> en <span x-text="Math.floor(at/60) + ':' + String(at%60).padStart(2,'0')"></span></span>
            </button>
            <button type="button" class="ht-notes__mark" x-show="at !== null" x-cloak wire:click="bookmark(at)" data-testid="bookmark-save">🔖 Marcar este momento</button>
        </div>
    </form>

    @if ($notes->isEmpty())
        <p class="ht-notes__empty">Aún no tienes notas aquí. Son solo para ti: nadie más las ve.</p>
    @else
        <ul class="ht-notes__list" data-testid="notes-list">
            @foreach ($notes as $n)
                <li class="ht-notes__item" wire:key="note-{{ $n->id }}">
                    @if ($n->seconds !== null)
                        <button type="button" class="ht-notes__time" onclick="window.crLessonVideo && window.crLessonVideo.seek({{ (int) $n->seconds }})" title="Ir a este momento del video">{{ \App\Services\LessonProgressService::clock((int) $n->seconds) }}</button>
                    @else
                        <button type="button" class="ht-notes__time" disabled>—</button>
                    @endif
                    @if ($n->kind === 'bookmark')<p class="is-mark">🔖 Marcador</p>@else<p>{{ $n->body }}</p>@endif
                    <button type="button" class="ht-notes__del" wire:click="remove({{ $n->id }})" wire:confirm="¿Borrar esta nota?" aria-label="Borrar nota">✕</button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
