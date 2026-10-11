<?php

use Livewire\Volt\Component;
use Diglactic\Breadcrumbs\Breadcrumbs;

new class extends Component
{

};
?>

<header class="hz-topbar">
    <button type="button" class="hz-iconbtn hz-topbar__menu" data-hz-menu aria-controls="hz-sidebar" aria-expanded="false" aria-label="{{ __('Abrir menú') }}">
        <x-panel.icon name="menu" />
    </button>
    <div class="hz-topbar__crumbs">{{ Breadcrumbs::render() }}</div>
    <div class="hz-topbar__actions">
        <button type="button" class="hz-searchbtn" @click="$dispatch('toggle-spotlight')" aria-label="{{ __('general.quick_search') }}">
            <x-panel.icon name="search" />
            <span>{{ __('general.quick_search') }}</span>
            <kbd class="hz-kbd">{{ __('general.ctrl_k') }}</kbd>
        </button>
        <x-panel.topbar-actions />
    </div>
</header>
