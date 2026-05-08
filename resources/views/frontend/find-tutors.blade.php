@extends('layouts.frontend-app')

@prepend('styles')
<style>
    /* Find Tutors Page Styles */
    .am-find-tutors-area {
        min-height: 100vh;
        background-color: #f8f9fa;
    }
    
    /* Header Section */
    .am-searchhead {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        padding: 40px 0;
        color: white;
    }
    
    .am-searchhead_title h2 {
        font-size: 1.75rem;
        font-weight: 700;
        color: white;
        margin-bottom: 0.5rem;
    }
    
    .am-searchhead_title p {
        font-size: 1rem;
        color: rgba(255,255,255,0.7);
        margin: 0;
    }
    
    /* Breadcrumb */
    .am-breadcrumb {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        list-style: none;
        padding: 0;
        margin: 0 0 1rem 0;
        font-size: 0.875rem;
    }
    
    .am-breadcrumb li {
        display: flex;
        align-items: center;
    }
    
    .am-breadcrumb a {
        color: rgba(255,255,255,0.7);
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .am-breadcrumb a:hover {
        color: white;
    }
    
    .am-breadcrumb em {
        color: rgba(255,255,255,0.4);
        font-style: normal;
    }
    
    .am-breadcrumb .active span {
        color: #F2D07F;
    }
    
    /* Filter Tabs */
    .am-searchfilter_tabs {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1rem 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .am-searchfilter_tabslist {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        list-style: none;
        padding: 0;
        margin: 0;
        flex-wrap: wrap;
    }
    
    .am-session-tab {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
        color: #6b7280;
        background-color: #f3f4f6;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .am-session-tab:hover {
        background-color: #e5e7eb;
    }
    
    .am-session-tab.active {
        background-color: #1a1a1a;
        color: white;
    }
    
    .am-clearfilterbtn a {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        color: #ef4444;
        background: #fef2f2;
        border-radius: 9999px;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .am-clearfilterbtn a:hover {
        background: #fee2e2;
    }
    
    /* Search Filter Bar */
    .am-searchfilter_wrap {
        background: white;
        padding: 1.5rem 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .am-searchfilter {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .am-searchfilter_item {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .am-searchfilter_title {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .am-searchfilteritems {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .am-searchfilter_left {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        width: 100%;
    }
    
    .am-searchinput {
        position: relative;
        flex: 1;
        min-width: 200px;
    }
    
    .am-searchinput input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        transition: all 0.2s;
    }
    
    .am-searchinput input:focus {
        outline: none;
        border-color: #F2D07F;
        box-shadow: 0 0 0 3px rgba(242, 208, 127, 0.1);
    }
    
    .am-searchinput_icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
    }
    
    .am-select_title {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        white-space: nowrap;
    }
    
    /* Tutor Search Section */
    .am-tutorsearch_section {
        padding: 2rem 0;
    }
    
    /* Best Tutor Card */
    .am-besttutor {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 1rem;
    }
    
    .am-besttutor_video {
        border-radius: 0.75rem;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    
    .am-besttutor_video video {
        width: 100%;
        height: auto;
        display: block;
    }
    
    .am-besttutor_footer_tips h4 {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 0.75rem;
    }
    
    .am-besttutor_footer_tips p {
        font-size: 0.875rem;
        color: #6b7280;
        line-height: 1.6;
        margin-bottom: 1rem;
    }
    
    .am-besttutor_info_list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .am-besttutor_info_list li {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.5rem 0;
        font-size: 0.875rem;
        color: #374151;
    }
    
    .am-besttutor_info_list li::before {
        content: '✓';
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.25rem;
        height: 1.25rem;
        background: #dcfce7;
        color: #16a34a;
        border-radius: 50%;
        font-size: 0.75rem;
        font-weight: bold;
        flex-shrink: 0;
    }
    
    /* Responsive Design */
    @media (max-width: 1024px) {
        .am-searchfilter {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .am-besttutor {
            position: static;
            margin-top: 2rem;
        }
    }
    
    @media (max-width: 768px) {
        .am-searchhead {
            padding: 30px 0;
        }
        
        .am-searchhead_title h2 {
            font-size: 1.5rem;
        }
        
        .am-searchfilter_wrap {
            padding: 1rem 0;
        }
        
        .am-searchfilter {
            grid-template-columns: 1fr;
        }
        
        .am-searchfilter_tabslist {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            flex-wrap: nowrap;
        }
        
        .am-session-tab {
            white-space: nowrap;
        }
        
        .am-searchfilteritems {
            flex-direction: column;
            align-items: stretch;
        }
        
        .am-searchfilter_left {
            flex-direction: column;
        }
        
        .am-searchinput {
            width: 100%;
            min-width: 100%;
        }
        
        .am-select {
            width: 100%;
        }
        
        .am-select select {
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .am-searchhead {
            padding: 20px 0;
        }
        
        .am-searchhead_title h2 {
            font-size: 1.25rem;
        }
        
        .am-searchhead_title p {
            font-size: 0.875rem;
        }
        
        .am-breadcrumb {
            font-size: 0.75rem;
        }
        
        .am-session-tab {
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
        }
    }
</style>
@endprepend

@section('content')
<div class="am-find-tutors-area">
    <div class="am-searchhead">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <ol class="am-breadcrumb">
                        <li><a href="{{ route('home') }}">{{ __('sidebar.home') }}</a></li>
                        <li><em>/</em></li>
                        <li class="active"><span>{{ __('sidebar.find_tutor') }}</span></li>
                    </ol>
                    <div class="am-searchhead_title">
                        <h2>{{ __('sidebar.discover_tutor_text') }}</h2>
                        <p>{{ __('sidebar.discover_tutor_desc') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="am-searchfilter_wrap">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="am-searchfilter_tabs">
                        <ul class="am-searchfilter_tabslist">
                            <li>
                                <a href="javascript:void(0);" data-type="" @class(['am-session-tab', 'active'=> $filters['session_type'] == ''])>
                                    {{ __('tutor.all_sessions') }}
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" data-type="one" @class(['am-session-tab', 'active'=> $filters['session_type'] == 'one'])>
                                    {{ __('tutor.private_sessions') }}
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" data-type="group" @class(['am-session-tab', 'active'=> $filters['session_type'] == 'group'])>
                                    {{ __('tutor.group_sessions') }}
                                </a>
                            </li>
                        </ul>
                        <div class="am-clearfilterbtn d-none">
                            <a href="javascript:void(0);" id="clear_filters">
                                {{ __('general.clear_all_filter') }}
                                <i class="am-icon-multiply-02"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="am-searchfilter">
                        <div class="am-searchfilter_item">
                            <span class="am-searchfilter_title">{{ __('subject.subject_group') }}</span>
                            <span class="am-select">
                                <select id="group_id" class="am-select2" data-searchable="true"
                                    data-class="am-filter-dropdown"
                                    data-placeholder="{{ __('subject.choose_subject_group') }}">
                                    <option></option>
                                    @foreach ($subjectGroups as $group)
                                    <option value="{{ $group->id }}" {{ $group->id == ($filters['group_id'] ?? '') ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </span>
                        </div>
                        <div class="am-searchfilter_item">
                            <span class="am-searchfilter_title">{{ __('subject.choose_subject_label') }}</span>
                            <span class="am-select">
                                <select id="subject_id" class="am-select2" multiple data-searchable="true"
                                    data-class="am-filter-dropdown"
                                    data-placeholder="{{ __('subject.choose_subject_label') }}">
                                    <option></option>
                                    @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ in_array($subject->id, $filters['subject_id'] ?? []) ? 'selected' : '' }}>
                                        {{ $subject?->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </span>
                        </div>
                        <div class="am-searchfilter_item">
                            <span class="am-searchfilter_title">{{ __('calendar.max_price') }}</span>
                            <input type="text" placeholder="{{ getCurrencySymbol() }}0.00" class="form-control"
                                id="max_price" value="{!! (!empty($filters['max_price']) ? (getCurrencySymbol().$filters['max_price']) : '') !!}">
                        </div>
                        <div class="am-searchfilter_item">
                            <span class="am-searchfilter_title">{{ __('general.tutor_location') }}</span>
                            <span class="am-select">
                                @if(!empty(setting('_api.google_places_api_key')))
                                <input type="text" class="form-control" id="map_location" value="{{ $filters['country'] ?? '' }}"
                                    placeholder="{{ __('general.enter_tutor_location') }}">
                                @else
                                <select class="am-select2" id="tutor_country" data-searchable="true"
                                    data-class="am-sort_dp_option am-sort-location" data-placeholder="{{ __('general.search_by_country') }}">
                                    <option></option>
                                    @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" {{ $country->id == ($filters['country'] ?? '') ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @endif
                            </span>
                        </div>
                    </div>
                    
                    <div class="am-searchfilteritems">
                        <div class="am-searchfilter_left">
                            <div class="am-searchinput">
                                <input type="text" value="{{ $filters['keyword'] ?? '' }}"
                                    placeholder="{{ __('general.search_by_keyword') }}" class="form-control" id="keyword">
                                <span class="am-searchinput_icon">
                                    <i class="am-icon-search-02"></i>
                                </span>
                            </div>
                            <span class="am-select">
                                <span class="am-select_title">{{ __('general.sort_by') }}:</span>
                                <select class="am-select2" id="sort_by" data-searchable="false"
                                    data-class="am-sort_dp_option" data-placeholder="{{ __('general.sort_by') }}">
                                    <option></option>
                                    <option value="newest" {{ (($filters['sort_by'] ?? '') == 'newest' ? 'selected' : '') }}>{{ __('general.newest_first') }}</option>
                                    <option value="oldest" {{ (($filters['sort_by'] ?? '') == 'oldest' ? 'selected' : '') }}>{{ __('general.oldest_first') }}</option>
                                    <option value="asc" {{ (($filters['sort_by'] ?? '') == 'asc' ? 'selected' : '') }}>{{ __('general.sort_by_a_z') }}</option>
                                    <option value="desc" {{ (($filters['sort_by'] ?? '') == 'desc' ? 'selected' : '') }}>{{ __('general.sort_by_z_a') }}</option>
                                </select>
                            </span>
                            <span class="am-select am-languageselect">
                                <span class="am-select_title">{{ __('general.language') }}:</span>
                                <select class="am-select2" id="language_id" data-searchable="true" multiple
                                    data-class="am-sort_dp_option" data-placeholder="{{ __('general.select_lang') }}">
                                    <option></option>
                                    @foreach ($languages as $lang)
                                    <option value="{{ $lang->id }}" {{ in_array($lang->id, $filters['language_id'] ?? []) ? 'selected' : '' }}>
                                        {{ $lang->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="am-tutorsearch_section">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-8 col-xl-9">
                    <livewire:components.search-tutor :filters="$filters" wire:key="tutors-list-{{ time() }}" />
                </div>
                @if(!empty(setting('_lernen.help_section_media')) ||
                    !empty(setting('_lernen.help_section_title')) ||
                    !empty(setting('_lernen.help_section_description')) ||
                    !empty(setting('_lernen.help_section_bullets')) ||
                    !empty(setting('_lernen.or_section_title')) ||
                    !empty(setting('_lernen.or_section_description'))
                )
                <div class="col-12 col-lg-4 col-xl-3">
                    <div class="am-besttutor">
                        @if(!empty(setting('_lernen.help_section_media')[0]['path']))
                        <div class="am-besttutor_video">
                            <video width="560" height="180"
                                src="{{ url(Storage::url(setting('_lernen.help_section_media')[0]['path'])) }}" controls
                                class="video-js" data-setup='{}' preload="auto"></video>
                        </div>
                        @endif
                        @if (!empty(setting('_lernen.help_section_title')) ||
                            !empty(setting('_lernen.help_section_description')) ||
                            !empty(setting('_lernen.help_section_bullets')) ||
                            !empty(setting('_lernen.or_section_title')) ||
                            !empty(setting('_lernen.or_section_description'))
                        )
                        <div class="am-besttutor_footer">
                            <div class="am-besttutor_footer_tips">
                                @if (!empty(setting('_lernen.help_section_title')))
                                <h4>{{ setting('_lernen.help_section_title') }}</h4>
                                @endif
                                @if (!empty(setting('_lernen.help_section_description')))
                                <p>{{ setting('_lernen.help_section_description') }}</p>
                                @endif
                                @if (!empty(setting('_lernen.help_section_bullets')))
                                <ul class="am-besttutor_info_list">
                                    @foreach (setting('_lernen.help_section_bullets') as $bullet)
                                    <li><span>{{ $bullet['help_section'] }}</span></li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@vite([
    'public/css/flags.css',
    'public/css/videojs.css'
])
@endpush

@push('scripts')
<script src="{{ asset('js/video.min.js') }}"></script>
@if(!empty(setting('_api.google_places_api_key')))
<script async src="https://maps.googleapis.com/maps/api/js?key={{ setting('_api.google_places_api_key') }}&libraries=places&loading=async&callback=initializePlaceApi"></script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', function() {
        address = '';
        window.session_type = '';
        var filter_record = @js($filters);
        var applyFilter = true;
        let timeout;
        
        setTimeout(() => {
            clearFilters();
        }, 500);

        jQuery('.am-select2').each((index, item) => {
            let _this = jQuery(item);
            searchable = _this.data('searchable');
            let params = {
                dropdownCssClass: _this.data('class'),
                placeholder: _this.data('placeholder'),
                allowClear: true
            };
            if(!searchable){
                params['minimumResultsForSearch'] = Infinity;
            }
            _this.select2(params);
        });

        function initializePlaceApi() {
            var tutorAddress = document.getElementById('map_location');
            if(typeof google != 'undefined' && typeof google.maps.places != 'undefined'){
                var autocompleteTutor = new google.maps.places.Autocomplete(tutorAddress);
                google.maps.event.addListener(autocompleteTutor, 'place_changed', function() {
                    var place = autocompleteTutor.getPlace();
                    place.address_components?.forEach((item) => {
                        if(item.types.includes('country')){
                            filter_record['country'] = item.long_name;
                        }
                    });
                    applySearchFilter();
                });
            }
        }

        jQuery(document).on('input', '#max_price, #keyword', function(event) {
            clearTimeout(timeout);
            filter_record[event.target.id] = event.target.value;
            timeout = setTimeout(() => applySearchFilter(), 300);
        });

        jQuery(document).on('change', '#tutor_country', function(e) {
            filter_record['country'] = $('#tutor_country')?.select2("val");
            applySearchFilter();
        });

        jQuery(document).on('click', '#clear_filters', function(e) {
            filter_record = {};
            $('#keyword').val('');
            $('#max_price').val('');
            $('#map_location')?.val('');
            $('#availability')?.val(null).trigger('change');
            $('#group_id')?.val(null).trigger('change');
            $('#subject_id')?.val(null).trigger('change');
            $('#tutor_country')?.val(null)?.trigger('change');
            $('#language_id')?.val(null)?.trigger('change');
            $('#clear_filters').parent().addClass('d-none');
            applySearchFilter(false);
            let newUrl = `${window.location.pathname}`;
            window.history.replaceState({}, '', newUrl);
        });

        jQuery(document).on('click', '.am-session-tab', function(e) {
            let _this = jQuery(this);
            jQuery('.am-session-tab').removeClass('active');
            _this.addClass('active');
            filter_record['session_type'] = _this.data('type');
            applySearchFilter(false);
        });

        jQuery(document).on('change', '#group_id, #availability, #sort_by, #per_page', function(e) {
            let value = $('#'+e.target.id).select2("val");
            filter_record[e.target.id] = value?.length > 0 ? value : null;
            applySearchFilter();
        });

        jQuery(document).on('change', '#subject_id', function(e) {
            let value = $('#subject_id').select2("val");
            if(value?.length > 0){
                filter_record['subject_id'] = value[0]?.length > 0 ? value : [];
            } else {
                filter_record['subject_id'] = [];
            }
            applySearchFilter();
        });

        jQuery(document).on('change', '#language_id', function(e) {
            let value = $('#language_id').select2("val");
            if(value?.length > 0){
                filter_record['language_id'] = value[0]?.length > 0 ? value : [];
            } else {
                filter_record['language_id'] = [];
            }
            applySearchFilter();
        });

        function applySearchFilter(clearFilter = true) {
            $('.tutors-skeleton').toggleClass('d-none');
            let params = new URLSearchParams(window.location.search);
            for (let key in filter_record) {
                if (filter_record.hasOwnProperty(key)) {
                    if (filter_record[key] && (!Array.isArray(filter_record[key]) || filter_record[key].length > 0)) {
                        params.set(key, filter_record[key]);
                    } else {
                        params.delete(key);
                    }
                }
            }
            let newUrl = `${window.location.pathname}?${params.toString()}`;
            window.history.replaceState({}, '', newUrl);
            clearFilters(clearFilter);
            Livewire.dispatch('tutorFilters', {filters: filter_record});
        }

        function clearFilters(clearFilter = true) {
            const allClear = !Object.values(filter_record).some(value => value?.length > 0 );
            $('#clear_filters').parent().toggleClass('d-none', allClear || !clearFilter);
        }
    });
</script>
@endpush
@endsection
