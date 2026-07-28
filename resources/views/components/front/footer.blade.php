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
                    <div class="am-footer_wrap">
                        <div class="am-footer_logoarea">
                            <strong class="am-flogo">
                                <a href="/">
                                    <img src="{{ asset('images/nuevo/Logo-Horizontia-footer.png') }}" alt="Logo Horizontia">
                                </a>
                            </strong>
                            @if(!empty(setting('_front_page_settings.footer_paragraph')))
                                <p>{!! setting('_front_page_settings.footer_paragraph') !!}</p>
                            @endif
                            @if(
                                !empty(setting('_front_page_settings.footer_contact')) ||
                                !empty(setting('_front_page_settings.footer_email')) ||
                                !empty(setting('_front_page_settings.footer_address'))
                            )
                                <ul class="am-footer_contact">
                                    @if(!empty(setting('_front_page_settings.footer_contact')))
                                        <li>
                                            <a href="tel:{!! setting('_front_page_settings.footer_contact') !!}"><i class="am-icon-audio-03"></i>{!! setting('_front_page_settings.footer_contact') !!}</a>
                                        </li>
                                    @endif
                                    @if(!empty(setting('_front_page_settings.footer_email')))
                                        <li>
                                            <a href="mailto:hello@gmail.com"><i class="am-icon-email-01"></i>{!! setting('_front_page_settings.footer_email') !!}</a>
                                        </li>
                                    @endif
                                    @if(!empty(setting('_front_page_settings.footer_address')))
                                        <li>
                                            <address><i class="am-icon-location"></i>{!! setting('_front_page_settings.footer_address') !!}</address>
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
                                <ul class="am-socialmedia">
                                    @if ( !empty( setting('_general.fb_link')))
                                        <li><a href="{{ setting('_general.fb_link') }}"><i class="am-icon-facebook"></i></a></li>
                                    @endif
                                    @if ( !empty( setting('_general.insta_link')))
                                        <li><a href="{{ setting('_general.insta_link') }}"><i class="am-icon-instagram"></i></a></li>
                                    @endif
                                    @if ( !empty( setting('_general.linkedin_link')))
                                        <li><a href="{{ setting('_general.linkedin_link') }}"><i class="am-icon-linkedin"></i></a></li>
                                    @endif
                                    @if ( !empty( setting('_general.yt_link')))
                                        <li><a href="{{ setting('_general.yt_link') }}"><i class="am-icon-youtube"></i></a></li>
                                    @endif
                                    @if ( !empty( setting('_general.tiktok_link')))
                                        <li><a href="{{ setting('_general.tiktok_link') }}"><i class="am-icon-tiktok"></i></a></li>
                                    @endif
                                </ul>
                            @endif
                            @if(!empty(setting('_front_page_settings.footer_button_text')))
                                <a href="{{ !empty(setting('_front_page_settings.footer_button_url')) ? url(setting('_front_page_settings.footer_button_url')) : '#' }}" class="am-btn">
                                    {{ setting('_front_page_settings.footer_button_text') }}
                                </a>
                            @endif
                        </div>
                        <div class="am-fnavigation_wrap">
                            <nav class="am-fnavigation">
                                <div class="am-fnavigation_title">
                                    <h3>Explorar</h3>
                                </div>
                                <ul>
                                    <li><a href="{{ route('home', [], false) }}">Inicio</a></li>
                                    <li><a href="{{ route('courses.search-courses', [], false) }}">Cursos</a></li>
                                    <li><a href="{{ route('find-tutors', [], false) }}">Tutores</a></li>
                                </ul>
                            </nav>
                            
                            <!-- Eliminado temporalmente por no existir las páginas de soporte y legal -->
                            <!--
                            <nav class="am-fnavigation">
                                <div class="am-fnavigation_title">
                                    <h3>Soporte</h3>
                                </div>
                                <ul>
                                    <li><a href="/faq">Preguntas Frecuentes</a></li>
                                    <li><a href="/contact-us">Contáctanos</a></li>
                                </ul>
                            </nav>
                            
                            <nav class="am-fnavigation">
                                <div class="am-fnavigation_title">
                                    <h3>Legal</h3>
                                </div>
                                <ul>
                                    <li><a href="/terms-condition">Términos y Condiciones</a></li>
                                    <li><a href="/privacy-policy">Política de Privacidad</a></li>
                                </ul>
                            </nav>
                            -->
                            
                            @if (
                            !empty( setting('_front_page_settings.app_section_heading')) ||
                            !empty(setting('_front_page_settings.app_section_description'))
                            )
                            <div class="am-fnavigation">
                                @if (!empty( setting('_front_page_settings.app_section_heading')))
                                    <div class="am-fnavigation_title">
                                        <h3>{{ setting('_front_page_settings.app_section_heading') }}</h3>
                                    </div>
                                @endif
                                @if (!empty( setting('_front_page_settings.app_section_description')))
                                    <p>{{ setting('_front_page_settings.app_section_description') }}</p>
                                @endif
                            </div>
                            @endif
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
