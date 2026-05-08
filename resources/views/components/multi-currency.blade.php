@if(!empty(setting('_general.enable_multi_currency')))
    @if(!empty(setting('_general.multi_currency_list')))
        @php
            $currencies = currencyList();
            $selectedCurrency  = getCurrentCurrency();
        @endphp
        <form class="am-switch-language am-multi-currency" action="{{ route('switch-currency') }}" method="POST">
            @csrf
            <input type="hidden" name="am-currency">
            <div class="am-language-select am-currency-select">
                <a href="javascript:void(0);" class="am-currency-anchor">
                    {!! $selectedCurrency['code'] . '&nbsp;' . $selectedCurrency['symbol'] !!}<i class="am-icon-chevron-down"></i>
                </a>
                <ul class="sub-menutwo currency-menu">
                    @foreach(setting('_general.multi_currency_list') as $currency)
                        <li data-currency="{!! $currencies[$currency]['code'] !!}" class="{{ $selectedCurrency['code'] == $currency ? 'active' : '' }}">
                            <span>{!! $currencies[$currency]['code'] . '&nbsp;' . $currencies[$currency]['symbol']  !!}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </form>
    @endif
@endif