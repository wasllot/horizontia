<div wire:init="loadData">
    <div class="cr-course-box">
        <div class="cr-content-box">
            <h2>{{ __('courses::courses.course_content') }}</h2>
            <p>{{ __('courses::courses.organize_sections') }}</p>
        </div>
        <div class="cr-course-body">
            @foreach ($sections as $key => $section)
                <div wire:key="section-{{ $section->id }}" id="section-{{ $section->id }}" class="cr-faq-accordion">
                    <div class="cr-formarea accordion">
                        <div class="accordion-item">
                            <input type="radio" name="accordion" id="{{ 'accordion-item-' . $section->id }}"
                                class="accordion-checkbox">
                            <label for="{{ 'accordion-item-' . $section->id }}" class="cr-course-item accordion-header">
                                <div class="cr-contentbox">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="16"
                                        viewBox="0 0 15 16" fill="none">
                                        <path
                                            d="M1.25 4.95C1.25 3.82989 1.25 3.26984 1.46799 2.84202C1.65973 2.46569 1.96569 2.15973 2.34202 1.96799C2.76984 1.75 3.3299 1.75 4.45 1.75H5.19795C5.62403 1.75 5.83707 1.75 6.03136 1.78888C6.50188 1.88303 6.92288 2.14322 7.21751 2.52196C7.33918 2.67835 7.43445 2.8689 7.625 3.25V3.25C7.75203 3.50407 7.81555 3.6311 7.89666 3.73536C8.09308 3.98785 8.37374 4.16131 8.68743 4.22408C8.81695 4.25 8.95898 4.25 9.24303 4.25H9.48333C10.9768 4.25 11.7235 4.25 12.294 4.54065C12.7957 4.79631 13.2037 5.20426 13.4594 5.70603C13.75 6.27646 13.75 7.02319 13.75 8.51667V9.98333C13.75 11.4768 13.75 12.2235 13.4594 12.794C13.2037 13.2957 12.7957 13.7037 12.294 13.9594C11.7235 14.25 10.9768 14.25 9.48333 14.25H5.51667C4.02319 14.25 3.27646 14.25 2.70603 13.9594C2.20426 13.7037 1.79631 13.2957 1.54065 12.794C1.25 12.2235 1.25 11.4768 1.25 9.98333V4.95Z"
                                            stroke="#585858" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <span> {{ $section->title }}</span>
                                </div>
                                <div class="am-itemdropdown">
                                    <a href="javascript:void(0);" id="am-itemdropdown" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                        <i><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 14 14" fill="none">
                                                <path
                                                    d="M2.62484 5.54166C1.82275 5.54166 1.1665 6.19791 1.1665 6.99999C1.1665 7.80207 1.82275 8.45832 2.62484 8.45832C3.42692 8.45832 4.08317 7.80207 4.08317 6.99999C4.08317 6.19791 3.42692 5.54166 2.62484 5.54166Z"
                                                    fill="#585858" />
                                                <path
                                                    d="M11.3748 5.54166C10.5728 5.54166 9.9165 6.19791 9.9165 6.99999C9.9165 7.80207 10.5728 8.45832 11.3748 8.45832C12.1769 8.45832 12.8332 7.80207 12.8332 6.99999C12.8332 6.19791 12.1769 5.54166 11.3748 5.54166Z"
                                                    fill="#585858" />
                                                <path
                                                    d="M5.5415 6.99999C5.5415 6.19791 6.19775 5.54166 6.99984 5.54166C7.80192 5.54166 8.45817 6.19791 8.45817 6.99999C8.45817 7.80207 7.80192 8.45832 6.99984 8.45832C6.19775 8.45832 5.5415 7.80207 5.5415 6.99999Z"
                                                    fill="#585858" />
                                            </svg>
                                        </i>
                                    </a>
                                    <ul class="am-itemdropdown_list dropdown-menu" aria-labelledby="dropdownMenuLink">
                                        <li wire:click="editSectionFunction({{ $section }})">
                                            <a href="javascript:void(0);">
                                                <i>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                                        height="20" viewBox="0 0 20 20" fill="none">
                                                        <path
                                                            d="M16.6663 17.5H3.33301M13.333 3.33335C13.1247 4.79169 14.3747 6.04169 15.833 5.83335M5.83301 13.3334L6.23639 11.7198C6.39642 11.0797 6.47644 10.7596 6.60511 10.4612C6.71935 10.1963 6.86191 9.9445 7.03031 9.71024C7.22 9.44637 7.45328 9.21309 7.91985 8.74653L13.7498 2.91667C14.4401 2.22633 15.5594 2.22635 16.2498 2.91671V2.91671C16.9401 3.60706 16.9401 4.7263 16.2497 5.41663L10.4198 11.2465C9.95327 11.7131 9.71999 11.9464 9.45612 12.1361C9.22187 12.3045 8.97008 12.447 8.70515 12.5612C8.40675 12.6899 8.08669 12.7699 7.44657 12.93L5.83301 13.3334Z"
                                                            stroke="#585858" stroke-width="1.5" stroke-linecap="round"
                                                            stroke-linejoin="round" />
                                                    </svg>
                                                </i>
                                                {{ __('courses::courses.edit') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="cr-delete-curriculum" data-id="{{ $section->id }}" data-component_id="@this">
                                                <i>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                                        height="20" viewBox="0 0 20 20" fill="none">
                                                        <path
                                                            d="M3.33317 4.16669L3.82396 12.5101C3.9375 14.4402 3.99427 15.4053 4.37553 16.1521C4.79523 16.9742 5.48635 17.6259 6.3317 17.9966C7.09962 18.3334 8.06636 18.3334 9.99984 18.3334V18.3334C11.9333 18.3334 12.9001 18.3334 13.668 17.9966C14.5133 17.6259 15.2044 16.9742 15.6241 16.1521C16.0054 15.4053 16.0622 14.4402 16.1757 12.5101L16.6665 4.16669M3.33317 4.16669H1.6665M3.33317 4.16669H16.6665M16.6665 4.16669H18.3332M13.3332 4.16669L13.0469 3.30774C12.8502 2.71763 12.7518 2.42257 12.5694 2.20442C12.4083 2.01179 12.2014 1.86268 11.9677 1.77077C11.7031 1.66669 11.3921 1.66669 10.77 1.66669H9.22966C8.60762 1.66669 8.29661 1.66669 8.03197 1.77077C7.79828 1.86268 7.5914 2.01179 7.4303 2.20442C7.24788 2.42257 7.14952 2.71763 6.95282 3.30774L6.6665 4.16669M8.33317 8.33335V14.1667M11.6665 8.33335V11.6667"
                                                            stroke="#585858" stroke-width="1.5" stroke-linecap="round"
                                                            stroke-linejoin="round" />
                                                    </svg>
                                                </i>
                                                {{ __('courses::courses.delete') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <span class="accordion-icon">
                                    <i class="am-icon-chevron-down"></i>
                                </span>
                            </label>
                            <div class="accordion-content">
                                <livewire:courses::manage-course-content.components.curriculum :id="$section->id" :section="$section" :key="now() . $section->id . 'curriculum'" />
                            </div>
                            </label>
                        </div>
                    </div>
                </div>
            @endforeach

            @if ($addSection)
                <div class="cr-formarea">
                    <div class="form-group">
                        <label class="am-important"
                            for="section-title">{{ __('courses::courses.section_title') }}</label>
                        <input wire:model="title" class="form-control @error('title') is-invalid @enderror"
                            type="text" id="section-title"
                            placeholder="{{ __('courses::courses.enter_course_title') }}">
                        @error('title')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group @error('description') cr-invalid @enderror">
                        <label class="am-important" for="description*">{{ __('courses::courses.description') }}</label>
                        <div wire:ignore class="am-custom-editor am-custom-textarea">
                            <textarea class="form-control cr-summernote" id="section-desc" data-id="@this" data-model_id="description"
                                placeholder="{{ __('courses::courses.enter_description') }}"></textarea>
                            <span class="characters-count"></span>
                        </div>
                        @error('description')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="cr-btns">
                        <button wire:click="addSectionState(false)" wire:loading.class="am-btn_disable" wire:target="addSectionState(false)" class="am-cancel-btn">{{ __('courses::courses.cancel') }}</button>
                        <button wire:click="createSection" type="submit" class="am-btn">
                            <span wire:loading wire:target="createSection">{{ __('courses::courses.loading') }}</span>
                            <span wire:loading.remove wire:target="createSection">{{ __('courses::courses.add_section') }}</span>
                        </button>
                    </div>
                </div>
            @else
                <button wire:click="addSectionState(true)" class="cr-addbtn" wire:loading.attr="disabled" wire:target="addSectionState(true)" wire:loading.class="am-btn_disable">
                    <svg class="am-border-svg ">
                        <rect width="100%" height="100%"></rect>
                    </svg>
                    {{ __('courses::courses.create_section') }}
                    <i class="am-icon-plus-02" wire:loading.remove wire:target="addSectionState(true)"></i>
                </button>
            @endif
        </div>
        {{ $sections->links('courses::pagination.pagination') }}

        <div class="am-themeform_footer">
            <a href="{{ route('courses.tutor.edit-course', ['tab' =>  (isPaidSystem() ? 'pricing' : 'media'), 'id' => $course->id]) }}">
                <button class="am-white-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18"
                        fill="none">
                        <path d="M10.5 4.5L6 9L10.5 13.5" stroke="#585858" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    {{ __('courses::courses.back') }}
                </button>
            </a>
            <button wire:click="save" type="button" class="am-btn" wire:loading.remove
                wire:target="save">{{ __('courses::courses.save_continue') }}</button>
            <button class="am-btn am-btn_disable" wire:loading.flex
                wire:target="save">{{ __('courses::courses.save_continue') }}
            </button>
        </div>
    </div>
    <!-- edit model start -->
    <div wire:ignore.self class="modal fade cr-course-modal" id="edit-content" tabindex="-1" aria-labelledby="edit-contentlabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit-contentlabel">{{ __('courses::courses.edit_course_section') }}</h5>
                    <span class="cr-close" data-bs-dismiss="modal" aria-label="Close">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <g opacity="0.7">
                                <path d="M4 12L12 4M4 4L12 12" stroke="#585858" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </g>
                        </svg>
                    </span>
                </div>
                <div class="modal-body">
                    <form class="am-themeform">
                        <fieldset>
                            <div class="form-group">
                                <label class="am-important"
                                    for="course-title">{{ __('courses::courses.title') }}</label>
                                <input type="text" wire:model="title" id="course-title"
                                    placeholder="{{ __('courses::courses.enter_title') }}"
                                    class="form-control @error('title') is-invalid @enderror">
                                @error('title')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group @error('description') cr-invalid @enderror">
                                <div wire:ignore class="am-editor-wrapper">
                                    <div class="am-custom-editor am-custom-textarea">
                                        <textarea id="edit_section_description" data-id="@this" data-model_id="description" class="form-control cr-summernote" placeholder="{{ __('courses::courses.enter_answer') }}"></textarea>
                                        <span class="characters-count"></span>
                                    </div>
                                </div>
                                <x-input-error field_name='description' />
                            </div>
                        </fieldset>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="am-white-btn" data-bs-dismiss="modal">{{ __('courses::courses.close') }}</button>
                    <button type="button" class="am-btn" wire:click="updateSection" wire:loading.attr="disabled">
                        <span wire:loading.remove
                            wire:target="updateSection">{{ __('courses::courses.save_changes') }}</span>
                        <span wire:loading wire:target="updateSection">{{ __('courses::courses.saving') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete model start -->
    <div wire:ignore.self class="modal fade cr-course-modal cr-course-del-modal " id="delete-confirm-modal" tabindex="-1"
        aria-labelledby="delete-curriculum-modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered ">
            <div class="modal-content">
                <div class="modal-body">
                    <span class="cr-close" data-bs-dismiss="modal" aria-label="Close">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <g opacity="0.7">
                                <path d="M4 12L12 4M4 4L12 12" stroke="#585858" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </g>
                        </svg>
                    </span>
                    <figure class="cr-delete-icon">
                        <svg width="30" height="30" viewBox="0 0 30 30" fill="none">
                            <path
                                d="M5 6.25L5.80705 19.9698C5.96296 22.6203 6.04091 23.9455 6.6066 24.9512C7.10457 25.8366 7.86039 26.5492 8.77345 26.9944C9.81064 27.5 11.1382 27.5 13.7932 27.5H16.2068C18.8618 27.5 20.1894 27.5 21.2266 26.9944C22.1396 26.5492 22.8954 25.8366 23.3934 24.9512C23.9591 23.9455 24.037 22.6203 24.193 19.9698L25 6.25M5 6.25H2.5M5 6.25H25M25 6.25H27.5M20 6.25L19.4338 4.55132C19.1879 3.81367 19.065 3.44485 18.8369 3.17217C18.6356 2.93138 18.377 2.74499 18.0848 2.6301C17.754 2.5 17.3653 2.5 16.5877 2.5H13.4123C12.6347 2.5 12.246 2.5 11.9152 2.6301C11.623 2.74499 11.3644 2.93138 11.1631 3.17217C10.935 3.44485 10.8121 3.81367 10.5662 4.55132L10 6.25M12.5 12.5V21.25M17.5 12.5V17.5"
                                stroke="#F04438" stroke-width="1.875" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        <figcaption>
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </figcaption>
                    </figure>
                    <div class="cr-delete-content">
                        <h3>{{ __('courses::courses.confirm') }}</h3>
                        <p>{{ __('courses::courses.confirm_delete') }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="am-white-btn" data-bs-dismiss="modal">{{ __('courses::courses.no') }}</button>
                    <button type="button" class="am-btn cr-del-btn cr-delete-action">
                        <span>{{ __('courses::courses.yes') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/courses/css/main.css') }}">
    @vite(['public/summernote/summernote-lite.min.css'])
<style>
/* ── Course content builder ───────────────────────────────────────── */

/* Section spacing */
.cr-course-body { display: flex; flex-direction: column; gap: 12px; margin-top: 8px; }

/* ── Section accordion (cr-faq-accordion) ───────────────────────── */
/* NO overflow:hidden — dropdown menus are position:absolute and get clipped */
.cr-faq-accordion { border-radius: 14px; box-shadow: 0 2px 8px rgba(20,33,61,.06); position: relative; }
.cr-faq-accordion .cr-formarea.accordion { margin: 0 !important; }
.cr-faq-accordion .accordion-item { border: 1.5px solid #e4e9f0 !important; border-radius: 14px !important; overflow: visible !important; }

/* Section header — fully rounded by default (collapsed) */
.cr-faq-accordion .cr-course-item.accordion-header {
    display: flex !important; align-items: center !important;
    padding: 0 !important; cursor: pointer;
    background: linear-gradient(135deg, #14213d 0%, #1a2b52 100%) !important;
    border-radius: 12px !important; min-height: 56px;
    transition: background .2s;
}
/* When expanded (radio checked), only top corners rounded */
.cr-faq-accordion .accordion-checkbox:checked ~ .cr-course-item.accordion-header {
    border-radius: 12px 12px 0 0 !important;
}
.cr-faq-accordion .cr-course-item.accordion-header:hover {
    background: linear-gradient(135deg, #1a2b52 0%, #1e3460 100%) !important;
}

/* Section title area */
.cr-faq-accordion .cr-course-item.accordion-header .cr-contentbox {
    flex: 1 !important; display: flex !important; align-items: center !important;
    gap: 10px !important; padding: 14px 18px !important; min-width: 0;
}
.cr-faq-accordion .cr-course-item.accordion-header .cr-contentbox svg path { stroke: #aab3c5; }
.cr-faq-accordion .cr-course-item.accordion-header .cr-contentbox svg { flex-shrink: 0; }
.cr-faq-accordion .cr-course-item.accordion-header .cr-contentbox span {
    font-size: 14px !important; font-weight: 700 !important; color: #fff !important;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* "..." dropdown in section header */
.cr-faq-accordion .cr-course-item.accordion-header .am-itemdropdown {
    flex-shrink: 0; padding: 0 8px;
}
.cr-faq-accordion .cr-course-item.accordion-header .am-itemdropdown > a {
    display: flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(255,255,255,.1);
    transition: background .2s;
}
.cr-faq-accordion .cr-course-item.accordion-header .am-itemdropdown > a:hover { background: rgba(254,211,4,.2); }
.cr-faq-accordion .cr-course-item.accordion-header .am-itemdropdown > a svg path { fill: #aab3c5; }

/* Chevron */
.cr-faq-accordion .accordion-header .accordion-icon { padding: 0 18px 0 4px; }
.cr-faq-accordion .accordion-header .accordion-icon i { color: #aab3c5 !important; font-size: 13px; }

/* Accordion content panel */
.cr-faq-accordion .accordion-content {
    background: #f8f9fc !important;
    border-top: 1px solid #e4e9f0;
    padding: 16px !important;
}

/* ── Lesson items (cr-curriculum-item) ──────────────────────────── */
.cr-curriculum-item {
    background: #fff;
    border: 1.5px solid #e4e9f0;
    border-radius: 10px;
    margin-bottom: 8px;
    overflow: hidden;
    transition: border-color .2s, box-shadow .2s;
}
.cr-curriculum-item:hover { border-color: #c0cce0; box-shadow: 0 2px 8px rgba(20,33,61,.06); }
.cr-curriculum-item:last-child { margin-bottom: 0; }

.cr-curriculum-item .cr-contentbox-area { display: flex; flex-direction: column; }

/* Lesson header row */
.cr-curriculum-item .cr-contentbox {
    display: flex !important; align-items: center !important; gap: 10px !important;
    padding: 12px 14px !important;
    border-bottom: none;
}
/* Drag handle */
.cr-curriculum-item .cr-drag { cursor: grab; color: #c0cce0; flex-shrink: 0; font-size: 16px; }
.cr-curriculum-item .cr-drag:active { cursor: grabbing; }

/* Lesson checkmark icon */
.cr-curriculum-item .cr-contentbox > span:not(.cr-drag):not(.cr-contentbox_title) svg path { stroke: #14213d; }

/* Lesson title */
.cr-curriculum-item .cr-contentbox_title {
    flex: 1 !important; font-size: 13px !important; font-weight: 600 !important;
    color: #14213d !important; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

/* Lesson "..." dropdown */
.cr-curriculum-item .am-itemdropdown > a {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px;
    background: #f0f3fa; transition: background .2s;
}
.cr-curriculum-item .am-itemdropdown > a:hover { background: #e4e9f0; }

/* Lesson action button (edit content) */
.cr-curriculum-item .cr-actionbox { padding: 0 14px 12px; }
.cr-curriculum-item .cr-actionbox .am-btn {
    height: 34px !important; padding: 0 14px !important;
    font-size: 12px !important; border-radius: 7px !important;
}

/* ── Add-lesson form inside expanded section (.cr-curriculum-state) ─ */
.cr-curriculum-state {
    background: #fff;
    border: 1.5px solid #e4e9f0;
    border-radius: 12px;
    padding: 20px;
    margin-top: 12px;
}
.cr-curriculum-state .form-group {
    display: flex !important;
    flex-direction: column !important;
    gap: 6px !important;
    margin-bottom: 16px !important;
}
.cr-curriculum-state .form-group label {
    font-size: .72rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: .08em !important;
    color: #64748b !important;
    margin: 0 !important;
}
.cr-curriculum-state .form-group .form-control:not(.cr-summernote) {
    border: 1.5px solid #e4e9f0 !important;
    border-radius: 8px !important;
    padding: 10px 14px !important;
    font-size: .875rem !important;
    color: #14213d !important;
    background: #fff !important;
    transition: border-color .2s !important;
}
.cr-curriculum-state .form-group .form-control:focus {
    border-color: #14213d !important;
    outline: none !important;
    box-shadow: none !important;
}
/* Summernote container */
.cr-curriculum-state .am-custom-editor {
    border: 1.5px solid #e4e9f0 !important;
    border-radius: 8px !important;
    overflow: hidden !important;
}
.cr-curriculum-state .note-editor.note-frame {
    border: none !important;
    border-radius: 0 !important;
}
.cr-curriculum-state .note-toolbar {
    background: #f8f9fc !important;
    border-bottom: 1px solid #e4e9f0 !important;
    padding: 6px 10px !important;
}
.cr-curriculum-state .note-editing-area {
    min-height: 100px !important;
}
.cr-curriculum-state .characters-count {
    display: block;
    font-size: .7rem;
    color: #94a3b8;
    text-align: right;
    padding: 4px 10px 6px;
    background: #f8f9fc;
    border-top: 1px solid #f1f5f9;
}
/* Buttons row */
.cr-curriculum-state .cr-btns {
    display: flex !important;
    justify-content: flex-end !important;
    gap: 10px !important;
    margin-top: 4px !important;
}
.cr-curriculum-state .am-cancel-btn {
    padding: 9px 18px !important;
    border-radius: 8px !important;
    border: 1.5px solid #e4e9f0 !important;
    background: #fff !important;
    font-size: .8rem !important;
    font-weight: 600 !important;
    color: #5a6480 !important;
    cursor: pointer !important;
    transition: border-color .2s !important;
}
.cr-curriculum-state .am-cancel-btn:hover { border-color: #aab3c5 !important; }
.cr-curriculum-state .am-btn {
    height: 38px !important;
    padding: 0 18px !important;
    font-size: .8rem !important;
    border-radius: 8px !important;
}

/* ── Add lesson form (within section) ───────────────────────────── */
.cr-curriculum-item .cr-contentbox-area > div:not(.cr-contentbox):not(.cr-actionbox) {
    padding: 0 14px 14px; border-top: 1px solid #f0f3f8; margin-top: 2px;
}

/* ── Add section form (appears at bottom of list) ────────────────── */
.cr-course-body > .cr-formarea {
    background: #fff;
    border: 1.5px solid #e4e9f0;
    border-radius: 14px;
    padding: 20px !important;
}
.cr-course-body > .cr-formarea .form-group {
    display: flex !important; flex-direction: column !important;
    gap: 6px !important; margin-bottom: 16px !important;
}
.cr-course-body > .cr-formarea .form-group label {
    font-size: .72rem !important; font-weight: 700 !important;
    text-transform: uppercase !important; letter-spacing: .08em !important;
    color: #64748b !important; margin: 0 !important;
}
.cr-course-body > .cr-formarea .form-group .form-control:not(.cr-summernote) {
    border: 1.5px solid #e4e9f0 !important; border-radius: 8px !important;
    padding: 10px 14px !important; font-size: .875rem !important;
}
.cr-course-body > .cr-formarea .am-custom-editor {
    border: 1.5px solid #e4e9f0 !important; border-radius: 8px !important; overflow: hidden !important;
}
.cr-course-body > .cr-formarea .note-editor.note-frame { border: none !important; }
.cr-course-body > .cr-formarea .note-toolbar {
    background: #f8f9fc !important; border-bottom: 1px solid #e4e9f0 !important; padding: 6px 10px !important;
}
.cr-course-body > .cr-formarea .cr-btns {
    display: flex !important; gap: 10px !important; justify-content: flex-end !important; margin-top: 16px !important;
}
.cr-course-body > .cr-formarea .am-cancel-btn {
    padding: 9px 18px !important; border-radius: 8px !important; border: 1.5px solid #e4e9f0 !important;
    background: #fff !important; font-size: .8rem !important; font-weight: 600 !important;
    color: #5a6480 !important; cursor: pointer !important; transition: border-color .2s !important;
}
.cr-course-body > .cr-formarea .am-cancel-btn:hover { border-color: #aab3c5 !important; }
.cr-course-body > .cr-formarea .am-btn {
    height: 38px !important; padding: 0 18px !important; font-size: .8rem !important; border-radius: 8px !important;
}

/* ── Create section button ───────────────────────────────────────── */
.cr-addbtn {
    display: flex !important; align-items: center !important; justify-content: center !important;
    gap: 8px !important; width: 100% !important;
    padding: 14px 20px !important;
    background: #fff !important;
    border: 2px dashed #c0cce0 !important;
    border-radius: 14px !important;
    font-size: 14px !important; font-weight: 700 !important; color: #14213d !important;
    cursor: pointer !important;
    transition: border-color .2s, background .2s !important;
    position: relative !important; overflow: hidden;
}
.cr-addbtn:hover {
    border-color: #fed304 !important;
    background: #fffef5 !important;
    color: #14213d !important;
}
.cr-addbtn svg { display: none !important; }
.cr-addbtn i { color: #fed304 !important; font-size: 18px !important; }
.cr-addbtn .am-border-svg { display: none !important; }

/* ── Dropdown menus ──────────────────────────────────────────────── */
.am-itemdropdown_list {
    min-width: 140px !important; border-radius: 10px !important;
    border: 1px solid #e4e9f0 !important;
    box-shadow: 0 8px 24px rgba(20,33,61,.1) !important;
    padding: 6px !important; overflow: hidden;
}
.am-itemdropdown_list li a {
    display: flex !important; align-items: center !important; gap: 8px !important;
    padding: 8px 12px !important; border-radius: 7px !important;
    font-size: 13px !important; font-weight: 500 !important; color: #3d4a63 !important;
    transition: background .15s !important; text-decoration: none !important;
}
.am-itemdropdown_list li a:hover { background: #f4f6fb !important; }
.am-itemdropdown_list li:last-child a:hover { background: #fff5f5 !important; color: #dc2626 !important; }
.am-itemdropdown_list li:last-child a:hover svg path { stroke: #dc2626 !important; }

/* ── Video preview ───────────────────────────────────────────────── */
.cr-video-preview-uploaded { display: block !important; padding: 0 !important; border: none !important; background: transparent !important; margin-top: 10px; }
.cr-lesson-video-preview {
    width: 100%; max-width: 420px; height: 240px;
    border-radius: 12px; object-fit: cover; background: #14213d; display: block;
}

/* ── Lesson content panel (.cr-curriculum-content) ──────────────── */
.cr-curriculum-content {
    padding: 20px !important;
    background: #fff !important;
    border: 1.5px solid #e4e9f0 !important;
    border-top: none !important;
    border-radius: 0 0 12px 12px !important;
}
.cr-curriculum-content > p {
    font-size: .72rem !important; font-weight: 700 !important;
    text-transform: uppercase !important; letter-spacing: .1em !important;
    color: #94a3b8 !important; margin-bottom: 14px !important;
}

/* ── Content-type selector tabs ──────────────────────────────────── */
.cr-curriculum-content > ul {
    display: flex !important; flex-wrap: wrap !important;
    gap: 8px !important; list-style: none !important;
    padding: 0 !important; margin: 0 0 20px !important;
}
.cr-curriculum-content > ul > li {
    position: relative !important;
    display: flex !important; flex-direction: column !important;
    align-items: center !important; justify-content: center !important;
    padding: 12px 16px !important;
    min-width: 88px !important;
    border: 1.5px solid #e4e9f0 !important;
    border-radius: 12px !important;
    background: #fff !important;
    cursor: pointer !important;
    transition: border-color .2s, background .2s !important;
    gap: 6px !important;
}
.cr-curriculum-content > ul > li:hover:not([class*="cr-active"]) {
    border-color: #c0cce0 !important;
    background: #f8f9fc !important;
}
.cr-curriculum-content > ul > li.cr-active {
    border-color: #14213d !important;
    background: linear-gradient(135deg, #14213d 0%, #1a2b52 100%) !important;
    box-shadow: 0 4px 14px rgba(20,33,61,.2) !important;
}
/* Tab icon */
.cr-curriculum-btnconten { display: flex !important; flex-direction: column !important; align-items: center !important; gap: 6px !important; }
.cr-curriculum-btnconten figure {
    width: 36px !important; height: 36px !important;
    border-radius: 8px !important;
    background: #f0f4ff !important;
    display: flex !important; align-items: center !important; justify-content: center !important;
    margin: 0 !important; transition: background .2s !important;
}
.cr-curriculum-btnconten figure img { width: 20px !important; height: 20px !important; object-fit: contain !important; }
.cr-curriculum-btnconten figure i { font-size: 18px !important; color: #3d52a4 !important; }
.cr-curriculum-content > ul > li.cr-active .cr-curriculum-btnconten figure {
    background: rgba(255,255,255,.15) !important;
}
.cr-curriculum-content > ul > li.cr-active .cr-curriculum-btnconten figure i,
.cr-curriculum-content > ul > li.cr-active .cr-curriculum-btnconten figure img { filter: brightness(10) !important; }
/* Tab label */
.cr-curriculum-btnconten span {
    font-size: .72rem !important; font-weight: 600 !important;
    color: #3d4a63 !important; text-align: center !important; line-height: 1.2 !important;
}
.cr-curriculum-content > ul > li.cr-active .cr-curriculum-btnconten span { color: #fff !important; }
/* "Próximamente" badge */
.cr-curriculum-content > ul > li .cr-tag {
    position: absolute !important;
    top: -8px !important; right: -4px !important;
    font-size: .58rem !important; font-weight: 800 !important;
    letter-spacing: .04em !important; text-transform: uppercase !important;
    background: #f59e0b !important; color: #fff !important;
    padding: 2px 7px !important; border-radius: 20px !important;
    white-space: nowrap !important;
}

/* ── Video sub-type selector ─────────────────────────────────────── */
.am-upload-options {
    padding: 16px !important;
    background: #f8f9fc !important;
    border: 1.5px solid #e4e9f0 !important;
    border-radius: 10px !important;
    margin-bottom: 16px !important;
}
.am-upload-options h6.am-important {
    font-size: .7rem !important; font-weight: 700 !important;
    text-transform: uppercase !important; letter-spacing: .1em !important;
    color: #94a3b8 !important; margin: 0 0 12px !important;
}
.am-upload-options { display: flex !important; flex-wrap: wrap !important; align-items: center !important; gap: 8px !important; }
.am-upload-options h6 { width: 100% !important; }
/* Hide native radio, style label as pill */
.am-radio { display: contents !important; }
.am-radio input[type="radio"] { display: none !important; }
.am-radio label {
    display: inline-flex !important; align-items: center !important;
    padding: 6px 14px !important; border-radius: 20px !important;
    border: 1.5px solid #e4e9f0 !important;
    background: #fff !important; color: #3d4a63 !important;
    font-size: .78rem !important; font-weight: 600 !important;
    cursor: pointer !important; transition: all .2s !important;
    white-space: nowrap !important;
}
.am-radio label:hover { border-color: #c0cce0 !important; background: #f0f4ff !important; }
.am-radio input[type="radio"]:checked + label {
    background: #14213d !important; color: #fff !important;
    border-color: #14213d !important;
}

/* ── Upload dropzone ─────────────────────────────────────────────── */
.cr-curriculum-content .am-uploadfile {
    display: flex !important; flex-direction: column !important;
    align-items: center !important; justify-content: center !important;
    gap: 10px !important; text-align: center !important;
    padding: 32px 24px !important;
    border: 2px dashed #c0cce0 !important;
    border-radius: 12px !important;
    background: #f8f9fc !important;
    cursor: pointer !important; position: relative !important;
    transition: border-color .2s, background .2s !important;
    min-height: 160px !important;
}
.cr-curriculum-content .am-uploadfile:hover {
    border-color: #14213d !important; background: #f0f4ff !important;
}
/* Hide the SVG dashed border rect (we draw the border with CSS) */
.cr-curriculum-content .am-uploadfile .am-border-svg { display: none !important; }
/* Hide the input */
.cr-curriculum-content .am-uploadfile input[type="file"] {
    position: absolute !important; inset: 0 !important; opacity: 0 !important;
    width: 100% !important; height: 100% !important; cursor: pointer !important;
    z-index: 2 !important;
}
/* Upload icon */
.cr-curriculum-content .am-uploadfile em {
    display: flex !important; align-items: center !important; justify-content: center !important;
    width: 52px !important; height: 52px !important; border-radius: 50% !important;
    background: #eef2ff !important; font-style: normal !important;
}
.cr-curriculum-content .am-uploadfile em i {
    font-size: 22px !important; color: #3d52a4 !important;
    background: none !important; display: block !important;
}
/* Upload text */
.cr-curriculum-content .am-uploadfile > span:not(.am-dropfileshadow) {
    font-size: .85rem !important; color: #3d4a63 !important; font-weight: 500 !important;
    display: block !important;
}
.cr-curriculum-content .am-uploadfile > span:not(.am-dropfileshadow) strong { color: #14213d !important; font-weight: 700 !important; }
.cr-curriculum-content .am-uploadfile > span:not(.am-dropfileshadow) > span em {
    font-size: .75rem !important; color: #94a3b8 !important; font-style: normal !important;
    display: block !important; margin-top: 4px !important; width: auto !important;
    height: auto !important; background: none !important; border-radius: 0 !important;
}
/* Drag overlay */
.cr-curriculum-content .am-dropfileshadow {
    position: absolute !important; inset: 0 !important;
    background: rgba(20,33,61,.06) !important; border-radius: 12px !important;
    display: flex !important; align-items: center !important; justify-content: center !important;
    opacity: 0 !important; pointer-events: none !important; transition: opacity .2s !important;
}
.cr-curriculum-content .am-uploadfile.am-dragfile .am-dropfileshadow { opacity: 1 !important; }

/* ── Action buttons inside lesson editor ─────────────────────────── */
.cr-curriculum-content .cr-btns {
    display: flex !important; align-items: center !important;
    justify-content: space-between !important;
    padding-top: 16px !important;
    border-top: 1px solid #f1f5f9 !important;
    margin-top: 16px !important; flex-wrap: wrap !important; gap: 10px !important;
}
.cr-curriculum-content .cr-preview {
    display: flex !important; align-items: center !important; gap: 8px !important;
}
.cr-curriculum-content .cr-label {
    font-size: .78rem !important; font-weight: 600 !important; color: #3d4a63 !important;
}
.cr-curriculum-content .am-white-btn {
    padding: 9px 16px !important; border-radius: 8px !important;
    border: 1.5px solid #e4e9f0 !important; background: #fff !important;
    font-size: .78rem !important; font-weight: 600 !important; color: #5a6480 !important;
    cursor: pointer !important; transition: border-color .2s !important;
}
.cr-curriculum-content .am-white-btn:hover { border-color: #aab3c5 !important; }
.cr-curriculum-content .am-btn { height: 36px !important; padding: 0 16px !important; font-size: .78rem !important; border-radius: 8px !important; }
.cr-curriculum-content .am-remove-curriculum {
    background: #fee2e2 !important; color: #dc2626 !important;
    box-shadow: none !important;
}
.cr-curriculum-content .am-remove-curriculum:hover { background: #fecaca !important; }

/* ── Iframe video previews ───────────────────────────────────────── */
.cr-curriculum-content .am-iframe-video {
    width: 100% !important; border-radius: 10px !important;
    margin-top: 12px !important; border: 1px solid #e4e9f0 !important;
    aspect-ratio: 16/9 !important; height: auto !important;
}

/* ── Content-type selector tabs (inside lesson editor) ───────────── */
.cr-course-body .am-uploadoption { max-width: 100% !important; }

/* ── SCORM upload wrapper ────────────────────────────────────────── */
.cr-scorm-upload { width: 100%; }
.cr-scorm-upload .form-group { margin: 0 !important; }
.cr-scorm-uploading {
    display: flex !important; align-items: center !important; gap: 10px !important;
    padding: 20px !important; background: #f0f4ff !important;
    border: 1.5px solid #c7d4f8 !important; border-radius: 12px !important;
    font-size: .85rem !important; color: #3d52a4 !important; font-weight: 600 !important;
}
.cr-scorm-uploading i { font-size: 20px !important; animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.cr-scorm-done {
    display: flex !important; align-items: center !important; gap: 12px !important;
    padding: 16px 20px !important; background: #f0fdf4 !important;
    border: 1.5px solid #86efac !important; border-radius: 12px !important;
}
.cr-scorm-done > i { font-size: 24px !important; color: #22c55e !important; flex-shrink: 0; }
.cr-scorm-done > div { display: flex; flex-direction: column; gap: 2px; }
.cr-scorm-done strong { font-size: .85rem !important; color: #166534 !important; }
.cr-scorm-done small { font-size: .72rem !important; color: #4ade80 !important; word-break: break-all; }
</style>
@endpush
@push('scripts')
    <script src="https://www.youtube.com/iframe_api"></script>
    <script src="https://player.vimeo.com/api/player.js"></script>
    <script defer src="{{ asset('summernote/summernote-lite.min.js') }}"></script>
    <script defer src="{{ asset('js/livewire-sortable.js') }}"></script>
    <script src="{{ asset('js/video.min.js') }}"></script>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', (event) => {

            document.addEventListener('toggleEditorModal', function(event) {
                const {target, action} = event.detail ?? {};
                $(target).modal(action);
            });

            jQuery(document).on('click', '.cr-delete-curriculum', function() {
                const _this = $(this);
                const id = _this.data('id');
                const componentId = _this.data('component_id');
                jQuery('.cr-delete-action').attr('data-curriculum_id', id);
                jQuery('.cr-delete-action').attr('data-component_id', componentId);
                const component = eval(componentId);
                callToggleEditorModal('#delete-confirm-modal', 'show');
            });

            // Function to call toggleEditorModal
            function callToggleEditorModal(target, action) {
                const event = new CustomEvent('toggleEditorModal', {
                    detail: { target, action }
                });
                document.dispatchEvent(event);
            }

            jQuery(document).on('click', '.cr-delete-action', async function() {
                let _this = $(this);
                let componentId = _this.attr('data-component_id');
                let component = eval(componentId);
                let id = _this.attr('data-curriculum_id');
                _this.attr('disabled', 'disabled');
                _this.addClass('am-btn_disable');
                _this.find('span').text('{{ __('courses::courses.deleting') }}');
                await component.deleteRecord(id);
                _this.removeAttr('disabled');
                _this.removeClass('am-btn_disable');
                _this.find('span').text('{{ __('courses::courses.yes') }}');
                callToggleEditorModal('#delete-confirm-modal', 'hide');
            });

            jQuery(document).on('summernote.change', '.cr-summernote', function(we, contents, $editable) {
                let _this = $(this);
                let componentId = _this.data('id');
                let modelId = _this.data('model_id');
                if (componentId) {
                    const component = eval(componentId);
                    component.set(modelId, contents, false);
                }
            });

            document.addEventListener('initEditor', function(event) {
                let {target, content, modal = ''} = event?.detail ?? {};
                initEditor(target, content);
                if(modal){
                    $(modal).modal('show');
                }
            });
        });

        function initEditor(target ='', content = '') {
            setTimeout(() => {
                $(target).summernote('destroy');
                $(target).summernote(summernoteConfigs(target,'.characters-count'));
                if(content){
                    $(target).summernote('code', content);
                    var charLength = $('<div>').html(content)?.text()?.length;
                    let charSelector = '.characters-count';
                    charLeft(charLength, charSelector)
                }
            }, 0);
        }
    </script>
@endpush