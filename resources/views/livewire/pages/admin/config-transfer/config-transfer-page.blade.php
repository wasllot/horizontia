<div class="hz-ct">
    <header class="hz-seo__head">
        <div>
            <h2>Exportar e importar configuración</h2>
            <p>Lleva páginas, menús, SEO, ajustes y plantillas de un sitio a otro (por ejemplo, de tu equipo al servidor). <strong>Nunca</strong> incluye cursos, blogs ni usuarios, y las claves secretas solo viajan si tú lo pides.</p>
        </div>
    </header>

    <div class="hz-ct__tabs" role="tablist">
        <button type="button" role="tab" class="hz-ct__tab {{ $tab === 'export' ? 'is-on' : '' }}" aria-selected="{{ $tab === 'export' ? 'true' : 'false' }}" wire:click="$set('tab', 'export')" data-testid="tab-export">Exportar</button>
        <button type="button" role="tab" class="hz-ct__tab {{ $tab === 'import' ? 'is-on' : '' }}" aria-selected="{{ $tab === 'import' ? 'true' : 'false' }}" wire:click="$set('tab', 'import')" data-testid="tab-import">Importar</button>
    </div>

    @if ($tab === 'export')
        <section class="hz-ct__card" wire:key="ct-export" data-testid="export-card">
            <h3>¿Qué quieres exportar?</h3>
            <div class="hz-ct__list">
                @foreach ($sections as $key => $s)
                    @continue($key === 'credentials' && !$withCredentials)
                    <label class="hz-ct__opt" wire:key="exp-{{ $key }}">
                        <input type="checkbox" value="{{ $key }}" wire:model.live="exportSections">
                        <span>
                            <strong>{{ $s['label'] }}</strong>
                            @if ($s['sensitive'])<em class="hz-ct__tag">sensible</em>@endif
                            <small>{{ $s['desc'] }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
            <small class="hz-en__err" aria-live="polite">{{ $errors->first('exportSections') }}</small>

            @if (in_array('pages', $exportSections, true))
                <div class="hz-ct__pages" wire:key="exp-pages">
                    <label class="hz-ct__check"><input type="checkbox" wire:model.live="allPages"> Todas las páginas ({{ $localPages->count() }})</label>
                    @unless ($allPages)
                        <div class="hz-ct__plist">
                            @foreach ($localPages as $p)
                                <label class="hz-ct__check" wire:key="ep-{{ $p->slug }}"><input type="checkbox" value="{{ $p->slug }}" wire:model="exportPages"> {{ $p->name }} <small>/{{ ltrim($p->slug, '/') }}</small></label>
                            @endforeach
                        </div>
                    @endunless
                </div>
            @endif

            <label class="hz-ct__check hz-ct__cred"><input type="checkbox" wire:model.live="withCredentials" data-testid="with-credentials"> <span><strong>Incluir credenciales</strong> (claves secretas de pagos, API y almacenamiento). El archivo quedará con datos sensibles: guárdalo con cuidado y bórralo después de usarlo.</span></label>

            <div class="hz-en__formacts">
                <button type="button" class="hz-btn" wire:click="download" wire:loading.attr="disabled" data-testid="download-btn"><i class="icon-download" aria-hidden="true"></i> Descargar archivo</button>
            </div>
        </section>
    @else
        <section class="hz-ct__card" wire:key="ct-import" data-testid="import-card">
            @if ($error)<p class="hz-ct__alert hz-ct__alert--bad" role="alert" data-testid="import-error">{{ $error }}</p>@endif

            @if (!$token)
                <h3>Sube el archivo exportado</h3>
                <p class="hz-ct__hint">Elige el archivo <code>.json</code> que descargaste desde «Exportar» en el otro sitio. Primero verás qué contiene y podrás hacer una vista previa sin guardar nada.</p>
                <input type="file" accept=".json,application/json" wire:model="upload" data-testid="upload-input" aria-label="Archivo de exportación">
                <div wire:loading wire:target="upload" class="hz-ct__hint">Leyendo el archivo…</div>
            @elseif ($report && !$report['dry'])
                <h3>Importación terminada</h3>
                @include('livewire.pages.admin.config-transfer.report', ['report' => $report, 'sections' => $sections])
                <div class="hz-en__formacts"><button type="button" class="hz-btn hz-btn--secondary" wire:click="resetImport">Importar otro archivo</button></div>
            @else
                <h3>Esto contiene el archivo</h3>
                <p class="hz-ct__hint">Del sitio «{{ $meta['site'] ?? '—' }}» ({{ $meta['source'] ?? '' }}) · exportado el {{ !empty($meta['exported_at']) ? dateEs($meta['exported_at'], 'j M Y, H:i') : '—' }}@if (!empty($meta['includes_credentials'])) · <strong>incluye credenciales</strong>@endif</p>

                <div class="hz-ct__list">
                    @foreach ($summary as $key => $s)
                        <label class="hz-ct__opt" wire:key="imp-{{ $key }}">
                            <input type="checkbox" value="{{ $key }}" wire:model.live="importSections">
                            <span><strong>{{ $s['label'] }}</strong> <em class="hz-ct__count">{{ $s['count'] }}</em>@if ($s['sensitive'])<em class="hz-ct__tag">sensible</em>@endif<small>{{ $sections[$key]['desc'] }}</small></span>
                        </label>
                    @endforeach
                </div>

                @if (in_array('pages', $importSections, true) && $pageList)
                    <div class="hz-ct__pages" wire:key="imp-pages">
                        <label class="hz-ct__check"><input type="checkbox" wire:model.live="allImportPages"> Todas las páginas del archivo ({{ count($pageList) }})</label>
                        @unless ($allImportPages)
                            <div class="hz-ct__plist">
                                @foreach ($pageList as $p)
                                    <label class="hz-ct__check" wire:key="ip-{{ $p['slug'] }}"><input type="checkbox" value="{{ $p['slug'] }}" wire:model="importPages"> {{ $p['name'] }} <small>/{{ ltrim($p['slug'], '/') }}{{ $p['exists'] ? ' · ya existe aquí' : '' }}</small></label>
                                @endforeach
                            </div>
                        @endunless
                    </div>
                @endif

                <fieldset class="hz-ct__mode">
                    <legend>Si algo ya existe en este sitio</legend>
                    <label class="hz-ct__check"><input type="radio" value="skip" wire:model.live="mode"> <span><strong>Solo añadir lo que falta</strong> (no toca lo que ya tienes)</span></label>
                    <label class="hz-ct__check"><input type="radio" value="overwrite" wire:model.live="mode"> <span><strong>Reemplazar lo existente</strong> con lo del archivo (páginas con el mismo enlace, menús, ajustes, plantillas…)</span></label>
                </fieldset>
                <label class="hz-ct__check"><input type="checkbox" wire:model.live="backup"> Hacer un respaldo de la base de datos antes de importar <small>(recomendado)</small></label>

                @if ($report && $report['dry'])
                    <div class="hz-ct__preview" wire:key="ct-preview" data-testid="preview">
                        <h4>Vista previa (no se guardó nada)</h4>
                        @include('livewire.pages.admin.config-transfer.report', ['report' => $report, 'sections' => $sections])
                    </div>
                @endif

                <div class="hz-en__formacts">
                    <button type="button" class="hz-btn hz-btn--secondary" wire:click="preview" wire:loading.attr="disabled" data-testid="preview-btn">Vista previa</button>
                    <button type="button" class="hz-btn" wire:click="import" wire:loading.attr="disabled"
                        wire:confirm="¿Importar ahora? {{ $mode === 'overwrite' ? 'Se reemplazará lo que ya existe en este sitio.' : 'Solo se añadirá lo que falta.' }}" data-testid="import-btn">Importar</button>
                    <button type="button" class="hz-btn hz-btn--ghost" wire:click="resetImport">Cancelar</button>
                </div>
            @endif
        </section>
    @endif
</div>
