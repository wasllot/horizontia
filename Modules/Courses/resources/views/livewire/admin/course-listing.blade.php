<main class="tb-main am-dispute-system am-courses-system">
    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="tb-dhb-mainheading">
                <h4>{{ __('courses::courses.all_courses') }} <span class="hz-count">{{ $courses->total() }}</span></h4>
                <a href="{{ route('courses.admin.create-course') }}" class="hz-btn hz-btn--sm" id="admin-create-course-btn"><i class="icon-plus" aria-hidden="true"></i> {{ __('courses::courses.create_course') }}</a>
            </div>
            <form class="hz-toolbar" onsubmit="return false">
                <div class="hz-toolbar__search">
                    <i class="icon-search" aria-hidden="true"></i>
                    <input type="search" class="form-control" wire:model.live.debounce.500ms="filters.keyword" autocomplete="off" placeholder="{{ __('courses::courses.search_by_keyword') }}" aria-label="{{ __('courses::courses.search_by_keyword') }}">
                </div>
                <div class="hz-toolbar__filter hz-toolbar__filter--wide" wire:ignore>
                    <select data-componentid="@this" class="am-select2 form-control" data-searchable="false" data-live="true" id="course-status-filter" data-wiremodel="filters.status" aria-label="{{ __('courses::courses.status') }}">
                        <option value="">{{ __('Todos los estados') }}</option>
                        @foreach ($statuses as $filterStatus)
                            <option value="{{ $filterStatus }}" @selected(($filters['status'] ?? '') === $filterStatus)>{{ __('courses::courses.' . $filterStatus) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
            <div class="am-disputelist_wrap">
                <div class="am-disputelist am-custom-scrollbar-y">
                    @if (!$courses->isEmpty())
                        <table class="tb-table">
                            <thead>
                                <tr>
                                    <th class="hz-col-main">{{ __('courses::courses.course_title') }}</th>
                                    <th>{{ __('courses::courses.instructor') }}</th>
                                    <th>{{ __('courses::courses.category') }}</th>
                                    <th>{{ __('courses::courses.status') }}</th>
                                    <th><span class="hz-sr">{{ __('courses::courses.actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($courses as $course)
                                    @php
                                        $hasThumb = !empty($course->thumbnail?->path) && mediaExists($course->thumbnail->path);
                                        $tone = ['active' => 'success', 'under_review' => 'warning', 'need_revision' => 'danger'][$course->status] ?? 'neutral';
                                        $instructor = $course->instructor?->profile;
                                    @endphp
                                    <tr wire:key="course-{{ $course->id }}">
                                        <td data-label="{{ __('courses::courses.course_title') }}">
                                            <div class="hz-coursecell">
                                                <span class="hz-coursecell__thumb" aria-hidden="true">
                                                    <img src="{{ $hasThumb ? mediaThumb($course->thumbnail->path, 160) : asset('modules/courses/images/course.png') }}" alt="" loading="lazy" decoding="async" width="72" height="48">
                                                </span>
                                                <span class="hz-coursecell__text">
                                                    <strong>{{ html_entity_decode($course->title) }}</strong>
                                                    <small>#{{ $course->id }}@unless ($hasThumb) · <em>{{ __('Sin imagen de portada') }}</em>@endunless</small>
                                                </span>
                                            </div>
                                        </td>
                                        <td data-label="{{ __('courses::courses.instructor') }}">
                                            <x-panel.user-cell :name="$instructor?->full_name ?? '—'" :image="$instructor?->image" />
                                        </td>
                                        <td data-label="{{ __('courses::courses.category') }}">
                                            <span class="hz-twoline"><strong>{{ $course->category?->name }}</strong><small>{{ \Illuminate\Support\Str::after((string) $course->subCategory?->name, ' · ') }}</small></span>
                                        </td>
                                        <td data-label="{{ __('courses::courses.status') }}">
                                            <x-panel.badge :tone="$tone">{{ __('courses::courses.' . $course->status) }}</x-panel.badge>
                                        </td>
                                        <td data-label="{{ __('courses::courses.actions') }}">
                                            <div class="hz-acts">
                                                @if ($course->status == 'under_review')
                                                    <button type="button" class="hz-chipbtn hz-chipbtn--ok" wire:click="approveCourse({{ $course->id }})"><i class="icon-check" aria-hidden="true"></i> {{ __('courses::courses.approve_course') }}</button>
                                                    <x-panel.action icon="icon-x" :label="__('courses::courses.reject_course')" tone="danger" wire:click="rejectCourse({{ $course->id }})" />
                                                @endif
                                                <a class="hz-act" href="{{ route('courses.course-detail', ['slug' => $course->slug]) }}" target="_blank" rel="noopener" title="{{ __('courses::courses.view_details') }}" aria-label="{{ __('courses::courses.view_details') }}"><i class="icon-eye" aria-hidden="true"></i></a>
                                                <a class="hz-act" href="{{ $course->enrolleesUrl() }}" title="Inscritos" aria-label="Inscritos" data-testid="enrollees-link"><i class="icon-users" aria-hidden="true"></i></a>
                                                <a class="hz-act" href="{{ route('courses.admin.edit-course', ['tab' => 'details', 'id' => $course->id]) }}" title="{{ __('courses::courses.edit_course') }}" aria-label="{{ __('courses::courses.edit_course') }}"><i class="icon-edit" aria-hidden="true"></i></a>
                                                <x-panel.action icon="icon-trash-2" :label="__('courses::courses.remove_course')" tone="danger" @click="$wire.dispatch('showConfirm', { id : {{ $course->id }}, action : 'delete-course' })" />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <x-no-record :image="asset('images/empty.png')" :title="__('courses::courses.no_records_found')" />
                    @endif
                </div>
                {{ $courses->links('pagination.custom') }}
            </div>
        </div>
    </div>
</main>
