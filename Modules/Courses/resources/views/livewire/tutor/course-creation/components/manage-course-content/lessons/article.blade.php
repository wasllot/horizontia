<div class="cr-field" x-data="crArticleEditor(@js($content['article'] ?? ''), '{{ route('courses.editor.upload-image') }}')" :class="{ 'is-fullscreen': full }" wire:key="article-{{ $lesson->id }}">
    <div class="cr-field__row">
        <label>{{ __('Contenido del artículo') }} <span class="cr-req">*</span></label>
        <button type="button" class="cr-btn cr-btn--sm" x-on:click="toggle()"><x-courses::icon name="expand" :size="14" /> <span x-text="full ? '{{ __('Salir de pantalla completa') }}' : '{{ __('Pantalla completa') }}'"></span></button>
    </div>
    <div wire:ignore class="cr-article"><textarea x-ref="editor" id="article-editor-{{ $lesson->id }}"></textarea></div>
    @error('content.article')<p class="cr-err">{{ $message }}</p>@enderror
</div>
