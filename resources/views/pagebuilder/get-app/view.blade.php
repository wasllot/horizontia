
<div class="am-coming-section {{ pagesetting('select_verient') }}">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="am-coming-soon_wrap">
                    <div class="am-coming-soon">
                        <div class="am-coming-soon_content">
                            @if(!empty(pagesetting('pre_heading')))<span data-aos="fade-up" data-aos-easing="ease" data-aos-delay="200">{{ pagesetting('pre_heading') }}</span>@endif
                            @if(!empty(pagesetting('heading')))<h3 data-aos="fade-up" data-aos-easing="ease" data-aos-delay="300">{!! pagesetting('heading') !!}</h3>@endif
                            @if(!empty(pagesetting('paragraph')))<p data-aos="fade-up" data-aos-easing="ease" data-aos-delay="400">{!! pagesetting('paragraph') !!}</p>@endif
                            @if(!empty(pagesetting('app_store_image')) || !empty(pagesetting('google_image')))
                            <div class="am-coming-soon_btns" data-aos="fade-up" data-aos-easing="ease" data-aos-delay="500">
                                @if(!empty(pagesetting('app_store_image')[0]['path']))
                                    <img src="{{url(Storage::url(pagesetting('app_store_image')[0]['path']))}}" alt="App store image">
                                @endif
                                @if(!empty(pagesetting('google_image')[0]['path']))
                                    <img src="{{url(Storage::url(pagesetting('google_image')[0]['path']))}}" alt="Google play store image">
                                @endif
                            </div>
                            @endif
                        </div>
                        @if(!empty(pagesetting('mobile_image')))
                            <figure data-aos="fade-left"  data-aos-duration="500" data-aos-easing="linear" data-aos="fade-up" data-aos-easing="ease" data-aos-delay="600">
                                @if(!empty(pagesetting('mobile_image')[0]['path']))
                                    <img src="{{url(Storage::url(pagesetting('mobile_image')[0]['path']))}}" alt="Mobile image">
                                @endif
                            </figure>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <img src="{{asset('demo-content/footer-vector-img.png')}}" alt="image">
</div>