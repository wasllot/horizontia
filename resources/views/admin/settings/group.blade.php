<section class="hz-set__group" data-testid="group-{{ \Illuminate\Support\Str::slug($g['title']) }}">
    <h4>{{ $g['title'] }}</h4>
    @if (!empty($g['intro']))<p class="hz-set__intro">{{ $g['intro'] }}</p>@endif
    {!! getSectionSetting(['tab_key' => $g['section']], $g['fields']) !!}
</section>
