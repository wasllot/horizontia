<div class="am-featured-mentors {{ pagesetting('select_verient') }} {{ pagesetting('bg_color_verient') }}">
    <div class="container">
        <div class="row">
            <div class="col-12">
                @if(!empty(pagesetting('pre_heading')) || !empty(pagesetting('heading')) || !empty(pagesetting('paragraph')))
                    <div class="am-featured-mentors-head">
                        <div class="am-section_title {{ pagesetting('section_title_variation') }}">
                            @if(!empty(pagesetting('pre_heading'))) <span class="am-tag">{{ pagesetting('pre_heading') }}</span> @endif 
                            @if(!empty(pagesetting('heading'))) <h2>{!! pagesetting('heading') !!}</h2> @endif
                            @if(!empty(pagesetting('paragraph'))) <p>{{ pagesetting('paragraph') }}</p> @endif
                        </div>
                        @if(!empty(pagesetting('explore_mentors_btn_text')))
                            <a href="@if(!empty(pagesetting('explore_mentors_btn_url'))) {{ pagesetting('explore_mentors_btn_url') }} @endif" class="am-primary-btn-white">
                                {{ pagesetting('explore_mentors_btn_text') }}
                            </a>
                        @endif
                    </div>
                @endif
                @php
                    $sectionVerient     = !empty(pagesetting('select_verient')) ? pagesetting('select_verient') : 'am-tutors-varient-two';
                    $viewProfileBtn     = !empty(pagesetting('view_tutor_btn_text')) ? pagesetting('view_tutor_btn_text') : 'View Profile';
                    $viewProfileBtnUrl  = !empty(pagesetting('view_tutor_btn_url')) ? pagesetting('view_tutor_btn_url') : '#';
                    $selectTutor        = !empty(pagesetting('select_tutor')) ? pagesetting('select_tutor') : 8;
                    $slider             = true; 
                @endphp
                <livewire:experienced-tutors :sectionVerient="$sectionVerient" :viewProfileBtn="$viewProfileBtn" :viewProfileBtnUrl="$viewProfileBtnUrl" :selectTutor="$selectTutor" :slider="$slider">
            </div>
        </div>
    </div>
    @if(!empty(pagesetting('left_shape_image')))
        @if(!empty(pagesetting('left_shape_image')[0]['path']))
            <img src="{{url(Storage::url(pagesetting('left_shape_image')[0]['path']))}}" class="am-bgimg1" alt="Background shape image">
        @endif
    @endif
    @if(!empty(pagesetting('right_shape_image')))
        @if(!empty(pagesetting('right_shape_image')[0]['path']))
            <img src="{{url(Storage::url(pagesetting('right_shape_image')[0]['path']))}}" class="am-bgimg2" alt="Background shape image">
        @endif
    @endif
</div>






