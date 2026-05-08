<section class="am-achievements_section">
    @if(!empty(pagesetting('shape_image')))
        <img class="am-placeholder-one" src="{{url(Storage::url(pagesetting('shape_image')[0]['path']))}}" alt="Background shape image"> 
    @endif
    @if(!empty(pagesetting('shape_second_image')))
        <img class="am-placeholder-two" src="{{url(Storage::url(pagesetting('shape_second_image')[0]['path']))}}" alt="Background shape image"> 
    @endif
    <div class="container">
        <div class="row">
            @if(!empty(pagesetting('pre_heading')) || !empty(pagesetting('heading')) || !empty(pagesetting('paragraph')) || !empty(pagesetting('repeater_data')))
                <div class="col-12">
                    @if(!empty(pagesetting('pre_heading')) || !empty(pagesetting('heading')) || !empty(pagesetting('paragraph')))
                        <div class="am-content_box lx-text-white">
                            @if(!empty(pagesetting('pre_heading'))) <span>{{ pagesetting('pre_heading') }}</span> @endif 
                            @if(!empty(pagesetting('heading'))) <h3>{!! pagesetting('heading') !!}</h3> @endif
                            @if(!empty(pagesetting('paragraph'))) <p>{!! pagesetting('paragraph') !!}</p> @endif
                        </div>
                    @endif
                    @if(!empty(pagesetting('repeater_data')))
                        <div class="am-achievements_reviews">
                            <ul>
                                @foreach(pagesetting('repeater_data') as $key => $option)
                                    @if (!empty($option['icon']) || empty($option['sub_heading']))
                                        <li>
                                            <div class="am-achievements_Commit">
                                                @if(!empty($option['icon']))
                                                    <span>
                                                        <i class="{!! $option['icon'] !!}"></i>
                                                    </span>
                                                @endif
                                                @if(!empty($option['sub_heading']))
                                                    <strong>
                                                        {!! $option['sub_heading'] !!}
                                                    </strong>
                                                @endif
                                            </div>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>