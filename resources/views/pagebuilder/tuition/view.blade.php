
<div class="am-tuition {{ pagesetting('select_verient') }}">
    <div class="container">
        <div class="row">
            <div class="col-12">
                @if(!empty(pagesetting('pre_heading')) || !empty(pagesetting('heading')) || !empty(pagesetting('paragraph')))
                    <div class="am-section_title am-section_title_center {{ pagesetting('section_title_variation') }}">
                        @if(!empty(pagesetting('pre_heading')))<span class="am-tag">{{ pagesetting('pre_heading') }}</span>@endif
                        @if(!empty(pagesetting('heading')))<h2>{!! pagesetting('heading') !!}</h2>@endif
                        @if(!empty(pagesetting('paragraph')))<p>{!! pagesetting('paragraph') !!}</p>@endif
                    </div>
                @endif
                <div class="am-tuition_content">
                    @if(!empty(pagesetting('become_student_heading')) 
                    || !empty(pagesetting('become_student_paragraph'))
                    || !empty(pagesetting('become_student_btn_txt'))
                    || !empty(pagesetting('become_student_btn_url'))
                    || !empty(pagesetting('become_student_image')))
                        <div class="am-tuition_content_detail" data-aos="fade-up" data-aos-easing="ease" data-aos-delay="200">
                            @if(!empty(pagesetting('become_student_heading')))
                            <h4 data-aos="fade-up" data-aos-easing="ease" data-aos-delay="200">{!! pagesetting('become_student_heading') !!}</h4>
                            @endif
                            @if(!empty(pagesetting('become_student_paragraph')))
                            <p data-aos="fade-up" data-aos-easing="ease" data-aos-delay="400">{!! pagesetting('become_student_paragraph') !!}</p>
                            @endif
                            @if(!empty(pagesetting('become_student_btn_txt')) || !empty(pagesetting('become_student_btn_url'))) 
                                <a href="@if(!empty(pagesetting('become_student_btn_url'))) {{ pagesetting('become_student_btn_url') }} @endif" class="am-primary-btn" data-aos="fade-up" data-aos-easing="ease" data-aos-delay="600">
                                    {{ pagesetting('become_student_btn_txt') }}
                                </a>
                            @endif
                            @if(!empty(pagesetting('become_student_image')[0]['path']))
                                <figure class="am-tuition_content_image" data-aos="fade-up" data-aos-easing="ease" data-aos-delay="800">
                                    <img src="{{url(Storage::url(pagesetting('become_student_image')[0]['path']))}}" alt="Learning community image">
                                </figure>
                            @endif
                            <img class="am-postion-img" src="{{ asset('demo-content/home-v2/community-element1.png') }}" alt="">
                        </div>
                    @endif
                    @if(!empty(pagesetting('become_tutor_heading')) 
                    || !empty(pagesetting('become_tutor_paragraph'))
                    || !empty(pagesetting('become_tutor_btn_txt'))
                    || !empty(pagesetting('become_tutor_btn_url'))
                    || !empty(pagesetting('become_tutor_image')))
                        <div class="am-tuition_content_detail" data-aos="fade-up" data-aos-easing="ease" data-aos-delay="1200">
                            @if(!empty(pagesetting('become_tutor_heading')))
                            <h4 data-aos="fade-up" data-aos-easing="ease" data-aos-delay="1400">{!! pagesetting('become_tutor_heading') !!}</h4>
                            @endif
                            @if(!empty(pagesetting('become_tutor_paragraph')))
                            <p data-aos="fade-up" data-aos-easing="ease" data-aos-delay="1600">{!! pagesetting('become_tutor_paragraph') !!}</p>
                            @endif
                            @if(!empty(pagesetting('become_tutor_btn_txt')) || !empty(pagesetting('become_tutor_btn_url'))) 
                                <a href="@if(!empty(pagesetting('become_tutor_btn_url'))) {{ pagesetting('become_tutor_btn_url') }} @endif" class="am-primary-btn" data-aos="fade-up" data-aos-easing="ease" data-aos-delay="1800">
                                    {{ pagesetting('become_tutor_btn_txt') }}
                                </a>
                            @endif
                            @if(!empty(pagesetting('become_tutor_image')[0]['path']))
                                <figure class="am-tuition_content_image" data-aos="fade-up" data-aos-easing="ease" data-aos-delay="2000">
                                    <img src="{{url(Storage::url(pagesetting('become_tutor_image')[0]['path']))}}" alt="Join as a tutor image">
                                </figure>
                            @endif
                            <img class="am-postion-img2" src="{{ asset('demo-content/home-v2/community-element2.png') }}" alt="">
                        </div>
                    @endif   
                </div>
            </div>
        </div>
    </div>
</div>