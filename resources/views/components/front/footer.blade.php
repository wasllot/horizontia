@props(['page'=> null])
@php
    $footerVariations = setting('_front_page_settings.footer_variation_for_pages');
    $footerVariation  = '';
    if (!empty($footerVariations)) {
        foreach ($footerVariations as $key => $variation) {
           if($variation['page_id'] == $page?->id) {
                $footerVariation = $variation['footer_variation'];
                break;
           }
        }
    }
@endphp

@if($footerVariation != 'am-footer_three')
    <div class="container">
        @livewire('frontend.newsletter')
    </div>
    <footer class="am-footer">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="am-footer_wrap" style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:2rem;">
                        <div class="am-footer_logoarea" style="max-width: 400px;">
                            <strong class="am-flogo" style="display:block; margin-bottom:1.5rem;">
                                <a href="/">
                                    <img src="{{ asset('images/nuevo/Logo-Horizontia-footer.png') }}" alt="Logo Horizontia" style="max-width: 250px;">
                                </a>
                            </strong>
                            <p style="color:#d1d5db; line-height:1.6; margin-bottom:1.5rem;">
                                Horizontia es la plataforma líder de tutorías en línea, dedicada a conectar a cada estudiante con el tutor perfecto. Con una amplia red de profesionales calificados, brindamos apoyo educativo excepcional en todas las materias.
                            </p>
                            
                            @if(
                                !empty(setting('_front_page_settings.footer_contact')) ||
                                !empty(setting('_front_page_settings.footer_email')) ||
                                !empty(setting('_front_page_settings.footer_address'))
                            )
                                <ul class="am-footer_contact" style="list-style:none; padding:0; margin:0 0 1.5rem 0;">
                                    @if(!empty(setting('_front_page_settings.footer_contact')))
                                        <li style="margin-bottom:0.5rem;">
                                            <a href="tel:{!! setting('_front_page_settings.footer_contact') !!}" style="color:#f3f4f6; text-decoration:none;"><i class="am-icon-audio-03" style="margin-right:8px; color:#fdc500;"></i>{!! setting('_front_page_settings.footer_contact') !!}</a>
                                        </li>
                                    @endif
                                    @if(!empty(setting('_front_page_settings.footer_email')))
                                        <li style="margin-bottom:0.5rem;">
                                            <a href="mailto:hello@gmail.com" style="color:#f3f4f6; text-decoration:none;"><i class="am-icon-email-01" style="margin-right:8px; color:#fdc500;"></i>{!! setting('_front_page_settings.footer_email') !!}</a>
                                        </li>
                                    @endif
                                    @if(!empty(setting('_front_page_settings.footer_address')))
                                        <li>
                                            <address style="color:#f3f4f6; font-style:normal; margin:0;"><i class="am-icon-location" style="margin-right:8px; color:#fdc500;"></i>{!! setting('_front_page_settings.footer_address') !!}</address>
                                        </li>
                                    @endif
                                </ul>
                            @endif

                            @if (
                                !empty( setting('_general.fb_link')) ||
                                !empty( setting('_general.insta_link')) ||
                                !empty(setting('_general.linkedin_link')) ||
                                !empty(setting('_general.yt_link')) ||
                                !empty(setting('_general.tiktok_link'))
                                )
                                <ul class="am-socialmedia" style="display:flex; gap:10px; list-style:none; padding:0; margin:0;">
                                    @if ( !empty( setting('_general.fb_link')))
                                        <li><a href="{{ setting('_general.fb_link') }}" style="display:flex; align-items:center; justify-content:center; width:36px; height:36px; background:rgba(255,255,255,0.1); border-radius:50%; color:#fff; transition:0.3s;"><i class="am-icon-facebook"></i></a></li>
                                    @endif
                                    @if ( !empty( setting('_general.insta_link')))
                                        <li><a href="{{ setting('_general.insta_link') }}" style="display:flex; align-items:center; justify-content:center; width:36px; height:36px; background:rgba(255,255,255,0.1); border-radius:50%; color:#fff; transition:0.3s;"><i class="am-icon-instagram"></i></a></li>
                                    @endif
                                    @if ( !empty( setting('_general.linkedin_link')))
                                        <li><a href="{{ setting('_general.linkedin_link') }}" style="display:flex; align-items:center; justify-content:center; width:36px; height:36px; background:rgba(255,255,255,0.1); border-radius:50%; color:#fff; transition:0.3s;"><i class="am-icon-linkedin"></i></a></li>
                                    @endif
                                    @if ( !empty( setting('_general.yt_link')))
                                        <li><a href="{{ setting('_general.yt_link') }}" style="display:flex; align-items:center; justify-content:center; width:36px; height:36px; background:rgba(255,255,255,0.1); border-radius:50%; color:#fff; transition:0.3s;"><i class="am-icon-youtube"></i></a></li>
                                    @endif
                                    @if ( !empty( setting('_general.tiktok_link')))
                                        <li><a href="{{ setting('_general.tiktok_link') }}" style="display:flex; align-items:center; justify-content:center; width:36px; height:36px; background:rgba(255,255,255,0.1); border-radius:50%; color:#fff; transition:0.3s;"><i class="am-icon-tiktok"></i></a></li>
                                    @endif
                                </ul>
                            @endif
                        </div>
                        
                        <div class="am-fnavigation_wrap" style="display:flex; gap:4rem; flex-wrap:wrap;">
                            <nav class="am-fnavigation">
                                <div class="am-fnavigation_title" style="margin-bottom:1.5rem;">
                                    <h3 style="color:#fff; font-size:1.125rem; font-weight:600;">Explorar</h3>
                                </div>
                                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.75rem;">
                                    <li><a href="{{ route('home', [], false) }}" style="color:#d1d5db; text-decoration:none; transition:0.3s;">Inicio</a></li>
                                    <li><a href="{{ route('courses.search-courses', [], false) }}" style="color:#d1d5db; text-decoration:none; transition:0.3s;">Cursos</a></li>
                                    <li><a href="{{ route('find-tutors', [], false) }}" style="color:#d1d5db; text-decoration:none; transition:0.3s;">Tutores</a></li>
                                </ul>
                            </nav>
                            
                            <div class="am-fnavigation" style="max-width: 250px;">
                                <div class="am-fnavigation_title" style="margin-bottom:1.5rem;">
                                    <h3 style="color:#fff; font-size:1.125rem; font-weight:600;">Lleva el aprendizaje contigo</h3>
                                </div>
                                <p style="color:#d1d5db; line-height:1.6; margin-bottom:1rem;">
                                    Continúa tu educación en cualquier lugar. ¡Descarga nuestra app móvil gratis para dispositivos iOS y Android!
                                </p>
                                <div style="display:flex; gap:10px;">
                                    <a href="#" style="opacity:0.8; transition:opacity 0.3s;"><i class="am-icon-apple" style="font-size:2rem; color:#fff;"></i></a>
                                    <a href="#" style="opacity:0.8; transition:opacity 0.3s;"><i class="am-icon-android" style="font-size:2rem; color:#fff;"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="am-footer_bottom">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="am-footer_info">
                            <p>{{ __('general.copyright_txt',['year' => date('Y')]) }}</p>
                            <!--
                            <nav>
                                <ul>
                                    <li><a href="/terms-condition">{{ __('general.terms_and_conditions') }}</a></li>
                                    <li><a href="/privacy-policy">{{ __('general.privacy_policy') }}</a></li>
                                </ul>
                            </nav>
                            -->
                        </div>
                    </div>
                </div>
            </div>
            <a class="am-clicktop" href="#"><i class="am-icon-arrow-up"></i></a>
        </div>
    </footer>
