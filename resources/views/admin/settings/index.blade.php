@extends(config('optionbuilder.layout'))

@section(config('optionbuilder.section'))
@php
    $icons = [
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 4 5.7 4 9s-1.4 6.4-4 9c-2.6-2.6-4-5.7-4-9s1.4-6.4 4-9z"/>',
        'sliders' => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
        'file' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
        'share' => '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="m8.2 10.8 7.6-3.6M8.2 13.2l7.6 3.6"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'plug' => '<path d="M9 3v4M15 3v4M6 7h12v4a6 6 0 0 1-12 0zM12 17v4"/>',
        'spark' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8zM18 16l.8 2.2L21 19l-2.2.8L18 22l-.8-2.2L15 19l2.2-.8z"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M16 7l3 3M14 9l2 2"/>',
        'code' => '<path d="m8 8-5 4 5 4M16 8l5 4-5 4M14 5l-4 14"/>',
        'tool' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/>',
    ];
@endphp
<div class="hz-set" data-testid="settings">
    <header class="hz-seo__head">
        <div>
            <h2>Ajustes</h2>
            <p>Configura tu academia. Cada página se guarda por separado con su botón «Guardar cambios».</p>
        </div>
        @if (Route::has('admin.config-transfer'))
            <a class="hz-btn hz-btn--secondary hz-btn--sm" href="{{ route('admin.config-transfer') }}">Exportar / importar</a>
        @endif
    </header>

    <div class="hz-set__layout">
        <aside class="hz-set__nav" aria-label="Secciones de ajustes">
            <ul class="op-feildlisting nav nav-tabs" role="tablist">
                @foreach ($pages as $p)
                    <li>
                        <a class="{{ $loop->first ? 'active' : '' }}" href="#{{ $p['id'] }}" id="{{ $p['id'] }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $p['id'] }}" role="tab" aria-controls="{{ $p['id'] }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-testid="nav-{{ $p['id'] }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$p['icon']] ?? '' !!}</svg>
                            <span>{{ $p['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </aside>

        <div class="hz-set__main tab-content">
            @foreach ($pages as $p)
                <section class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $p['id'] }}" role="tabpanel" aria-labelledby="{{ $p['id'] }}-tab" data-testid="page-{{ $p['id'] }}">
                    <form id="{{ $p['id'] }}-form" class="op-themeform op-fieldform hz-set__form" data-page="{{ $p['id'] }}" onsubmit="return false">
                        <div class="hz-set__head">
                            <div>
                                <h3>{{ $p['label'] }}</h3>
                                <p>{{ $p['desc'] }}</p>
                            </div>
                            <div class="op-btnholder hz-set__actions">
                                <button type="button" class="hz-set__reset reset-section-settings" data-form="{{ $p['id'] }}-form" data-page="{{ $p['id'] }}">Restablecer</button>
                                <button type="button" class="op-btn update-section-settings hz-btn" data-form="{{ $p['id'] }}-form" data-page="{{ $p['id'] }}" data-testid="save-{{ $p['id'] }}">
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span> Guardar cambios
                                </button>
                            </div>
                        </div>

                        @if (!empty($p['tabs']))
                            @php $tabs = collect($p['groups'])->groupBy('tab'); @endphp
                            <ul class="op-feildlisting op-feildlistingvtwo nav nav-tabs hz-set__subtabs" role="tablist">
                                @foreach ($tabs as $tabId => $tabGroups)
                                    <li><a class="{{ $loop->first ? 'active' : '' }}" href="#" id="{{ $tabId }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tabId }}" role="tab" aria-controls="{{ $tabId }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $tabGroups->first()['tab_title'] }}</a></li>
                                @endforeach
                            </ul>
                            <div class="tab-content">
                                @foreach ($tabs as $tabId => $tabGroups)
                                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $tabId }}" role="tabpanel" aria-labelledby="{{ $tabId }}-tab">
                                        @foreach ($tabGroups as $g) @include('admin.settings.group', ['g' => $g]) @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @else
                            @foreach ($p['groups'] as $g) @include('admin.settings.group', ['g' => $g]) @endforeach
                        @endif

                        <div class="hz-set__foot">
                            <button type="button" class="op-btn update-section-settings hz-btn" data-form="{{ $p['id'] }}-form" data-page="{{ $p['id'] }}">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span> Guardar cambios
                            </button>
                        </div>
                    </form>
                </section>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push(config('optionbuilder.style_var'))
    <link rel="stylesheet" href="{{ asset('vendor/optionbuilder/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/optionbuilder/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/optionbuilder/css/jquery.mCustomScrollbar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/optionbuilder/css/feather-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/optionbuilder/css/jquery-confirm.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/optionbuilder/css/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/optionbuilder/css/jquery.colorpicker.bygiro.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/optionbuilder/css/summernote-lite.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/optionbuilder/css/nouislider.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/optionbuilder/css/main.css') }}">
