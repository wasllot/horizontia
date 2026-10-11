<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Editor · {{ $page->name }}</title>
    <x-favicon />
    <link rel="stylesheet" href="{{ asset('vendor/grapesjs/grapes.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/page-editor.css') }}?v={{ @filemtime(public_path('css/page-editor.css')) }}">
</head>
<body class="hzed">
<div class="hzed__bar" role="toolbar" aria-label="Editor de página">
    <a class="hzed__back" href="{{ route('admin.pages') }}" title="Volver a Páginas" data-no-spa>← Páginas</a>
    <div class="hzed__title">
        <strong id="hzed-name">{{ $page->name }}</strong>
        <span class="hzed__status" id="hzed-status" data-status="{{ $page->status }}">{{ $page->status === 'published' ? 'Publicada' : 'Borrador' }}</span>
        <span class="hzed__saved" id="hzed-saved" role="status" aria-live="polite">Cargando…</span>
    </div>
    <div class="hzed__group" role="group" aria-label="Dispositivo">
        <button type="button" class="hzed__btn is-on" data-device="Escritorio" title="Ver como en escritorio" aria-label="Escritorio"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg></button>
        <button type="button" class="hzed__btn" data-device="Tablet" title="Ver como en tablet" aria-label="Tablet"><svg viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M11 18h2"/></svg></button>
        <button type="button" class="hzed__btn" data-device="Móvil" title="Ver como en móvil" aria-label="Móvil"><svg viewBox="0 0 24 24"><rect x="8" y="3" width="8" height="18" rx="2"/><path d="M11 18h2"/></svg></button>
    </div>
    <div class="hzed__group" role="group" aria-label="Historial de cambios">
        <button type="button" class="hzed__btn" id="hzed-undo" title="Deshacer (Ctrl+Z)" aria-label="Deshacer"><svg viewBox="0 0 24 24"><path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/></svg></button>
        <button type="button" class="hzed__btn" id="hzed-redo" title="Rehacer (Ctrl+Shift+Z)" aria-label="Rehacer"><svg viewBox="0 0 24 24"><path d="m15 14 5-5-5-5"/><path d="M20 9H10a6 6 0 0 0 0 12h3"/></svg></button>
        <button type="button" class="hzed__btn hzed__btn--text" id="hzed-outline" title="Mostrar u ocultar los bordes de cada bloque" aria-pressed="true">Bordes</button>
        <button type="button" class="hzed__btn hzed__btn--text" id="hzed-revisions">Versiones</button>
    </div>
    <div class="hzed__group">
        <button type="button" class="hzed__btn hzed__btn--text" id="hzed-settings">Ajustes</button>
        <button type="button" class="hzed__btn hzed__btn--text" id="hzed-preview">Vista previa</button>
        <a class="hzed__btn hzed__btn--text" id="hzed-view" href="{{ $page->url() }}" target="_blank" rel="noopener">Ver página ↗</a>
        <button type="button" class="hzed__btn hzed__btn--primary" id="hzed-save">Guardar</button>
    </div>
</div>

