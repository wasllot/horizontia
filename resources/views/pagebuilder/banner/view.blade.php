<section class="am-learning"> 
    <div class="container">
        <div class="row">
            <div class="col-12">
                @if(!empty(pagesetting('pre_heading')) 
                    || !empty(pagesetting('heading')) 
                    || !empty(pagesetting('paragraph')) 
                    || !empty(pagesetting('search_placeholder')) 
                    || !empty(pagesetting('search_btn_txt')) 
                    || !empty(pagesetting('video')) 
                    || !empty(pagesetting('image_heading')) 
                    || !empty(pagesetting('image_paragraph')) 
                    || !empty(pagesetting('tutors_image')) 
                    || !empty(pagesetting('student_image')))
                    <div class="am-learning_content">
                        @if(!empty(pagesetting('pre_heading')) 
                            || !empty(pagesetting('heading')) 
                            || !empty(pagesetting('paragraph')) 
                            || !empty(pagesetting('search_placeholder')) 
                            || !empty(pagesetting('search_btn_txt')))
                            <div class="am-learning_details">
                                @if(!empty(pagesetting('pre_heading')))
    
                                @endif
                                @if(!empty(pagesetting('heading')) || !empty(pagesetting('paragraph')))
                                    <div class="am-learning_title">
                                        @if(!empty(pagesetting('heading')))
                                            <h3>{!! pagesetting('heading') !!}</h3>
                                        @endif
                                        @if(!empty(pagesetting('paragraph')))
                                            <p>{!! pagesetting('paragraph') !!}</p>
                                        @endif
                                    </div>
                                @endif
                                <!-- <form action="{{ url('find-tutors') }}" method="GET" class="am-learning_search">
                                    <div class="am-learning_search_input">
                                        <input type="text" name="keyword" placeholder="{{ pagesetting('search_placeholder') }}">
                                    </div>
                                    <button type="submit" class="am-learning_search_btn am-btn"><i class="am-icon-search-02"></i></button>
                                </form> -->
                                <br><br>
                                <button type="button" class="am-btn">CONTÁCTANOS</button>
                            </div>
                        @endif
                        @if(!empty(pagesetting('video'))
                            || !empty(pagesetting('image_heading')) 
                            || !empty(pagesetting('image_paragraph')) 
                            || !empty(pagesetting('tutors_image')) 
                            || !empty(pagesetting('student_image')))
                            <div class="am-learning_video">
                                @if(!empty(pagesetting('video')))
                                    <div class="am-learning_video_info">
                                        @if(!empty(pagesetting('video')[0]['path']))
                                            <video class="video-js" data-setup='{}' preload="auto" id="auth-video" width="416" height="284" controls >
                                                <source src="{{ url(Storage::url(pagesetting('video')[0]['path'])) }}" type="video/mp4" >
                                            </video>   
                                        @endif
                                    </div>
                                @endif
                                @if(!empty(pagesetting('image_heading')) || !empty(pagesetting('image_paragraph')))
                                    <div class="am-learning_video_tag">
                                        <div class="am-learning_video_tag_talent">
                                            <div>
                                                @if(!empty(pagesetting('image_heading')))
                                                    <svg class="am-text-svg" viewBox="0 0 100 100">
                                                        <path id="circlePath" d="M 10, 50 a 40,40 0 1,1 80,0 40,40 0 1,1 -80,0"></path>
                                                        <text>
                                                            <textPath href="#circlePath">
                                                            {{pagesetting('image_heading')}}
                                                            </textPath>
                                                        </text>
                                                    </svg>
                                                @endif
                                                <span>
                                                    <svg width="93" height="92" viewBox="0 0 93 92" fill="none">
                                                        <g filter="url(#filter0_d_3553_56658)">
                                                            <path d="M46.4955 26.0208L51.2261 38.8052L64.0104 43.5358L51.2261 48.2664L46.4955 61.0508L41.7648 48.2664L28.9805 43.5358L41.7648 38.8052L46.4955 26.0208Z" fill="#F55C2B"/>
                                                        </g>
                                                        <defs>
                                                            <filter id="filter0_d_3553_56658" x="0.956495" y="0.33217" width="91.0782" height="91.0779" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                                            <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                                            <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                                                            <feOffset dy="2.33533"/>
                                                            <feGaussianBlur stdDeviation="14.012"/>
                                                            <feComposite in2="hardAlpha" operator="out"/>
                                                            <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.05 0"/>
                                                            <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_3553_56658"/>
                                                            <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_3553_56658" result="shape"/>
                                                            </filter>
                                                        </defs>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                        @if(!empty(pagesetting('image_paragraph')))
                                            <p>{{ pagesetting('image_paragraph') }}</p>
                                        @endif
                                    </div>
                                @endif
                                @if(!empty(pagesetting('tutors_image')))
                                    <figure class="am-learning_video_tutors-img">
                                        @if(!empty(pagesetting('tutors_image')[0]['path']))
                                            <img src="{{url(Storage::url(pagesetting('tutors_image')[0]['path']))}}" alt="Registered Tutors with profile pictures">
                                        @endif
                                    </figure>
                                @endif
                                @if(!empty(pagesetting('student_image')))
                                    <figure class="am-learning_video_talents-img">
                                        @if(!empty(pagesetting('student_image')[0]['path']))
                                            <img src="{{url(Storage::url(pagesetting('student_image')[0]['path']))}}" alt="Profile card">
                                        @endif
                                    </figure>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@pushOnce('styles')
@vite(['public/css/videojs.css'])
@endpushOnce
@pushOnce('scripts')
    <script src="{{ asset('js/video.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            setTimeout(() => {
                bannerVideoJs();
            }, 500);
        });

        document.addEventListener('loadSectionJs', (event) => {
            if(event.detail.sectionId === 'banner'){
                bannerVideoJs();
            }
        });

        function bannerVideoJs(){
            if(typeof videojs !== 'undefined'){
                jQuery('.video-js').each((index, item) => {
                    item.onloadeddata =  function(){
                        videojs(item);
                    }
                })
            }
        }
    </script>
@endpushOnce
