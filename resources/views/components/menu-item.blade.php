@props(['menu', 'enableToggle' => false])
@php
   $menu = is_array($menu) ? json_decode(json_encode($menu), false) : $menu;
   $hasChildren = !empty($menu->children) && count($menu->children) > 0;
@endphp
<li class="{{ $hasChildren ? 'page-item-has-children' : '' }}">
    <a href="{{ $hasChildren ? 'javascript:;' : (!empty($menu->route) ? url($menu->route ) : url('/'))}}"
        @if($enableToggle && $hasChildren)  data-bs-toggle="collapse" data-bs-target="#{{ $menu->id }}" @endif>
        {!! ucfirst($menu->label ?? '') !!}
        @if( $hasChildren )
            <i class="am-icon-chevron-down"></i>
        @endif
    </a>
    @if( $hasChildren )
        <ul class="sub-menu {{ $enableToggle ? 'collapse' : '' }}" id="{{ $menu->id ?? uniqid() }}">
            @foreach( $menu->children as $child)
                <x-menu-item :menu="$child" />
            @endforeach
        </ul>
    @endif
</li>