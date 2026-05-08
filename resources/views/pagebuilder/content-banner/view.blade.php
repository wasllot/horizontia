
<section class="am-works"> 
    @if(!empty(pagesetting('pre_heading')) || !empty(pagesetting('heading')) || !empty(pagesetting('paragraph')))
        <div class="am-page-title-wrap">
            <div class="am-content_box">
                @if(!empty(pagesetting('pre_heading'))) <span>{{ pagesetting('pre_heading') }}</span> @endif 
                @if(!empty(pagesetting('heading'))) <h3>{!! pagesetting('heading') !!}</h3> @endif
                @if(!empty(pagesetting('paragraph'))) <p>{{ pagesetting('paragraph') }}</p> @endif
            </div>
        </div>
    @endif

    @if(!empty(pagesetting('student_btn_txt')) || !empty(pagesetting('student_btn_url')) || !empty(pagesetting('tutor_btn_url')) || !empty(pagesetting('tutor_btn_txt')))
        <div class="am-joincommunity_btn">
            @if(!empty(pagesetting('student_btn_txt')))
                <a href="@if(!empty(pagesetting('student_btn_url'))) {{ pagesetting('student_btn_url') }} @endif" class="am-btn">
                    {{ pagesetting('student_btn_txt') }}
                </a>
            @endif
            @if(!empty(pagesetting('tutor_btn_url')))
                <a href="@if(!empty(pagesetting('tutor_btn_url'))) {{ pagesetting('tutor_btn_url') }} @endif" class="am-white-btn">
                    {{ pagesetting('tutor_btn_txt') }}
                </a>
            @endif
        </div>
    @endif
</section>