@else
    <div class="container">
        @livewire('frontend.newsletter')
    </div>
    <footer class="am-footer-v4">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="am-footer-content">
                        @if(!empty(setting('_front_page_settings.footer_heading')))
                            <h2>{!! setting('_front_page_settings.footer_heading') !!}</h2>
                        @endif
                        @if(!empty(setting('_front_page_settings.footer3_paragraph')))
                            <p>{!! setting('_front_page_settings.footer3_paragraph') !!}</p>
                        @endif
                        @if(!empty(setting('_front_page_settings.primary_button_url')) || !empty(setting('_front_page_settings.primary_button_text')) || !empty(setting('_front_page_settings.secondary_button_url')) || !empty(setting('_front_page_settings.secondary_button_text')))
                            <div class="am-actions">
                                @if(!empty(setting('_front_page_settings.primary_button_url')) || !empty(setting('_front_page_settings.primary_button_text')))
                                    <a href="{!! setting('_front_page_settings.primary_button_url') !!}" class="am-getstarted-btn">{!! setting('_front_page_settings.primary_button_text') !!}</a>
                                @endif
                                @if(!empty(setting('_front_page_settings.secondary_button_url')) || !empty(setting('_front_page_settings.secondary_button_text')))
                                    <a href="{!! setting('_front_page_settings.secondary_button_url') !!}" class="am-outline-btn">{!! setting('_front_page_settings.secondary_button_text') !!}</a>
                                @endif
                            </div>
                        @endif
                        <ul class="am-footer-nav">
                            <!--
                            <li><a href="/about-us">{{ __('general.about') }}</a></li>
                            <li><a href="/terms-condition">{{ __('general.terms_and_conditions') }}</a></li>
                            <li><a href="/privacy-policy">{{ __('general.privacy_policy') }}</a></li>
                            <li><a href="#">{{ __('general.contact_us') }}</a></li>
                            <li><a href="/faq">{{ __('general.faqs') }}</a></li>
                            -->
                            <li><a href="{{ route('blogs', [], false) }}">{{ __('general.blogs') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </footer>
@endif
