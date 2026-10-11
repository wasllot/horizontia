@php $banner = app(\App\Services\PageBannerService::class)->system('solicitar-acceso'); @endphp
<x-guest-layout>
    <x-front.header />

    <x-page-banner :title="$banner['title']" :subtitle="$banner['subtitle']" :image="$banner['image']" :overlay="$banner['overlay']" :align="$banner['align']" crumbs />

    @livewire('frontend.request-access')

    <x-front.footer />
</x-guest-layout>
