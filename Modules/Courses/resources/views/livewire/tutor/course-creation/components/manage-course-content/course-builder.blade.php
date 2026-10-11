@php
    $groups = \Modules\Courses\Services\Lessons\LessonType::GROUPS;
    $fmt = fn (int $min) => $min >= 60 ? intdiv($min, 60) . ' h ' . ($min % 60 ? ($min % 60) . ' min' : '') : $min . ' min';
@endphp
<div class="cr-course-box cr-builder-box" wire:init="loadData">
    <div class="cr-content-box cr-builder-head">
        <div>
            <h2>{{ __('courses::courses.course_content') }}</h2>
            <p>{{ __('Organiza tu curso en secciones y lecciones. Arrastra para reordenar y elige qué tipo de contenido tiene cada lección.') }}</p>
        </div>
        <ul class="cr-stats" aria-label="{{ __('Resumen del contenido') }}">
            <li><strong>{{ $stats['sections'] }}</strong> {{ trans_choice('sección|secciones', $stats['sections']) }}</li>
            <li><strong>{{ $stats['lessons'] }}</strong> {{ trans_choice('lección|lecciones', $stats['lessons']) }}</li>
            <li><strong>{{ $fmt($stats['minutes']) }}</strong> {{ __('de contenido') }}</li>
            @if ($stats['incomplete'] > 0)
                <li class="is-warn"><x-courses::icon name="alert" :size="14" /> {{ trans_choice(':count lección incompleta|:count lecciones incompletas', $stats['incomplete']) }}</li>
            @endif
        </ul>
    </div>

    <div class="cr-builder" x-data="crBuilder()">
        {{-- ───────── Esquema del curso ───────── --}}
        <aside class="cr-outline" aria-label="{{ __('Estructura del curso') }}">
            <div class="cr-outline__list" x-init="crSortSections($el, $wire)">
                @foreach ($sections as $section)
                    <section class="cr-sec" data-id="{{ $section->id }}" wire:key="sec-{{ $section->id }}">
                        <header class="cr-sec__head">
                            <span class="cr-handle cr-handle--sec" title="{{ __('Arrastra para mover la sección') }}"><x-courses::icon name="drag" :size="16" /></span>
                            @if ($renamingSection === $section->id)
                                <input type="text" class="cr-sec__input" wire:model="sectionTitle" wire:keydown.enter="saveSectionTitle" wire:keydown.escape="cancelRename" wire:blur="saveSectionTitle" x-init="$nextTick(() => $el.select())" maxlength="150" aria-label="{{ __('Nombre de la sección') }}">
                            @else
                                <button type="button" class="cr-sec__title" wire:click="startRenameSection({{ $section->id }})" title="{{ __('Clic para renombrar') }}">{{ $section->title }}</button>
                            @endif
                            <span class="cr-sec__count">{{ $section->curriculums->count() }}</span>
                            <button type="button" class="cr-iconbtn cr-iconbtn--danger" title="{{ __('Eliminar sección') }}" aria-label="{{ __('Eliminar sección') }}"
                                    wire:click="deleteSection({{ $section->id }})" wire:confirm="{{ __('¿Eliminar la sección «:t» y todas sus lecciones? Esta acción no se puede deshacer.', ['t' => $section->title]) }}">
                                <x-courses::icon name="trash" :size="15" />
                            </button>
                        </header>
                        @error('sectionTitle') @if ($renamingSection === $section->id)<p class="cr-err">{{ $message }}</p>@endif @enderror

                        <ul class="cr-lessons" data-section="{{ $section->id }}" x-init="crSortLessons($el, $wire)">
                            @foreach ($section->curriculums as $item)
                                @php $g = \Modules\Courses\Services\Lessons\LessonType::groupOf($item->type); @endphp
                                <li class="cr-lesson @if ($lessonId === $item->id) is-active @endif" data-id="{{ $item->id }}" wire:key="les-{{ $item->id }}">
                                    <span class="cr-handle" title="{{ __('Arrastra para mover la lección') }}"><x-courses::icon name="drag" :size="14" /></span>
                                    <button type="button" class="cr-lesson__main" wire:click="selectLesson({{ $item->id }})" @if ($lessonId === $item->id) aria-current="true" @endif>
                                        <span class="cr-lesson__icon cr-t-{{ $g }}"><x-courses::icon :name="$groups[$g]['icon']" :size="15" /></span>
                                        <span class="cr-lesson__text">
                                            <span class="cr-lesson__title">{{ $item->title }}</span>
                                            <small>{{ $groups[$g]['label'] }}@if ($item->content_length) · {{ max(1, (int) round($item->content_length / 60)) }} min @endif @if ($item->is_preview) · {{ __('Vista previa') }} @endif</small>
                                        </span>
                                        @unless ($complete[$item->id] ?? false)
                                            <span class="cr-dot" title="{{ __('Falta contenido') }}"></span>
                                        @endunless
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        @if ($addingLessonIn === $section->id)
                            <form class="cr-addrow" wire:submit.prevent="addLesson">
                                <input type="text" wire:model="newLessonTitle" placeholder="{{ __('Título de la lección') }}" maxlength="200" x-init="$nextTick(() => $el.focus())" wire:keydown.escape="cancelAddLesson" aria-label="{{ __('Título de la nueva lección') }}">
                                <button type="submit" class="cr-btn cr-btn--sm cr-btn--primary">{{ __('Crear') }}</button>
                                <button type="button" class="cr-btn cr-btn--sm" wire:click="cancelAddLesson">{{ __('Cancelar') }}</button>
                            </form>
                            @error('newLessonTitle')<p class="cr-err">{{ $message }}</p>@enderror
                        @else
                            <button type="button" class="cr-add" wire:click="startAddLesson({{ $section->id }})"><x-courses::icon name="plus" :size="14" /> {{ __('Añadir lección') }}</button>
                        @endif
                    </section>
                @endforeach
            </div>

            @if ($addingSection)
                <form class="cr-addrow cr-addrow--section" wire:submit.prevent="addSection">
                    <input type="text" wire:model="newSectionTitle" placeholder="{{ __('Nombre de la sección') }}" maxlength="150" x-init="$nextTick(() => $el.focus())" wire:keydown.escape="$set('addingSection', false)" aria-label="{{ __('Nombre de la nueva sección') }}">
                    <button type="submit" class="cr-btn cr-btn--sm cr-btn--primary">{{ __('Crear') }}</button>
                    <button type="button" class="cr-btn cr-btn--sm" wire:click="$set('addingSection', false)">{{ __('Cancelar') }}</button>
                </form>
                @error('newSectionTitle')<p class="cr-err">{{ $message }}</p>@enderror
            @else
                <button type="button" class="cr-add cr-add--section" wire:click="$set('addingSection', true)"><x-courses::icon name="folder" :size="16" /> {{ __('Nueva sección') }}</button>
            @endif
        </aside>

        {{-- ───────── Editor de la lección ───────── --}}
        <section class="cr-editor" aria-label="{{ __('Editor de lección') }}">
            @if ($lesson)
                <header class="cr-editor__head">
                    <div class="cr-editor__titlewrap">
                        <input type="text" class="cr-title-input" wire:model="lessonTitle" placeholder="{{ __('Título de la lección') }}" maxlength="200" aria-label="{{ __('Título de la lección') }}">
                        @error('lessonTitle')<p class="cr-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="cr-editor__tools">
                        @if ($complete[$lesson->id] ?? false)
                            <span class="cr-pill cr-pill--ok"><x-courses::icon name="check" :size="14" /> {{ __('Completa') }}</span>
                        @else
                            <span class="cr-pill cr-pill--warn"><x-courses::icon name="alert" :size="14" /> {{ __('Falta contenido') }}</span>
                        @endif
                        <button type="button" class="cr-iconbtn" wire:click="duplicateLesson({{ $lesson->id }})" title="{{ __('Duplicar lección') }}" aria-label="{{ __('Duplicar lección') }}"><x-courses::icon name="copy" :size="16" /></button>
                        <button type="button" class="cr-iconbtn cr-iconbtn--danger" wire:click="deleteLesson({{ $lesson->id }})" wire:confirm="{{ __('¿Eliminar esta lección?') }}" title="{{ __('Eliminar lección') }}" aria-label="{{ __('Eliminar lección') }}"><x-courses::icon name="trash" :size="16" /></button>
                    </div>
                </header>

                <div class="cr-types" role="tablist" aria-label="{{ __('Tipo de contenido') }}">
                    @foreach ($groups as $key => $def)
                        <button type="button" role="tab" aria-selected="{{ $group === $key ? 'true' : 'false' }}" class="cr-type cr-t-{{ $key }} @if ($group === $key) is-active @endif" wire:click="setGroup('{{ $key }}')" title="{{ __($def['hint']) }}">
                            <span class="cr-type__icon"><x-courses::icon :name="$def['icon']" :size="18" /></span>
                            <span class="cr-type__label">{{ __($def['label']) }}</span>
                            <small>{{ __($def['hint']) }}</small>
                        </button>
                    @endforeach
                </div>

                <div class="cr-form" wire:key="form-{{ $lesson->id }}-{{ $group }}">
                    @include('courses::livewire.tutor.course-creation.components.manage-course-content.lessons.' . $group)

                    <details class="cr-more" @if (trim($lessonDescription) !== '') open @endif>
                        <summary>{{ __('Descripción de la lección (opcional)') }}</summary>
                        <textarea class="form-control" rows="3" wire:model="lessonDescription" maxlength="5000" placeholder="{{ __('Resumen que verá el alumno junto a la lección') }}" aria-label="{{ __('Descripción de la lección') }}"></textarea>
                    </details>
                </div>

                <footer class="cr-editor__foot">
                    <label class="cr-switch">
                        <input type="checkbox" class="cr-toggle" wire:model="isPreview">
                        <span>{{ __('Vista previa gratuita') }} <small>{{ __('Visible sin comprar el curso') }}</small></span>
                    </label>
                    <div class="cr-editor__save">
                        <button type="button" class="cr-btn" wire:click="saveLesson(true)" wire:loading.attr="disabled" wire:target="saveLesson,videoUpload,scormUpload">{{ __('Guardar y siguiente') }} <x-courses::icon name="arrow-right" :size="15" /></button>
                        <button type="button" class="cr-btn cr-btn--primary" wire:click="saveLesson" wire:loading.attr="disabled" wire:target="saveLesson,videoUpload,scormUpload">
                            <span wire:loading.remove wire:target="saveLesson">{{ __('Guardar lección') }}</span>
                            <span wire:loading wire:target="saveLesson">{{ __('Guardando…') }}</span>
                        </button>
                    </div>
                </footer>
            @else
                <div class="cr-empty">
                    <x-courses::icon name="folder" :size="40" />
                    <h3>{{ $sections->isEmpty() ? __('Empieza creando tu primera sección') : __('Selecciona una lección') }}</h3>
                    <p>{{ $sections->isEmpty() ? __('Las secciones agrupan las lecciones (por ejemplo «Introducción» o «Módulo 1»).') : __('Elige una lección del esquema para editarla o crea una nueva con «Añadir lección».') }}</p>
                    @if ($sections->isEmpty())
                        <button type="button" class="cr-btn cr-btn--primary" wire:click="$set('addingSection', true)"><x-courses::icon name="plus" :size="15" /> {{ __('Nueva sección') }}</button>
                    @endif
                </div>
            @endif
        </section>
    </div>

    <div class="am-themeform_footer">
        <a href="{{ $backUrl }}" class="am-white-btn"><x-courses::icon name="arrow-left" :size="16" /> {{ __('courses::courses.back') }}</a>
        <button wire:click="next" type="button" class="am-btn" wire:loading.attr="disabled" wire:target="next">{{ __('courses::courses.save_continue') }}</button>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/courses/css/main.css') }}">
    @vite(['public/summernote/summernote-lite.min.css'])
@endpush
@push('scripts')
    <script src="{{ asset('vendor/pagebuilder/js/Sortable.min.js') }}"></script>
    <script defer src="{{ asset('summernote/summernote-lite.min.js') }}"></script>
    <script src="{{ asset('js/course-builder.js') }}?v={{ @filemtime(public_path('js/course-builder.js')) }}"></script>
@endpush
