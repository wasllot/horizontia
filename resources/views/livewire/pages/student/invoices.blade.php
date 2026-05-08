<div class="am-dbbox am-invoicelist_wrap" wire:init="loadData">
    @if($isLoading)
        @include('skeletons.invoices')
    @else
        <div class="am-dbbox_content am-invoicelist">
            <div class="am-dbbox_title">
                @slot('title')
                    {{ __('invoices.invoices') }}
                @endslot
                <h2>{{ __('invoices.invoices') }}</h2>
                <div class="am-dbbox_title_sorting">
                    <em>{{ __('invoices.filter_by') }}</em>
                    <span class="am-select" wire:ignore>
                        <select data-componentid="@this" data-live="true" class="am-select2" id="status"
                            data-wiremodel="status">
                            <option value="" {{ $status == '' ? 'selected' : '' }}>{{ __('invoices.all_invoices') }}</option>
                            <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>{{ __('invoices.pending') }}</option>
                            <option value="complete" {{ $status == 'complete' ? 'selected' : '' }}>{{ __('invoices.complete') }}</option>
                        </select>
                    </span>
                </div>
            </div>
            <div class="am-invoicetable">
                <table class="am-table @if(setting('_general.table_responsive') == 'yes') am-table-responsive @endif">
                    <thead>
                        <tr>
                            <th>{{ __('booking.id') }}</th>
                            <th>{{ __('booking.date') }}</th>
                            <th>{{ __('booking.transaction_id') }}</th>
                            <th>{{ __('general.item' )}}</th>
                            @role('tutor')
                                <th>{{ __('booking.student_name') }}</th>
                                <th>{{ __('booking.amount') }}</th>
                                <th>{{ __('booking.tutor_payout') }}</th>
                            @elserole('student')
                                <th>{{ __('booking.tutor_name') }}</th>
                                <th>{{ __('booking.amount') }}</th>
                            @endrole
                            <th>{{ __('booking.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!$orders->isEmpty())
                            @foreach($orders as $order)
                                @php
                                    $options        = $order?->options;
                                    $subject        = !empty($options['subject']) ? $options['subject'] : '';
                                    $image          = !empty($options['image']) ? $options['image'] : '';
                                    $subjectGroup   = !empty($options['subject_group']) ? $options['subject_group'] : '';
                                    $tutor_payout = $order?->price - getCommission($order?->price);
                                @endphp
                                <tr>
                                    <td data-label="{{ __('booking.id') }}"><span>{{ $order?->order_id }}</span></td>
                                    <td data-label="{{ __('booking.date') }}"><span>{{ $order?->created_at->format('F j, Y') }}</span></td>
                                    <td data-label="{{ __('booking.transaction_id') }}"><span>{{ !empty($order?->orders?->transaction_id) ? $order?->orders?->transaction_id : '-' }}</span></td>
                                    <td data-label="{{ __('general.item' )}}">
                                        <div class="tb-varification_userinfo">
                                            <strong class="tb-adminhead__img">
                                                @if (!empty($image) && Storage::disk(getStorageDisk())->exists($image))
                                                    <img src="{{ resizedImage($image,34,34) }}" alt="{{$image}}" />
                                                @else
                                                    <img src="{{ resizedImage('placeholder.png',34,34) }}" alt="{{ $image }}" />
                                                @endif
                                            </strong>
                                            @if($order->orderable_type == App\Models\SlotBooking::class)
                                                <span>{{ $subject  }}<small>{{ $subjectGroup }}</small></span>
                                            @elseif($order->orderable_type == \Modules\Courses\Models\Course::class && !empty($options['title']))
                                                <span>{{ $options['title'] }}</span>
                                            @elseif(\Nwidart\Modules\Facades\Module::has('subscriptions') && \Nwidart\Modules\Facades\Module::isEnabled('subscriptions') && $order->orderable_type == 'Modules\Subscriptions\Models\Subscription')
                                                <span>{{ $order->options['name'] }} <small>{{ __($order->options['period']) }}</small></span>
                                            @endif
                                        </div>
                                    </td>
                                    @role('student')
                                        <td data-label="{{ __('booking.tutor_name' )}}">
                                            <div class="tb-varification_userinfo">
                                                @if($order?->orderable_type == \Modules\Courses\Models\Course::class)
                                                    <strong class="tb-adminhead__img">
                                                        @if (!empty($order?->orderable?->instructor?->profile?->image) && Storage::disk(getStorageDisk())->exists($order?->orderable?->instructor?->profile?->image))
                                                        <img src="{{ resizedImage($order?->orderable?->instructor?->profile?->image,34,34) }}" alt="{{$order?->orderable?->instructor?->profile?->image}}" />
                                                        @else
                                                            <img src="{{ setting('_general.default_avatar_for_user') ? url(Storage::url(setting('_general.default_avatar_for_user')[0]['path'])) : resizedImage('placeholder.png', 34, 34) }}" alt="{{ $order?->orderable?->instructor?->profile?->image }}" />
                                                        @endif
                                                    </strong>
                                                    <span>{{ $order?->orderable?->instructor?->profile?->first_name }}</span>
                                                @elseif($order?->orderable_type == App\Models\SlotBooking::class)
                                                    <strong class="tb-adminhead__img">
                                                        @if (!empty($order?->orderable?->tutor?->image) && Storage::disk(getStorageDisk())->exists($order?->orderable?->tutor?->image))
                                                        <img src="{{ resizedImage($order?->orderable?->tutor?->image,34,34) }}" alt="{{$order?->orderable?->tutor?->image}}" />
                                                        @else
                                                            <img src="{{ setting('_general.default_avatar_for_user') ? url(Storage::url(setting('_general.default_avatar_for_user')[0]['path'])) : resizedImage('placeholder.png', 34, 34) }}" alt="{{ $order?->orderable?->tutor?->image }}" />
                                                        @endif
                                                    </strong>
                                                    <span>{{ $order?->orderable?->tutor?->first_name }}</span>
                                                @else
                                                    <span>-</span>    
                                                @endif
                                            </div>
                                        </td>
                                        <td data-label="{{ __('booking.amount') }}">
                                            <span>{!! formatAmount($order?->price) !!}</span>
                                        </td>
                                    @elserole('tutor')
                                        <td data-label="{{ __('booking.student_name' )}}">
                                            <div class="tb-varification_userinfo">
                                                @if($order->orderable_type != 'Modules\Subscriptions\Models\Subscription')
                                                    <strong class="tb-adminhead__img">
                                                        @if (!empty($order?->orders?->userProfile?->image) && Storage::disk(getStorageDisk())->exists($order?->orders?->userProfile?->image))
                                                        <img src="{{ resizedImage($order?->orders?->userProfile?->image,34,34) }}" alt="{{$order?->orders?->userProfile?->image}}" />
                                                        @else
                                                            <img src="{{ setting('_general.default_avatar_for_user') ? url(Storage::url(setting('_general.default_avatar_for_user')[0]['path'])) : resizedImage('placeholder.png', 34, 34) }}" alt="{{ $order?->orderable?->student?->image }}" />
                                                        @endif
                                                    </strong>
                                                    <span>{{ $order?->orders?->userProfile?->first_name }}</span>
                                                @else
                                                    <span>-</span> 
                                                @endif
                                            </div>
                                        </td>
                                        <td data-label="{{ __('booking.amount') }}">
                                            <span>{!! formatAmount($order?->price) !!}</span>
                                        </td>
                                        <td data-label="{{ __('booking.tutor_payout') }}">
                                            @if ($order->orderable_type != 'Modules\Subscriptions\Models\Subscription')
                                                <span>{!! formatAmount($tutor_payout) !!}</span>
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                    @endrole
                                    <td data-label="{{ __('booking.status' )}}">
                                        <div class="am-status-tag">
                                            <em class="tk-project-tag-two {{ $order?->orders?->status == 'complete' ? 'tk-hourly-tag' : 'tk-fixed-tag' }}">{{ $order?->orders?->status}}</em>
                                        </div>
                                    </td>

                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            @if ($orders->isEmpty())
            <x-no-record :image="asset('images/payouts.png')" :title="__('general.no_record_title')"
            :description="__('general.no_records_available')"  />
            @else
            {{ $orders->links('pagination.pagination') }}
            @endif
        </div>
    @endif
</div>
@push('scripts' )
<script type="text/javascript" data-navigate-once>
    var component = '';
    document.addEventListener('livewire:navigated', function() {
            component = @this;
    },{ once: true });
    document.addEventListener('loadPageJs', (event) => {
        component.dispatch('initSelect2', {target:'.am-select2'});
    })
</script>
@endpush
