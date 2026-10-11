@php $has = $lesson->type === 'scorm' && !empty($lesson->media_path); @endphp
<div class="cr-drop" x-data="{ uploading: false, progress: 0, name: '' }"
     x-on:livewire-upload-start="uploading = true; progress = 0" x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false" x-on:livewire-upload-progress="progress = $event.detail.progress">
    @if ($has)
        <div class="cr-callout cr-callout--ok">
            <x-courses::icon name="check" :size="20" />
            <div><strong>{{ __('Paquete SCORM cargado') }}</strong><small>{{ basename($lesson->media_path) }}</small></div>
            <label class="cr-btn cr-btn--sm" for="scorm-file-{{ $lesson->id }}">{{ __('Reemplazar') }}</label>
        </div>
    @else
        <label for="scorm-file-{{ $lesson->id }}" class="cr-dropzone">
            <span class="cr-dropzone__icon"><x-courses::icon name="package" :size="22" /></span>
            <strong>{{ __('Haz clic para subir tu paquete SCORM') }}</strong>
            <small>{{ __('Archivo .zip · SCORM 1.2 o 2004 (con imsmanifest.xml)') }}</small>
        </label>
    @endif
    <input type="file" id="scorm-file-{{ $lesson->id }}" class="cr-file" accept=".zip" wire:model="scormUpload" x-on:change="name = $event.target.files[0]?.name || ''">
    <p class="cr-hint" x-show="name" x-cloak><x-courses::icon name="doc" :size="14" /> <span x-text="name"></span> — {{ __('se procesará al guardar la lección.') }}</p>
    <div class="cr-progress-line" x-show="uploading" x-cloak role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"><span :style="'width:' + progress + '%'"></span><em x-text="progress + '%'"></em></div>
    @error('content.files.scorm')<p class="cr-err">{{ $message }}</p>@enderror
    @error('content.scorm')<p class="cr-err">{{ $message }}</p>@enderror
    <p class="cr-hint">{{ __('Por seguridad solo se extraen archivos web (HTML, JS, CSS, imágenes, audio, video, fuentes). Se descartan scripts de servidor.') }}</p>
</div>
