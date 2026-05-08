<main class="tb-main am-dispute-system am-invoice-system">
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
                                        <td data-label="{{ __('booking.transaction_id') }}"><span>{{ !empty($order?->transaction_id) ? $order->transaction_id : '-' }}</span></td>
                                        <td data-label="{{ __('booking.items' )}}">
                                            <div class="tb-varification_userinfo">
                                                <strong class="tb-adminhead__img">
                                                    @if(!empty($order?->slot_bookings_count))
                                                        {{ $order?->slot_bookings_count == 1 ? __('booking.session_count', ['count' => $order?->slot_bookings_count]) : __('booking.sessions_count', ['count' => $order?->slot_bookings_count]) }}
                                                    @endif
                                                    @if(\Nwidart\Modules\Facades\Module::has('courses') && \Nwidart\Modules\Facades\Module::isEnabled('courses') && !empty($order?->courses_count) )
                                                        @if(!empty($order?->slot_bookings_count)) | @endif
                                                        {{ $order?->courses_count == 1 ? __('booking.course_count', ['count' => $order?->courses_count]) : __('booking.courses_count', ['count' => $order?->courses_count]) }}
                                                    @endif
                                                    @if(\Nwidart\Modules\Facades\Module::has('subscriptions') && \Nwidart\Modules\Facades\Module::isEnabled('subscriptions') && !empty($order?->subscriptions_count))
                                                        @if(!empty($order?->slot_bookings_count) || !empty($order?->courses_count)) | @endif
                                                        {{ $order?->subscriptions_count == 1 ? __('booking.subscription_count', ['count' => $order?->subscriptions_count]) : __('booking.subscriptions_count', ['count' => $order?->subscriptions_count]) }}
                                                    @endif
                                                </strong>
                                            </div>
                                        </td>
                                        <td data-label="{{ __('booking.student_name' )}}">
                                            <div class="tb-varification_userinfo">
                                                <strong class="tb-adminhead__img">
                                                    @if (!empty($order?->userProfile?->image) && Storage::disk(getStorageDisk())->exists($order?->userProfile?->image))
                                                    <img src="{{ resizedImage($order?->userProfile?->image,34,34) }}" alt="{{$order?->userProfile?->image}}" />
                                                    @else
                                                        <img src="{{ setting('_general.default_avatar_for_user') ? url(Storage::url(setting('_general.default_avatar_for_user')[0]['path'])) : resizedImage('placeholder.png', 34, 34) }}" alt="{{ $order?->orderable?->student?->image }}" />
                                                    @endif
                                                </strong>
                                                <span>{{ $order?->userProfile?->full_name }}</span>
                                            </div>
                                        </td>
                                        <td data-label="{{ __('booking.payment_method' )}}">
                                            <span>{{ __("settings." .$order?->payment_method. "_title")  }}</span>
                                        </td>
                                        <td data-label="{{ __('booking.amount') }}">
                                            <span>{!! formatAmount($order?->amount) !!}</span>
                                        </td>
                                        <td data-label="{{ __('booking.admin_commission') }}">
                                            <span>{!! empty($order?->subscription_id) ? formatAmount($order?->admin_commission) : formatAmount(0) !!}</span>
                                        </td>
                                        <td data-label="{{ __('booking.status' )}}">
                                            <div class="am-status-tag">
                                                <em class="tk-project-tag {{ $order?->status == 'complete' ? 'tk-hourly-tag' : 'tk-fixed-tag' }}">{{ $order?->status}}</em>
                                            </div>
                                        </td>
                                        <td data-label="{{ __('general.actions') }}">
                                            <button class="am-btn" wire:click.prevent="viewInvoice({{ $order?->id }})">Preview</button>
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
                                            {{ $invoice?->city }}, {{ $invoice?->countryDetails?->name ?? '' }}, {{ $invoice?->state }}
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
                                                <div class="tb-col tb-col-title" data-label="Title">{{ $item->title }}</div>
                                                <div class="tb-col tb-col-qty" data-label="Qty.">{{ $item->quantity }}</div>
                                                <div class="tb-col tb-col-discount" data-label="Qty.">
                                                    @if(!empty($item?->options['discount_code']))
                                                        {{ 
                                                            $item?->options['discount_code'] 
                                                        }}
                                                    @else
                                                     --
                                                    @endif
                                                </div>

                                                <div class="tb-col tb-col-discount" data-label="Discount">
                                                    {{ $item?->discount_amount ?? 0 }}
                                                </div>
                                                <div class="tb-col tb-col-price" data-label="Price">{{ ( $item?->price - $item?->discount_amount ?? 0) }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <!-- Invoice Summary -->
                            <div class="tb-invoice-summary">
                                @php
                                    $subTotal   = 0;
                                    $grossTotal = 0;
                                    foreach($invoice->items as $item) {
                                        $subTotal += ( $item?->price - $item?->discount_amount ?? 0) * $item->quantity;
                                    }
                                    $grossTotal = $subTotal;
                                @endphp
                                <div class="tb-summary-row">
                                    <span class="tb-label" data-label="Subtotal">{{ __('invoices.invoice_subtotal') }}:</span>
                                    <span class="tb-value">${{ number_format($subTotal, 2) }}</span>
                                </div>

                                <div class="tb-summary-row tb-total">
                                    <span class="tb-label" data-label="Total">{{ __('invoices.invoice_total') }}:</span>
                                    <span class="tb-value">${{ number_format($grossTotal, 2) }}</span>
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