<?php

use Livewire\Volt\Component;
use Diglactic\Breadcrumbs\Breadcrumbs;

new class extends Component
{

};
?>

<header class="am-header" style="justify-content:space-between; display:flex; align-items:center;">
    <div style="display:flex; align-items:center; gap:20px;">
        {{ Breadcrumbs::render() }}
    </div>
    
    <div style="display:flex; align-items:center; justify-content:flex-end; gap:20px; width:100%; max-width:600px;">
        <div style="display:none; /* or use a media query class to hide on mobile */ display:flex; gap:15px; margin-right:15px;">
            <a href="{{ route('home') }}" style="color:#667eea; font-weight:700; text-decoration:none; display:flex; align-items:center; gap:6px; font-size:.9rem; background:#f4f6fa; padding:8px 16px; border-radius:12px; transition:all .2s;" onmouseenter="this.style.background='#eaedf5'" onmouseleave="this.style.background='#f4f6fa'">
                <i class="am-icon-home"></i> Inicio
            </a>
            <a href="{{ route('courses.search-courses') }}" style="color:#ff9a9e; font-weight:700; text-decoration:none; display:flex; align-items:center; gap:6px; font-size:.9rem; background:#fff0f1; padding:8px 16px; border-radius:12px; transition:all .2s;" onmouseenter="this.style.background='#ffe6e8'" onmouseleave="this.style.background='#fff0f1'">
                <i class="am-icon-book-1"></i> Explorar Cursos
            </a>
        </div>

        <form class="am-header_form" style="margin:0;">
            <fieldset>
                <div class="form-group" style="background:#fff; border-radius:12px; padding:2px; box-shadow:0 4px 10px rgba(0,0,0,0.02);" @click="$dispatch('toggle-spotlight')">
                    <i class="am-icon-search-02" style="color:#aaa;"></i>
                    <input type="text" class="form-control" placeholder="{{ __('general.quick_search') }}" style="border:none; box-shadow:none;">
                    <span style="background:#f5f5f5; border-radius:6px; padding:2px 6px;">{{ __('general.ctrl_k') }}</span>
                </div>
            </fieldset>
        </form>
        <div class="am-header_user" style="margin-left:0;">
            <x-frontend.user-menu />
        </div>
    </div>
</header>