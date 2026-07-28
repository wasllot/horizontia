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
                            <h3 style="font-size: 1.5rem; font-weight: 300; margin-bottom: 10px; color: #F2D07F;">Con Horizontia, empodera tu futuro</h3>
                            <h1 style="font-size: 3.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 20px; color: #fff;">Aprende hoy para un futuro más brillante</h1>
                            <p style="font-size: 1.1rem; margin-bottom: 30px; max-width: 80%; color: #fff;">Alcanza tus metas con tutorías personalizadas de los mejores expertos. Conéctate con tutores dedicados para alcanzar el éxito.</p>
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
                        <span style="display: block; font-size: 0.9rem; color: #999; margin-bottom: 5px;">GUÍA PASO A PASO</span>
                        <h2 style="font-size: 2.5rem; font-weight: 700; color: #333;">Desbloquea tu potencial con sencillos pasos</h2>
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
                            <p style="color: #666; font-size: 0.95rem; margin-bottom: 20px;">Crea tu cuenta rápidamente para comenzar a utilizar nuestra plataforma</p>
                            <a href="{{ route('login') }}"
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
                            <p style="color: #666; font-size: 0.95rem; margin-bottom: 20px;">Busca y selecciona el curso de tu preferencia según tus necesidades</p>
                            <a href="{{ route('courses.search-courses') }}"
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
                            <p style="color: #666; font-size: 0.95rem; margin-bottom: 20px;">Sigue los pasos para formalizar tu inscripción en el curso seleccionado</p>
                            <a href="{{ route('login') }}"
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
                            <h4 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 10px; color: #000;">Comienza tu viaje</h4>
                            <p style="color: #000; font-size: 0.95rem; margin-bottom: 25px;">¡Encuentra tu curso y reserva tu primera sesión hoy mismo!</p>
                            <a href="{{ url('login') }}" class="am-btn"
                                style="background-color: #000; color: #fff !important; padding: 10px 25px; border-radius: 50px; font-size: 0.9rem;">Empieza ahora!</a>
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
                        <div class="am-about-content" style="background: rgba(255, 255, 255, 0.9); padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                            <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 30px; color: #000;">Qué ofrece Horizontia</h2>
                            <p style="font-size: 1.1rem; margin-bottom: 25px; color: #000;">Nuestra misión es conectar a estudiantes con tutores de primera categoría para alcanzar sus objetivos académicos y personales.</p>
                            <p style="font-size: 1.1rem; margin-bottom: 25px; color: #000;">Ofrecemos una plataforma intuitiva donde puedes encontrar:</p>
                            <ul style="list-style: none; padding: 0; margin-bottom: 30px;">
                                <li style="margin-bottom: 15px; display: flex; align-items: start; color: #000; font-weight: 500;">
                                    <i class="am-icon-check-circle-01" style="color: #000; margin-right: 15px; font-size: 1.2rem;"></i>
                                    <div>
                                        <strong>Tutorías 1 a 1:</strong>
                                        <p style="margin: 0; font-size: 0.95rem;">Sesiones personalizadas adaptadas a tu ritmo.</p>
                                    </div>
                                </li>
                                <li style="margin-bottom: 15px; display: flex; align-items: start; color: #000; font-weight: 500;">
                                    <i class="am-icon-check-circle-01" style="color: #000; margin-right: 15px; font-size: 1.2rem;"></i>
                                    <div>
                                        <strong>Gran variedad de temas:</strong>
                                        <p style="margin: 0; font-size: 0.95rem;">Desde idiomas hasta desarrollo profesional.</p>
                                    </div>
                                </li>
                                <li style="margin-bottom: 15px; display: flex; align-items: start; color: #000; font-weight: 500;">
                                    <i class="am-icon-check-circle-01" style="color: #000; margin-right: 15px; font-size: 1.2rem;"></i>
                                    <div>
                                        <strong>Horarios flexibles:</strong>
                                        <p style="margin: 0; font-size: 0.95rem;">Agenda sesiones cuando más te convenga.</p>
                                    </div>
                                </li>
                                <li style="margin-bottom: 15px; display: flex; align-items: start; color: #000; font-weight: 500;">
                                    <i class="am-icon-check-circle-01" style="color: #000; margin-right: 15px; font-size: 1.2rem;"></i>
                                    <div>
                                        <strong>Plataforma segura:</strong>
                                        <p style="margin: 0; font-size: 0.95rem;">Pagos seguros y verificados.</p>
                                    </div>
                                </li>
                            </ul>
                            <a href="{{ route('login') }}" class="am-btn"
                                style="background-color: #000; color: #fff !important; padding: 12px 35px; border-radius: 50px; font-weight: 600;">UNETE AHORA</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <figure
                            style="margin: 0; box-shadow: 0 20px 40px rgba(0,0,0,0.2); border-radius: 20px; overflow: hidden;">
                            <img src="{{ asset('images/nuevo/que-ofrece-horizontia.jpg') }}" alt="Qué ofrece Horizontia"
                                style="width: 100%; height: auto; display: block;">
                        </figure>
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
                        <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 20px; color: #333;">Soporte integral en cada paso</h2>
                        <p style="font-size: 1.1rem; color: #666; line-height: 1.6;">Nuestro equipo de soporte está dedicado a garantizar que tu experiencia sea fluida y exitosa. Desde problemas técnicos hasta dudas sobre cursos, estamos aquí para ayudarte en todo momento.</p>
                    </div>
                </div>
                <!-- Row 2: Team (Wellness Image) -->
                <div class="row align-items-center">
                    <div class="col-lg-6 pr-lg-5 mb-4 mb-lg-0">
                        <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 20px; color: #333;">Nuestro equipo de expertos te guiará</h2>
                        <p style="font-size: 1.1rem; color: #666; line-height: 1.6;">Contamos con tutores y mentores altamente calificados, seleccionados rigurosamente para ofrecerte la mejor educación. Aprende de profesionales con años de experiencia en sus campos.</p>
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

                <!-- Dynamic Course List Banner -->
                <div class="row justify-content-center" style="margin-top: 30px; padding-bottom: 80px;">
                    <div class="col-md-10">
                        @php
                            $homeCourses = \Modules\Courses\Models\Course::with(['instructor.profile'])->where('status', 'published')->latest()->take(6)->get();
                        @endphp
                        
                        <div class="row">
                            @foreach($homeCourses as $course)
                                <div class="col-md-4 mb-4">
                                    <a href="{{ route('courses.course-detail', $course->slug) }}" style="text-decoration: none;">
                                        <div class="am-course-card" style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s; height: 100%;">
                                            <figure style="margin: 0; position: relative; padding-top: 56.25%;">
                                                @if(!empty($course->thumbnail) && Storage::disk(getStorageDisk())->exists($course->thumbnail))
                                                    <img src="{{ Storage::url($course->thumbnail) }}" alt="{{ $course->title }}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                                                @else
                                                    <img src="{{ asset('images/default-course.png') }}" alt="{{ $course->title }}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; background: #eee;">
                                                @endif
                                            </figure>
                                            <div style="padding: 15px;">
                                                <h4 style="font-size: 1.1rem; font-weight: 700; color: #333; margin-bottom: 10px; line-height: 1.4;">{{ Str::limit($course->title, 50) }}</h4>
                                                <p style="margin: 0; font-size: 0.9rem; color: #777;">
                                                    <i class="am-icon-user-01" style="margin-right: 5px;"></i> 
                                                    {{ $course->instructor->profile->full_name ?? 'Tutor' }}
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Office Meditation Banner Section -->
        <section class="am-meditation-banner" style="width: 100%; overflow: hidden; line-height: 0; margin: 0; padding: 0;">
            <div style="width: 100%; aspect-ratio: 1024 / 333; position: relative; overflow: hidden; background-color: #000;">
                <img src="{{ asset('images/nuevo/meditacion-oficina.png') }}" alt="Meditación en la oficina"
                    style="width: 100%; height: auto; position: absolute; top: calc(-100% * 19 / 333); left: 0; display: block;">
            </div>
        </section>
        <!-- Footer Banner Image -->
        <section class="am-footer-banner"
            style="background-image: url('{{ asset('images/nuevo/respiracion-consciente-horizontia.jpg') }}'); background-size: cover; background-position: center; height: 500px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; position: relative;">
            <div style="position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0, 0, 0, 0.4);"></div> <!-- Dark Overlay -->
            <div style="position: relative; z-index: 2; padding: 20px;">
                <h2 style="font-size: 4rem; font-weight: 800; color: #fff; margin-bottom: 20px; text-shadow: 0px 4px 15px rgba(0,0,0,0.8);">Horizontia</h2>
                <h3 style="font-size: 2rem; font-weight: 600; color: #fff; margin-bottom: 40px; text-shadow: 0px 2px 10px rgba(0,0,0,0.8);">Conciencia humana con talento</h3>
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