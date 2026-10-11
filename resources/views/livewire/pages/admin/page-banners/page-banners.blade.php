<main class="tb-main am-courses-system hz-bn hz-pb">
    <header class="hz-seo__head">
        <div>
            <h2>Banners de páginas</h2>
            <p>Cambia el título, el subtítulo y la imagen de la portada de tus páginas estáticas (Nosotros, Términos, Contacto…). Si no personalizas una página, se ve como siempre.</p>
        </div>
        <a class="hz-btn hz-btn--secondary hz-btn--sm" href="{{ route('admin.pages') }}">← Páginas</a>
    </header>

    @if ($editingKey && $preview)
        <form class="hz-en__form hz-bn__form" wire:submit="save" wire:key="banner-form" data-testid="banner-form">
            <strong>Banner de «{{ $pages[$editingKey]['label'] }}»</strong>

            <label class="hz-ct__check"><input type="checkbox" wire:model.live="enabled"> <span>Usar un banner personalizado en esta página</span></label>

            <div class="hz-pb__preview" aria-label="Vista previa">
                <x-page-banner :title="$preview['title']" :subtitle="$preview['subtitle']" :image="$preview['image']" :overlay="$preview['overlay']" :align="$preview['align']" />
            </div>

            <fieldset @disabled(!$enabled) class="hz-pb__fields">
                <label for="pb-title">Título</label>
                <input id="pb-title" class="form-control" wire:model.live.debounce.300ms="title" maxlength="140" placeholder="Ej. Conoce nuestra academia">
                <small class="hz-en__err" aria-live="polite">{{ $errors->first('title') }}</small>

                <label for="pb-sub">Subtítulo (opcional)</label>
                <input id="pb-sub" class="form-control" wire:model.live.debounce.300ms="subtitle" maxlength="280" placeholder="Una frase que explique la página">
                <small class="hz-en__err" aria-live="polite">{{ $errors->first('subtitle') }}</small>

                <label for="pb-img">Imagen de fondo (opcional)</label>
                <input id="pb-img" type="file" class="form-control" wire:model="upload" accept="image/jpeg,image/png,image/webp,image/avif" data-testid="banner-upload">
                <small class="hz-bn__save">Recomendado: 1600 × 600 px o más, JPG/WEBP, máximo 4 MB. Elige una foto con zonas tranquilas: el texto va encima.</small>
                <small class="hz-en__err" aria-live="polite">{{ $errors->first('upload') }}</small>
                <div wire:loading wire:target="upload" class="hz-bn__save">Subiendo…</div>
                @if ($preview['image'])
                    <button type="button" class="hz-rp__link" wire:click="removeCurrentImage">Quitar la imagen</button>
                @endif

                @if ($preview['image'])
                    <div class="hz-bn__row">
                        <div>
                            <label for="pb-ov">Oscurecer la imagen: {{ $overlay }} %</label>
                            <input id="pb-ov" type="range" min="0" max="85" step="5" wire:model.live="overlay" class="form-range">
                        </div>
                        <div>
                            <label for="pb-al">Alineación del texto</label>
                            <select id="pb-al" class="form-control" wire:model.live="align"><option value="left">A la izquierda</option><option value="center">Centrado</option></select>
                        </div>
                    </div>
                @endif
            </fieldset>

            <div class="hz-en__formacts">
                <button type="submit" class="hz-btn hz-btn--sm" wire:loading.attr="disabled" wire:target="save,upload" data-testid="banner-save">Guardar banner</button>
                <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="cancel">Cancelar</button>
            </div>
        </form>
    @endif

    <div class="am-disputelist_wrap" wire:key="banner-list">
        <div class="am-disputelist">
            <table class="tb-table" data-testid="banners-table">
                <thead><tr><th class="hz-col-main">Página</th><th>Banner</th><th><span class="hz-sr">Acciones</span></th></tr></thead>
                <tbody>
                    @foreach ($pages as $key => $p)
                        @php $b = $saved[$key] ?? null; @endphp
                        <tr wire:key="pb-{{ $key }}">
                            <td data-label="Página"><strong>{{ $p['label'] }}</strong><br><small><a href="{{ $p['url'] }}" target="_blank" rel="noopener">{{ parse_url($p['url'], PHP_URL_PATH) }}</a></small></td>
                            <td data-label="Banner">
                                @if ($b && $b->enabled)
                                    <x-panel.badge tone="success">Personalizado</x-panel.badge>
                                    <br><small>{{ $b->title }}@if ($b->image) · con imagen @endif</small>
                                @else
                                    <x-panel.badge tone="neutral">Original</x-panel.badge>
                                @endif
                            </td>
                            <td data-label="Acciones">
                                <div class="hz-acts">
                                    <x-panel.action icon="icon-edit" label="Editar banner" wire:click="edit('{{ $key }}')" />
                                    @if ($b)<x-panel.action icon="icon-rotate-ccw" label="Restaurar el original" wire:click="reset_('{{ $key }}')" wire:confirm="¿Volver al banner original de esta página?" />@endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</main>
