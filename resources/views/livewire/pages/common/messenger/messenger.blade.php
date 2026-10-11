@php
    $tz = config('academy.timezone', 'America/Mexico_City');
    $stamp = fn ($d) => $d->copy()->timezone($tz);
    $short = function ($d) use ($stamp) {
        $d = $stamp($d);
        return $d->isToday() ? $d->format('H:i') : ($d->isYesterday() ? 'Ayer' : $d->locale('es')->translatedFormat('j M'));
    };
    $dayLabel = function ($d) use ($stamp) {
        $d = $stamp($d);
        return $d->isToday() ? 'Hoy' : ($d->isYesterday() ? 'Ayer' : $d->locale('es')->translatedFormat('l j \d\e F'));
    };
    $initials = fn ($u) => mb_strtoupper(mb_substr(\App\Services\ChatService::displayName($u), 0, 1));
    $hue = fn ($u) => (($u?->id ?? 0) * 47) % 360;
@endphp
<div class="hz-msg {{ $active ? 'has-thread' : '' }}" data-testid="messenger" wire:poll.5s="refresh"
     x-data="{ stick: true, el: null,
        toBottom() { this.$nextTick(() => { const b = this.$refs.thread; if (b) b.scrollTop = b.scrollHeight; }); } }"
     x-init="Livewire.hook('morph.updated', () => { if (stick) toBottom(); })"
     @chat-scroll.window="stick = true; toBottom()">

    {{-- ───────── Lista de conversaciones ───────── --}}
    <aside class="hz-msg__side" aria-label="Conversaciones">
        <header class="hz-msg__sidehead">
            <h2>Mensajes</h2>
            <button type="button" class="hz-msg__new" wire:click="openNew" data-testid="chat-new" aria-label="Nueva conversación" title="Nueva conversación">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            </button>
        </header>
        <div class="hz-msg__search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" wire:model.live.debounce.250ms="search" placeholder="Buscar conversación…" aria-label="Buscar conversación">
        </div>

        <ul class="hz-msg__list" data-testid="chat-list">
            @forelse ($list as $c)
                @php $o = $others[$c->otherId($me)] ?? null; $n = $unread[$c->id] ?? 0; @endphp
                <li wire:key="conv-{{ $c->id }}">
                    <button type="button" class="hz-msg__item {{ $active?->id === $c->id ? 'is-active' : '' }} {{ $n ? 'is-unread' : '' }}" wire:click="open({{ $c->id }})" data-testid="chat-item">
                        <span class="hz-msg__avatar" style="--h: {{ $hue($o) }}">{{ $initials($o) }}</span>
                        <span class="hz-msg__meta">
                            <span class="hz-msg__row"><strong>{{ \App\Services\ChatService::displayName($o) }}</strong><time>{{ $short($c->last_message_at) }}</time></span>
                            <span class="hz-msg__row"><span class="hz-msg__preview">@if ((int) $c->last_sender_id === (int) $me->id)Tú: @endif{{ $c->last_preview }}</span>@if ($n)<em class="hz-msg__badge" aria-label="{{ $n }} sin leer">{{ $n }}</em>@endif</span>
                        </span>
                    </button>
                </li>
            @empty
                <li class="hz-msg__empty">
                    @if ($search !== '')<p>No hay conversaciones con ese nombre.</p>
                    @else<p><strong>Aún no tienes mensajes.</strong></p><p>Pulsa <b>+</b> para escribirle a {{ $me->role === 'student' ? 'tus instructores o a soporte' : ($me->role === 'tutor' ? 'tus alumnos o a soporte' : 'cualquier persona') }}.</p>@endif
                </li>
            @endforelse
        </ul>
    </aside>

    {{-- ───────── Hilo abierto ───────── --}}
    <section class="hz-msg__main" aria-label="Conversación">
        @if ($active && isset($otherUser))
            <header class="hz-msg__head">
                <button type="button" class="hz-msg__back" wire:click="close" aria-label="Volver a las conversaciones">←</button>
                <span class="hz-msg__avatar" style="--h: {{ $hue($otherUser) }}">{{ $initials($otherUser) }}</span>
                <div>
                    <strong>{{ \App\Services\ChatService::displayName($otherUser) }}</strong>
                    <small>{{ \App\Services\ChatService::roleLabel($otherUser) }}</small>
                </div>
            </header>

            <div class="hz-msg__thread" x-ref="thread" @scroll.passive="stick = ($refs.thread.scrollHeight - $refs.thread.scrollTop - $refs.thread.clientHeight) < 90" x-init="toBottom()" data-testid="chat-thread">
                @if ($hasMore)<button type="button" class="hz-msg__more" wire:click="earlier">Ver mensajes anteriores</button>@endif
                @php $lastDay = null; @endphp
                @foreach ($messages as $m)
                    @php $mine = (int) $m->sender_id === (int) $me->id; $day = $stamp($m->created_at)->toDateString(); @endphp
                    @if ($day !== $lastDay)<div class="hz-msg__day"><span>{{ $dayLabel($m->created_at) }}</span></div>@php $lastDay = $day; @endphp @endif
                    <div class="hz-msg__bubblewrap {{ $mine ? 'is-mine' : '' }}" wire:key="msg-{{ $m->id }}" data-testid="chat-message">
                        <div class="hz-msg__bubble">
                            @if ($m->file_path)
                                @if ($m->isImage())
                                    <a href="{{ route('chat.file', $m->id) }}" target="_blank" rel="noopener"><img src="{{ route('chat.file', $m->id) }}" alt="{{ $m->file_name }}" loading="lazy" class="hz-msg__img"></a>
                                @else
                                    <a class="hz-msg__file" href="{{ route('chat.file', $m->id) }}" download><span aria-hidden="true">📄</span><span><b>{{ $m->file_name }}</b><small>{{ number_format(($m->file_size ?? 0) / 1024, 0) }} KB</small></span></a>
                                @endif
                            @endif
                            @if ($m->body)<p>{!! \App\Services\ChatService::render($m->body) !!}</p>@endif
                            <span class="hz-msg__foot">
                                {{ $stamp($m->created_at)->format('H:i') }}
                                @if ($mine)<span class="hz-msg__ticks {{ $m->read_at ? 'is-read' : '' }}" title="{{ $m->read_at ? 'Leído' : 'Enviado' }}" aria-label="{{ $m->read_at ? 'Leído' : 'Enviado' }}">{{ $m->read_at ? '✓✓' : '✓' }}</span>@endif
                            </span>
                            @if ($mine)<button type="button" class="hz-msg__del" wire:click="deleteMessage({{ $m->id }})" wire:confirm="¿Borrar este mensaje para los dos?" aria-label="Borrar mensaje">🗑</button>@endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($canReply)
                <form class="hz-msg__composer" wire:submit="send" data-testid="chat-composer">
                    @if ($upload)
                        <div class="hz-msg__attach"><span>📎 {{ $upload->getClientOriginalName() }}</span><button type="button" wire:click="removeUpload" aria-label="Quitar archivo">✕</button></div>
                    @endif
                    <small class="hz-msg__err" role="alert" aria-live="polite">{{ $error }}</small>
                    <div class="hz-msg__inputrow">
                        <label class="hz-msg__clip" title="Adjuntar un archivo" aria-label="Adjuntar un archivo">
                            <input type="file" wire:model="upload" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt" hidden data-testid="chat-file">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 11-8.5 8.5a5 5 0 0 1-7-7L14 4a3.5 3.5 0 0 1 5 5l-8.6 8.6a2 2 0 0 1-3-3L15 7"/></svg>
                        </label>
                        <textarea wire:model="body" rows="1" maxlength="2000" placeholder="Escribe un mensaje…" aria-label="Mensaje" data-testid="chat-input"
                                  x-data x-init="$el.style.height='auto'" @input="$el.style.height='auto'; $el.style.height = Math.min($el.scrollHeight, 140) + 'px'"
                                  @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $el.closest('form').requestSubmit(); }"
                                  @chat-scroll.window="$el.style.height='auto'"></textarea>
                        <button type="submit" class="hz-msg__send" wire:loading.attr="disabled" wire:target="send,upload" data-testid="chat-send" aria-label="Enviar">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.4 20.4 21 12 3.4 3.6 3.3 10l12.6 2-12.6 2z"/></svg>
                        </button>
                    </div>
                    <small class="hz-msg__hint">Enter envía · Shift + Enter hace un salto de línea</small>
                </form>
            @else
                <p class="hz-msg__locked">Ya no puedes escribirle a esta persona (por ejemplo, porque ya no comparten un curso). Puedes releer la conversación.</p>
            @endif
        @else
            <div class="hz-msg__welcome" data-testid="chat-welcome">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/><path d="M8.5 11h7M8.5 14h4"/></svg>
                <h3>Tus mensajes</h3>
                <p>Elige una conversación o empieza una nueva para resolver dudas con tus {{ $me->role === 'student' ? 'instructores' : 'alumnos' }} o con soporte.</p>
                <button type="button" class="hz-msg__cta" wire:click="openNew">Nueva conversación</button>
            </div>
        @endif
    </section>

    {{-- ───────── Nueva conversación ───────── --}}
    @if ($newOpen)
        <div class="hz-msg__modal" role="dialog" aria-modal="true" aria-label="Nueva conversación" @keydown.escape.window="$wire.closeNew()" data-testid="chat-contacts">
            <div class="hz-msg__modalbox">
                <header><h3>Nueva conversación</h3><button type="button" wire:click="closeNew" aria-label="Cerrar">✕</button></header>
                <input type="search" wire:model.live.debounce.250ms="contactSearch" placeholder="Buscar por nombre…" aria-label="Buscar persona" autofocus>
                <ul>
                    @forelse ($contacts as $u)
                        <li wire:key="ct-{{ $u->id }}">
                            <button type="button" wire:click="startWith({{ $u->id }})" data-testid="chat-contact">
                                <span class="hz-msg__avatar" style="--h: {{ $hue($u) }}">{{ $initials($u) }}</span>
                                <span><strong>{{ \App\Services\ChatService::displayName($u) }}</strong><small>{{ \App\Services\ChatService::roleLabel($u) }}</small></span>
                            </button>
                        </li>
                    @empty
                        <li class="hz-msg__empty"><p>{{ $contactSearch !== '' ? 'No encontramos a nadie con ese nombre.' : 'Todavía no tienes personas a quienes escribir. Aparecerán cuando te inscribas en un curso o tengas alumnos.' }}</p></li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/messenger.css') }}?v={{ @filemtime(public_path('css/messenger.css')) }}">
@endpush
