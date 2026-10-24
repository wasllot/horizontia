<div>
@if ($rows->isNotEmpty() && $days > 0)
    <section class="ht-rf" data-testid="refund-box" aria-labelledby="ht-rf-title">
        <style>
            .ht-rf { margin: 0 0 22px; padding: 18px 20px; border-radius: 16px; background: #f6f8fc; border: 1px solid #e1e6f0; }
            .ht-rf h3 { margin: 0 0 4px; font-size: 1.05rem; color: #14213d; }
            .ht-rf > p { margin: 0 0 14px; color: #5b6478; font-size: .92rem; }
            .ht-rf__row { display: flex; gap: 12px 18px; flex-wrap: wrap; align-items: center; justify-content: space-between; padding: 12px 0; border-top: 1px solid #e1e6f0; }
            .ht-rf__row strong { color: #14213d; display: block; }
            .ht-rf__row small { color: #5b6478; }
            .ht-rf__chip { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: .78rem; font-weight: 700; background: #e8ecf6; color: #14213d; }
            .ht-rf__chip--ok { background: #e3f6ea; color: #14703b; }
            .ht-rf__chip--wait { background: #fff3b8; color: #6b5300; }
            .ht-rf__chip--no { background: #fde4e1; color: #912018; }
            .ht-rf__btn { padding: 7px 16px; border: 0; border-radius: 9px; background: #fed304; color: #14213d; font-weight: 700; font-size: .88rem; cursor: pointer; }
            .ht-rf__btn--ghost { background: #fff; border: 1px solid #c5cde0; }
            .ht-rf__form { width: 100%; display: grid; gap: 8px; margin-top: 4px; }
            .ht-rf__form textarea { width: 100%; min-height: 84px; padding: 10px 12px; border: 1.5px solid #c5cde0; border-radius: 10px; font: inherit; }
            .ht-rf__err { color: #b42318; font-size: .85rem; }
        </style>
        <h3 id="ht-rf-title">Garantía de reembolso de {{ $days }} días</h3>
        <p>Si el curso no es lo que esperabas, pídelo aquí: dentro de {{ $days }} días de tu pago y con máximo {{ \App\Services\RefundRequestService::MAX_PROGRESS }} % del contenido visto. <a href="{{ route('legal.refunds') }}">Ver la política</a>.</p>

        @foreach ($rows as $r)
            @php $o = $r->order; $title = $o->items->pluck('title')->map(fn ($t) => html_entity_decode($t))->implode(' · '); @endphp
            <div class="ht-rf__row" wire:key="rf-{{ $o->id }}" data-testid="refund-row-{{ $o->id }}">
                <div>
                    <strong>{{ \Illuminate\Support\Str::limit($title, 70) }}</strong>
                    <small>Pedido #{{ $o->id }} · {!! formatAmount($o->amount) !!} · {{ $o->created_at->translatedFormat('j M Y') }}</small>
                </div>
                <div>
                    @if ($r->request?->status === \App\Models\RefundRequest::PENDING)
                        <span class="ht-rf__chip ht-rf__chip--wait">En revisión</span>
                    @elseif ($o->status === 'refunded' || $r->request?->status === \App\Models\RefundRequest::APPROVED)
                        <span class="ht-rf__chip ht-rf__chip--ok">Reembolsado</span>
                    @elseif ($r->check['ok'])
                        <button type="button" class="ht-rf__btn" wire:click="open({{ $o->id }})" data-testid="refund-open-{{ $o->id }}">Solicitar reembolso</button>
                        <small style="display:block">hasta el {{ $r->check['deadline']->translatedFormat('j M Y') }}</small>
                    @elseif ($r->request?->status === \App\Models\RefundRequest::REJECTED)
                        <span class="ht-rf__chip ht-rf__chip--no">No aprobado</span>
                        @if ($r->request->review_note)<small style="display:block">{{ $r->request->review_note }}</small>@endif
                    @else
                        <small>{{ $r->check['reason'] }}</small>
                    @endif
                </div>

                @if ($openOrder === $o->id)
                    <form class="ht-rf__form" wire:submit="submit" data-testid="refund-form">
                        <label for="rf-reason-{{ $o->id }}"><strong>¿Por qué lo pides?</strong></label>
                        <textarea id="rf-reason-{{ $o->id }}" wire:model="reason" maxlength="500" placeholder="Cuéntanos qué esperabas y qué no encontraste."></textarea>
                        <span class="ht-rf__err" role="alert">{{ $errors->first('reason') }}</span>
                        <div>
                            <button type="submit" class="ht-rf__btn" wire:loading.attr="disabled" data-testid="refund-send">Enviar solicitud</button>
                            <button type="button" class="ht-rf__btn ht-rf__btn--ghost" wire:click="cancel">Cancelar</button>
                        </div>
                    </form>
                @endif
            </div>
        @endforeach
    </section>
@endif
</div>
