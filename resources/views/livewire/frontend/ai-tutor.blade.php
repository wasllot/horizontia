<div class="ht-tutor" x-data="{ open: @entangle('open') }" x-init="$watch('open', () => $nextTick(() => { const b = $refs.body; if (b) b.scrollTop = b.scrollHeight; }))">
    <style>
        .ht-tutor { position: fixed; right: 20px; bottom: 20px; z-index: 1050; font-family: inherit; }
        .ht-tutor__fab { display: inline-flex; align-items: center; gap: 8px; border: 0; border-radius: 999px; padding: 12px 18px; background: #fed304; color: #14213d; font-weight: 700; box-shadow: 0 8px 24px rgba(20,33,61,.3); cursor: pointer; }
        .ht-tutor__panel { width: min(380px, calc(100vw - 24px)); height: min(560px, calc(100vh - 100px)); background: #fff; border-radius: 16px; box-shadow: 0 16px 48px rgba(20,33,61,.35); display: flex; flex-direction: column; overflow: hidden; }
        .ht-tutor__head { background: #14213d; color: #fff; padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; }
        .ht-tutor__head strong { font-size: 15px; }
        .ht-tutor__head button { background: none; border: 0; color: #aab3c5; cursor: pointer; font-size: 13px; margin-left: 10px; }
        .ht-tutor__body { flex: 1; overflow-y: auto; padding: 14px; background: #f6f7fb; }
        .ht-tutor__msg { max-width: 88%; padding: 9px 12px; border-radius: 12px; margin-bottom: 10px; font-size: 14px; line-height: 1.45; white-space: pre-wrap; word-wrap: break-word; }
        .ht-tutor__msg--user { margin-left: auto; background: #14213d; color: #fff; border-bottom-right-radius: 4px; }
        .ht-tutor__msg--model { background: #fff; color: #1f2937; border: 1px solid #e5e7eb; border-bottom-left-radius: 4px; }
        .ht-tutor__hint { color: #6b7280; font-size: 13px; text-align: center; padding: 18px 8px; }
        .ht-tutor__error { color: #b91c1c; font-size: 13px; padding: 4px 2px 8px; }
        .ht-tutor__form { display: flex; gap: 8px; padding: 10px; border-top: 1px solid #e5e7eb; background: #fff; }
        .ht-tutor__form input { flex: 1; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; }
        .ht-tutor__form button { border: 0; border-radius: 10px; padding: 0 14px; background: #14213d; color: #fff; font-weight: 600; cursor: pointer; }
        .ht-tutor__form button[disabled] { opacity: .6; cursor: progress; }
    </style>

    <button type="button" class="ht-tutor__fab" x-show="!open" @click="open = true" aria-label="{{ __('courses::courses.tutor_open') }}">
        <span aria-hidden="true" style="display:inline-flex"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9L12 2zm7 11l.9 2.6 2.6.9-2.6.9L19 20l-.9-2.6-2.6-.9 2.6-.9L19 13z"/></svg></span> {{ __('courses::courses.tutor_title') }}
    </button>

    <section class="ht-tutor__panel" x-show="open" x-cloak role="dialog" aria-label="{{ __('courses::courses.tutor_title') }}">
        <header class="ht-tutor__head">
            <strong style="display:inline-flex;align-items:center;gap:8px"><span style="display:inline-flex;color:#fed304"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9L12 2zm7 11l.9 2.6 2.6.9-2.6.9L19 20l-.9-2.6-2.6-.9 2.6-.9L19 13z"/></svg></span>{{ __('courses::courses.tutor_title') }}</strong>
            <span>
                @if (count($messages))
                    <button type="button" wire:click="clear">{{ __('courses::courses.tutor_clear') }}</button>
                @endif
                <button type="button" @click="open = false" aria-label="{{ __('courses::courses.tutor_close') }}">✕</button>
            </span>
        </header>

        <div class="ht-tutor__body" x-ref="body">
            @forelse ($messages as $item)
                <div class="ht-tutor__msg ht-tutor__msg--{{ $item['role'] }}">{{ $item['text'] }}</div>
            @empty
                <p class="ht-tutor__hint">{{ __('courses::courses.tutor_hint') }}</p>
            @endforelse
            <div wire:loading wire:target="send" class="ht-tutor__msg ht-tutor__msg--model">{{ __('courses::courses.tutor_thinking') }}</div>
            @if ($error)
                <p class="ht-tutor__error">{{ $error }}</p>
            @endif
            @error('message')
                <p class="ht-tutor__error">{{ $message }}</p>
            @enderror
        </div>

        <form class="ht-tutor__form" wire:submit="send">
            <input type="text" wire:model="message" maxlength="{{ \App\Services\GeminiTutorService::MAX_MESSAGE }}" placeholder="{{ __('courses::courses.tutor_placeholder') }}" autocomplete="off">
            <button type="submit" wire:loading.attr="disabled" wire:target="send">{{ __('courses::courses.tutor_send') }}</button>
        </form>
    </section>
</div>
