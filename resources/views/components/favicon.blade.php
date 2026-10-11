@if(!empty(setting('_general.favicon')))
    <link rel="icon" href="{{ url(mediaUrl(setting('_general.favicon')[0]['path'])) }}" type="image/x-icon">
@endif