@endpush
@push(config('optionbuilder.script_var'))
    <script src="{{ asset('vendor/optionbuilder/js/jquery.min.js') }}"></script>
    <script defer src="{{ asset('vendor/optionbuilder/js/bootstrap.min.js') }}"></script>
    <script defer src="{{ asset('vendor/optionbuilder/js/select2.min.js') }}"></script>
    <script defer src="{{ asset('vendor/optionbuilder/js/jquery.mCustomScrollbar.concat.min.js') }}"></script>
    <script defer src="{{ asset('vendor/optionbuilder/js/jquery-confirm.min.js') }}"></script>
    <script defer src="{{ asset('vendor/optionbuilder/js/popper-core.js') }}"></script>
    <script defer src="{{ asset('vendor/optionbuilder/js/tippy.js') }}"></script>
    <script defer src="{{ asset('vendor/optionbuilder/js/flatpickr.js') }}"></script>
    <script defer src="{{ asset('vendor/optionbuilder/js/jquery.colorpicker.bygiro.js') }}"></script>
    <script defer src="{{ asset('vendor/optionbuilder/js/summernote-lite.min.js') }}"></script>
    <script defer src="{{ asset('vendor/optionbuilder/js/nouislider.min.js') }}"></script>
    <script defer src="{{ asset('vendor/optionbuilder/js/optionbuilder.js') }}"></script>
    <script>
        (function () {
            const urls = {
                save: @json(route('optionbuilder.update-section-settings')),
                reset: @json(route('optionbuilder.reset-section-settings')),
                smtp: @json(Route::has('admin.update-smtp-settings') ? route('admin.update-smtp-settings') : null),
                google: @json(Route::has('admin.update-social-login-settings') ? route('admin.update-social-login-settings') : null),
            };
            const csrf = @json(csrf_token());
            const post = (url, data) => jQuery.ajax({ url, method: 'post', data, headers: { 'X-CSRF-TOKEN': csrf } });
            const notify = (r, fallback) => {
                const s = (r && r.success) || {};
                showAlert({ type: s.type || 'success', title: s.title || '', message: s.message || fallback, autoclose: 2500 });
            };

            // Deep links: /admin/ajustes#correo abre esa página; el hash se actualiza al cambiar de página.
            jQuery(function () {
                const open = (hash) => { const a = document.querySelector('.hz-set__nav a[href="' + hash + '"]'); if (a && window.bootstrap) bootstrap.Tab.getOrCreateInstance(a).show(); };
                if (location.hash) setTimeout(() => open(location.hash), 250);
                jQuery(document).on('shown.bs.tab', '.hz-set__nav a', function () { history.replaceState(null, '', jQuery(this).attr('href')); });
            });

            // Guardar: solo los ajustes de ESA página. Los interruptores apagados envían 0.
            jQuery(document).on('click', '.hz-set .update-section-settings', function () {
                const btn = jQuery(this); const form = jQuery('#' + btn.data('form')); const page = btn.data('page');
                let data = form.serialize();
                form.find('input[type=checkbox]').each(function () { if (!this.checked) data += '&' + encodeURIComponent(this.name) + '=0'; });
                btn.find('.spinner-border').removeClass('d-none').end().prop('disabled', true);
                post(urls.save, { page, data })
                    .done((r) => {
                        notify(r, 'Ajustes guardados.');
                        // Estas dos páginas también deben quedar escritas en el .env (conexión de correo / acceso con Google).
                        if (page === 'correo' && urls.smtp && form.find('[name=smtp_host]').val() && jQuery('#correo-smtp').hasClass('active')) {
                            post(urls.smtp).done((x) => showAlert({ type: x.success ? 'success' : 'error', title: x.success ? 'Conexión de correo' : 'No se pudo conectar', message: x.message, autoclose: 4500 }));
                        }
                        if (page === 'acceso' && urls.google) {
                            post(urls.google).done((x) => showAlert({ type: x.success ? 'success' : 'error', title: x.success ? 'Acceso con Google' : 'No se pudo guardar', message: x.message, autoclose: 4500 }));
                        }
                    })
                    .fail(() => showAlert({ type: 'error', title: 'No se guardó', message: 'Revisa tu conexión e inténtalo de nuevo.', autoclose: 4000 }))
                    .always(() => btn.find('.spinner-border').addClass('d-none').end().prop('disabled', false));
            });

            jQuery(document).on('click', '.hz-set .reset-section-settings', function () {
                const page = jQuery(this).data('page');
                jQuery.confirm({
                    title: 'Restablecer esta página', content: 'Los campos de esta página volverán a sus valores originales. ¿Continuar?', type: 'red', closeIcon: true, typeAnimated: false,
                    buttons: {
                        yes: { text: 'Sí, restablecer', btnClass: 'btn-danger', action: () => post(urls.reset, { page }).done((r) => { notify(r, 'Valores restablecidos.'); setTimeout(() => location.reload(), 900); }) },
                        no: { text: 'Cancelar' },
                    },
                });
            });
        })();
    </script>
@endpush
