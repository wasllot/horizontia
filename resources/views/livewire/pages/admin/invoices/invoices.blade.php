<main class="tb-main am-dispute-system am-invoice-system">
    <style>
        .am-invoice-system .inv-item { max-width: 250px; }
        .am-invoice-system .inv-item strong { display: block; font-weight: 600; color: #14213d; line-height: 1.3; overflow-wrap: anywhere; }
        .am-invoice-system .inv-item small, .am-invoice-system .inv-muted { color: #7a8296; }
        .am-invoice-system .inv-free { display: inline-block; padding: 2px 10px; border-radius: 99px; background: #e3f6ea; color: #14703b; font-style: normal; font-weight: 700; font-size: .85rem; }
        .am-invoice-system .inv-meta { display: flex; flex-wrap: wrap; gap: 8px 24px; padding: 0 0 16px; margin-bottom: 16px; border-bottom: 1px solid #e6eaf2; color: #5b6478; font-size: .9rem; }
        .am-invoice-system .inv-meta b { color: #14213d; margin-right: 4px; }
    </style>
    <div class ="row">
        <div class="col-lg-12 col-md-12">
            <div class="tb-dhb-mainheading">
                <h4> {{ __('general.invoices') .' ('. $orders->total() .')'}}</h4>
                <div class="tb-sortby">
                    <form class="tb-themeform tb-displistform">
                        <fieldset>
                            <div class="tb-themeform__wrap">
                                <div class="tb-actionselect" wire:ignore>
                                    <div class="tb-select">
                                        <select data-componentid="@this" class="am-select2 form-control" data-searchable="false" data-live='true' id="status" data-wiremodel="status" >
                                            <option value="" {{ $status == '' ? 'selected' : '' }} >{{ __('invoices.all_invoices')  }}</option>
                                            <option value="pending" {{ $status == 'pending' ? 'selected' : '' }} >{{ __('booking.pending')  }}</option>
                                            <option value="complete" {{ $status == 'complete' ? 'selected' : '' }} >{{ __('booking.complete')  }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="tb-actionselect" wire:ignore>
                                    <div class="tb-select">
                                        <select data-componentid="@this" class="am-select2 form-control" data-searchable="false" data-live='true' id="sort_by" data-wiremodel="sortby" >
                                            <option value="asc" {{ $sortby == 'asc' ? 'selected' : '' }} >{{ __('general.asc')  }}</option>
                                            <option value="desc" {{ $sortby == 'desc' ? 'selected' : '' }} >{{ __('general.desc')  }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group tb-inputicon tb-inputheight">
                                    <i class="icon-search"></i>
                                    <input type="text" class="form-control" wire:model.live.debounce.500ms="search"  autocomplete="off" placeholder="{{ __('general.search') }}">
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
            <div class="am-disputelist_wrap">
                <div class="am-disputelist am-custom-scrollbar-y">
                    @if( !$orders->isEmpty() )
                        <table class="tb-table @if(setting('_general.table_responsive') == 'yes') tb-table-responsive @endif">
                            <thead>
                                <th>{{ __('booking.id') }}</th>
                                <th>{{ __('booking.transaction_id') }}</th>
                                <th>{{ __('booking.items') }}</th>
                                <th>{{ __('booking.student_name') }}</th>
                                <th>{{ __('booking.payment_method') }}</th>
                                <th>{{ __('booking.amount') }}</th>
                                <th>{{ __('booking.admin_commission') }}</th>
                                <th>{{ __('booking.status') }}</th>
                                <th>{{ __('general.actions') }}</th>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    @php
                                       $options = $order?->options ?? [];
                                       $subject = $options['subject'] ?? '';
                                       $image   = $options['image'] ?? '';
                                    @endphp
                                    <tr>
                                        <td data-label="{{ __('booking.id') }}"><span>{{ $order?->id }}</span></td>
                                        <td data-label="{{ __('booking.transaction_id') }}"><span>{{ !empty($order?->transaction_id) ? $order->transaction_id : '—' }}</span></td>
                                        <td data-label="{{ __('booking.items' )}}">
                                            @php
                                                $first = $order->items->first();
                                                $more = $order->items->count() - 1;
                                                $kinds = ['Course' => 'Curso', 'SlotBooking' => 'Sesión', 'Subscription' => 'Suscripción'];
                                                $kind = $first ? ($kinds[class_basename($first->orderable_type)] ?? 'Artículo') : '';
                                            @endphp
                                            @if ($first)
                                                <div class="inv-item" title="{{ $order->items->pluck('title')->map(fn ($t) => html_entity_decode($t))->implode(' · ') }}">
                                                    <strong>{{ \Illuminate\Support\Str::limit(html_entity_decode($first->title), 60) }}</strong>
                                                    <small>{{ $kind }}@if ($more > 0) · +{{ $more }} {{ $more === 1 ? 'más' : 'más' }}@endif</small>
                                                </div>
                                            @else
                                                <span class="inv-muted">—</span>
                                            @endif
                                        </td>
                                        <td data-label="{{ __('booking.student_name' )}}">
                                            <div class="tb-varification_userinfo">
                                                <strong class="tb-adminhead__img">
                                                    @if (!empty($order?->userProfile?->image) && mediaExists($order?->userProfile?->image))
                                                    <img src="{{ resizedImage($order?->userProfile?->image,34,34) }}" alt="{{$order?->userProfile?->image}}" />
                                                    @else
                                                        <img src="{{ setting('_general.default_avatar_for_user') ? url(mediaUrl(setting('_general.default_avatar_for_user')[0]['path'])) : resizedImage('placeholder.png', 34, 34) }}" alt="{{ $order?->orderable?->student?->image }}" />
                                                    @endif
                                                </strong>
                                                <span>{{ $order?->userProfile?->full_name }}</span>
                                            </div>
                                        </td>
                                        <td data-label="{{ __('booking.payment_method' )}}">
                                            @if ((float) $order?->amount <= 0)
                                                <span class="inv-muted">Sin pago</span>
                                            @else
                                                <span>{{ __("settings." .$order?->payment_method. "_title")  }}</span>
                                            @endif
                                        </td>
                                        <td data-label="{{ __('booking.amount') }}">
                                            @if ((float) $order?->amount <= 0)
                                                <em class="inv-free">Gratis</em>
                                            @else
                                                <span>{!! formatAmount($order?->amount) !!}</span>
                                                @if ((float) $order?->discount_amount > 0)
                                                    <small class="inv-muted d-block">Cupón {{ $order->coupon_code }} −{!! formatAmount($order->discount_amount) !!}</small>
                                                @endif
                                            @endif
                                        </td>
                                        <td data-label="{{ __('booking.admin_commission') }}">
                                            @if ((float) $order?->amount <= 0)
                                                <span class="inv-muted">—</span>
                                            @else
                                                <span>{!! empty($order?->subscription_id) ? formatAmount($order?->admin_commission) : formatAmount(0) !!}</span>
                                            @endif
                                        </td>
                                        <td data-label="{{ __('booking.status' )}}">
                                            <div class="am-status-tag">
                                                <em class="tk-project-tag {{ $order?->status == 'complete' ? 'tk-hourly-tag' : 'tk-fixed-tag' }}">{{ statusLabel($order?->status) }}</em>
                                            </div>
                                        </td>
                                        <td data-label="{{ __('general.actions') }}">
                                            <button class="am-btn" wire:click.prevent="viewInvoice({{ $order?->id }})">Ver detalle</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                            {{ $orders->links('pagination.custom') }}
                    @else
                        <x-no-record :image="asset('images/empty.png')" :title="__('general.no_record_title')" />
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- Add Invoice Preview Modal -->
    
    <div class="modal fade tb-invoice-preview-modal" data-bs-backdrop="static" id="invoicePreviewModal" tabindex="-1" aria-labelledby="fileUploadModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('invoices.invoice') }}</h5>
                    <span class="am-closepopup" data-bs-dismiss="modal" aria-label="Close">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <g opacity="0.7">
                            <path d="M4 12L12 4M4 4L12 12" stroke="#585858" stroke-linecap="round" stroke-linejoin="round"/>
                            </g>
                        </svg>
                    </span>
                    
                </div>
                @if(!empty($invoice))
                    <div class="modal-body">
                        <div class="tb-invoice-container">
                            <!-- Invoice Header -->
                            <div class="inv-meta">
                                <span><b>Folio</b> #{{ $invoice->id }}</span>
                                <span><b>Fecha</b> {{ dateEs($invoice->created_at) }}</span>
                                <span><b>Pago</b> {{ (float) $invoice->amount > 0 ? __("settings." . $invoice->payment_method . "_title") : 'Sin pago (curso gratis)' }}</span>
                                <span><b>Estado</b> {{ statusLabel($invoice->status) }}</span>
                            </div>
                            <div class="tb-invoice-head">
                                <h2>{{ __('invoices.invoice_bill_to') }}:</h2>
                                <div class="tb-customer-details">
                                    <div class="tb-detail-row">
                                        <span class="tb-label">{{ __('invoices.invoice_student_name') }}:</span>
                                        <span class="tb-value">{{ $invoice?->first_name }} {{ $invoice?->last_name }}</span>
                                    </div>
                                    <div class="tb-detail-row">
                                        <span class="tb-label">{{ __('invoices.invoice_address') }}:</span>
                                        <span class="tb-value">
                                            {{ collect([$invoice?->city, $invoice?->state, !empty($invoice?->country) ? $invoice?->countryDetails?->name : null])->filter()->implode(', ') ?: '—' }}
                                        </span>
                                    </div>
                                    <div class="tb-detail-row">
                                        <span class="tb-label">{{ __('invoices.invoice_email') }}:</span>
                                        <span class="tb-value">{{ $invoice?->email }}</span>
                                    </div>
                                </div>
                            </div>
                            @if(!empty($invoice?->items))
                            <!-- Invoice Items -->
                                <div class="tb-invoice-items">
                                    <div class="tb-items-header">
                                        <div class="tb-col tb-col-id">{{ __('invoices.invoice_id') }}</div>
                                        <div class="tb-col tb-col-title">{{ __('invoices.invoice_title') }}</div>
                                        <div class="tb-col tb-col-qty">{{ __('invoices.invoice_qty') }}</div>
                                        <div class="tb-col tb-col-discount">{{ __('invoices.invoice_discount_code') }}</div>
                                        <div class="tb-col tb-col-discount">{{ __('invoices.invoice_discount_amount') }}</div>
                                        <div class="tb-col tb-col-price">{{ __('invoices.invoice_price') }}</div>
                                    </div>
                                
                                    <div class="tb-items-body">
                                        @foreach($invoice?->items as $item)
                                            <div class="tb-item-row">
                                                <div class="tb-col tb-col-id" data-label="ID">{{ $item->id }}</div>
                                                <div class="tb-col tb-col-title" data-label="Título">{{ $item->title }}</div>
                                                <div class="tb-col tb-col-qty" data-label="Qty.">{{ $item->quantity }}</div>
                                                <div class="tb-col tb-col-discount" data-label="Código">
                                                    @if(!empty($item?->options['discount_code']))
                                                        {{ 
                                                            $item?->options['discount_code'] 
                                                        }}
                                                    @else
                                                     --
                                                    @endif
                                                </div>

                                                <div class="tb-col tb-col-discount" data-label="Descuento">
                                                    {!! formatAmount($item?->discount_amount ?? 0) !!}
                                                </div>
                                                <div class="tb-col tb-col-price" data-label="Precio">{!! formatAmount($item?->price - ($item?->discount_amount ?? 0)) !!}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <!-- Invoice Summary -->
                            <div class="tb-invoice-summary">
                                @php
                                    $subTotal = 0;
                                    foreach ($invoice->items as $item) {
                                        $subTotal += ($item?->price - ($item?->discount_amount ?? 0)) * $item->quantity;
                                    }
                                    $couponOff = (float) $invoice->discount_amount;
                                    $grandTotal = (float) $invoice->amount;
                                @endphp
                                <div class="tb-summary-row">
                                    <span class="tb-label" data-label="Subtotal">{{ __('invoices.invoice_subtotal') }}:</span>
                                    <span class="tb-value">{!! formatAmount($subTotal) !!}</span>
                                </div>
                                @if ($couponOff > 0)
                                    <div class="tb-summary-row">
                                        <span class="tb-label">Cupón {{ $invoice->coupon_code }}:</span>
                                        <span class="tb-value">−{!! formatAmount($couponOff) !!}</span>
                                    </div>
                                @endif
                                <div class="tb-summary-row tb-total">
                                    <span class="tb-label" data-label="Total">{{ __('invoices.invoice_total') }}:</span>
                                    <span class="tb-value">{!! $grandTotal > 0 ? formatAmount($grandTotal) : 'Gratis' !!}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>


@push('scripts')
    <script>
       document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('openInvoiceModal', function(event) {
                setTimeout(() => {
                    $('#invoicePreviewModal').modal('show');
                }, 500);
            });
       });
    </script>
@endpush