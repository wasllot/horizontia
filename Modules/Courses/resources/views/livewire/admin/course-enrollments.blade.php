<main class="tb-main am-dispute-system am-enrollment-system">
    <div class ="row">
        <div class="col-lg-12 col-md-12">
            <div class="tb-dhb-mainheading">
                <h4> {{ __('courses::courses.course_enrolments') .' ('. $orders->total() .')'}}</h4>
                <button type="button" class="hz-btn hz-btn--sm" wire:click="$toggle('manualOpen')" data-testid="manual-enroll-toggle"><i class="icon-plus" aria-hidden="true"></i> Inscribir manualmente</button>
                <div class="tb-sortby">
                    <form class="tb-themeform tb-displistform">
                        <fieldset>
                            <div class="tb-themeform__wrap">
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
            @if ($manualOpen)
                <form class="hz-en__form" wire:submit="enrollManually" data-testid="manual-enroll-form">
                    <strong>Inscribir a un alumno sin pedido</strong>
                    <small>Para cortesías, pagos por transferencia o errores de cobro. Queda registrado en el log.</small>
                    <label for="me-email">Correo del alumno</label>
                    <input id="me-email" type="email" class="form-control" wire:model="manualEmail" placeholder="alumno@correo.com" autocomplete="off">
                    @error('manualEmail') <small class="hz-en__err">{{ $message }}</small> @enderror
                    <label for="me-course">Curso</label>
                    <select id="me-course" class="form-control" wire:model.live="manualCourseId">
                        <option value="">Elige un curso…</option>
                        @foreach ($courseOptions as $c)<option value="{{ $c->id }}">{{ html_entity_decode($c->title) }}</option>@endforeach
                    </select>
                    @error('manualCourseId') <small class="hz-en__err">{{ $message }}</small> @enderror
                    @if ($manualDates->isNotEmpty())
                        <label for="me-date">Fecha</label>
                        <select id="me-date" class="form-control" wire:model="manualSessionId">
                            <option value="">Elige una fecha…</option>
                            @foreach ($manualDates as $d)<option value="{{ $d->id }}">{{ $d->startsLocal()->locale('es')->translatedFormat('j M Y, H:i') }} · {{ $d->city }}</option>@endforeach
                        </select>
                        @error('manualSessionId') <small class="hz-en__err">{{ $message }}</small> @enderror
                    @endif
                    <div class="hz-en__formacts">
                        <button type="submit" class="hz-btn hz-btn--sm" wire:loading.attr="disabled">Inscribir</button>
                        <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="$set('manualOpen', false)">Cerrar</button>
                    </div>
                </form>
            @endif
            <div class="am-disputelist_wrap">
                <div class="am-disputelist am-custom-scrollbar-y">
                    @if( !$orders->isEmpty() )
                        <table class="table tb-table tb-dbholder @if(setting('_general.table_responsive') == 'yes') tb-table-responsive @endif">
                            <thead>
                                <tr>
                                    <th>{{ __('courses::courses.id') }}</th>
                                    <th>{{ __('courses::courses.transaction_id') }}</th>
                                    <th>{{ __('courses::courses.course_title') }}</th>
                                    <th>{{ __('courses::courses.student_name') }}</th>
                                    <th>{{ __('courses::courses.tutor_name') }}</th>
                                    <th>{{ __('courses::courses.amount') }}</th>
                                    <th>{{ __('courses::courses.tutor_payout') }}</th>
                                    <th>{{ __('courses::courses.status') }}</th>
                                    <th>{{ __('courses::courses.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    @php
                                      
                                       $options = $order?->options ?? [];
                                       $courseTitle = $options['title'] ?? '';
                                       $image = $options['image'] ?? '';
                                       $tutor_payout = $order?->price - getCommission($order?->price);
                                    @endphp
                                    <tr>
                                        <td data-label="{{ __('courses::courses.id') }}"><span>{{ $order?->order_id }}</span></td>
                                        <td data-label="{{ __('courses::courses.transaction_id') }}" class="hz-wrapany"><span>{{$order?->orders?->transaction_id }}</span></td>
                                        <td data-label="{{ __('courses::courses.course_title' )}}">
                                            <div class="tb-varification_userinfo">
                                                <strong class="tb-adminhead__img">
                                                    @if (!empty($image) && mediaExists($image))
                                                        <img src="{{ resizedImage($image,34,34) }}" alt="{{$image}}" />
                                                    @else
                                                        <img src="{{ setting('_general.default_avatar_for_user') ? url(mediaUrl(setting('_general.default_avatar_for_user')[0]['path'])) : resizedImage('placeholder.png',34,34) }}" alt="{{ $image }}" />
                                                    @endif
                                                </strong>
    
                                                <span>{{ $order?->orderable?->title }}<br>
                                                    <small>{{ $order->orderable?->category?->name ?? '' }}</small>
                                                </span>
                                            </div>
                                        </td>
                                        <td data-label="{{ __('booking.student_name' )}}">
                                            <div class="tb-varification_userinfo">
                                                <strong class="tb-adminhead__img">
                                                    @if (!empty($order?->orders?->userProfile?->image) && mediaExists($order?->orders?->userProfile?->image))
                                                    <img src="{{ resizedImage($order?->orders?->userProfile?->image,34,34) }}" alt="{{$order?->orders?->userProfile?->image}}" />
                                                    @else
                                                        <img src="{{ setting('_general.default_avatar_for_user') ? url(mediaUrl(setting('_general.default_avatar_for_user')[0]['path'])) : resizedImage('placeholder.png', 34, 34) }}" alt="{{ $order?->orderable?->student?->image }}" />
                                                    @endif
                                                </strong>
                                                <span>{{ $order?->orders?->userProfile?->full_name }}</span>
                                            </div>
                                        </td>
                                        <td data-label="{{ __('booking.tutor_name' )}}">
                                            <div class="tb-varification_userinfo">
                                                <strong class="tb-adminhead__img">
                                                   
                                                    @if (!empty($order?->orderable?->instructor->profile?->image) && mediaExists($order?->orderable?->instructor->profile?->image))
                                                    <img src="{{ resizedImage($order?->orderable?->instructor->profile?->image,34,34) }}" alt="{{$order?->orderable?->instructor->profile?->image}}" />
                                                    @else 
                                                        <img src="{{ setting('_general.default_avatar_for_user') ? url(mediaUrl(setting('_general.default_avatar_for_user')[0]['path'])) : resizedImage('placeholder.png',34,34) }}" alt="{{ $order?->orderable?->instructor->profile?->image }}" />
                                                    @endif
                                                </strong>
                                                <span>{{ $order?->orderable?->instructor->profile?->full_name }}</span>
                                            </div>
                                        </td>
                                        <td data-label="{{ __('courses::courses.amount') }}">
                                            <span>{!! formatAmount($order?->price) !!}</span>
                                        </td>
                                        <td data-label="{{ __('courses::courses.tutor_payout') }}">
                                            <span>{!! formatAmount($tutor_payout) !!}</span>
                                        </td>
                                        <td data-label="{{ __('courses::courses.status' )}}">
                                            <div class="am-status-tag">
                                                <em class="tk-project-tag {{ $order?->orders?->status == 'complete' ? 'tk-hourly-tag' : 'tk-fixed-tag' }}">{{ ['pending' => 'Pendiente', 'complete' => 'Pagado', 'processed' => 'Procesado', 'refunded' => 'Reembolsado'][$order?->orders?->status] ?? $order?->orders?->status }}</em>
                                            </div>
                                        </td>
                                        <td data-label="{{ __('courses::courses.course_enrolments_detail') }}">
                                            <ul class="tb-action-icon">
                                                @if ($order?->orders?->status === 'complete')
                                                    <li>
                                                        <x-panel.action icon="icon-refresh-ccw" label="Reembolsar" tone="danger" data-testid="refund-order"
                                                            wire:click="refund({{ $order->order_id }})"
                                                            wire:confirm="¿Reembolsar este pedido? {{ $order->orders->payment_method === 'mercadopago' ? 'Se enviará el reembolso a Mercado Pago y ' : '' }}el alumno perderá el acceso al curso." />
                                                    </li>
                                                @endif
                                                <li>
                                                    <div class="am-custom-tooltip">
                                                        <span class="am-tooltip-text">{{ __('courses::courses.view_details') }}</span>
                                                        <a href="{{ route('courses.course-detail', ['slug' => $order->orderable->slug]) }}" target="_blank">
                                                            <i class="icon-eye"></i>
                                                        </a>
                                                    </div>
                                                </li>
                                            </ul>
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
</main>
