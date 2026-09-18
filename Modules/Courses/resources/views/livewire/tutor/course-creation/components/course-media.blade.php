@push('styles')
    @vite(['public/css/venobox.min.css'])
    <link rel="stylesheet" href="{{ asset('modules/courses/css/main.css') }}">
<style>
/* ── Media form ──────────────────────────────────────────────────── */
.cr-media-page { display: flex; flex-direction: column; gap: 24px; }

.cr-media-card {
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(20,33,61,.05);
}

.cr-media-card-head {
    display: flex; align-items: center; gap: 14px;
    padding: 20px 24px 18px;
    border-bottom: 1px solid #f0f3f8;
    background: linear-gradient(135deg, #14213d 0%, #1e2f55 100%);
}
.cr-media-card-head .cr-mh-icon {
    width: 44px; height: 44px; border-radius: 11px;
    background: rgba(254,211,4,.15); border: 1px solid rgba(254,211,4,.3);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.cr-media-card-head .cr-mh-icon svg { width: 22px; height: 22px; }
.cr-media-card-head .cr-mh-text h3 { margin: 0 0 2px; font-size: 15px; font-weight: 700; color: #fff; }
.cr-media-card-head .cr-mh-text p  { margin: 0; font-size: 12px; color: #aab3c5; line-height: 1.4; }
.cr-media-card-head .cr-mh-required {
    margin-left: auto; background: #fed304; color: #14213d;
    font-size: 10px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
    padding: 3px 8px; border-radius: 5px; flex-shrink: 0;
}

.cr-media-card-body { padding: 24px; }

.cr-media-specs {
    display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px;
}
.cr-media-spec-tag {
    display: inline-flex; align-items: center; gap: 5px;
    background: #f4f6fb; border: 1px solid #e4e9f0;
    border-radius: 6px; padding: 4px 10px;
    font-size: 11px; font-weight: 600; color: #5a6480;
}
.cr-media-spec-tag svg { width: 11px; height: 11px; color: #8a95b0; }

/* ── Drop zone ───────────────────────────────────────────────────── */
.cr-dropzone {
    border: 2px dashed #d5dce8;
    border-radius: 12px; padding: 32px 24px; text-align: center;
    cursor: pointer; background: #f9fafc;
    transition: border-color .2s, background .2s;
    position: relative; overflow: hidden;
}
.cr-dropzone:hover  { border-color: #fed304; background: #fffef5; }
.cr-dropzone.is-drag { border-color: #fed304; background: #fffef5; }
.cr-dropzone label {
    cursor: pointer; display: block;
    font-size: 14px; color: #3d4a63; margin: 0;
}
.cr-dropzone label em { color: #14213d; font-style: normal; font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
.cr-dropzone label span { display: block; font-size: 12px; color: #8a95b0; margin-top: 4px; }
.cr-dz-icon {
    width: 52px; height: 52px; border-radius: 13px;
    background: #14213d; display: flex; align-items: center;
    justify-content: center; margin: 0 auto 12px;
}
.cr-dz-hint { font-size: 11px; color: #aab3c5; margin-top: 8px; }

/* ── Upload loader overlay ───────────────────────────────────────── */
.cr-upload-loading {
    position: absolute; inset: 0;
    background: rgba(255,255,255,.92);
    display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 12px;
    border-radius: 10px; z-index: 10;
}
.cr-upload-loading .cr-spinner {
    width: 40px; height: 40px;
    border: 3px solid #e4e9f0;
    border-top-color: #14213d;
    border-radius: 50%;
    animation: cr-spin .7s linear infinite;
}
@keyframes cr-spin { to { transform: rotate(360deg); } }
.cr-upload-loading span { font-size: 13px; font-weight: 600; color: #14213d; }

/* ── Thumbnail preview ───────────────────────────────────────────── */
.cr-thumb-wrap { border-radius: 12px; overflow: hidden; border: 1px solid #e4e9f0; }
.cr-thumb-wrap img { width: 100%; height: 220px; object-fit: cover; display: block; background: #14213d; }
.cr-thumb-bar {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px; background: #f4f6fb; border-top: 1px solid #e4e9f0;
}
.cr-thumb-bar .cr-thumb-info { flex: 1; min-width: 0; }
.cr-thumb-bar .cr-thumb-info strong {
    display: block; font-size: 13px; font-weight: 600; color: #14213d;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.cr-thumb-bar .cr-thumb-info em { font-style: normal; font-size: 11px; color: #8a95b0; }
.cr-thumb-bar .cr-ok-badge {
    background: #d1fae5; color: #065f46; border-radius: 5px;
    font-size: 11px; font-weight: 700; padding: 2px 8px; flex-shrink: 0;
}
.cr-thumb-bar .cr-change-btn {
    background: #14213d; color: #fed304;
    border: none; border-radius: 8px; padding: 7px 14px;
    font-size: 12px; font-weight: 700; cursor: pointer;
    white-space: nowrap; flex-shrink: 0;
    transition: background .2s;
}
.cr-thumb-bar .cr-change-btn:hover { background: #1e2f55; }
.cr-thumb-bar .cr-del-btn {
    width: 34px; height: 34px; border-radius: 8px; flex-shrink: 0;
    background: #fff5f5; border: 1px solid #fecaca; color: #dc2626;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .2s;
}
.cr-thumb-bar .cr-del-btn:hover { background: #fecaca; }

/* ── Video uploaded state ────────────────────────────────────────── */
.cr-video-uploaded {
    border: 1px solid #e4e9f0; border-radius: 12px; overflow: hidden;
}
.cr-video-uploaded-body {
    display: flex; align-items: center; gap: 14px; padding: 16px 18px;
    background: #f9fafc;
}
.cr-video-icon-wrap {
    width: 46px; height: 46px; border-radius: 11px; flex-shrink: 0;
    background: #14213d; display: flex; align-items: center; justify-content: center;
}
.cr-video-info { flex: 1; min-width: 0; }
.cr-video-info strong {
    display: block; font-size: 13px; font-weight: 700; color: #14213d;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.cr-video-info span { font-size: 12px; color: #8a95b0; }
.cr-video-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.cr-video-preview-btn {
    padding: 6px 12px; border: 1px solid #e4e9f0; border-radius: 8px;
    font-size: 12px; font-weight: 600; color: #14213d; background: #fff;
    text-decoration: none; white-space: nowrap; transition: border-color .2s;
}
.cr-video-preview-btn:hover { border-color: #14213d; }
.cr-video-del-btn {
    width: 36px; height: 36px; border-radius: 8px; flex-shrink: 0;
    background: #fff5f5; border: 1.5px solid #fecaca; color: #dc2626;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .2s;
}
.cr-video-del-btn:hover { background: #fecaca; }

/* ── Video upload progress bar ───────────────────────────────────── */
.cr-video-progress-wrap {
    padding: 0 18px 14px; background: #f9fafc; border-top: 1px solid #e4e9f0;
}
.cr-video-progress-bar {
    height: 4px; background: #e4e9f0; border-radius: 4px; overflow: hidden;
}
.cr-video-progress-bar::after {
    content: '';
    display: block; height: 100%; width: 60%;
    background: linear-gradient(90deg, #14213d, #fed304);
    border-radius: 4px;
    animation: cr-bar-slide 1.4s ease-in-out infinite;
}
@keyframes cr-bar-slide {
    0%   { transform: translateX(-100%); }
    100% { transform: translateX(200%); }
}

/* ── Error message ───────────────────────────────────────────────── */
.cr-media-error {
    display: flex; align-items: flex-start; gap: 8px;
    background: #fff5f5; border: 1px solid #fecaca; border-radius: 8px;
    padding: 10px 14px; margin-top: 12px; font-size: 13px; color: #dc2626;
}
.cr-media-error svg { flex-shrink: 0; margin-top: 1px; }
</style>
@endpush

<div class="cr-course-box" wire:init="loadData" wire:key="@this">
    <div class="cr-content-box">
        <h2>{{ __('courses::courses.media') }}</h2>
        <p>{{ __('courses::courses.select_category_upload') }}</p>
    </div>

    <form class="am-themeform">
        <fieldset>
            <div class="cr-media-page">

                {{-- ── Miniatura ─────────────────────────────────────────── --}}
                <div class="cr-media-card">
                    <div class="cr-media-card-head">
                        <div class="cr-mh-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="3" width="18" height="18" rx="3" stroke="#fed304" stroke-width="1.8"/>
                                <circle cx="8.5" cy="8.5" r="1.5" fill="#fed304"/>
                                <path d="M3 16l5-5 4 4 3-3 6 6" stroke="#fed304" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="cr-mh-text">
                            <h3>{{ __('courses::courses.add_course_thumbnail') }}</h3>
                            <p>Imagen de portada — la verán los estudiantes al explorar el catálogo</p>
                        </div>
                        <span class="cr-mh-required">Requerido</span>
                    </div>

                    <div class="cr-media-card-body">
                        <div class="cr-media-specs">
                            @if (!empty($imageExtensions))
                                <span class="cr-media-spec-tag">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2"/></svg>
                                    {{ $imageExtensions }}
                                </span>
                            @endif
                            @if (!empty($imageSize))
                                <span class="cr-media-spec-tag">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Máx. {{ round($imageSize / 1000) }} MB
                                </span>
                            @endif
                            <span class="cr-media-spec-tag">
                                <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 9h18M9 21V9" stroke="currentColor" stroke-width="2"/></svg>
                                Recomendado: 1280 × 720 px
                            </span>
                        </div>

                        {{-- Hidden input --}}
                        <div style="display:none;">
                            <x-text-input
                                name="file" type="file" id="at_upload_thumbnail"
                                accept="{{ !empty($allowImgFileExt) ? join(',', array_map(fn($ex) => '.'.$ex, $allowImgFileExt)) : '*' }}"
                                x-on:change="$wire.dispatch('file-dropped', {'dataTransfer': { files: $event.target.files }})"
                            />
                        </div>

                        @if (!empty($thumbnail))
                            {{-- Preview --}}
                            <div class="cr-thumb-wrap">
                                <img src="{{ $isBase64 ? $thumbnail : Storage::url($thumbnail) }}" alt="Thumbnail Preview">
                                <div class="cr-thumb-bar">
                                    <div class="cr-thumb-info">
                                        @if ($isBase64)
                                            <strong>{{ $imageName }}.png</strong>
                                            @if (!empty($uploadedThumbnailSize))<em>{{ $uploadedThumbnailSize }} KB</em>@endif
                                        @else
                                            <strong>{{ basename(parse_url(Storage::url($thumbnail), PHP_URL_PATH)) }}</strong>
                                            @if (!empty($thumbnailSize))<em>{{ $thumbnailSize }} KB</em>@endif
                                        @endif
                                    </div>
                                    <span class="cr-ok-badge">✓ Lista</span>
                                    <label for="at_upload_thumbnail" class="cr-change-btn">Cambiar</label>
                                    <button type="button" class="cr-del-btn" wire:click.prevent="removeMedia('thumbnail')" title="Eliminar miniatura">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14H6L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10 11v6M14 11v6M9 6V4h6v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                    </button>
                                </div>
                            </div>
                        @else
                            {{-- Drop zone with loader --}}
                            <div
                                class="cr-dropzone"
                                x-data="{ isDrag: false, isLoading: false }"
                                x-bind:class="{ 'is-drag': isDrag }"
                                x-on:dragover.prevent="isDrag = true"
                                x-on:dragleave.prevent="isDrag = false"
                                x-on:drop.prevent="isDrag = false; isLoading = true; $wire.dispatch('file-dropped', { dataTransfer: $event.dataTransfer })"
                            >
                                {{-- Loader overlay (file reading) --}}
                                <div class="cr-upload-loading" x-show="isLoading" x-cloak>
                                    <div class="cr-spinner"></div>
                                    <span>Procesando imagen…</span>
                                </div>
                                {{-- Loader overlay (Livewire uploading) --}}
                                <div class="cr-upload-loading" wire:loading wire:target="thumbnail">
                                    <div class="cr-spinner"></div>
                                    <span>Subiendo imagen…</span>
                                </div>

                                <div class="cr-dz-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 16V4M12 4l-3.5 3.5M12 4l3.5 3.5" stroke="#fed304" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="#aab3c5" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <label for="at_upload_thumbnail">
                                    Arrastra tu imagen aquí o <em>haz clic para seleccionar</em>
                                    <span>Sube una imagen atractiva que represente tu curso</span>
                                </label>
                                @if (!empty($imageExtensions))
                                    <p class="cr-dz-hint">{{ $imageExtensions }} · máx. {{ round($imageSize / 1000) }} MB</p>
                                @endif
                            </div>
                        @endif

                        <x-input-error field_name="thumbnail" />
                        @if ($thumbnailError)
                            <div class="cr-media-error">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                {{ $thumbnailErrorMessage }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ── Video promocional ────────────────────────────────── --}}
                <div class="cr-media-card">
                    <div class="cr-media-card-head">
                        <div class="cr-mh-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <polygon points="5 3 19 12 5 21 5 3" fill="#fed304"/>
                            </svg>
                        </div>
                        <div class="cr-mh-text">
                            <h3>{{ __('courses::courses.add_promotional_video') }}</h3>
                            <p>Video corto (1–3 min) que presente el curso a potenciales estudiantes</p>
                        </div>
                        <span class="cr-mh-required" style="background:rgba(255,255,255,.12);color:#aab3c5;">Opcional</span>
                    </div>

                    <div class="cr-media-card-body">
                        <div class="cr-media-specs">
                            @if (!empty($videoExtensions))
                                <span class="cr-media-spec-tag">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2"/></svg>
                                    {{ $videoExtensions }}
                                </span>
                            @endif
                            @if (!empty($videoSize))
                                <span class="cr-media-spec-tag">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Máx. {{ round($videoSize / 1000) }} MB
                                </span>
                            @endif
                            <span class="cr-media-spec-tag">
                                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                Duración: 1–3 min
                            </span>
                        </div>

                        @if (!empty($coursePromotionalVideo) || !empty($promotionalVideo))
                            <div class="cr-video-uploaded">
                                <div class="cr-video-uploaded-body">
                                    <div class="cr-video-icon-wrap">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><polygon points="5 3 19 12 5 21 5 3" fill="#fed304"/></svg>
                                    </div>
                                    <div class="cr-video-info">
                                        @if (!empty($coursePromotionalVideo))
                                            <strong>{{ basename($coursePromotionalVideo->path) }}</strong>
                                            <span>{{ $promotionalVideoSize }} MB · Guardado</span>
                                        @else
                                            <strong>{{ basename($promotionalVideo->getClientOriginalName()) }}</strong>
                                            <span>Listo para guardar</span>
                                        @endif
                                    </div>
                                    <div class="cr-video-actions">
                                        @if (!empty($coursePromotionalVideo))
                                            <a href="{{ Storage::url($coursePromotionalVideo->path) }}" data-gall="gall" data-vbtype="iframe" class="tu-themegallery cr-video-preview-btn">
                                                ▶ Ver
                                            </a>
                                        @endif
                                        <button type="button" class="cr-video-del-btn" wire:click.prevent="removeMedia('video')" title="Eliminar video">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14H6L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10 11v6M14 11v6M9 6V4h6v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Video drop zone --}}
                            <div
                                class="cr-dropzone"
                                x-data="{ isDrag: false }"
                                x-bind:class="{ 'is-drag': isDrag }"
                                x-on:dragover.prevent="isDrag = true"
                                x-on:dragleave.prevent="isDrag = false"
                                x-on:drop.prevent="isDrag = false; $wire.upload('promotionalVideo', $event.dataTransfer.files[0])"
                            >
                                {{-- Livewire upload loader --}}
                                <div class="cr-upload-loading" wire:loading wire:target="promotionalVideo">
                                    <div class="cr-spinner"></div>
                                    <span>Subiendo video…</span>
                                </div>

                                <div style="display:none;">
                                    <x-text-input
                                        name="file" type="file" id="at_upload_video"
                                        x-ref="file_upload"
                                        accept="{{ !empty($allowVideoFileExt) ? join(',', array_map(fn($ex) => '.'.$ex, $allowVideoFileExt)) : '*' }}"
                                        x-on:change="$wire.upload('promotionalVideo', $refs.file_upload.files[0])"
                                    />
                                </div>
                                <div class="cr-dz-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 16V4M12 4l-3.5 3.5M12 4l3.5 3.5" stroke="#fed304" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="#aab3c5" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <label for="at_upload_video">
                                    Arrastra tu video aquí o <em>haz clic para seleccionar</em>
                                    <span>Un video de presentación aumenta las inscripciones hasta 3×</span>
                                </label>
                                @if (!empty($videoExtensions))
                                    <p class="cr-dz-hint">{{ $videoExtensions }} · máx. {{ round($videoSize / 1000) }} MB</p>
                                @endif
                            </div>

                            {{-- Video upload progress bar (Livewire wire:loading) --}}
                            <div wire:loading wire:target="promotionalVideo" style="margin-top:10px;">
                                <div class="cr-video-progress-wrap" style="padding:0;">
                                    <div style="font-size:12px;color:#14213d;font-weight:600;margin-bottom:6px;">Subiendo video, espera un momento…</div>
                                    <div class="cr-video-progress-bar"></div>
                                </div>
                            </div>
                        @endif

                        <x-input-error field_name="promotionalVideo" />
                    </div>
                </div>

            </div>{{-- .cr-media-page --}}
        </fieldset>

        <div class="am-themeform_footer">
            <a href="{{ route('courses.tutor.edit-course', ['id' => $courseId, 'tab' => 'details']) }}" class="am-white-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M10.5 4.5L6 9L10.5 13.5" stroke="#585858" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                {{ __('courses::courses.back') }}
            </a>
            <button wire:click="store" type="button" class="am-btn" wire:loading.remove wire:target="store">{{ __('courses::courses.save_continue') }}</button>
            <button class="am-btn am-btn_disable" wire:loading.flex disable wire:target="store">{{ __('courses::courses.save_continue') }}</button>
        </div>
    </form>
</div>

@push('scripts')
    <script defer src="{{ asset('js/venobox.min.js') }}"></script>

    <script type="text/javascript" data-navigate-once>
        var component = '';

        document.addEventListener('livewire:navigated', function () {
            component = @this;
        });

        window.addEventListener('load', function () {
            setTimeout(() => {
                if (typeof jQuery !== 'undefined') {
                    jQuery('.tu-themegallery').venobox({ spinner: 'cube-grid' });
                }
            }, 200);
        });

        document.addEventListener('livewire:initialized', function () {
            component = @this;

            Livewire.on('file-dropped', (event) => {
                if (!event.dataTransfer || event.dataTransfer.files.length === 0) return;

                const file      = event.dataTransfer.files[0];
                const fileExt   = file.name.split('.').pop().toLowerCase();
                const fileSize  = file.size / 1024; // KB
                const allowSize = Number("{{ $imageSize }}");
                const extJson   = @json($imageExtensions);
                const allowExts = extJson
                    ? extJson.split(',').map(s => s.trim().replace('.', ''))
                    : [];

                component.set('thumbnailError', false);
                component.set('thumbnailErrorMessage', '');

                if (!allowExts.includes(fileExt)) {
                    component.set('thumbnailError', true);
                    component.set('thumbnailErrorMessage', "{{ __('courses::courses.invalid_file_type', ['file_types' => join(',', array_map(fn($e) => '.'.$e, explode(',', $imageExtensions)))]) }}");
                    const inp = document.getElementById('at_upload_thumbnail');
                    if (inp) inp.value = '';
                    return;
                }
                if (fileSize > allowSize) {
                    component.set('thumbnailError', true);
                    component.set('thumbnailErrorMessage', "{{ __('courses::courses.max_course_thumbnail_size_error', ['file_size' => round($imageSize / 1000)]) }}");
                    const inp = document.getElementById('at_upload_thumbnail');
                    if (inp) inp.value = '';
                    return;
                }

                // Valid — convert to base64 and push directly (no crop modal)
                const reader = new FileReader();
                reader.onload = (e) => {
                    const base64   = e.target.result;
                    const fileName = file.name.split('.').slice(0, -1).join('.');
                    const sizeKB   = (file.size / 1000).toFixed(2);

                    component.set('thumbnail', base64, false);
                    component.set('isBase64', true, false);
                    component.set('imageName', fileName);
                    component.set('uploadedThumbnailSize', sizeKB);

                    if (typeof component.$refresh === 'function') component.$refresh();
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endpush
