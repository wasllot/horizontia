@php
    $name = config('academy.legal_name');
    $refundDays = config('academy.refund_days');
    $banner = app(\App\Services\PageBannerService::class)->system('nosotros');
    $accessOn = \App\Services\AccessRequestService::enabled();
    $requestUrl = $accessOn ? route('access.request') : route('login');
    $stats = \Illuminate\Support\Facades\Cache::remember('home_stats', 600, fn () => [
        'courses' => \Modules\Courses\Models\Course::where('status', \Modules\Courses\Models\Course::STATUSES['active'])->count(),
        'instructors' => \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', 'tutor'))->count(),
    ]);
    $tour = [
        ['Solicita tu acceso', $accessOn ? 'Nadie se registra solo: eliges si quieres aprender o enseñar y envías una solicitud corta. Nuestro equipo la revisa y, al aprobarla, recibes un correo para crear tu contraseña.' : 'El acceso es por invitación: el equipo crea tu cuenta y te envía un correo para elegir tu contraseña.'],
        ['Elige tu curso', 'Explora el catálogo con filtros por nivel, duración y precio. Cada curso muestra su temario, lecciones de vista previa, instructor y reseñas.'],
        ['Paga con Mercado Pago', 'Pago en pesos mexicanos con tarjeta, efectivo o transferencia. Aplica tu código de descuento y el curso se activa en cuanto se aprueba el pago.'],
        ['Aprende en tu aula', 'Videos, lecturas y evaluaciones en un solo lugar. Guarda notas con marcas de tiempo, retoma donde te quedaste y pregunta al tutor con IA o a tu instructor.'],
        ['Obtén tu certificado', 'Al completar el curso recibes un certificado con código único y una página pública donde cualquiera puede comprobar que es auténtico.'],
    ];
