<x-guest-layout>
    <x-front.header />

    <!-- Breadcrumb -->
    <div class="am-breadcrumb-area" style="background-color: #f9f9f9; padding: 40px 0;">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 style="font-size: 2.5rem; font-weight: 800; color: #000; margin-bottom: 10px;">Contacto</h1>
                    <ul class="am-breadcrumb" style="list-style: none; padding: 0; margin: 0; display: flex; gap: 10px; align-items: center;">
                        <li><a href="{{ route('home') }}" style="color: #666; text-decoration: none;">Inicio</a></li>
                        <li style="color: #ccc;">/</li>
                        <li style="color: #F4C430; font-weight: 600;">Contacto</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @livewire('frontend.contact-form')

    <x-front.footer />
</x-guest-layout>
