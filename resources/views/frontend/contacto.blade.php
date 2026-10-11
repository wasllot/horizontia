@php $banner = app(\App\Services\PageBannerService::class)->system('contacto'); @endphp
<x-guest-layout>
    <x-front.header />

    <x-page-banner :title="$banner['title']" :subtitle="$banner['subtitle']" :image="$banner['image']" :overlay="$banner['overlay']" :align="$banner['align']" crumb="Contacto" crumbs />

    @livewire('frontend.contact-form')

    <x-front.footer />
</x-guest-layout>
