/* Horizontia · Editor visual de páginas (GrapesJS, BSD-3). Sin build: se sirve tal cual.
   - Biblioteca de bloques a la izquierda (miniaturas reales y buscador) e inspector a la derecha.
   - Bloques dinámicos (cursos, eventos, fechas, ficha del curso) con VISTA PREVIA EN TIEMPO REAL en el lienzo.
   - Proyecto JSON + HTML + CSS guardados por API (autoguardado, Ctrl+S, versiones).
   - Idioma español, vista de dispositivos, gestor de imágenes con subida, plantillas de inicio. */
(function () {
    'use strict';
    var CFG = window.HZ_EDITOR, URLS = CFG.urls, $ = function (s) { return document.querySelector(s); }, $$ = function (s) { return Array.prototype.slice.call(document.querySelectorAll(s)); };
    var token = document.querySelector('meta[name="csrf-token"]').content;
    var dirty = false, saving = false, timer = null, loaded = false;

    function api(url, method, body) {
        return fetch(url, {
            method: method, credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: body ? JSON.stringify(body) : undefined
        }).then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { if (!r.ok) { var e = new Error(j.message || ('Error ' + r.status)); e.errors = j.errors; throw e; } return j; }); });
    }
    function plugin(name) { var m = window[name]; return m && m.default ? m.default : m; }
    function esc(t) { return String(t == null ? '' : t).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function debounce(fn, ms) { var t; return function () { var a = arguments, self = this; clearTimeout(t); t = setTimeout(function () { fn.apply(self, a); }, ms); }; }

    /* ---------------------------------------------------------------- bloques de la academia */
    var cat = { a: 'Academia', dyn: 'Cursos y eventos', b: 'Estructura', c: 'Contenido', f: 'Formularios', adv: 'Avanzado' };
    var BLOCKS = [
        { id: 'hz-hero', label: 'Encabezado', category: cat.a, media: '<svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="10" rx="2"/><path d="M6 18h12"/></svg>',
          content: '<section class="ht-block ht-hero ht-hero--navy"><div class="container"><span class="ht-hero__eyebrow">Etiqueta</span><h1>Título principal de la página</h1><p>Una descripción breve que explique el valor de este contenido.</p><a class="am-btn" href="/cursos">Ver cursos</a></div></section>' },
        { id: 'hz-hero-light', label: 'Encabezado claro', category: cat.a, media: '<svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="9" rx="2"/><path d="M6 17h8"/></svg>',
          content: '<section class="ht-block ht-hero ht-hero--light"><div class="container"><h1>Título de la página</h1><p>Descripción corta.</p></div></section>' },
        { id: 'hz-text', label: 'Texto enriquecido', category: cat.a, media: '<svg viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h10M4 18h14"/></svg>',
          content: '<section class="ht-block ht-rich ht-rich--narrow"><div class="container"><header class="ht-block__head"><h2>Título de la sección</h2></header><div class="ht-prose"><p>Escribe aquí tu contenido. Haz doble clic para editar; usa la barra para negritas, enlaces y listas.</p><ul><li>Primer punto</li><li>Segundo punto</li></ul></div></div></section>' },
        { id: 'hz-img-text', label: 'Imagen + texto', category: cat.a, media: '<svg viewBox="0 0 24 24"><rect x="2" y="5" width="9" height="14" rx="1"/><path d="M14 8h8M14 12h8M14 16h5"/></svg>',
          content: '<section class="ht-block hz-split"><div class="container"><div class="row align-items-center g-4"><div class="col-md-6"><img class="hz-split__img" src="/images/nuevo/Slider-horizontia.jpg" alt="Descripción de la imagen"></div><div class="col-md-6"><h2>Título</h2><p>Cuenta algo importante junto a una imagen. Cambia la imagen con doble clic.</p><a class="am-btn" href="/cursos">Conoce más</a></div></div></div></section>' },
        { id: 'hz-steps', label: 'Pasos numerados', category: cat.a, media: '<svg viewBox="0 0 24 24"><circle cx="6" cy="6" r="3"/><path d="M12 6h9M12 12h9M12 18h9"/><circle cx="6" cy="18" r="3"/></svg>',
          content: '<section class="ht-block ht-steps"><div class="container"><header class="ht-block__head"><h2>Cómo funciona</h2></header><ol class="ht-steps__list"><li><span class="ht-steps__n">1</span><div><h3>Elige tu curso</h3><p>Explora el catálogo y revisa el temario.</p></div></li><li><span class="ht-steps__n">2</span><div><h3>Paga con seguridad</h3><p>Tarjeta, efectivo o transferencia.</p></div></li><li><span class="ht-steps__n">3</span><div><h3>Aprende a tu ritmo</h3><p>Accede de inmediato desde cualquier dispositivo.</p></div></li></ol></div></section>' },
        { id: 'hz-faq', label: 'Preguntas frecuentes', category: cat.a, media: '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="4" rx="1"/><rect x="3" y="10" width="18" height="4" rx="1"/><rect x="3" y="16" width="18" height="4" rx="1"/></svg>',
          content: '<section class="ht-block ht-faq"><div class="container"><header class="ht-block__head"><h2>Preguntas frecuentes</h2></header><div class="accordion ht-accordion" id="faq-1"><div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq-1-a" aria-expanded="true">¿Primera pregunta?</button></h3><div id="faq-1-a" class="accordion-collapse collapse show" data-bs-parent="#faq-1"><div class="accordion-body">Respuesta a la primera pregunta.</div></div></div><div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-1-b" aria-expanded="false">¿Segunda pregunta?</button></h3><div id="faq-1-b" class="accordion-collapse collapse" data-bs-parent="#faq-1"><div class="accordion-body">Respuesta a la segunda pregunta.</div></div></div></div></div></section>' },
        { id: 'hz-cta', label: 'Llamado a la acción', category: cat.a, media: '<svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="10" rx="3"/><path d="M15 12h4"/></svg>',
          content: '<section class="ht-block ht-cta-wrap"><div class="container"><div class="ht-cta ht-cta--gold"><div><h2>¿Listo para empezar?</h2><p>Elige tu curso y comienza hoy mismo.</p></div><a class="am-btn" href="/cursos">Ver cursos</a></div></div></section>' },
        { id: 'hz-stats', label: 'Cifras', category: cat.a, media: '<svg viewBox="0 0 24 24"><path d="M5 20V10M12 20V4M19 20v-7"/></svg>',
          content: '<section class="ht-block hz-stats"><div class="container"><div class="row g-4 text-center"><div class="col-6 col-md-3"><strong class="hz-stats__n">+5,000</strong><span>Alumnos</span></div><div class="col-6 col-md-3"><strong class="hz-stats__n">120</strong><span>Cursos</span></div><div class="col-6 col-md-3"><strong class="hz-stats__n">4.8★</strong><span>Valoración</span></div><div class="col-6 col-md-3"><strong class="hz-stats__n">24/7</strong><span>Acceso</span></div></div></div></section>' },
        { id: 'hz-testimonials', label: 'Testimonios', category: cat.a, media: '<svg viewBox="0 0 24 24"><path d="M4 5h16v10H9l-5 4z"/></svg>',
          content: '<section class="ht-block hz-quotes"><div class="container"><header class="ht-block__head"><h2>Lo que dicen nuestros alumnos</h2></header><div class="row g-4"><div class="col-md-4"><figure class="hz-quote"><blockquote>“Aprendí a mi ritmo y conseguí un mejor empleo.”</blockquote><figcaption><strong>Nombre Apellido</strong><span>Alumna</span></figcaption></figure></div><div class="col-md-4"><figure class="hz-quote"><blockquote>“Clases claras y prácticas, totalmente recomendable.”</blockquote><figcaption><strong>Nombre Apellido</strong><span>Alumno</span></figcaption></figure></div><div class="col-md-4"><figure class="hz-quote"><blockquote>“El soporte del instructor marcó la diferencia.”</blockquote><figcaption><strong>Nombre Apellido</strong><span>Alumna</span></figcaption></figure></div></div></div></section>' },
        { id: 'hz-pricing', label: 'Planes y precios', category: cat.a, media: '<svg viewBox="0 0 24 24"><rect x="2" y="5" width="6" height="14" rx="1"/><rect x="9" y="3" width="6" height="16" rx="1"/><rect x="16" y="5" width="6" height="14" rx="1"/></svg>',
          content: '<section class="ht-block hz-plans"><div class="container"><header class="ht-block__head"><h2>Elige tu plan</h2></header><div class="row g-4"><div class="col-md-4"><div class="hz-plan"><h3>Básico</h3><p class="hz-plan__price">$499<small> MXN</small></p><ul><li>Acceso al curso</li><li>Certificado</li></ul><a class="am-btn" href="/cursos">Elegir</a></div></div><div class="col-md-4"><div class="hz-plan hz-plan--hot"><h3>Completo</h3><p class="hz-plan__price">$899<small> MXN</small></p><ul><li>Todo lo del básico</li><li>Tutor IA</li><li>Material descargable</li></ul><a class="am-btn" href="/cursos">Elegir</a></div></div><div class="col-md-4"><div class="hz-plan"><h3>Equipos</h3><p class="hz-plan__price">A medida</p><ul><li>Licencias múltiples</li><li>Soporte dedicado</li></ul><a class="am-btn" href="/contacto">Contactar</a></div></div></div></div></section>' },
        { id: 'hz-video', label: 'Video (YouTube)', category: cat.a, media: '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m10 9 5 3-5 3z"/></svg>',
          content: '<section class="ht-block ht-video"><div class="container"><div class="ht-video__frame"><iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ" title="Video" loading="lazy" allowfullscreen></iframe></div></div></section>' },
        { id: 'hz-courses', label: 'Cursos destacados', category: cat.dyn, attributes: { class: 'gjs-fonts' }, media: '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="8" height="7" rx="1"/><rect x="13" y="4" width="8" height="7" rx="1"/><rect x="3" y="13" width="8" height="7" rx="1"/><rect x="13" y="13" width="8" height="7" rx="1"/></svg>',
          content: { type: 'hz-block', attributes: { 'data-hz-block': 'courses', 'data-count': '3', 'data-category': '', 'data-heading': 'Cursos destacados' } } },
        { id: 'hz-course-info', label: 'Datos y compra del curso', category: cat.dyn, media: '<svg viewBox="0 0 24 24"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6"/></svg>',
          content: { type: 'hz-block', attributes: { 'data-hz-block': 'course-info' } } },
        { id: 'hz-container', label: 'Contenedor', category: cat.b, media: '<svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>', content: '<section class="ht-block"><div class="container"><p>Contenedor vacío: arrastra aquí contenido.</p></div></section>' },
        { id: 'hz-row2', label: '2 columnas', category: cat.b, media: '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="8" height="16" rx="1"/><rect x="13" y="4" width="8" height="16" rx="1"/></svg>', content: '<div class="row g-4"><div class="col-md-6"><p>Columna 1</p></div><div class="col-md-6"><p>Columna 2</p></div></div>' },
        { id: 'hz-row3', label: '3 columnas', category: cat.b, media: '<svg viewBox="0 0 24 24"><rect x="2" y="4" width="6" height="16" rx="1"/><rect x="9" y="4" width="6" height="16" rx="1"/><rect x="16" y="4" width="6" height="16" rx="1"/></svg>', content: '<div class="row g-4"><div class="col-md-4"><p>Columna 1</p></div><div class="col-md-4"><p>Columna 2</p></div><div class="col-md-4"><p>Columna 3</p></div></div>' },
        { id: 'hz-btn', label: 'Botón', category: cat.c, media: '<svg viewBox="0 0 24 24"><rect x="3" y="8" width="18" height="8" rx="4"/></svg>', content: '<a class="am-btn" href="/cursos">Botón</a>' },
        { id: 'hz-callout', label: 'Nota destacada', category: cat.c, media: '<svg viewBox="0 0 24 24"><path d="M4 6v12M8 8h12M8 12h12M8 16h8"/></svg>', content: '<div class="ht-prose"><div class="ht-callout"><strong>Importante.</strong> Escribe aquí el aviso que quieres resaltar.</div></div>' }
    ];

    /* Bloques dinámicos nuevos: se guardan como marcador y el servidor los dibuja (aquí se previsualizan en vivo). */
    BLOCKS.push(
        { id: 'hz-events', label: 'Próximos eventos', category: cat.dyn, media: '<svg viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="15" rx="3"/><path d="M8 3.5v3M16 3.5v3M3.5 10h17"/></svg>',
          content: { type: 'hz-block', attributes: { 'data-hz-block': 'events', 'data-count': '3', 'data-city': '', 'data-heading': 'Próximos eventos' } } },
        { id: 'hz-course-dates', label: 'Fechas del curso', category: cat.dyn, media: '<svg viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="15" rx="3"/><path d="M8 3.5v3M16 3.5v3M3.5 10h17M8 14h3"/></svg>',
          content: { type: 'hz-block', attributes: { 'data-hz-block': 'course-dates', 'data-heading': 'Fechas disponibles' } } }
    );
    var DYNAMIC = { 'hz-courses': 'courses', 'hz-events': 'events', 'hz-course-info': 'course-info', 'hz-course-dates': 'course-dates' };
    var DESC = {
        'hz-hero': 'Portada con título, texto y botón', 'hz-hero-light': 'Portada sencilla en claro', 'hz-text': 'Título y párrafos',
        'hz-img-text': 'Imagen junto a un texto', 'hz-steps': 'Una secuencia de pasos', 'hz-faq': 'Preguntas que se despliegan',
        'hz-cta': 'Invita a dar el siguiente paso', 'hz-stats': 'Números que generan confianza', 'hz-testimonials': 'Opiniones de alumnos',
        'hz-pricing': 'Compara planes y precios', 'hz-video': 'Video incrustado de YouTube',
        'hz-courses': 'Se actualiza solo con tus cursos', 'hz-events': 'Se actualiza solo con tus eventos',
        'hz-course-info': 'Precio, datos y botón de compra', 'hz-course-dates': 'Fechas y lugares del curso'
    };

    /* ---------------------------------------------------------------- arranque */
    var editor = grapesjs.init({
        container: '#gjs', height: '100%', width: 'auto', fromElement: false, storageManager: false,
        i18n: { locale: 'es', detectLocale: false, messages: { es: window.GJS_LOCALE_ES } },
        canvas: { styles: CFG.canvas },
        panels: { defaults: [] },
        assetManager: { upload: URLS.media, uploadName: 'files', headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, multiUpload: true, autoAdd: true },
        deviceManager: { devices: [{ id: 'desktop', name: 'Escritorio' }, { id: 'tablet', name: 'Tablet', width: '820px', widthMedia: '992px' }, { id: 'mobile', name: 'Móvil', width: '390px', widthMedia: '576px' }] },
        selectorManager: { componentFirst: true },
        blockManager: { appendTo: '#hzed-blocks' },
        layerManager: { appendTo: '#hzed-layers' },
        traitManager: { appendTo: '#hzed-traits' },
        styleManager: {
            appendTo: '#hzed-styles',
            sectors: [
                { name: 'Texto', open: true, properties: [{ extend: 'font-size', label: 'Tamaño' }, { extend: 'font-weight', label: 'Grosor' }, { extend: 'color', label: 'Color' }, { extend: 'text-align', label: 'Alineación', options: [{ id: 'left', label: 'Izquierda' }, { id: 'center', label: 'Centro' }, { id: 'right', label: 'Derecha' }, { id: 'justify', label: 'Justificado' }] }, { extend: 'line-height', label: 'Interlineado' }] },
                { name: 'Fondo y bordes', open: false, properties: [{ extend: 'background-color', label: 'Color de fondo' }, { extend: 'border-radius', label: 'Esquinas redondeadas' }, { extend: 'border', label: 'Borde' }, { extend: 'box-shadow', label: 'Sombra' }, { extend: 'opacity', label: 'Opacidad' }] },
                { name: 'Espaciado', open: false, properties: [{ extend: 'margin', label: 'Margen exterior' }, { extend: 'padding', label: 'Relleno interior' }] },
                { name: 'Tamaño', open: false, properties: [{ extend: 'width', label: 'Ancho' }, { extend: 'max-width', label: 'Ancho máximo' }, { extend: 'height', label: 'Alto' }, { extend: 'min-height', label: 'Alto mínimo' }] }
            ]
        },
        plugins: [plugin('grapesjs-parser-postcss'), plugin('gjs-blocks-basic'), plugin('grapesjs-plugin-forms'), plugin('grapesjs-navbar'), plugin('grapesjs-tabs'), plugin('grapesjs-custom-code'), plugin('grapesjs-component-countdown'), plugin('grapesjs-style-bg'), plugin('grapesjs-touch'), plugin('grapesjs-tooltip')].filter(Boolean),
        pluginsOpts: {
            'gjs-blocks-basic': { flexGrid: false, category: cat.c, blocks: ['text', 'link', 'image', 'video', 'map'] },
            'grapesjs-plugin-forms': { category: cat.f },
            'grapesjs-navbar': { category: cat.adv },
            'grapesjs-tabs': { category: cat.adv },
            'grapesjs-custom-code': { category: cat.adv },
            'grapesjs-component-countdown': { category: cat.adv }
        }
    });

    /* ---------------------------------------------------------------- bloque dinámico con vista previa en vivo */
    var catOptions = [{ id: '', name: 'Todas las categorías' }].concat(CFG.categories);
    var cityOptions = [{ id: '', name: 'Todas las ciudades' }].concat((CFG.cities || []).map(function (c) { return { id: c, name: c }; }));
    var KINDS = {
        'courses': { name: 'Cursos destacados', traits: [
            { type: 'select', name: 'data-count', label: 'Cantidad', options: ['3', '6', '9', '12'].map(function (v) { return { id: v, name: v + ' cursos' }; }) },
            { type: 'select', name: 'data-category', label: 'Categoría', options: catOptions },
            { type: 'text', name: 'data-heading', label: 'Título de la sección' }] },
        'events': { name: 'Próximos eventos', traits: [
            { type: 'select', name: 'data-count', label: 'Cantidad', options: ['3', '6', '9', '12'].map(function (v) { return { id: v, name: v + ' eventos' }; }) },
            { type: 'select', name: 'data-city', label: 'Ciudad', options: cityOptions },
            { type: 'text', name: 'data-heading', label: 'Título de la sección' }] },
        'course-info': { name: 'Datos y compra del curso', traits: [] },
        'course-dates': { name: 'Fechas del curso', traits: [{ type: 'text', name: 'data-heading', label: 'Título de la sección' }] }
    };
    var previewCache = {};
    function previewParams(attrs) {
        var p = { type: attrs['data-hz-block'] };
        ['count', 'category', 'city', 'heading'].forEach(function (k) { if (attrs['data-' + k]) { p[k] = attrs['data-' + k]; } });
        return p;
    }
    function fetchPreview(params) {
        var qs = new URLSearchParams(params).toString();
        if (previewCache[qs]) { return Promise.resolve(previewCache[qs]); }
        return fetch(URLS.blockPreview + '?' + qs, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { if (!r.ok) { throw new Error('preview ' + r.status); } return r.json(); })
            .then(function (j) { previewCache[qs] = j; return j; });
    }

    editor.Components.addType('hz-block', {
        isComponent: function (el) { return el.getAttribute && el.getAttribute('data-hz-block') ? { type: 'hz-block' } : false; },
        model: {
            defaults: {
                droppable: false, copyable: true, tagName: 'div', name: 'Bloque dinámico', components: [],
                attributes: { 'data-hz-block': 'courses' }, traits: []
            },
            init: function () { this.configure(); this.on('change:attributes:data-hz-block', this.configure); },
            configure: function () {
                var k = KINDS[this.getAttributes()['data-hz-block']] || KINDS.courses;
                this.set('name', k.name); this.set('traits', k.traits);
            }
        },
        view: {
            init: function () { this.listenTo(this.model, 'change:attributes', this.refresh); },
            onRender: function () { this.el.classList.add('hz-live'); this.el.innerHTML = '<div class="hz-loading">Cargando vista previa…</div>'; this.refresh(); },
            refresh: function () {
                var self = this, el = this.el, model = this.model;
                clearTimeout(this._t);
                this._t = setTimeout(function () {
                    var seq = (self._seq = (self._seq || 0) + 1);
                    el.classList.add('is-loading');
                    fetchPreview(previewParams(model.getAttributes())).then(function (r) {
                        if (seq !== self._seq) { return; }
                        el.innerHTML = r.html; el.classList.remove('is-loading');
                        model.set('previewNote', r.note || '', { silent: true });
                        editor.trigger('hz:preview', model);
                    }).catch(function () {
                        if (seq !== self._seq) { return; }
                        el.classList.remove('is-loading');
                        el.innerHTML = '<div class="hz-empty-preview"><strong>No se pudo cargar la vista previa</strong><span>Guarda y recarga el editor si el problema continúa.</span></div>';
                    });
                }, 180);
            }
        }
    });

    /* ---------------------------------------------------------------- biblioteca de bloques */
    var bm = editor.Blocks;
    var ORDER = [cat.a, cat.dyn, cat.c, cat.b, cat.f, cat.adv];
    var COLLAPSED = [cat.f, cat.adv];
    var RENAME = { text: 'Texto', link: 'Enlace', image: 'Imagen', video: 'Video', map: 'Mapa', form: 'Formulario', input: 'Campo de texto', textarea: 'Área de texto', select: 'Lista desplegable',
        button: 'Botón de envío', label: 'Etiqueta', checkbox: 'Casilla', radio: 'Opción', navbar: 'Barra de navegación', tabs: 'Pestañas', 'custom-code': 'Código HTML', countdown: 'Cuenta regresiva' };

    function chip(label, icon) { return '<div class="hzed-chip"><span class="hzed-chip__ico">' + icon + '</span><span>' + esc(label) + '</span></div>'; }
    function richCard(b) {
        return '<div class="hzed-card"><div class="hzed-thumb" data-hz-thumb="' + b.id + '"><span class="hzed-thumb__ph"></span></div>' +
            '<div class="hzed-card__txt"><strong>' + esc(b.label) + '</strong><small>' + esc(DESC[b.id] || '') + '</small></div></div>';
    }
    BLOCKS.forEach(function (b) {
        var rich = !!DYNAMIC[b.id] || (typeof b.content === 'string' && b.content.indexOf('<section') === 0 && b.id !== 'hz-container');
        bm.add(b.id, { label: rich ? richCard(b) : chip(b.label, b.media), media: '', category: b.category, content: b.content, select: true, attributes: { 'data-search': b.label.toLowerCase(), 'data-rich': rich ? '1' : '' } });
    });
    /* Bloques de los plugins: nombre en español y la misma tarjeta compacta. */
    bm.getAll().forEach(function (b) {
        var id = b.getId();
        if (RENAME[id]) {
            var icon = b.get('media') || '';
            b.set({ label: chip(RENAME[id], icon), media: '', attributes: Object.assign({}, b.get('attributes'), { 'data-search': RENAME[id].toLowerCase() }) });
        }
    });
    /* Tokens ([[company]]…) como bloque de texto rápido. */
    CFG.tokens.forEach(function (t) { bm.add('tok-' + t, { label: chip(t, '{ }'), media: '', category: 'Datos de la academia', content: '<span>' + t + '</span>', attributes: { 'data-search': t } }); });
    ORDER.push('Datos de la academia'); COLLAPSED.push('Datos de la academia');

    var DOC_CSS = CFG.canvas.map(function (h) { return '<link rel="stylesheet" href="' + h + '">'; }).join('') + '<style>html,body{margin:0;overflow:hidden;background:#fff;pointer-events:none}</style>';
    function thumbDoc(html) { return '<!doctype html><html lang="es"><head><meta charset="utf-8">' + DOC_CSS + '</head><body>' + html + '</body></html>'; }
    function mountThumb(box) {
        if (box.dataset.ready) { return; }
        var id = box.dataset.hzThumb, b = BLOCKS.filter(function (x) { return x.id === id; })[0];
        if (!b) { return; }
        box.dataset.ready = '1';
        var show = function (html) {
            var f = document.createElement('iframe');
            f.setAttribute('loading', 'lazy'); f.setAttribute('tabindex', '-1'); f.setAttribute('aria-hidden', 'true'); f.title = '';
            f.srcdoc = thumbDoc(html);
            box.innerHTML = ''; box.appendChild(f);
            f.style.setProperty('--s', Math.max(0.1, (box.clientWidth || 280) / 1000));
        };
        if (DYNAMIC[id]) {
            var attrs = (b.content && b.content.attributes) || {};
            fetchPreview(previewParams(attrs)).then(function (r) { show(r.html); }).catch(function () { box.innerHTML = '<span class="hzed-thumb__ph"></span>'; });
        } else { show(b.content); }
    }
    function hydrate() { $$('#hzed-blocks [data-hz-thumb]').forEach(mountThumb); }

    function renderBlocks(q) {
        q = (q || '').trim().toLowerCase();
        var list = bm.getAll().filter(function (b) {
            var a = b.get('attributes') || {};
            return !q || (a['data-search'] || '').indexOf(q) !== -1 || String(b.getCategoryLabel ? b.getCategoryLabel() : '').toLowerCase().indexOf(q) !== -1;
        });
        list.sort(function (a, b) {
            var ia = ORDER.indexOf(a.getCategoryLabel()), ib = ORDER.indexOf(b.getCategoryLabel());
            return (ia < 0 ? 99 : ia) - (ib < 0 ? 99 : ib);
        });
        bm.render(list);
        $('#hzed-block-none').hidden = list.length > 0;
        $$('#hzed-blocks .gjs-block-category').forEach(function (el) {
            var title = (el.querySelector('.gjs-title') || {}).textContent || '';
            var open = q ? true : COLLAPSED.indexOf(title.trim()) === -1;
            el.classList.toggle('gjs-open', open);
            var c = el.querySelector('.gjs-blocks-c'); if (c) { c.style.display = open ? '' : 'none'; }
        });
        hydrate();
    }
    $('#hzed-block-search').addEventListener('input', debounce(function (e) { renderBlocks(e.target.value); }, 120));

    /* ---------------------------------------------------------------- paneles: pestañas, selección y acciones */
    function tab(group, name) {
        $$('[data-' + group + ']').forEach(function (b) { var on = b.dataset[group] === name; b.classList.toggle('is-on', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
        if (group === 'left') { $('#hzed-pane-blocks').hidden = name !== 'blocks'; $('#hzed-pane-layers').hidden = name !== 'layers'; }
        else { $('#hzed-pane-traits').hidden = name !== 'traits'; $('#hzed-pane-styles').hidden = name !== 'styles'; }
    }
    $$('[data-left]').forEach(function (b) { b.addEventListener('click', function () { tab('left', b.dataset.left); }); });
    $$('[data-right]').forEach(function (b) { b.addEventListener('click', function () { tab('right', b.dataset.right); }); });

    function showSelection(c) {
        var has = !!c;
        $('#hzed-sel').hidden = !has; $('#hzed-right-tabs').hidden = !has; $('#hzed-none').hidden = has;
        if (!has) { $('#hzed-pane-traits').hidden = true; $('#hzed-pane-styles').hidden = true; return; }
        $('#hzed-sel-name').textContent = c.getName();
        var path = [], p = c.parent();
        while (p && path.length < 3 && p.getName) { path.unshift(p.getName()); p = p.parent(); }
        $('#hzed-sel-path').textContent = path.join(' › ');
        var dyn = !!(c.getAttributes && c.getAttributes()['data-hz-block']);
        var cur = ($$('[data-right].is-on')[0] || {}).dataset;
        tab('right', dyn ? 'traits' : (cur && cur.right) || 'traits');
        var note = $('#hzed-note'); var n = c.get('previewNote');
        note.hidden = !(dyn && n); note.textContent = n || '';
    }
    editor.on('component:selected', function () { showSelection(editor.getSelected()); });
    editor.on('component:deselected', function () { showSelection(editor.getSelected()); });
    editor.on('hz:preview', function (m) { if (editor.getSelected() === m) { showSelection(m); } });

    function sel() { return editor.getSelected(); }
    $('#hzed-sel-parent').addEventListener('click', function () { var c = sel(); if (c && c.parent() && c.parent() !== editor.getWrapper()) { editor.select(c.parent()); } });
    $('#hzed-sel-copy').addEventListener('click', function () { if (sel()) { editor.runCommand('tlb-clone'); } });
    $('#hzed-sel-del').addEventListener('click', function () { if (sel()) { editor.runCommand('tlb-delete'); } });
    function move(d) { var c = sel(); if (!c || !c.collection) { return; } var i = c.index(), to = i + d; if (to < 0 || to >= c.collection.length) { return; } c.move(c.parent(), { at: d < 0 ? to : to + 1 }); editor.select(c); }
    $('#hzed-sel-up').addEventListener('click', function () { move(-1); });
    $('#hzed-sel-down').addEventListener('click', function () { move(1); });

    /* ---------------------------------------------------------------- página vacía: plantillas de inicio */
    var STARTERS = {
        info: ['hz-hero-light', 'hz-text', 'hz-cta'],
        landing: ['hz-hero', 'hz-steps', 'hz-courses', 'hz-faq', 'hz-cta'],
        event: ['hz-hero', 'hz-course-dates', 'hz-course-info', 'hz-faq', 'hz-cta']
    };
    function toggleEmpty() { $('#hzed-empty').hidden = !loaded || editor.getWrapper().components().length > 0; }
    $$('[data-starter]').forEach(function (b) {
        b.addEventListener('click', function () {
            editor.addComponents(STARTERS[b.dataset.starter].map(function (id) { return bm.get(id).get('content'); }));
            toggleEmpty();
        });
    });
    editor.on('component:add component:remove', debounce(toggleEmpty, 60));

    /* ---------------------------------------------------------------- carga / guardado */
    var lastSig = '';
    function signature() { return editor.getHtml() + '|' + editor.getCss(); }
    var saved = $('#hzed-saved');
    function status(t, cls) { saved.textContent = t; saved.dataset.state = cls || ''; }

    function payload(note) { return { project: editor.getProjectData(), html: editor.getHtml(), css: editor.getCss(), note: note || null }; }

    function save(note) {
        if (saving || !loaded) { return Promise.resolve(); }
        saving = true; status('Guardando…', 'saving'); $('#hzed-save').disabled = true;
        var sent = signature();
        return api(URLS.save, 'PUT', payload(note)).then(function (r) {
            dirty = false; lastSig = sent; status('Guardado ' + new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }), 'ok');
            applyMeta(r.page);
        }).catch(function (e) { status('Error al guardar: ' + e.message, 'error'); })
          .then(function () { saving = false; $('#hzed-save').disabled = false; });
    }
    function schedule() { dirty = true; status('Cambios sin guardar', 'dirty'); clearTimeout(timer); timer = setTimeout(save, 2500); }

    var outlines = true;
    editor.on('load', function () { renderBlocks(''); showSelection(null); tab('left', 'blocks'); editor.runCommand('sw-visibility'); });
    api(URLS.project, 'GET').then(function (r) {
        if (r.project) { editor.loadProjectData(r.project); }
        else if (r.html) { editor.setComponents(r.html); if (r.css) { editor.setStyle(r.css); } }
        loaded = true; status('Listo', 'ok'); toggleEmpty();
        editor.UndoManager.clear();
        // Opening a layer or selecting something also fires `update`: only a real change of the page content counts.
        lastSig = signature();
        editor.on('update', debounce(function () { if (loaded && signature() !== lastSig) { schedule(); } }, 150));
    }).catch(function (e) { status('No se pudo cargar: ' + e.message, 'error'); });

    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    document.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); save(); } });
    $('#hzed-save').addEventListener('click', function () { clearTimeout(timer); save(); });

    /* ---------------------------------------------------------------- barra superior */
    $$('[data-device]').forEach(function (b) {
        b.addEventListener('click', function () {
            editor.setDevice(b.dataset.device === 'Escritorio' ? 'desktop' : b.dataset.device === 'Tablet' ? 'tablet' : 'mobile');
            $$('[data-device]').forEach(function (x) { x.classList.toggle('is-on', x === b); });
        });
    });
    $('#hzed-undo').addEventListener('click', function () { editor.UndoManager.undo(); });
    $('#hzed-redo').addEventListener('click', function () { editor.UndoManager.redo(); });
    $('#hzed-outline').addEventListener('click', function () {
        outlines = !outlines; editor[outlines ? 'runCommand' : 'stopCommand']('sw-visibility');
        this.setAttribute('aria-pressed', outlines ? 'true' : 'false'); this.classList.toggle('is-on', outlines);
    });
    function preview(on) {
        document.body.classList.toggle('hzed--preview', on); $('#hzed-exit-preview').hidden = !on;
        if (on) { editor.select(); if (outlines) { editor.stopCommand('sw-visibility'); } } else if (outlines) { editor.runCommand('sw-visibility'); }
    }
    $('#hzed-preview').addEventListener('click', function () { preview(true); });
    $('#hzed-exit-preview').addEventListener('click', function () { preview(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && document.body.classList.contains('hzed--preview')) { preview(false); } });

    function applyMeta(p) {
        if (!p) { return; }
        $('#hzed-name').textContent = p.name;
        var s = $('#hzed-status'); s.dataset.status = p.status; s.textContent = p.status === 'published' ? 'Publicada' : 'Borrador';
        var v = $('#hzed-view'); v.href = p.url;
        document.title = 'Editor · ' + p.name;
    }

    // Diálogos
    $$('dialog [data-close]').forEach(function (b) { b.addEventListener('click', function () { b.closest('dialog').close(); }); });
    $$('dialog').forEach(function (d) { d.addEventListener('click', function (e) { if (e.target === d) { d.close(); } }); });

    var dlgSettings = $('#hzed-dlg-settings'), formSettings = $('#hzed-form-settings'), errBox = $('#hzed-settings-error');
    $('#hzed-settings').addEventListener('click', function () {
        var p = CFG.page; ['name', 'title', 'description', 'slug', 'status'].forEach(function (k) { formSettings.elements[k].value = p[k] || ''; });
        errBox.hidden = true; dlgSettings.showModal();
    });
    formSettings.addEventListener('submit', function (e) {
        e.preventDefault(); errBox.hidden = true;
        var data = {}; ['name', 'title', 'description', 'slug', 'status'].forEach(function (k) { data[k] = formSettings.elements[k].value; });
        api(URLS.meta, 'PUT', data).then(function (r) { CFG.page = r.page; applyMeta(r.page); dlgSettings.close(); })
            .catch(function (er) { var m = er.errors ? Object.values(er.errors).flat().join(' ') : er.message; errBox.textContent = m; errBox.hidden = false; });
    });

    var dlgRev = $('#hzed-dlg-revisions'), revList = $('#hzed-revision-list');
    $('#hzed-revisions').addEventListener('click', function () {
        revList.innerHTML = '<li>Cargando…</li>'; dlgRev.showModal();
        api(URLS.revisions, 'GET').then(function (rows) {
            revList.innerHTML = rows.length ? '' : '<li>Aún no hay versiones: se crean al guardar.</li>';
            rows.forEach(function (r) {
                var li = document.createElement('li'); li.innerHTML = '<span><strong>' + esc(r.note || 'Guardado automático') + '</strong><small>' + esc(r.human) + '</small></span>';
                var b = document.createElement('button'); b.type = 'button'; b.className = 'hzed__btn hzed__btn--text'; b.textContent = 'Restaurar';
                b.addEventListener('click', function () {
                    if (!confirm('¿Restaurar esta versión? Los cambios actuales se reemplazan (puedes deshacer).')) { return; }
                    api(URLS.revisions + '/' + r.id, 'GET').then(function (res) { editor.loadProjectData(res.project); dlgRev.close(); schedule(); });
                });
                li.appendChild(b); revList.appendChild(li);
            });
        });
    });

    window.editor = editor;   // útil para pruebas E2E y depuración
})();
