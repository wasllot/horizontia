<main class="tb-main am-courses-system hz-bn">
    <header class="hz-seo__head">
        <div>
            <h2>Cupones <span class="hz-count">{{ $coupons->count() }}</span></h2>
            <p>Códigos de descuento para el pago: porcentaje o monto fijo, con vigencia y límites de uso. Solo descuentan cursos sueltos (los paquetes ya traen su propio descuento).</p>
        </div>
        <button type="button" class="hz-btn hz-btn--sm" wire:click="create" data-testid="coupon-new"><i class="icon-plus" aria-hidden="true"></i> Nuevo cupón</button>
    </header>

    @if ($formOpen)
        <form class="hz-en__form hz-bn__form" wire:key="coupon-form" wire:submit="save" data-testid="coupon-form">
            <strong>{{ $editingId ? 'Editar cupón' : 'Nuevo cupón' }}</strong>

            <label for="cp-code">Código</label>
            <div class="hz-bn__row">
                <input id="cp-code" class="form-control" wire:model="code" maxlength="40" placeholder="Ej. BIENVENIDA20" autocomplete="off" style="text-transform: uppercase">
                <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="suggestCode">Generar uno</button>
            </div>
            <small class="hz-en__err" aria-live="polite">{{ $errors->first('code') }}</small>

            <label for="cp-desc">Nota interna (opcional)</label>
            <input id="cp-desc" class="form-control" wire:model="description" maxlength="160" placeholder="Ej. Campaña de octubre en Instagram">

            <div class="hz-bn__row">
                <div>
                    <label for="cp-type">Tipo de descuento</label>
                    <select id="cp-type" class="form-control" wire:model.live="type">
                        <option value="percent">Porcentaje (%)</option>
                        <option value="fixed">Monto fijo (MXN)</option>
                    </select>
                </div>
                <div>
                    <label for="cp-value">{{ $type === 'percent' ? 'Porcentaje' : 'Monto' }}</label>
                    <input id="cp-value" type="number" step="0.01" min="0" class="form-control" wire:model="value" placeholder="{{ $type === 'percent' ? '20' : '300' }}">
                    <small class="hz-en__err" aria-live="polite">{{ $errors->first('value') }}</small>
                </div>
                <div>
                    <label for="cp-min">Compra mínima (opcional)</label>
                    <input id="cp-min" type="number" step="0.01" min="0" class="form-control" wire:model="minAmount" placeholder="Sin mínimo">
                    <small class="hz-en__err" aria-live="polite">{{ $errors->first('minAmount') }}</small>
                </div>
            </div>

            <div class="hz-bn__row">
                <div>
                    <label for="cp-max">Usos totales (opcional)</label>
                    <input id="cp-max" type="number" min="1" class="form-control" wire:model="maxUses" placeholder="Sin límite">
                    <small class="hz-en__err" aria-live="polite">{{ $errors->first('maxUses') }}</small>
                </div>
                <div>
                    <label for="cp-per">Usos por persona</label>
                    <input id="cp-per" type="number" min="0" class="form-control" wire:model="perUserLimit">
                    <small class="hz-bn__save">0 = sin límite por persona.</small>
                </div>
                <div>
                    <label for="cp-start">Válido desde</label>
                    <input id="cp-start" type="date" class="form-control" wire:model="startsAt">
                </div>
                <div>
                    <label for="cp-end">Válido hasta</label>
                    <input id="cp-end" type="date" class="form-control" wire:model="endsAt">
                    <small class="hz-en__err" aria-live="polite">{{ $errors->first('endsAt') }}</small>
                </div>
            </div>

            <label class="hz-ct__check"><input type="checkbox" wire:model="firstPurchaseOnly"> <span>Solo para la primera compra de la persona</span></label>
            <label class="hz-ct__check"><input type="checkbox" wire:model="active"> <span>Activo</span></label>

            <label for="cp-scope">Aplica a</label>
            <select id="cp-scope" class="form-control" wire:model.live="scope">
                <option value="all">Todos los cursos</option>
                <option value="courses">Solo cursos específicos</option>
            </select>
            @if ($scope === 'courses')
                <div class="hz-bn__picker">
                    @foreach ($courses as $c)
                        <label class="hz-bn__opt" wire:key="cpc-{{ $c->id }}"><input type="checkbox" value="{{ $c->id }}" wire:model="courseIds"> <span>{{ html_entity_decode($c->title) }}</span></label>
                    @endforeach
                </div>
            @endif
            <small class="hz-en__err" aria-live="polite">{{ $errors->first('courseIds') }}</small>

            <div class="hz-en__formacts">
                <button type="submit" class="hz-btn hz-btn--sm" wire:loading.attr="disabled">Guardar cupón</button>
                <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="closeForm">Cancelar</button>
            </div>
        </form>
    @endif

    <div class="am-disputelist_wrap" wire:key="coupon-list">
        <div class="am-disputelist">
            @if ($coupons->isNotEmpty())
                <table class="tb-table" data-testid="coupons-table">
                    <thead><tr><th class="hz-col-main">Código</th><th>Descuento</th><th>Usos</th><th>Vigencia</th><th>Estado</th><th><span class="hz-sr">Acciones</span></th></tr></thead>
                    <tbody>
                        @foreach ($coupons as $c)
                            @php
                                $expired = $c->ends_at && $c->ends_at->isPast();
                                $soldOut = $c->max_uses !== null && $c->used_count >= $c->max_uses;
                            @endphp
                            <tr wire:key="coupon-{{ $c->id }}">
                                <td data-label="Código"><strong>{{ $c->code }}</strong>@if ($c->description)<br><small>{{ $c->description }}</small>@endif</td>
                                <td data-label="Descuento">{!! $c->label() !!}@if ($c->min_amount)<br><small>Desde {!! formatAmount($c->min_amount, true) !!}</small>@endif<br><small>{{ empty($c->course_ids) ? 'Todos los cursos' : count($c->course_ids) . ' curso(s)' }}@if ($c->first_purchase_only) · 1.ª compra @endif</small></td>
                                <td data-label="Usos">{{ $c->used_count }}{{ $c->max_uses ? ' / ' . $c->max_uses : '' }}</td>
                                <td data-label="Vigencia">{{ $c->starts_at ? $c->starts_at->format('d/m/Y') : '—' }} → {{ $c->ends_at ? $c->ends_at->format('d/m/Y') : '—' }}</td>
                                <td data-label="Estado">
                                    @if (!$c->active)<x-panel.badge tone="neutral">Pausado</x-panel.badge>
                                    @elseif ($expired)<x-panel.badge tone="warning" :dot="false">Vencido</x-panel.badge>
                                    @elseif ($soldOut)<x-panel.badge tone="warning" :dot="false">Agotado</x-panel.badge>
                                    @else<x-panel.badge tone="success">Activo</x-panel.badge>@endif
                                </td>
                                <td data-label="Acciones">
                                    <div class="hz-acts">
                                        <x-panel.action icon="icon-edit" label="Editar" wire:click="edit({{ $c->id }})" />
                                        <x-panel.action icon="icon-power" :label="$c->active ? 'Pausar' : 'Activar'" wire:click="toggle({{ $c->id }})" />
                                        <x-panel.action icon="icon-trash-2" label="Eliminar" tone="danger" wire:click="delete({{ $c->id }})" wire:confirm="¿Eliminar este cupón? Los pedidos que ya lo usaron conservan su descuento." />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="hz-empty"><h3>Aún no hay cupones</h3><p>Crea el primero, por ejemplo «BIENVENIDA20» con 20 % para la primera compra.</p></div>
            @endif
        </div>
    </div>
</main>