@endphp
<x-guest-layout title="Nosotros">
    <x-front.header />
    <link rel="stylesheet" href="{{ asset('css/about.css') }}?v={{ @filemtime(public_path('css/about.css')) }}">

    <x-page-banner :title="$banner['title']" :subtitle="$banner['subtitle']" :image="$banner['image']" :overlay="$banner['overlay']" :align="$banner['align']" crumb="Nosotros" crumbs />

    <main class="ab">
        {{-- 1. Quiénes somos --}}
        <section class="ab-sec">
            <div class="ab-wrap ab-intro">
                <div class="ht-reveal">
                    <span class="ab-eyebrow">Quiénes somos</span>
                    <h2>Aprender también es avanzar con calma</h2>
                    <p><strong>{{ $name }}</strong> es una academia en línea privada: cursos diseñados por instructores especialistas, con contenido en video, lecturas, ejercicios y evaluaciones, y certificado al terminar.</p>
                    <p>Creemos que el talento florece cuando la mente está en calma. Por eso combinamos contenido práctico con un ritmo humano: sin prisas, con acompañamiento y a tu medida.</p>
                    <div class="ab-stats">
                        <div><b data-count="{{ max(1, (int) $stats['courses']) }}">{{ $stats['courses'] }}</b><span>cursos disponibles</span></div>
                        <div><b data-count="{{ max(1, (int) $stats['instructors']) }}">{{ $stats['instructors'] }}</b><span>instructores</span></div>
                        <div><b>100&nbsp;%</b><span>a tu ritmo</span></div>
                    </div>
                </div>
                <div class="ab-media ht-reveal" style="--d: .12s">
                    <figure><img src="{{ webpAsset('images/nuevo/respiracion-consciente-horizontia.jpg') }}" {!! imgAttrs('images/nuevo/respiracion-consciente-horizontia.jpg') !!} alt="Persona respirando con calma al aire libre" loading="lazy"></figure>
                    <span class="ab-chip ab-chip--a"><x-about.icon name="award" :size="18" /> Certificado verificable</span>
                    <span class="ab-chip ab-chip--b"><x-about.icon name="shield" :size="18" /> Pago 100&nbsp;% seguro</span>
                </div>
            </div>
        </section>

        {{-- 2. Lo que nos distingue --}}
        <section class="ab-sec ab-sec--soft">
            <div class="ab-wrap">
                <div class="ab-head ht-reveal">
                    <span class="ab-eyebrow">Lo que nos distingue</span>
                    <h2>Una academia pensada para que sí termines lo que empiezas</h2>
                    <p class="ab-lead">Menos ruido, más práctica y alguien que te acompaña en cada paso.</p>
                </div>
                <div class="ab-grid3">
                    @foreach ([
                        ['target', 'Aprendizaje práctico', 'Cada curso termina con aplicaciones reales, no solo teoría: ejercicios, casos y evaluaciones.'],
                        ['clock', 'A tu ritmo', 'Accede cuando quieras desde computadora, tableta o celular y retoma justo donde te quedaste.'],
                        ['users', 'Acompañamiento real', 'Comunidad de preguntas por curso, mensajes con tu instructor y un tutor con IA que te guía con pistas.'],
                        ['lock', 'Contenido protegido', 'Los materiales son exclusivos para quienes se inscriben: respetamos el trabajo de cada instructor.'],
                        ['card', 'Pagos seguros', 'Pagas en pesos mexicanos con Mercado Pago: tarjeta, efectivo o transferencia.'],
                        ['award', 'Certificado verificable', 'Al terminar recibes un certificado con código único que cualquiera puede comprobar en línea.'],
                    ] as $i => [$icon, $title, $text])
                        <article class="ab-card ht-reveal" style="--d: {{ ($i % 3) * 0.08 }}s">
                            <span class="ab-card__ico"><x-about.icon :name="$icon" /></span>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </article>
                    @endforeach
                </div>
                <div class="ab-modes">
                    <div class="ab-mode ht-reveal"><span class="ab-mode__ico"><x-about.icon name="play" :size="22" /></span><div><h3>En línea, a tu ritmo</h3><p>Lecciones en video y lecturas disponibles todo el tiempo.</p></div></div>
                    <div class="ab-mode ht-reveal" style="--d: .08s"><span class="ab-mode__ico"><x-about.icon name="live" :size="22" /></span><div><h3>En vivo, con grabación</h3><p>Sesiones con tu instructor; si no puedes asistir, queda la grabación.</p></div></div>
                    <div class="ab-mode ht-reveal" style="--d: .16s"><span class="ab-mode__ico"><x-about.icon name="pin" :size="22" /></span><div><h3>Presencial</h3><p>Cursos en sede con fecha, horario y lugar claros desde el inicio.</p></div></div>
                </div>
            </div>
        </section>

        {{-- 3. Recorrido por la plataforma (mockups) --}}
        <section class="ab-sec ab-tour" id="como-funciona" aria-labelledby="ab-tour-title">
            <div class="ab-wrap">
                <div class="ab-head ht-reveal">
                    <span class="ab-eyebrow ab-eyebrow--dark">Así funciona</span>
                    <h2 id="ab-tour-title">De tu solicitud a tu certificado, en cinco pasos</h2>
                    <p class="ab-lead">Un vistazo a lo que verás dentro de la plataforma.</p>
                </div>

                <div class="ab-tour__grid ht-reveal"
                     x-data="{ tab: 0, n: 5, paused: false, run: false, timer: null,
                        go(i) { this.tab = (i + this.n) % this.n; this.restart(); },
                        restart() { clearTimeout(this.timer); this.run = false; this.$nextTick(() => { this.run = true; });
                            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) { this.timer = setTimeout(() => { if (!this.paused) { this.go(this.tab + 1); } }, 7000); } },
                        hold() { this.paused = true; clearTimeout(this.timer); },
                        release() { this.paused = false; this.restart(); } }"
                     x-init="restart()" x-on:mouseenter="hold()" x-on:mouseleave="release()" x-on:focusin="hold()" x-on:focusout="release()">

                    <div>
                        <div class="ab-steps" role="tablist" aria-label="Pasos de la plataforma" x-on:keydown.arrow-down.prevent="go(tab + 1)" x-on:keydown.arrow-up.prevent="go(tab - 1)" x-on:keydown.arrow-right.prevent="go(tab + 1)" x-on:keydown.arrow-left.prevent="go(tab - 1)">
                            @foreach ($tour as $i => [$title, $desc])
                                <button type="button" class="ab-step" role="tab" id="ab-tab-{{ $i }}" aria-controls="ab-panel-{{ $i }}"
                                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}" :aria-selected="tab === {{ $i }} ? 'true' : 'false'" :tabindex="tab === {{ $i }} ? 0 : -1" x-on:click="go({{ $i }})">
                                    <span class="ab-step__n">{{ $i + 1 }}</span>
                                    <strong>{{ $title }}</strong>
                                    <small>{{ $desc }}</small>
                                    <span class="ab-step__bar" :class="{ 'is-run': run, 'is-paused': paused }"></span>
                                </button>
                            @endforeach
                        </div>
                        @foreach ($tour as $i => [$title, $desc])
                            <p class="ab-tour__desc" x-show="tab === {{ $i }}" @if ($i > 0) style="display: none" @endif>{{ $desc }}</p>
                        @endforeach
                    </div>

                    <div>
                        <div class="ab-stage">
                            {{-- Paso 1: solicitud de acceso --}}
                            <div class="ab-panel is-on" :class="{ 'is-on': tab === 0 }" id="ab-panel-0" role="tabpanel" aria-labelledby="ab-tab-0" :aria-hidden="tab !== 0 ? 'true' : 'false'">
                                <div class="mk">
                                    <div class="mk__bar"><i></i><i></i><i></i><span class="mk__url"><x-about.icon name="lock" :size="12" /> horizontia.mx/solicitar-acceso</span></div>
                                    <div class="mk__body">
                                        <h4>Solicitar acceso</h4>
                                        <small>Pide tu cuenta como estudiante o como instructor.</small>
                                        <div class="mk-seg" style="margin-top: 14px">
                                            <div class="is-on"><x-about.icon name="book" :size="18" /> Quiero aprender</div>
                                            <div><x-about.icon name="users" :size="18" /> Quiero enseñar</div>
                                        </div>
                                        <div class="mk-grid2">
                                            <div class="mk-field"><label>Nombre completo</label><div class="mk-input is-filled">Ana Torres</div></div>
                                            <div class="mk-field"><label>Correo electrónico</label><div class="mk-input is-focus is-filled">ana@correo.mx</div></div>
                                        </div>
                                        <div class="mk-field"><label>¿Qué te gustaría aprender? <span class="mk-muted">(opcional)</span></label><div class="mk-input is-filled">Mindfulness y manejo del estrés</div></div>
                                        <div class="mk-between"><span class="mk-btn">Enviar solicitud</span><span class="mk-pill mk-pill--gold">Respuesta en 1–2 días hábiles</span></div>
                                        <div class="mk-flow">
                                            <span class="is-done"><x-about.icon name="check" :size="13" /> Enviada</span><em></em>
                                            <span class="is-done"><x-about.icon name="check" :size="13" /> En revisión</span><em></em>
                                            <span>Aprobada</span>
                                        </div>
                                        <div class="mk-mail">
                                            <div class="mk-row"><span class="mk-ico"><x-about.icon name="mail" :size="18" /></span><div><strong style="color: var(--navy)">Tu acceso fue aprobado</strong><br><small>Usa este enlace para elegir tu contraseña y entrar.</small></div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Paso 2: catálogo --}}
                            <div class="ab-panel" :class="{ 'is-on': tab === 1 }" id="ab-panel-1" role="tabpanel" aria-labelledby="ab-tab-1" :aria-hidden="tab !== 1 ? 'true' : 'false'">
                                <div class="mk">
                                    <div class="mk__bar"><i></i><i></i><i></i><span class="mk__url"><x-about.icon name="lock" :size="12" /> horizontia.mx/cursos</span></div>
                                    <div class="mk__body">
                                        <div class="mk-between" style="margin-bottom: 14px"><div class="mk-input" style="flex: 1; max-width: 300px"><x-about.icon name="search" :size="14" />&nbsp; Buscar cursos…</div><span class="mk-pill">12 cursos</span></div>
                                        <div class="mk-cat">
                                            <div class="mk-side">
                                                <b>Nivel</b>
                                                <div class="mk-chk is-on">Principiante</div><div class="mk-chk">Intermedio</div><div class="mk-chk">Experto</div>
                                                <b>Duración</b>
                                                <div class="mk-chk">Hasta 1 hora</div><div class="mk-chk is-on">3 a 6 horas</div>
                                                <b>Precio</b>
                                                <div class="mk-chk">Gratis</div><div class="mk-chk">De pago</div>
                                            </div>
                                            <div class="mk-courses">
                                                @foreach ([['mk-th--1', 'Respiración consciente', '$849', 'Principiante · 3 h'], ['mk-th--2', 'Liderazgo con atención plena', '$1,299', 'Intermedio · 5 h'], ['mk-th--3', 'Hábitos para el bienestar', 'Gratis', 'Principiante · 1 h']] as [$th, $t, $p, $m])
                                                    <div class="mk-course"><div class="mk-th {{ $th }}"></div><h5>{{ $t }}</h5><small>{{ $m }}</small><div class="mk-between"><b>{{ $p }}</b><span class="mk-pill">Ver curso</span></div></div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="mk-mail" style="margin-top: 16px"><div class="mk-row"><span class="mk-ico"><x-about.icon name="play" :size="18" /></span><div><strong style="color: var(--navy)">Lecciones de vista previa</strong><br><small>Prueba una lección gratis antes de decidir.</small></div></div></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Paso 3: pago --}}
                            <div class="ab-panel" :class="{ 'is-on': tab === 2 }" id="ab-panel-2" role="tabpanel" aria-labelledby="ab-tab-2" :aria-hidden="tab !== 2 ? 'true' : 'false'">
                                <div class="mk">
                                    <div class="mk__bar"><i></i><i></i><i></i><span class="mk__url"><x-about.icon name="lock" :size="12" /> horizontia.mx/finalizar-compra</span></div>
                                    <div class="mk__body">
                                        <div class="mk-pay">
                                            <div>
                                                <h4>Tu pedido</h4>
                                                <div class="mk-row" style="margin: 12px 0"><div class="mk-th mk-th--1" style="width: 86px; flex: 0 0 86px"></div><div><strong style="color: var(--navy)">Respiración consciente</strong><br><small>Acceso completo · certificado incluido</small></div></div>
                                                <div class="mk-field" style="margin-top: 14px"><label>Código de descuento</label>
                                                    <div class="mk-row"><div class="mk-input is-filled" style="flex: 1">BIENVENIDA10</div><span class="mk-pill mk-pill--ok"><x-about.icon name="check" :size="12" /> Aplicado</span></div></div>
                                                <div class="mk-line"><span>Subtotal</span><span>$999.00</span></div>
                                                <div class="mk-line mk-line--disc"><span>Descuento (10 %)</span><span>−$99.90</span></div>
                                                <div class="mk-line mk-line--total"><span>Total (MXN)</span><span>$899.10</span></div>
                                            </div>
                                            <div>
                                                <h4 style="margin-bottom: 10px">Método de pago</h4>
                                                <div class="mk-meth is-on"><span class="mk-radio"></span><x-about.icon name="card" :size="18" /> Tarjeta de crédito o débito</div>
                                                <div class="mk-meth"><span class="mk-radio"></span><x-about.icon name="pin" :size="18" /> Efectivo en tienda</div>
                                                <div class="mk-meth"><span class="mk-radio"></span><x-about.icon name="globe" :size="18" /> Transferencia bancaria</div>
                                                <label style="display: flex; gap: 8px; align-items: center; font-size: .76rem; margin: 12px 0; font-weight: 400"><span class="mk-chk is-on" style="gap: 0"></span> Quiero avisos por WhatsApp de mis clases</label>
                                                <span class="mk-btn mk-btn--block mk-btn--navy"><x-about.icon name="lock" :size="15" /> Pagar $899.10</span>
                                                <div class="mk-row" style="justify-content: center; margin-top: 12px"><span class="mk-mp"><i></i> Mercado Pago</span><small>· pago seguro</small></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Paso 4: aula --}}
                            <div class="ab-panel" :class="{ 'is-on': tab === 3 }" id="ab-panel-3" role="tabpanel" aria-labelledby="ab-tab-3" :aria-hidden="tab !== 3 ? 'true' : 'false'">
                                <div class="mk">
                                    <div class="mk__bar"><i></i><i></i><i></i><span class="mk__url"><x-about.icon name="lock" :size="12" /> horizontia.mx/aula/respiracion-consciente</span></div>
                                    <div class="mk__body">
                                        <div class="mk-class">
                                            <div>
                                                <div class="mk-video">
                                                    <span class="mk-play"><x-about.icon name="play" :size="26" /></span>
                                                    <div class="mk-ctrl"><span>04:32</span><div class="mk-bar"><i style="width: 38%"></i></div><span>12:10</span></div>
                                                </div>
                                                <div class="mk-between" style="margin-top: 12px"><div><h4>6. La pausa que ordena la mente</h4><small>Lección 6 de 9 · Video</small></div><span class="mk-pill mk-pill--gold"><x-about.icon name="clock" :size="12" /> Retomaste en 04:32</span></div>
                                                <div class="mk-tabs"><span class="is-on">Notas</span><span>Tutor IA</span><span>Comunidad</span></div>
                                                <div class="mk-row" style="padding: 10px 12px; border-radius: 10px; background: #fffbe0; border: 1px solid #f1df8a"><span class="mk-pill mk-pill--sel">04:32</span><span>Inhalar 4 s, sostener 4 s y soltar en 6 s.</span></div>
                                                <div style="margin-top: 10px"><div class="mk-msg mk-msg--me">¿Por qué es mejor exhalar más lento?</div><div class="mk-msg mk-msg--ia"><strong>Tutor IA:</strong> Buena pregunta. ¿Qué crees que le pasa a tu ritmo cardiaco cuando alargas la exhalación?</div></div>
                                            </div>
                                            <div class="mk-lessons">
                                                <small style="font-weight: 700; color: var(--navy)">Contenido del curso</small>
                                                <div class="mk-les"><x-about.icon name="check" :size="14" class="mk-ok" /> 1. Bienvenida</div>
                                                <div class="mk-les"><x-about.icon name="check" :size="14" class="mk-ok" /> 2. Cómo respiras hoy</div>
                                                <div class="mk-les"><x-about.icon name="check" :size="14" class="mk-ok" /> 3. Respiración diafragmática</div>
                                                <div class="mk-les"><x-about.icon name="check" :size="14" class="mk-ok" /> 4. Lectura: el sistema nervioso</div>
                                                <div class="mk-les"><x-about.icon name="check" :size="14" class="mk-ok" /> 5. Evaluación rápida</div>
                                                <div class="mk-les is-now"><x-about.icon name="play" :size="14" /> 6. La pausa que ordena</div>
                                                <div class="mk-les"><x-about.icon name="lock" :size="14" /> 7. Práctica guiada</div>
                                                <div class="mk-bar" style="margin-top: 6px"><i style="width: 68%"></i></div><small>68 % completado</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Paso 5: certificado --}}
                            <div class="ab-panel" :class="{ 'is-on': tab === 4 }" id="ab-panel-4" role="tabpanel" aria-labelledby="ab-tab-4" :aria-hidden="tab !== 4 ? 'true' : 'false'">
                                <div class="mk">
                                    <div class="mk__bar"><i></i><i></i><i></i><span class="mk__url"><x-about.icon name="lock" :size="12" /> horizontia.mx/verificar</span></div>
                                    <div class="mk__body">
                                        <div class="mk-cert">
                                            <div class="mk-seal"><x-about.icon name="award" :size="28" /></div>
                                            <small style="letter-spacing: .12em; text-transform: uppercase; font-weight: 700">Certificado de finalización</small>
                                            <h4>Ana Torres</h4>
                                            <small>completó satisfactoriamente el curso</small>
                                            <div style="font-weight: 800; color: var(--navy); margin: 4px 0 2px">Respiración consciente</div>
                                            <small>3 horas · 9 lecciones</small>
                                            <div class="mk-cert__row">
                                                <div><small>Código</small><br><strong style="color: var(--navy)">HZ-7K4Q-92XM</strong><br><small>Emitido el 11 oct. 2026</small></div>
                                                <svg class="mk-qr" viewBox="0 0 21 21" aria-hidden="true"><path fill="#14213d" d="M0 0h7v7H0zm1 1v5h5V1zm1 1h3v3H2zM14 0h7v7h-7zm1 1v5h5V1zm1 1h3v3h-3zM0 14h7v7H0zm1 1v5h5v-5zm1 1h3v3H2zM8 1h2v2H8zm3 2h2v2h-2zM8 5h1v2H8zm4 2h2v1h-2zM9 8h3v2H9zm5 1h2v2h-2zm3 0h3v1h-3zM8 12h2v2H8zm4 1h3v2h-3zm5 0h2v3h-2zM9 16h2v3H9zm4 3h4v2h-4zm5-2h3v2h-3zM12 9h1v3h-1z"/></svg>
                                            </div>
                                        </div>
                                        <div class="mk-verify"><div class="mk-input is-filled">HZ-7K4Q-92XM</div><span class="mk-btn">Verificar</span></div>
                                        <div class="mk-valid"><x-about.icon name="shield" :size="18" /> Certificado auténtico · emitido a nombre de Ana Torres</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ab-more" style="margin-top: 34px; text-align: center">
                    <a class="ab-btn ab-btn--gold" href="{{ $requestUrl }}">{{ $accessOn ? 'Solicitar acceso' : 'Iniciar sesión' }} <x-about.icon name="arrow" :size="18" /></a>
                </div>
            </div>
        </section>

        {{-- 4. Dentro de la plataforma (bento con mini-mockups) --}}
        <section class="ab-sec ab-sec--soft" aria-labelledby="ab-bento-title">
            <div class="ab-wrap">
                <div class="ab-head ht-reveal">
                    <span class="ab-eyebrow">Dentro de la plataforma</span>
                    <h2 id="ab-bento-title">Herramientas que hacen la diferencia</h2>
                    <p class="ab-lead">Pequeños detalles pensados para que estudies mejor y no te pierdas.</p>
                </div>
                <div class="ab-bento">
                    <article class="ab-tile ab-tile--wide ht-reveal">
                        <div><h3>Retoma donde te quedaste</h3><p>Guardamos tu posición en cada video y tu avance en cada curso. Entras y sigues, sin buscar.</p></div>
                        <div class="ab-tile__mock">
                            <div class="mk-row"><span class="ab-ring" style="--p: 68"><b>68%</b></span>
                                <div style="flex: 1"><strong style="color: var(--navy)">Respiración consciente</strong><br><small>Lección 6 · te quedaste en 04:32</small><div class="mk-bar" style="margin: 8px 0 10px"><i style="width: 68%"></i></div><span class="mk-btn">Continuar <x-about.icon name="arrow" :size="15" /></span></div></div>
                        </div>
                    </article>
                    <article class="ab-tile ab-tile--wide ht-reveal" style="--d: .08s">
                        <div><h3>Notas con marca de tiempo</h3><p>Anota mientras ves la lección; cada nota recuerda el minuto exacto para volver a él con un clic.</p></div>
                        <div class="ab-tile__mock ab-tl" style="padding-left: 34px">
                            <div class="is-done"><span><span class="mk-pill mk-pill--sel">02:10</span> &nbsp;La respiración baja el ritmo cardiaco.</span></div>
                            <div class="is-done"><span><span class="mk-pill mk-pill--sel">04:32</span> &nbsp;Inhalar 4 s · sostener 4 s · soltar 6 s.</span></div>
                            <div class="is-now"><span class="mk-muted">Escribe una nota…</span><span class="mk-pill mk-pill--gold">+ Agregar</span></div>
                        </div>
                    </article>
                    <article class="ab-tile ht-reveal">
                        <div><h3>Tutor con inteligencia artificial</h3><p>Te guía con preguntas y pistas, sin darte las respuestas de una evaluación.</p></div>
                        <div class="ab-tile__mock"><div class="mk-msg mk-msg--me">No entiendo el paso 3</div><div class="mk-msg mk-msg--ia">Vamos con una pista: ¿qué cambia si alargas la exhalación?</div></div>
                    </article>
                    @if (config('academy.community'))
                        <article class="ab-tile ht-reveal" style="--d: .08s">
                            <div><h3>Comunidad en cada curso</h3><p>Pregunta, responde y marca la solución. El instructor participa y se distingue.</p></div>
                            <div class="ab-tile__mock">
                                <div class="mk-row" style="align-items: flex-start"><span class="mk-av">LM</span><div><strong style="color: var(--navy)">¿Cuánto debo practicar al día?</strong><br><small>Luis · hace 2 h</small></div></div>
                                <div class="mk-row" style="align-items: flex-start; margin-top: 10px; padding: 8px; background: #e3f6ea; border-radius: 10px"><span class="mk-av mk-av--navy">IN</span><div><span class="mk-pill mk-pill--ok">Instructor · Solución</span><br>Con 10 minutos al día basta para empezar.</div></div>
                            </div>
                        </article>
                    @endif
                    <article class="ab-tile ht-reveal" style="--d: .16s">
                        <div><h3>Sesiones en vivo con grabación</h3><p>Asiste en directo o míralas después: la grabación se agrega sola como lección del curso.</p></div>
                        <div class="ab-tile__mock">
                            <div class="ab-live"><span class="mk-ico"><x-about.icon name="live" :size="18" /></span><div style="flex: 1"><strong style="color: var(--navy)">Práctica guiada en vivo</strong><br><small>Jueves 18:00 · Zoom</small></div><span class="mk-pill mk-pill--gold"><span class="ab-dot"></span> En vivo</span></div>
                            <div class="mk-row" style="margin-top: 10px"><x-about.icon name="check" :size="14" class="mk-ok" /> <small>Grabación disponible como lección 10</small></div>
                        </div>
                    </article>
                    <article class="ab-tile ht-reveal">
                        <div><h3>Rutas de aprendizaje</h3><p>Varios cursos en orden, con un certificado final de la ruta.</p></div>
                        <div class="ab-tile__mock ab-tl" style="padding-left: 34px">
                            <div class="is-done"><span>1. Fundamentos de calma</span><span class="mk-pill mk-pill--ok">Listo</span></div>
                            <div class="is-now"><span>2. Respiración consciente</span><span class="mk-pill mk-pill--gold">68 %</span></div>
                            <div><span>3. Liderazgo con atención plena</span><x-about.icon name="lock" :size="14" /></div>
                        </div>
                    </article>
                    @if (config('academy.pwa'))
                        <article class="ab-tile ht-reveal" style="--d: .08s">
                            <div><h3>Instálala como app</h3><p>Añádela a tu pantalla de inicio y entra en un toque, también desde el celular.</p></div>
                            <div class="ab-tile__mock"><div class="ab-phone"><div class="ab-phone__scr"><div class="ab-phone__ico">H</div><div class="mk-sk"></div><div class="mk-sk" style="width: 70%"></div><div class="mk-bar" style="margin-top: 10px"><i style="width: 68%"></i></div><span class="mk-btn" style="height: 26px; font-size: .68rem; margin-top: 10px; width: 100%">Continuar</span></div></div></div>
                        </article>
                    @endif
                    <article class="ab-tile ht-reveal" style="--d: .16s">
                        <div><h3>Avisos que sí sirven</h3><p>Recordatorios de tus clases en vivo y de tu avance, por correo o WhatsApp (solo si tú lo permites).</p></div>
                        <div class="ab-tile__mock">
                            <div class="mk-row"><span class="mk-ico"><x-about.icon name="bell" :size="18" /></span><div><strong style="color: var(--navy)">Tu clase empieza en 1 hora</strong><br><small>Práctica guiada · hoy 18:00</small></div></div>
                            <div class="mk-row" style="margin-top: 10px"><span class="mk-ico"><x-about.icon name="heart" :size="18" /></span><div><strong style="color: var(--navy)">¡Retoma tu curso!</strong><br><small>Te falta poco para tu certificado.</small></div></div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- 5. Para instructores --}}
        <section class="ab-sec" aria-labelledby="ab-inst-title">
            <div class="ab-wrap ab-inst">
                <div class="ht-reveal">
                    <span class="ab-eyebrow">Para instructores</span>
                    <h2 id="ab-inst-title">Enseña lo que sabes, nosotros cuidamos el resto</h2>
                    <p>Los instructores son especialistas seleccionados con rigor. Tú te enfocas en el contenido; la plataforma se encarga de cobrar, entregar, acompañar y medir.</p>
                    <ul class="ab-list">
                        <li><span class="mk-ico"><x-about.icon name="book" :size="18" /></span><div><strong>Crea cursos a tu manera</strong><span>Videos, lecturas, evaluaciones, sesiones en vivo o presenciales, en un mismo curso.</span></div></li>
                        <li><span class="mk-ico"><x-about.icon name="spark" :size="18" /></span><div><strong>Estudio de contenido con IA</strong><span>Genera borradores de temario, descripciones y preguntas para revisarlos y afinarlos.</span></div></li>
                        <li><span class="mk-ico"><x-about.icon name="chart" :size="18" /></span><div><strong>Mira cómo avanzan tus alumnos</strong><span>Inscripciones, avance y dudas pendientes, en un tablero claro.</span></div></li>
                        <li><span class="mk-ico"><x-about.icon name="chat" :size="18" /></span><div><strong>Acompaña sin esfuerzo</strong><span>Responde en la comunidad del curso y por mensajes directos.</span></div></li>
                    </ul>
                    <a class="ab-btn ab-btn--ghost" href="{{ $accessOn ? route('access.request', ['como' => 'instructor']) : route('contacto') }}">{{ $accessOn ? 'Quiero ser instructor' : 'Escríbenos' }} <x-about.icon name="arrow" :size="18" /></a>
                </div>
                <div class="ht-reveal" style="--d: .12s">
                    <div class="mk">
                        <div class="mk__bar"><i></i><i></i><i></i><span class="mk__url"><x-about.icon name="lock" :size="12" /> horizontia.mx/panel/tutor</span></div>
                        <div class="mk__body">
                            <div class="mk-between" style="margin-bottom: 14px"><div><h4>Tablero del instructor</h4><small>Últimos 7 meses</small></div><span class="mk-pill mk-pill--ok">+18 % inscripciones</span></div>
                            <div class="mk-kpis"><div class="mk-kpi"><b>142</b><small>Estudiantes</small></div><div class="mk-kpi"><b>86 %</b><small>Avance medio</small></div><div class="mk-kpi"><b>4.8</b><small>Reseñas</small></div></div>
                            <div class="mk-chart" aria-hidden="true">
                                @foreach ([38, 52, 44, 66, 58, 80, 94] as $h)<i style="height: {{ $h }}%"></i>@endforeach
                            </div>
                            <div class="mk-between" style="margin: 6px 4px 0; font-size: .7rem; color: #98a0b3"><span>Abr</span><span>May</span><span>Jun</span><span>Jul</span><span>Ago</span><span>Sep</span><span>Oct</span></div>
                            <div class="mk-gen">
                                <div class="mk-between"><strong style="color: var(--navy)"><x-about.icon name="spark" :size="16" style="vertical-align: -3px" /> Estudio de contenido</strong><span class="mk-pill mk-pill--gold">IA</span></div>
                                <div class="mk-sk" style="width: 92%"></div><div class="mk-sk" style="width: 74%"></div><div class="mk-sk" style="width: 58%"></div>
                                <span class="mk-btn" style="height: 32px; margin-top: 4px">Generar borrador</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 6. Nuestro compromiso --}}
        <section class="ab-sec ab-sec--warm" aria-labelledby="ab-trust-title">
            <div class="ab-wrap">
                <div class="ab-head ht-reveal">
                    <span class="ab-eyebrow">Nuestro compromiso</span>
                    <h2 id="ab-trust-title">Claridad en lo que pagas y cuidado de tus datos</h2>
                    <p class="ab-lead">Cuidamos la calidad del contenido, la claridad de los precios y la protección de tu información.</p>
                </div>
                <div class="ab-trust">
                    <div class="ht-reveal"><span class="ab-card__ico"><x-about.icon name="shield" /></span><h3>Garantía de reembolso</h3><p>Si algo no sale como esperabas, tienes una <a href="{{ route('legal.refunds') }}"><b>garantía de reembolso de {{ $refundDays }} días</b></a>.</p></div>
                    <div class="ht-reveal" style="--d: .06s"><span class="ab-card__ico"><x-about.icon name="card" /></span><h3>Precios claros</h3><p>Todo en <b>pesos mexicanos</b>, sin cargos escondidos. Pagos seguros con Mercado Pago.</p></div>
                    <div class="ht-reveal" style="--d: .12s"><span class="ab-card__ico"><x-about.icon name="lock" /></span><h3>Datos protegidos</h3><p>Tratamos tu información conforme a la ley mexicana. Consulta nuestro <a href="{{ route('legal.privacy') }}"><b>aviso de privacidad</b></a>.</p></div>
                    <div class="ht-reveal" style="--d: .18s"><span class="ab-card__ico"><x-about.icon name="users" /></span><h3>Personas reales</h3><p>¿Dudas? Un equipo humano te responde, normalmente en menos de 24 horas hábiles.</p></div>
                </div>
            </div>
        </section>

        {{-- 7. Preguntas frecuentes --}}
        <section class="ab-sec" aria-labelledby="ab-faq-title">
            <div class="ab-wrap">
                <div class="ab-head ht-reveal">
                    <span class="ab-eyebrow">Preguntas frecuentes</span>
                    <h2 id="ab-faq-title">Lo que más nos preguntan</h2>
                </div>
                <div class="ab-faq ht-reveal">
                    @foreach ([
                        ['¿Puedo registrarme por mi cuenta?', $accessOn ? 'No. Por ser una academia privada, cada persona pide su acceso como estudiante o instructor. Revisamos la solicitud y, al aprobarla, te enviamos un correo para elegir tu contraseña.' : 'No. Por ser una academia privada, el equipo crea las cuentas. Escríbenos desde la página de contacto y te ayudamos.'],
                        ['¿Cómo y en qué moneda pago?', 'Pagas en pesos mexicanos (MXN) con Mercado Pago: tarjeta, efectivo o transferencia. Tu curso se activa cuando se aprueba el pago.'],
                        ['¿Qué pasa si el curso no es lo que esperaba?', 'Puedes solicitar un reembolso dentro de los primeros ' . $refundDays . ' días, según las condiciones de nuestra política de reembolsos.'],
                        ['¿Recibo un certificado?', 'Sí. Al completar un curso en línea recibes un certificado con código único y una página pública para verificarlo.'],
                        ['¿Puedo estudiar desde el celular?', 'Sí, la plataforma funciona en computadora, tableta y celular' . (config('academy.pwa') ? ', y puedes instalarla como app.' : '.')],
                        ['¿El tutor con IA me da las respuestas de las evaluaciones?', 'No. Te guía con preguntas y pistas para que llegues tú a la respuesta; nunca contesta evaluaciones calificadas.'],
                    ] as [$q, $a])
                        <details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
                    @endforeach
                </div>
                <p class="ab-more"><a class="ab-btn ab-btn--ghost" href="{{ route('legal.faq') }}">Ver todas las preguntas <x-about.icon name="arrow" :size="18" /></a></p>
            </div>
        </section>

        {{-- 8. Llamado a la acción --}}
        <div class="ab-cta-wrap">
            <section class="ab-cta ht-reveal">
                <h2>¿Listo para aprender a tu ritmo?</h2>
                <p>Explora el catálogo y empieza hoy. Si tienes dudas, estamos a un mensaje de distancia.</p>
                <div class="ab-cta__btns">
                    <a class="ab-btn ab-btn--gold" href="{{ route('courses.search-courses') }}">Ver cursos <x-about.icon name="arrow" :size="18" /></a>
                    @if ($accessOn)<a class="ab-btn ab-btn--line" href="{{ route('access.request') }}">Solicitar acceso</a>@endif
                    <a class="ab-btn ab-btn--line" href="{{ route('contacto') }}">Contacto</a>
                </div>
            </section>
        </div>
    </main>

    <x-front.footer />
</x-guest-layout>