<div class="hzed__main">
    <aside class="hzed__left" aria-label="Biblioteca de bloques">
        <div class="hzed__tabs" role="tablist">
            <button type="button" role="tab" class="is-on" data-left="blocks" aria-selected="true">Bloques</button>
            <button type="button" role="tab" data-left="layers" aria-selected="false">Estructura</button>
        </div>
        <div class="hzed__pane" id="hzed-pane-blocks">
            <div class="hzed__search"><input type="search" id="hzed-block-search" placeholder="Buscar un bloque…" aria-label="Buscar un bloque" autocomplete="off"></div>
            <div id="hzed-blocks"></div>
            <p class="hzed__nores" id="hzed-block-none" hidden>No hay bloques con ese nombre.</p>
        </div>
        <div class="hzed__pane" id="hzed-pane-layers" hidden>
            <p class="hzed__hint">Aquí ves el orden de los bloques de la página. Arrastra para reordenar.</p>
            <div id="hzed-layers"></div>
        </div>
    </aside>

    <section class="hzed__stage">
        <div id="gjs" aria-label="Lienzo del editor"></div>
        <div class="hzed__empty" id="hzed-empty" hidden>
            <div class="hzed__empty-card">
                <h2>Empieza tu página</h2>
                <p>Arrastra un bloque desde la izquierda, o parte de una plantilla y cámbiala a tu gusto.</p>
                <div class="hzed__starters">
                    <button type="button" data-starter="info"><strong>Página informativa</strong><span>Encabezado, texto y llamado a la acción</span></button>
                    <button type="button" data-starter="landing"><strong>Página de curso</strong><span>Encabezado, pasos, cursos, preguntas y llamado a la acción</span></button>
                    <button type="button" data-starter="event"><strong>Página de evento</strong><span>Encabezado, fechas del curso, datos de compra y preguntas</span></button>
                </div>
            </div>
        </div>
        <p class="hzed__tip">Arrastra bloques al lienzo · Doble clic en un texto para escribir · Ctrl+Z deshace</p>
        <button type="button" class="hzed__exit" id="hzed-exit-preview" hidden>Salir de la vista previa</button>
    </section>

    <aside class="hzed__right" aria-label="Propiedades del elemento">
        <div class="hzed__sel" id="hzed-sel" hidden>
            <div class="hzed__sel-text"><strong id="hzed-sel-name">Elemento</strong><small id="hzed-sel-path"></small></div>
            <div class="hzed__sel-actions">
                <button type="button" class="hzed__ico" id="hzed-sel-parent" title="Seleccionar el contenedor" aria-label="Seleccionar el contenedor"><svg viewBox="0 0 24 24"><path d="M12 19V5M6 11l6-6 6 6"/></svg></button>
                <button type="button" class="hzed__ico" id="hzed-sel-up" title="Subir" aria-label="Subir"><svg viewBox="0 0 24 24"><path d="m6 15 6-6 6 6"/></svg></button>
                <button type="button" class="hzed__ico" id="hzed-sel-down" title="Bajar" aria-label="Bajar"><svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></button>
                <button type="button" class="hzed__ico" id="hzed-sel-copy" title="Duplicar" aria-label="Duplicar"><svg viewBox="0 0 24 24"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/></svg></button>
                <button type="button" class="hzed__ico hzed__ico--danger" id="hzed-sel-del" title="Eliminar" aria-label="Eliminar"><svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg></button>
            </div>
        </div>
        <div class="hzed__tabs" role="tablist" id="hzed-right-tabs" hidden>
            <button type="button" role="tab" class="is-on" data-right="traits" aria-selected="true">Contenido</button>
            <button type="button" role="tab" data-right="styles" aria-selected="false">Estilo</button>
        </div>
        <div class="hzed__pane" id="hzed-pane-traits">
            <p class="hzed__note" id="hzed-note" hidden></p>
            <div id="hzed-traits"></div>
        </div>
        <div class="hzed__pane" id="hzed-pane-styles" hidden><div id="hzed-styles"></div></div>
        <div class="hzed__none" id="hzed-none">
            <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 3 14 8-6 2-2 6z"/></svg></span>
            <strong>Selecciona algo en la página</strong>
            <p>Haz clic en un texto, imagen o bloque para cambiar su contenido y su estilo aquí.</p>
        </div>
    </aside>
</div>

<dialog class="hzed__dialog" id="hzed-dlg-settings">
    <form method="dialog" id="hzed-form-settings">
        <h2>Ajustes de la página</h2>
        <label>Nombre interno<input name="name" required maxlength="120"></label>
        <label>Título (SEO y pestaña)<input name="title" maxlength="120"></label>
        <label>Descripción<textarea name="description" maxlength="320" rows="3"></textarea></label>
        <label>URL<span class="hzed__slug"><em>{{ url('/') }}/</em><input name="slug" required maxlength="120" pattern="[a-z0-9][a-z0-9\/\-]*"></span></label>
        <label>Estado<select name="status"><option value="draft">Borrador (no visible)</option><option value="published">Publicada</option></select></label>
        <p class="hzed__hint">Para título y descripción avanzados, imagen social e indexación usa <a href="{{ route('admin.seo') }}" target="_blank">Admin → SEO</a>.</p>
        <p class="hzed__error" id="hzed-settings-error" role="alert" hidden></p>
        <div class="hzed__actions"><button type="button" class="hzed__btn hzed__btn--text" data-close>Cancelar</button><button type="submit" class="hzed__btn hzed__btn--primary">Guardar ajustes</button></div>
    </form>
</dialog>

<dialog class="hzed__dialog" id="hzed-dlg-revisions">
    <h2>Versiones guardadas</h2>
    <ul class="hzed__list" id="hzed-revision-list"><li>Cargando…</li></ul>
    <div class="hzed__actions"><button type="button" class="hzed__btn hzed__btn--text" data-close>Cerrar</button></div>
</dialog>

<script>window.HZ_EDITOR = @json($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);</script>
<script src="{{ asset('vendor/grapesjs/grapes.min.js') }}"></script>
<script src="{{ asset('vendor/grapesjs/locale/es.js') }}"></script>
@foreach (['parser-postcss', 'blocks-basic', 'forms', 'navbar', 'tabs', 'custom-code', 'countdown', 'style-bg', 'touch', 'tooltip', 'preset-webpage'] as $plugin)
    <script src="{{ asset("vendor/grapesjs/plugins/{$plugin}.min.js") }}"></script>
@endforeach
<script src="{{ asset('js/page-editor.js') }}?v={{ @filemtime(public_path('js/page-editor.js')) }}"></script>
</body>
</html>
