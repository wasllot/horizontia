@php
    $sources = \Modules\Courses\Services\Lessons\LessonType::VIDEO_SOURCES;
    $existing = ($lesson->type === 'video' && !empty($lesson->media_path) && lessonMediaExists($lesson->media_path)) ? lessonMediaUrl($lesson->media_path) : null;
    $exts = \Modules\Courses\Services\Lessons\VideoFileHandler::allowedExtensions();
    $max = \Modules\Courses\Services\Lessons\VideoFileHandler::maxMegabytes();
@endphp
<div class="cr-sources" role="radiogroup" aria-label="{{ __('Origen del video') }}">
    @foreach ($sources as $key => $label)
        <button type="button" role="radio" aria-checked="{{ $source === $key ? 'true' : 'false' }}" class="cr-chip @if ($source === $key) is-active @endif" wire:click="setSource('{{ $key }}')">{{ __($label) }}</button>
    @endforeach
</div>

@if ($source === 'video')
    <div class="cr-drop" x-data="crVideoPicker(@js($existing))" x-on:cr-video-rejected.window="reset()"
         x-on:livewire-upload-start="uploading = true; progress = 0" x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false"
         x-on:livewire-upload-progress="progress = $event.detail.progress"
         x-on:dragover.prevent="over = true" x-on:dragleave.prevent="over = false" x-on:drop.prevent="over = false; pick($event.dataTransfer.files[0], $refs.file)">
        <template x-if="url">
            <div class="cr-video-preview">
                <video :src="url" controls preload="metadata" x-on:loadedmetadata="duration($event.target.duration)"></video>
                <div class="cr-video-preview__bar">
                    <span x-text="name || '{{ __('Video actual') }}'"></span>
                    <label class="cr-btn cr-btn--sm" for="video-file-{{ $lesson->id }}">{{ __('Reemplazar') }}</label>
                </div>
            </div>
        </template>
        <label for="video-file-{{ $lesson->id }}" class="cr-dropzone" :class="{ 'is-over': over }" x-show="!url">
            <span class="cr-dropzone__icon"><x-courses::icon name="upload" :size="22" /></span>
            <strong>{{ __('Arrastra tu video aquí o haz clic para seleccionarlo') }}</strong>
            <small>{{ strtoupper(implode(', ', $exts)) }} · {{ __('máx.') }} {{ $max }} MB</small>
        </label>
        <input type="file" id="video-file-{{ $lesson->id }}" class="cr-file" accept="{{ implode(',', array_map(fn ($e) => '.' . $e, $exts)) }}" wire:model="videoUpload" x-ref="file" x-on:change="pick($event.target.files[0])">
        <div class="cr-progress-line" x-show="uploading" x-cloak role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"><span :style="'width:' + progress + '%'"></span><em x-text="progress + '%'"></em></div>
        @error('content.files.video')<p class="cr-err">{{ $message }}</p>@enderror
    </div>
@else
    @php
        $field = ['yt_link' => 'yt_link', 'vm_link' => 'vm_link', 'genially_link' => 'genially_link'][$source];
        $ph = ['yt_link' => 'https://www.youtube.com/watch?v=…', 'vm_link' => 'https://vimeo.com/123456789', 'genially_link' => 'https://view.genially.com/…'][$source];
    @endphp
    <div class="cr-field">
        <label for="lesson-link">{{ __('Enlace del video') }} <span class="cr-req">*</span></label>
        <div class="cr-input-icon"><x-courses::icon name="link" :size="16" />
            <input id="lesson-link" type="url" class="form-control" placeholder="{{ $ph }}" wire:model.live.blur="content.{{ $field }}" x-on:paste="$nextTick(() => $el.blur())" x-on:keydown.enter.prevent="$el.blur()" autocomplete="off">
        </div>
        @error('content.' . $field)<p class="cr-err">{{ $message }}</p>@enderror
    </div>
    @if ($embed)
        <div class="cr-embed" wire:key="embed-{{ md5($embed) }}">
            <iframe src="{{ $embed }}" title="{{ __('Vista previa') }}" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"></iframe>
        </div>
    @endif
    <div class="cr-field cr-field--narrow">
        <label for="lesson-duration">{{ __('Duración (minutos)') }} <small>{{ __('opcional') }}</small></label>
        <input id="lesson-duration" type="number" min="0" max="1440" class="form-control" wire:model="content.duration_min" placeholder="10">
        <p class="cr-hint">{{ __('Se usa para calcular el progreso del alumno en el curso.') }}</p>
    </div>
@endif
