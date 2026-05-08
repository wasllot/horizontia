@extends('layouts.frontend-app')

@section('content')
    <div class="page-home-page">
        <!-- Section start -->
        <!-- Hero Section -->
        <section class="am-hero-section"
            style="background-image: url('{{ asset('images/nuevo/Slider-horizontia.jpg') }}'); background-size: cover; background-position: center; height: 600px; display: flex; align-items: center; position: relative;">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-md-10">
                        <div class="am-hero-content" style="color: #fff; text-align: left;">
                            <h3 style="font-size: 1.5rem; font-weight: 300; margin-bottom: 10px; color: #F2D07F;">Con
                                Horizontia, empodera tu futuro</h3>
                            <h1 style="font-size: 3.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 20px;">Aprende
                                hoy para un<br>futuro más brillante</h1>
                            <p style="font-size: 1.1rem; margin-bottom: 30px; max-width: 80%;">Alcanza tus metas con
                                tutorías personalizadas de los mejores expertos. Conéctate con tutores dedicados para
                                alcanzar el éxito.</p>
                            <a href="#" class="am-btn"
                                style="background-color: #F2D07F; color: #000; padding: 12px 30px; font-weight: 700; border-radius: 50px; text-decoration: none; display: inline-block;">CONTÁCTANOS</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Banner Strip -->
        <div class="am-banner-strip" style="background-color: #e0e0e0; padding: 10px 0; text-align: center;">
            <p
                style="margin: 0; font-weight: 600; color: #333; font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px;">
                Conciencia humana con talento</p>
        </div>

        <!-- Steps Section -->
        <section class="am-steps-section" style="padding: 80px 0; background-color: #f9f9f9;">
            <div class="container">
                <div class="row text-center mb-5">
                    <div class="col-12">
                        <span style="display: block; font-size: 0.9rem; color: #999; margin-bottom: 5px;">GUÍA PASO A
                            PASO</span>
                        <h2 style="font-size: 2.5rem; font-weight: 700; color: #333;">Desbloquea tu potencial con sencillos
                            pasos</h2>
                    </div>
                </div>
                <!-- Steps Layout: 3 White Cards + 1 Yellow Card -->
                <div class="row justify-content-center">
                    <!-- Step 1 -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="am-step-card"
                            style="background: #fff; padding: 40px 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; height: 100%; transition: transform 0.3s;">
                            <figure style="margin-bottom: 25px;">
                                <img src="{{ asset('storage/optionbuilder/uploads/912805-12-2025_1226pmfoto-seguidas-1.jpg') }}"
                                    alt="Regístrate"
                                    style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                            </figure>
                            <h4 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 10px;">Regístrate</h4>
                            <p style="color: #666; font-size: 0.95rem; margin-bottom: 20px;">Crea tu cuenta rápidamente para
                                comenzar a utilizar nuestra plataforma</p>
                            <a href="#"
                                style="color: #F2D07F; font-weight: 700; text-decoration: none; border-bottom: 2px solid #F2D07F;">EMPEZAR</a>
                        </div>
                    </div>
                    <!-- Step 2 -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="am-step-card"
                            style="background: #fff; padding: 40px 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; height: 100%; transition: transform 0.3s;">
                            <figure style="margin-bottom: 25px;">
                                <img src="{{ asset('storage/optionbuilder/uploads/793305-12-2025_1240pmfoto-seguidas-2.jpg') }}"
                                    alt="Busca un curso"
                                    style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                            </figure>
                            <h4 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 10px;">Busca un curso</h4>
                            <p style="color: #666; font-size: 0.95rem; margin-bottom: 20px;">Busca y selecciona el curso de
                                tu preferencia según tus necesidades</p>
                            <a href="#"
                                style="color: #F2D07F; font-weight: 700; text-decoration: none; border-bottom: 2px solid #F2D07F;">BUSCAR</a>
                        </div>
                    </div>
                    <!-- Step 3 -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="am-step-card"
                            style="background: #fff; padding: 40px 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; height: 100%; transition: transform 0.3s;">
                            <figure style="margin-bottom: 25px;">
                                <img src="{{ asset('storage/optionbuilder/uploads/835805-12-2025_1243pmfoto-seguidas-3.jpg') }}"
                                    alt="Inscríbete"
                                    style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                            </figure>
                            <h4 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 10px;">Inscríbete</h4>
                            <p style="color: #666; font-size: 0.95rem; margin-bottom: 20px;">Sigue los pasos para formalizar
                                tu inscripción en el curso seleccionado</p>
                            <a href="#"
                                style="color: #F2D07F; font-weight: 700; text-decoration: none; border-bottom: 2px solid #F2D07F;">INSCRIPCIÓN</a>
                        </div>
                    </div>
                    <!-- Step 4 (Highlighted) -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="am-step-card"
                            style="background: #F4C430; padding: 40px 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(244, 196, 48, 0.3); text-align: center; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <div
                                style="background: rgba(255,255,255,0.2); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 25px;">
                                <i class="am-icon-layer-01" style="font-size: 30px; color: #000;"></i>
                            </div>
                            <h4 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 10px; color: #000;">Comienza tu
                                viaje</h4>
                            <p style="color: #000; font-size: 0.95rem; margin-bottom: 25px;">¡Encuentra tu curso y reserva
                                tu primera sesión hoy mismo!</p>
                            <a href="{{ url('login') }}" class="am-btn"
                                style="background-color: #000; color: #fff; padding: 10px 25px; border-radius: 50px; font-size: 0.9rem;">Empieza
                                ahora!</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- About Section (Qué ofrece Horizontia) -->
        <section class="am-about-section"
            style="padding: 100px 0; background-image: url('{{ asset('images/nuevo/fondo-horizontia-amarillo.jpg') }}'); background-size: cover; background-position: center;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-5 mb-lg-0">
                        <div class="am-about-content">
                            <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 30px; color: #000;">Qué ofrece
                                Horizontia</h2>
                            <p style="font-size: 1.1rem; margin-bottom: 25px; color: #000;">Nuestra misión es conectar a
                                estudiantes con tutores de primera categoría para alcanzar sus objetivos académicos y
                                personales.</p>
                            <p style="font-size: 1.1rem; margin-bottom: 25px; color: #000;">Ofrecemos una plataforma
                                intuitiva donde puedes encontrar:</p>
                            <ul style="list-style: none; padding: 0; margin-bottom: 30px;">
                                <li
                                    style="margin-bottom: 15px; display: flex; align-items: start; color: #000; font-weight: 500;">
                                    <i class="am-icon-check-circle-01"
                                        style="color: #000; margin-right: 15px; font-size: 1.2rem;"></i>
                                    <div>
                                        <strong>Tutorías 1 a 1:</strong>
                                        <p style="margin: 0; font-size: 0.95rem;">Sesiones personalizadas adaptadas a tu
                                            ritmo.</p>
                                    </div>
                                </li>
                                <li
                                    style="margin-bottom: 15px; display: flex; align-items: start; color: #000; font-weight: 500;">
                                    <i class="am-icon-check-circle-01"
                                        style="color: #000; margin-right: 15px; font-size: 1.2rem;"></i>
                                    <div>
                                        <strong>Gran variedad de temas:</strong>
                                        <p style="margin: 0; font-size: 0.95rem;">Desde idiomas hasta desarrollo
                                            profesional.</p>
                                    </div>
                                </li>
                                <li
                                    style="margin-bottom: 15px; display: flex; align-items: start; color: #000; font-weight: 500;">
                                    <i class="am-icon-check-circle-01"
                                        style="color: #000; margin-right: 15px; font-size: 1.2rem;"></i>
                                    <div>
                                        <strong>Horarios flexibles:</strong>
                                        <p style="margin: 0; font-size: 0.95rem;">Agenda sesiones cuando más te convenga.
                                        </p>
                                    </div>
                                </li>
                                <li
                                    style="margin-bottom: 15px; display: flex; align-items: start; color: #000; font-weight: 500;">
                                    <i class="am-icon-check-circle-01"
                                        style="color: #000; margin-right: 15px; font-size: 1.2rem;"></i>
                                    <div>
                                        <strong>Plataforma segura:</strong>
                                        <p style="margin: 0; font-size: 0.95rem;">Pagos seguros y verificados.</p>
                                    </div>
                                </li>
                            </ul>
                            <a href="#" class="am-btn"
                                style="background-color: #000; color: #fff; padding: 12px 35px; border-radius: 50px; font-weight: 600;">UNETE
                                AHORA</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <figure
                            style="margin: 0; box-shadow: 0 20px 40px rgba(0,0,0,0.2); border-radius: 20px; overflow: hidden;">
                            <img src="{{ asset('images/nuevo/que-ofrece-horizontia.jpg') }}" alt="Qué ofrece Horizontia"
                                style="width: 100%; height: auto; display: block;">
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        <!-- Support / Experts Section -->
        <section class="am-support-section" style="padding: 100px 0; background-color: #fff;">
            <div class="container">
                <!-- Row 1: Support -->
                <div class="row align-items-center mb-5" style="margin-bottom: 100px !important;">
                    <div class="col-lg-6 order-lg-1 order-2">
                        <figure
                            style="border-radius: 20px 0 20px 0; overflow: hidden; box-shadow: 0 15px 30px rgba(0,0,0,0.1);">
                            <img src="{{ asset('storage/optionbuilder/uploads/430205-12-2025_0744pmcomputer.jpg') }}"
                                alt="Soporte Integral" style="width: 100%; height: auto;">
                        </figure>
                    </div>
                    <div class="col-lg-6 order-lg-2 order-1 mb-4 mb-lg-0 pl-lg-5">
                        <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 20px; color: #333;">Soporte
                            integral<br>en cada paso</h2>
                        <p style="font-size: 1.1rem; color: #666; line-height: 1.6;">Nuestro equipo de soporte está dedicado
                            a garantizar que tu experiencia sea fluida y exitosa. Desde problemas técnicos hasta dudas sobre
                            cursos, estamos aquí para ayudarte en todo momento.</p>
                    </div>
                </div>
                <!-- Row 2: Team (Wellness Image) -->
                <div class="row align-items-center">
                    <div class="col-lg-6 pr-lg-5 mb-4 mb-lg-0">
                        <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 20px; color: #333;">Nuestro
                            equipo<br>de expertos te guiará</h2>
                        <p style="font-size: 1.1rem; color: #666; line-height: 1.6;">Contamos con tutores y mentores
                            altamente calificados, seleccionados rigurosamente para ofrecerte la mejor educación. Aprende de
                            profesionales con años de experiencia en sus campos.</p>
                    </div>
                    <div class="col-lg-6">
                        <figure
                            style="border-radius: 0 20px 0 20px; overflow: hidden; box-shadow: 0 15px 30px rgba(0,0,0,0.1);">
                            <img src="{{ asset('images/nuevo/wellness.jpg') }}" alt="Nuestro Equipo"
                                style="width: 100%; height: auto;">
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        <style>
            .am-sunburst-design {
                background-color: #1a1a1a;
                background-image: url('{{ asset('images/design/new-sunburst-bg.jpg') }}');
                background-size: cover;
                background-position: center;
                min-height: 600px;
                display: flex;
                align-items: center;
            }

            .am-sunburst-title {
                color: #fff;
                font-weight: 700;
                font-size: 3.5rem;
                margin-top: 15px;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            }

            .am-course-list {
                list-style: none;
                padding: 0;
                text-align: left;
                width: fit-content;
                margin: 0 auto;
            }

            .am-course-item {
                margin-bottom: 20px;
                color: #F2D07F;
                font-size: 1.6rem;
                display: flex;
                align-items: center;
                justify-content: flex-start;
                transition: transform 0.3s ease, color 0.3s ease;
                cursor: default;
            }

            .am-course-item:hover {
                transform: translateX(10px);
                color: #fff;
            }

            .am-course-dot {
                margin-right: 15px;
                font-size: 1rem;
                color: #fff;
            }

            @media (max-width: 768px) {
                .am-sunburst-title {
                    font-size: 2.5rem;
                }

                .am-course-list {
                    padding-left: 0;
                    text-align: center;
                }

                .am-course-item {
                    justify-content: center;
                    font-size: 1.4rem;
                }
            }
        </style>
        <!-- Custom Sunburst/Tutors Section -->
        <section class="pb-themesection section-categories am-sunburst-design" style="padding-bottom: 0 !important;">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12 text-center mb-4">
                        <!-- Header Icon -->
                        <div class="mb-3">
                            <img src="{{ asset('images/design/sunburst-icon.png') }}" alt="Icono"
                                style="width: 80px; height: auto; display: inline-block; border-bottom: 2px solid #fff; padding-bottom: 20px;">
                        </div>
                        <h2 class="am-sunburst-title">Cursos Horizontia</h2>
                    </div>
                </div>

                <!-- List Content -->
                <div class="row justify-content-center" style="margin-top: 30px; padding-bottom: 80px;">
                    <div class="col-md-8">
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6 mb-3">
                                <ul class="am-course-list">
                                    <li class="am-course-item">
                                        <span class="am-course-dot">●</span> Coaching
                                    </li>
                                    <li class="am-course-item">
                                        <span class="am-course-dot">●</span> Motivación
                                    </li>
                                    <li class="am-course-item">
                                        <span class="am-course-dot">●</span> Gestión del tiempo
                                    </li>
                                    <li class="am-course-item">
                                        <span class="am-course-dot">●</span> Manejo del estrés
                                    </li>
                                </ul>
                            </div>

                            <!-- Right Column -->
                            <div class="col-md-6 mb-3">
                                <ul class="am-course-list">
                                    <li class="am-course-item">
                                        <span class="am-course-dot">●</span> Liderazgo
                                    </li>
                                    <li class="am-course-item">
                                        <span class="am-course-dot">●</span> Innovación
                                    </li>
                                    <li class="am-course-item">
                                        <span class="am-course-dot">●</span> Creatividad
                                    </li>
                                    <li class="am-course-item">
                                        <span class="am-course-dot">●</span> Networking
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Office Meditation Banner Section -->
        <section class="am-meditation-banner" style="width: 100%; overflow: hidden; line-height: 0; margin: 0; padding: 0;">
            <img src="{{ asset('images/nuevo/meditacion-oficina.png') }}" alt="Meditación en la oficina"
                style="width: 100%; height: auto; display: block; margin-top: -45px;">
        </section>
    </div>
    </div>
    </div>
    </div>
    </section>

    <!-- Feedback Section -->
    <section class="am-feedback am-feedback-two am-feedback-three feedback-verient-one">
        <div class="container">
            <div class="am-feedback-two_wrap">
                <div class="am-section_title am-section_title_center am-section_title_one">
                    <h2>Historias de éxito</h2>
                    <p>Descubre cómo Horizontia hace la diferencia en la vida de los estudiantes. Lee las experiencias de
                        los usuarios satisfechos.</p>
                </div>

                <ul class="am-feedback_content_list" id="testimonial-slider-static">
                    <!-- Testimonial 1 -->
                    <li data-aos="fade-up" data-aos-duration="400" data-aos-easing="ease">
                        <div class="am-feedback_content_list_info">
                            <p>Horizontia ha sido una herramienta confiable y efectiva. Los tutores son de primera categoría
                                y el proceso de reserva es increíblemente sencillo.</p>
                            <div class="am-feedback_content_list_stars">
                                <i class="am-icon-star-filled"></i>
                                <i class="am-icon-star-filled"></i>
                                <i class="am-icon-star-filled"></i>
                                <i class="am-icon-star-filled"></i>
                                <i class="am-icon-star-filled"></i>
                            </div>
                            <div class="am-feedback_content_list_info_prof">
                                <figure>
                                    <img src="{{ asset('storage/optionbuilder/uploads/customer-01.png') }}" alt="Arlene M"
                                        onerror="this.src='{{ asset('images/default_avatar.webp') }}'">
                                </figure>
                                <div>
                                    <h3>Arlene M</h3>
                                    <span>Agile District</span>
                                </div>
                            </div>
                            <div class="am-feedbackicon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none">
                                    <g opacity="0.2">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M5.2 3.25H5.16957H5.16955C4.6354 3.24999 4.18956 3.24998 3.82533 3.27974C3.44545 3.31078 3.08879 3.37789 2.75153 3.54973C2.23408 3.81339 1.81339 4.23408 1.54973 4.75153C1.37789 5.08879 1.31078 5.44545 1.27974 5.82533C1.24998 6.18956 1.24999 6.6354 1.25 7.16955V7.16957V7.2V8.8V8.83044V8.83045C1.24999 9.3646 1.24998 9.81044 1.27974 10.1747C1.31078 10.5546 1.37789 10.9112 1.54973 11.2485C1.81339 11.7659 2.23408 12.1866 2.75153 12.4503C3.08879 12.6221 3.44545 12.6892 3.82533 12.7203C4.18955 12.75 4.63538 12.75 5.16951 12.75H5.16955H5.2H6.8H6.83045H6.8305C7.36462 12.75 7.81045 12.75 8.17467 12.7203C8.55456 12.6892 8.91121 12.6221 9.24848 12.4503L9.25 12.4495V13.25C9.25 13.9999 9.24882 14.3148 9.21477 14.6072C9.07258 15.8284 8.50628 16.961 7.6146 17.8075C7.40111 18.0102 7.1499 18.2001 6.55 18.65L5.55 19.4C5.21863 19.6485 5.15147 20.1186 5.4 20.45C5.64853 20.7814 6.11863 20.8485 6.45 20.6L7.45 19.85L7.4915 19.8189L7.49152 19.8189C8.03778 19.4092 8.36303 19.1653 8.64735 18.8954C9.79379 17.807 10.5219 16.3508 10.7047 14.7807C10.75 14.3913 10.75 13.9847 10.75 13.3019V13.3019L10.75 13.25V8.8822V8.83045V8.8V8V7.2V7.16955V7.16951C10.75 6.63538 10.75 6.18955 10.7203 5.82533C10.6892 5.44545 10.6221 5.08879 10.4503 4.75153C10.1866 4.23408 9.76592 3.81339 9.24848 3.54973C8.91121 3.37789 8.55456 3.31078 8.17467 3.27974C7.81044 3.24998 7.3646 3.24999 6.83046 3.25H6.83044H6.8H5.2ZM17.2 3.25H17.1696H17.1695C16.6354 3.24999 16.1896 3.24998 15.8253 3.27974C15.4454 3.31078 15.0888 3.37789 14.7515 3.54973C14.2341 3.81339 13.8134 4.23408 13.5497 4.75153C13.3779 5.08879 13.3108 5.44545 13.2797 5.82533C13.25 6.18955 13.25 6.63538 13.25 7.16951V7.16955V7.2V8.8V8.83045V8.8305C13.25 9.36462 13.25 9.81045 13.2797 10.1747C13.3108 10.5546 13.3779 10.9112 13.5497 11.2485C13.8134 11.7659 14.2341 12.1866 14.7515 12.4503C15.0888 12.6221 15.4454 12.6892 15.8253 12.7203C16.1896 12.75 16.6354 12.75 17.1695 12.75H17.1695H17.2H18.8H18.8305H18.8305C19.3646 12.75 19.8105 12.75 20.1747 12.7203C20.5546 12.6892 20.9112 12.6221 21.2485 12.4503L21.25 12.4495V13.25C21.25 13.9999 21.2488 14.3148 21.2148 14.6072C21.0726 15.8284 20.5063 16.961 19.6146 17.8075C19.4011 18.0102 19.1499 18.2001 18.55 18.65L17.55 19.4C17.2186 19.6485 17.1515 20.1186 17.4 20.45C17.6485 20.7814 18.1186 20.8485 18.45 20.6L19.45 19.85L19.4915 19.8189L19.4915 19.8189C20.0378 19.4092 20.363 19.1653 20.6473 18.8954C21.7938 17.807 22.5219 16.3508 22.7047 14.7807C22.75 14.3912 22.75 13.9847 22.75 13.3019V13.25V8.88427V8.83045V8.8V8V7.2V7.16955C22.75 6.6354 22.75 6.18956 22.7203 5.82533C22.6892 5.44545 22.6221 5.08879 22.4503 4.75153C22.1866 4.23408 21.7659 3.81339 21.2485 3.54973C20.9112 3.37789 20.5546 3.31078 20.1747 3.27974C19.8104 3.24998 19.3646 3.24999 18.8305 3.25H18.8304H18.8H17.2Z"
                                            fill="black"></path>
                                    </g>
                                </svg>
                            </div>
                        </div>
                    </li>

                    <!-- Testimonial 2 -->
                    <li data-aos="fade-up" data-aos-duration="600" data-aos-easing="ease">
                        <div class="am-feedback_content_list_info">
                            <p>Lernen es un recurso confiable para nuestra agencia, ofreciendo tutores conocedores y
                                dedicados.</p>
                            <div class="am-feedback_content_list_stars">
                                <i class="am-icon-star-filled"></i>
                                <i class="am-icon-star-filled"></i>
                                <i class="am-icon-star-filled"></i>
                                <i class="am-icon-star-filled"></i>
                                <i class="am-icon-star-filled"></i>
                            </div>
                            <div class="am-feedback_content_list_info_prof">
                                <figure>
                                    <img src="{{ asset('storage/optionbuilder/uploads/customer-02.png') }}" alt="Ronald R"
                                        onerror="this.src='{{ asset('images/default_avatar.webp') }}'">
                                </figure>
                                <div>
                                    <h3>Ronald R</h3>
                                    <span>Consultor Educativo</span>
                                </div>
                            </div>
                            <div class="am-feedbackicon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none"> <!-- SVG Content -->
                                    <g opacity="0.2">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M5.2 3.25H5.16957H5.16955C4.6354 3.24999 4.18956 3.24998 3.82533 3.27974C3.44545 3.31078 3.08879 3.37789 2.75153 3.54973C2.23408 3.81339 1.81339 4.23408 1.54973 4.75153C1.37789 5.08879 1.31078 5.44545 1.27974 5.82533C1.24998 6.18956 1.24999 6.6354 1.25 7.16955V7.16957V7.2V8.8V8.83044V8.83045C1.24999 9.3646 1.24998 9.81044 1.27974 10.1747C1.31078 10.5546 1.37789 10.9112 1.54973 11.2485C1.81339 11.7659 2.23408 12.1866 2.75153 12.4503C3.08879 12.6221 3.44545 12.6892 3.82533 12.7203C4.18955 12.75 4.63538 12.75 5.16951 12.75H5.16955H5.2H6.8H6.83045H6.8305C7.36462 12.75 7.81045 12.75 8.17467 12.7203C8.55456 12.6892 8.91121 12.6221 9.24848 12.4503L9.25 12.4495V13.25C9.25 13.9999 9.24882 14.3148 9.21477 14.6072C9.07258 15.8284 8.50628 16.961 7.6146 17.8075C7.40111 18.0102 7.1499 18.2001 6.55 18.65L5.55 19.4C5.21863 19.6485 5.15147 20.1186 5.4 20.45C5.64853 20.7814 6.11863 20.8485 6.45 20.6L7.45 19.85L7.4915 19.8189L7.49152 19.8189C8.03778 19.4092 8.36303 19.1653 8.64735 18.8954C9.79379 17.807 10.5219 16.3508 10.7047 14.7807C10.75 14.3912 22.75 13.9847 22.75 13.3019V13.25V8.88427V8.83045V8.8V8V7.2V7.16955C22.75 6.6354 22.75 6.18956 22.7203 5.82533C22.6892 5.44545 22.6221 5.08879 22.4503 4.75153C22.1866 4.23408 21.7659 3.81339 21.2485 3.54973C20.9112 3.37789 20.5546 3.31078 20.1747 3.27974C19.8104 3.24998 19.3646 3.24999 18.8305 3.25H18.8304H18.8H17.2Z"
                                            fill="black"></path>
                                    </g>
                                </svg>
                            </div>
                        </div>
                    </li>

                    <!-- Testimonial 3 -->
                    <li data-aos="fade-up" data-aos-duration="800" data-aos-easing="ease">
                        <div class="am-feedback_content_list_info">
                            <p>Estamos encantados con Horizontia, sus tutores de primera y plataforma amigable han impulsado
                                enormemente a nuestros estudiantes.</p>
                            <div class="am-feedback_content_list_stars">
                                <i class="am-icon-star-filled"></i>
                                <i class="am-icon-star-filled"></i>
                                <i class="am-icon-star-filled"></i>
                                <i class="am-icon-star-filled"></i>
                                <i class="am-icon-star-filled"></i>
                            </div>
                            <div class="am-feedback_content_list_info_prof">
                                <figure>
                                    <img src="{{ asset('storage/optionbuilder/uploads/customer-03.png') }}" alt="Courtney H"
                                        onerror="this.src='{{ asset('images/default_avatar.webp') }}'">
                                </figure>
                                <div>
                                    <h3>Courtney H</h3>
                                    <span>Consejera Escolar</span>
                                </div>
                            </div>
                            <div class="am-feedbackicon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none"> <!-- SVG Content -->
                                    <g opacity="0.2">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M5.2 3.25H5.16957H5.16955C4.6354 3.24999 4.18956 3.24998 3.82533 3.27974C3.44545 3.31078 3.08879 3.37789 2.75153 3.54973C2.23408 3.81339 1.81339 4.23408 1.54973 4.75153C1.37789 5.08879 1.31078 5.44545 1.27974 5.82533C1.24998 6.18956 1.24999 6.6354 1.25 7.16955V7.16957V7.2V8.8V8.83044V8.83045C1.24999 9.3646 1.24998 9.81044 1.27974 10.1747C1.31078 10.5546 1.37789 10.9112 1.54973 11.2485C1.81339 11.7659 2.23408 12.1866 2.75153 12.4503C3.08879 12.6221 3.44545 12.6892 3.82533 12.7203C4.18955 12.75 4.63538 12.75 5.16951 12.75H5.16955H5.2H6.8H6.83045H6.8305C7.36462 12.75 7.81045 12.75 8.17467 12.7203C8.55456 12.6892 8.91121 12.6221 9.24848 12.4503L9.25 12.4495V13.25C9.25 13.9999 9.24882 14.3148 9.21477 14.6072C9.07258 15.8284 8.50628 16.961 7.6146 17.8075C7.40111 18.0102 7.1499 18.2001 6.55 18.65L5.55 19.4C5.21863 19.6485 5.15147 20.1186 5.4 20.45C5.64853 20.7814 6.11863 20.8485 6.45 20.6L7.45 19.85L7.4915 19.8189L7.49152 19.8189C8.03778 19.4092 8.36303 19.1653 8.64735 18.8954C9.79379 17.807 10.5219 16.3508 10.7047 14.7807C10.75 14.3912 22.75 13.9847 22.75 13.3019V13.25V8.88427V8.83045V8.8V8V7.2V7.16955C22.75 6.6354 22.75 6.18956 22.7203 5.82533C22.6892 5.44545 22.6221 5.08879 22.4503 4.75153C22.1866 4.23408 21.7659 3.81339 21.2485 3.54973C20.9112 3.37789 20.5546 3.31078 20.1747 3.27974C19.8104 3.24998 19.3646 3.24999 18.8305 3.25H18.8304H18.8H17.2Z"
                                            fill="black"></path>
                                    </g>
                                </svg>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Footer Banner Image -->
    <section class="am-footer-banner"
        style="background-image: url('{{ asset('images/nuevo/respiracion-consciente-horizontia.jpg') }}'); background-size: cover; background-position: center; height: 500px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; position: relative;">
        <div
            style="position: absolute; top:0; left:0; width:100%; height:100%; background: linear-gradient(to bottom, rgba(255,255,255,0) 0%, rgba(218, 165, 32, 0.6) 100%);">
        </div> <!-- Gradient Overlay -->
        <div style="position: relative; z-index: 2;">
            <h2
                style="font-size: 4rem; font-weight: 800; color: #333; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(255,255,255,0.5);">
                Horizontia</h2>
            <h3 style="font-size: 2rem; font-weight: 600; color: #333; margin-bottom: 40px;">Conciencia humana con talento
            </h3>
        </div>
    </section>

    <!-- Dynamic Style Block for Footer Override -->
    <style>
        footer,
        .am-footer {
            background-color: #222222 !important;
        }
    </style>
    </div>
@endsection