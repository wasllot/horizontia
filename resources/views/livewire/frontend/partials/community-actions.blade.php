<div class="hz-com__acts">
    @if ((int) $post->user_id !== (int) $me->id)
        <button type="button" wire:click="like({{ $post->id }})" aria-pressed="{{ in_array($post->id, $liked, true) ? 'true' : 'false' }}" data-testid="community-like">👍 Útil ({{ $post->likes_count }})</button>
    @elseif ($post->likes_count > 0)
        <span style="font-size:.82rem;color:#5b6478">👍 {{ $post->likes_count }}</span>
    @endif
    @if (!$isRoot && ($canMark ?? false))
        <button type="button" wire:click="markAnswer({{ $post->id }})" data-testid="community-mark">{{ $post->is_answer ? 'Quitar como solución' : '✓ Marcar como solución' }}</button>
    @endif
    @if ($isRoot && $moderator)
        <button type="button" wire:click="pin({{ $post->id }})" data-testid="community-pin">{{ $post->pinned ? 'Quitar fijado' : '📌 Fijar' }}</button>
    @endif
    @if ((int) $post->user_id !== (int) $me->id)
        <button type="button" wire:click="report({{ $post->id }})" wire:confirm="¿Reportar este mensaje a los administradores?">Reportar</button>
    @endif
    @if ((int) $post->user_id === (int) $me->id || $moderator)
        <button type="button" wire:click="delete({{ $post->id }})" wire:confirm="{{ $isRoot ? '¿Borrar esta pregunta y sus respuestas?' : '¿Borrar esta respuesta?' }}" data-testid="community-delete">Borrar</button>
    @endif
</div>
