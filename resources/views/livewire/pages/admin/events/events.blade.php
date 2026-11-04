<main class="tb-main am-courses-system hz-bn">
    <header class="hz-seo__head">
        <div>
            <h2>Eventos <span class="hz-count">{{ $events->count() }}</span></h2>
            <p>Eventos de entrada única con página propia (Astrofest y futuras ediciones): precio, cupo, descuentos, textos, políticas y compras. Las compras son de invitado, sin cuenta, y se cobran con Mercado Pago.</p>
        </div>
        <button type="button" class="hz-btn hz-btn--sm" wire:click="create" data-testid="event-new"><i class="icon-plus" aria-hidden="true"></i> Nuevo evento</button>
    </header>

    @if ($formOpen)
        <form class="hz-en__form hz-bn__form" wire:key="event-form" wire:submit="save" data-testid="event-form" style="max-width:none">
            <strong>{{ $editingId ? 'Editar evento' : 'Nuevo evento' }}</strong>

            @if ($ready)
                <div class="hz-ct__note" role="status" style="background:#f4f6fb;border-radius:12px;padding:12px 16px">
                    <b>Para vender entradas hace falta:</b>
                    <ul style="margin:6px 0 0;padding-left:0;list-style:none">
                        @foreach ($ready as [$label, $ok])<li>{{ $ok ? '✅' : '⬜' }} {{ $label }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <h3>Datos básicos</h3>
            <div class="hz-bn__row">
                <div><label for="ev-name">Nombre</label><input id="ev-name" class="form-control" wire:model="f.name" maxlength="160"><small class="hz-en__err" aria-live="polite">{{ $errors->first('f.name') }}</small></div>
                <div><label for="ev-slug">Dirección (/eventos/…)</label><input id="ev-slug" class="form-control" wire:model="f.slug" maxlength="120" placeholder="astrofest-geminidas-2026"><small class="hz-en__err" aria-live="polite">{{ $errors->first('f.slug') }}</small></div>
                <div><label for="ev-status">Estado</label><select id="ev-status" class="form-control" wire:model="f.status"><option value="draft">Borrador (solo admins)</option><option value="published">Publicado</option><option value="finished">Edición finalizada</option></select></div>
            </div>
            <div class="hz-bn__row">
                <div><label for="ev-start">Inicio</label><input id="ev-start" type="datetime-local" class="form-control" wire:model="f.start_at"></div>
                <div><label for="ev-end">Fin</label><input id="ev-end" type="datetime-local" class="form-control" wire:model="f.end_at"><small class="hz-en__err" aria-live="polite">{{ $errors->first('f.end_at') }}</small></div>
                <div><label for="ev-cd">El contador se muestra desde</label><input id="ev-cd" type="datetime-local" class="form-control" wire:model="f.countdown_from"><small class="hz-bn__save">Vacío = sin contador.</small></div>
            </div>

            <h3>Precio, cupo y ventas</h3>
            <div class="hz-bn__row">
                <div><label for="ev-price">Precio por persona (IVA incluido)</label><input id="ev-price" type="number" step="0.01" min="0" class="form-control" wire:model="f.price"><small class="hz-en__err" aria-live="polite">{{ $errors->first('f.price') }}</small></div>
                <div><label for="ev-iva">IVA contenido (%)</label><input id="ev-iva" type="number" step="0.01" min="0" class="form-control" wire:model="f.iva_rate"></div>
                <div><label for="ev-cap">Cupo máximo</label><input id="ev-cap" type="number" min="1" class="form-control" wire:model="f.capacity" placeholder="Sin límite"><small class="hz-bn__save">Al llegar al cupo se muestra «Agotado».</small><small class="hz-en__err" aria-live="polite">{{ $errors->first('f.capacity') }}</small></div>
                <div><label for="ev-max">Máximo por compra</label><input id="ev-max" type="number" min="1" max="50" class="form-control" wire:model="f.max_per_order"></div>
            </div>
            <div class="hz-bn__row">
                <div><label for="ev-open">Abren las ventas</label><input id="ev-open" type="datetime-local" class="form-control" wire:model="f.sales_open_at"><small class="hz-bn__save">Vacío = ya.</small></div>
                <div><label for="ev-close">Cierran las ventas</label><input id="ev-close" type="datetime-local" class="form-control" wire:model="f.sales_close_at"><small class="hz-bn__save">Vacío = hasta el evento.</small></div>
                <div><label for="ev-hold">Reserva de lugar al pagar (min)</label><input id="ev-hold" type="number" min="5" class="form-control" wire:model="f.hold_minutes"></div>
                <div><label for="ev-offline">Reserva en efectivo/SPEI (horas)</label><input id="ev-offline" type="number" min="1" class="form-control" wire:model="f.offline_hold_hours"></div>
            </div>
            <label class="hz-ct__check"><input type="checkbox" wire:model="f.sales_enabled"> <span><b>Activar las ventas</b> (interruptor maestro: sin políticas ni precio la página sigue mostrando «Próximamente»)</span></label>
            <label class="hz-ct__check"><input type="checkbox" wire:model="f.noindex"> <span><b>No indexar</b> (pide a Google que no muestre el evento ni sus políticas, y los saca del mapa del sitio). Déjalo activo hasta que el cliente apruebe; desactívalo para publicar y solicita la indexación en Search Console.</span></label>
            <label class="hz-ct__check"><input type="checkbox" wire:model="f.redirect_events_index"> <span>Que <code>/eventos</code> lleve directo a este evento (redirección 302, mientras sea el único)</span></label>

            <h3>Descuentos <small>(se suman si aplican los dos; apagados por defecto)</small></h3>
            @foreach (['early_bird' => 'Preventa', 'group' => 'Por grupo'] as $k => $title)
                <fieldset class="hz-bn__row" style="align-items:end;border:1px solid #e6eaf2;border-radius:12px;padding:12px">
                    <legend style="font-size:.95rem;padding:0 6px"><label class="hz-ct__check" style="margin:0"><input type="checkbox" wire:model="d.{{ $k }}.enabled"> <span><b>{{ $title }}</b> activo</span></label></legend>
                    <div><label for="d-{{ $k }}-label">Etiqueta</label><input id="d-{{ $k }}-label" class="form-control" wire:model="d.{{ $k }}.label" maxlength="60"></div>
                    <div><label for="d-{{ $k }}-pct">Porcentaje</label><input id="d-{{ $k }}-pct" type="number" step="0.01" min="0" max="100" class="form-control" wire:model="d.{{ $k }}.percent"></div>
                    @if ($k === 'group')<div><label for="d-group-min">Desde (entradas)</label><input id="d-group-min" type="number" min="2" class="form-control" wire:model="d.group.min_qty"></div>@endif
                    <div><label for="d-{{ $k }}-from">Válido desde</label><input id="d-{{ $k }}-from" type="date" class="form-control" wire:model="d.{{ $k }}.starts_at"></div>
                    <div><label for="d-{{ $k }}-to">Válido hasta</label><input id="d-{{ $k }}-to" type="date" class="form-control" wire:model="d.{{ $k }}.ends_at"></div>
                </fieldset>
            @endforeach

            <h3>Lugar y contacto <small>(lo que quede vacío no se muestra)</small></h3>
            <div class="hz-bn__row">
                <div><label for="ev-vn">Lugar</label><input id="ev-vn" class="form-control" wire:model="f.venue_name"></div>
                <div><label for="ev-vc">Ciudad / estado</label><input id="ev-vc" class="form-control" wire:model="f.venue_city"></div>
            </div>
            <label for="ev-va">Dirección completa <small>(se publica también en los datos estructurados)</small></label>
            <input id="ev-va" class="form-control" wire:model="f.venue_address">
            <div class="hz-bn__row">
                <div><label for="ev-map">Enlace de Google Maps</label><input id="ev-map" type="url" class="form-control" wire:model="f.venue_map_url" placeholder="https://maps.app.goo.gl/…"><small class="hz-en__err" aria-live="polite">{{ $errors->first('f.venue_map_url') }}</small></div>
            </div>
            <div class="hz-bn__row">
                <div><label for="ev-ce">Correo del evento</label><input id="ev-ce" type="email" class="form-control" wire:model="f.contact_email"><small class="hz-en__err" aria-live="polite">{{ $errors->first('f.contact_email') }}</small></div>
                <div><label for="ev-cw">WhatsApp del evento</label><input id="ev-cw" class="form-control" wire:model="f.contact_whatsapp"></div>
            </div>
            <label class="hz-ct__check"><input type="checkbox" wire:model="hide.rooms"> <span>Ocultar el bloque «¿Prefieres habitación?», la pregunta de habitaciones y el aviso del correo</span></label>
            <label class="hz-ct__check"><input type="checkbox" wire:model="hide.map"> <span>Ocultar el botón de mapa</span></label>
            <label class="hz-ct__check"><input type="checkbox" wire:model="hide.partners"> <span>Ocultar los logotipos de FAMA y del Club Campestre (por ejemplo, mientras no haya permiso de uso)</span></label>

            <h3>Textos <small>(vacío = texto por defecto de la guía)</small></h3>
            <div class="hz-bn__row">
                <div><label for="t-seo-t">Título en Google (máx. 60)</label><input id="t-seo-t" class="form-control" wire:model="t.seo.title" maxlength="70"></div>
                <div><label for="t-seo-h">Encabezado principal (H1)</label><input id="t-seo-h" class="form-control" wire:model="t.seo.h1"></div>
            </div>
            <label for="t-seo-d">Descripción en Google (máx. 155)</label>
            <input id="t-seo-d" class="form-control" wire:model="t.seo.description" maxlength="170">
            <div class="hz-bn__row">
                <div><label for="t-ht">Titular de la portada</label><input id="t-ht" class="form-control" wire:model="t.hero.title"></div>
                <div><label for="t-hc">Texto del botón principal</label><input id="t-hc" class="form-control" wire:model="t.hero.cta"></div>
            </div>
            <label for="t-hs">Subtítulo de la portada</label>
            <textarea id="t-hs" class="form-control" rows="2" wire:model="t.hero.subtitle"></textarea>
            <label for="t-rm">Texto de la sección de habitación</label>
            <textarea id="t-rm" class="form-control" rows="2" wire:model="t.rooms.text"></textarea>
            <label for="t-rc">Código de habitación con el hotel <small>(sale en el correo de confirmación; vacío = el correo avisa que se compartirá en breve)</small></label>
            <input id="t-rc" class="form-control" wire:model="t.rooms.code" maxlength="60" placeholder="Ej. ASTROFEST26">
            <label for="t-tr">Cómo llegar en transporte público <small>(solo se muestra si lo escribes y está verificado)</small></label>
            <textarea id="t-tr" class="form-control" rows="2" wire:model="t.transport"></textarea>
            <div class="hz-bn__row">
                <div><label for="t-ct">Titular de cierre</label><input id="t-ct" class="form-control" wire:model="t.closing.title"></div>
                <div><label for="t-cx">Texto de cierre</label><input id="t-cx" class="form-control" wire:model="t.closing.text"></div>
                <div><label for="ev-minors">Menores que no pagan (años)</label><input id="ev-minors" type="number" min="1" max="18" class="form-control" wire:model="f.minors_age"></div>
            </div>

            <h3>Políticas del evento <small>(obligatorias para vender)</small></h3>
            <label for="ev-pol">Clima, cancelaciones, reglamento y responsabilidad. Admite HTML básico (p, ul, li, strong, h2, h3, a).</label>
            <textarea id="ev-pol" class="form-control" rows="10" wire:model="f.policies_html" placeholder="<h2>Clima</h2><p>…</p>"></textarea>

            <h3>Imágenes y logotipos <small>(PNG, JPG, WebP o SVG · hasta 4 MB)</small></h3>
            <div class="hz-bn__row" style="flex-wrap:wrap">
                @foreach ($assetLabels as $key => $label)
                    <div style="min-width:240px">
                        <label for="up-{{ $key }}">{{ $label }}</label>
                        @if (!empty($assets[$key]))
                            <div style="display:flex;gap:10px;align-items:center;margin-bottom:6px"><img src="{{ asset($assets[$key]) }}" alt="" style="height:44px;max-width:120px;object-fit:contain;background:#070B1F;border-radius:8px;padding:4px"><button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="removeAsset('{{ $key }}')">Quitar</button></div>
                        @endif
                        <input id="up-{{ $key }}" type="file" class="form-control" wire:model="uploads.{{ $key }}" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                        <small class="hz-en__err" aria-live="polite">{{ $errors->first('uploads.' . $key) }}</small>
                    </div>
                @endforeach
            </div>

            <div class="hz-en__formacts">
                <button type="submit" class="hz-btn hz-btn--sm" wire:loading.attr="disabled" wire:target="save,uploads">Guardar evento</button>
                <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="closeForm">Cerrar</button>
                @if ($current)<a class="hz-btn hz-btn--ghost hz-btn--sm" href="{{ $current->url() }}" target="_blank" rel="noopener">Ver la página</a>@endif
            </div>
        </form>

        @if ($current)
            <section aria-labelledby="ev-orders" style="margin:26px 0">
                <header class="hz-seo__head">
                    <div><h2 id="ev-orders">Compras</h2>
                        <p>{{ $stats['paid'] }} entradas pagadas · ${{ number_format($stats['revenue'], 2) }} MXN · {{ $stats['pending'] }} reservadas por pagar{{ $current->capacity ? ' · cupo ' . $current->capacity : '' }}</p></div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                        <select class="form-control" wire:model.live="orderStatus" aria-label="Estado" style="width:auto">
                            <option value="paid">Pagadas</option><option value="pending">Por pagar</option><option value="expired">Vencidas</option><option value="refunded">Reembolsadas</option><option value="all">Todas</option>
                        </select>
                        <a class="hz-btn hz-btn--ghost hz-btn--sm" href="{{ route('admin.events.export', $current) }}?estado={{ $orderStatus }}" data-testid="event-csv">Descargar CSV</a>
                    </div>
                </header>
                <div class="am-disputelist_wrap"><div class="am-disputelist">
                    @if ($orders->isNotEmpty())
                        <table class="tb-table">
                            <thead><tr><th class="hz-col-main">Compra</th><th>Asistentes</th><th>Total</th><th>Estado</th><th><span class="hz-sr">Acciones</span></th></tr></thead>
                            <tbody>
                                @foreach ($orders as $o)
                                    <tr wire:key="eo-{{ $o->id }}">
                                        <td data-label="Compra"><strong>#{{ $o->id }} · {{ $o->name }}</strong><br><small>{{ $o->email }} · {{ $o->phone }}<br>{{ $o->created_at->timezone(\App\Models\Event::timezone())->format('d/m/Y H:i') }}</small></td>
                                        <td data-label="Asistentes">{{ $o->quantity }} · <small>{{ $o->tickets->pluck('attendee_name')->implode(', ') }}</small></td>
                                        <td data-label="Total">${{ number_format($o->total, 2) }}@if ($o->discount_label)<br><small>{{ $o->discount_label }}</small>@endif</td>
                                        <td data-label="Estado">
                                            @if ($o->status === 'paid')<x-panel.badge tone="success">Pagada</x-panel.badge>
                                            @elseif ($o->status === 'pending')<x-panel.badge tone="warning" :dot="false">Por pagar</x-panel.badge>
                                            @elseif ($o->status === 'refunded')<x-panel.badge tone="neutral">Reembolsada</x-panel.badge>
                                            @else<x-panel.badge tone="neutral">Vencida</x-panel.badge>@endif
                                        </td>
                                        <td data-label="Acciones"><div class="hz-acts">@if ($o->status === 'paid')<x-panel.action icon="icon-mail" label="Reenviar confirmación" wire:click="resend({{ $o->id }})" />@endif</div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="hz-empty"><h3>Sin compras en este estado</h3><p>Cuando alguien compre, aparecerá aquí.</p></div>
                    @endif
                </div></div>
            </section>
        @endif
    @endif

    <div class="am-disputelist_wrap" wire:key="event-list">
        <div class="am-disputelist">
            @if ($events->isNotEmpty())
                <table class="tb-table" data-testid="events-table">
                    <thead><tr><th class="hz-col-main">Evento</th><th>Fecha</th><th>Entradas</th><th>Venta</th><th><span class="hz-sr">Acciones</span></th></tr></thead>
                    <tbody>
                        @foreach ($events as $e)
                            @php $state = $sales->state($e); @endphp
                            <tr wire:key="event-{{ $e->id }}">
                                <td data-label="Evento"><strong>{{ $e->name }}</strong><br><small>/eventos/{{ $e->slug }} · {{ ['draft' => 'Borrador', 'published' => 'Publicado', 'finished' => 'Finalizado'][$e->status] ?? $e->status }}</small></td>
                                <td data-label="Fecha">{{ $e->start_at ? $e->local($e->start_at)->format('d/m/Y H:i') : '—' }}</td>
                                <td data-label="Entradas">{{ $sales->taken($e) }}{{ $e->capacity ? ' / ' . $e->capacity : '' }}</td>
                                <td data-label="Venta">
                                    @if ($state === 'open')<x-panel.badge tone="success">Abierta</x-panel.badge>
                                    @elseif ($state === 'sold_out')<x-panel.badge tone="warning" :dot="false">Agotado</x-panel.badge>
                                    @elseif ($state === 'finished')<x-panel.badge tone="neutral">Finalizado</x-panel.badge>
                                    @else<x-panel.badge tone="neutral">{{ ['soon' => 'Sin abrir', 'not_yet' => 'Abre pronto', 'ended' => 'Cerrada'][$state] ?? $state }}</x-panel.badge>@endif
                                </td>
                                <td data-label="Acciones"><div class="hz-acts">
                                    <x-panel.action icon="icon-edit" label="Editar" wire:click="edit({{ $e->id }})" />
                                    <x-panel.action icon="icon-trash-2" label="Eliminar" tone="danger" wire:click="delete({{ $e->id }})" wire:confirm="¿Eliminar este evento? Solo se puede si no tiene compras." />
                                </div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="hz-empty"><h3>Aún no hay eventos</h3><p>Crea el primero o ejecuta <code>php artisan events:astrofest</code>.</p></div>
            @endif
        </div>
    </div>
</main>
