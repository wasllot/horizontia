<div class="am-userearningwrap">

    {{-- Header --}}
    <div style="margin-bottom:32px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="background:linear-gradient(135deg,#667eea,#764ba2); border-radius:14px; width:52px; height:52px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 4px 15px rgba(118,75,162,.35);">
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h2 style="font-size:1.5rem; font-weight:800; color:#1a1a2e; margin:0 0 4px;">{{ __('tutor.my_earning') }}</h2>
                <p style="color:#8b92a5; font-size:.875rem; margin:0;">{{ __('tutor.description') }}</p>
            </div>
        </div>
    </div>

    {{-- Stat Cards Grid --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:18px;">

        {{-- Earned Income --}}
        <div style="background:linear-gradient(135deg,#43e97b 0%,#38f9d7 100%); border-radius:20px; padding:24px; position:relative; overflow:hidden; box-shadow:0 8px 25px rgba(67,233,123,.3); transition:transform .2s;" onmouseenter="this.style.transform='translateY(-4px)'" onmouseleave="this.style.transform='translateY(0)'">
            <div style="position:absolute; top:-20px; right:-20px; width:100px; height:100px; background:rgba(255,255,255,.1); border-radius:50%;"></div>
            <div style="position:absolute; bottom:-30px; right:20px; width:70px; height:70px; background:rgba(255,255,255,.08); border-radius:50%;"></div>
            <div style="background:rgba(255,255,255,.25); border-radius:12px; width:44px; height:44px; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div style="font-size:1.6rem; font-weight:800; color:#fff; line-height:1.2; margin-bottom:6px;">{!! formatAmount($earnedAmount, true) !!}</div>
            <div style="font-size:.8rem; color:rgba(255,255,255,.85); font-weight:600; text-transform:uppercase; letter-spacing:.05em;">{{ __('tutor.earned_income') }}</div>
        </div>

        {{-- Funds Withdraw --}}
        <div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); border-radius:20px; padding:24px; position:relative; overflow:hidden; box-shadow:0 8px 25px rgba(102,126,234,.35); transition:transform .2s;" onmouseenter="this.style.transform='translateY(-4px)'" onmouseleave="this.style.transform='translateY(0)'">
            <div style="position:absolute; top:-20px; right:-20px; width:100px; height:100px; background:rgba(255,255,255,.1); border-radius:50%;"></div>
            <div style="position:absolute; bottom:-30px; right:20px; width:70px; height:70px; background:rgba(255,255,255,.08); border-radius:50%;"></div>
            <div style="background:rgba(255,255,255,.25); border-radius:12px; width:44px; height:44px; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
            </div>
            <div style="font-size:1.6rem; font-weight:800; color:#fff; line-height:1.2; margin-bottom:6px;">{!! formatAmount($withdrawalBalance['completed_withdrawals'], true) !!}</div>
            <div style="font-size:.8rem; color:rgba(255,255,255,.85); font-weight:600; text-transform:uppercase; letter-spacing:.05em;">{{ __('tutor.funds_withdraw') }}</div>
        </div>

        {{-- Pending Amount --}}
        <div style="background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%); border-radius:20px; padding:24px; position:relative; overflow:hidden; box-shadow:0 8px 25px rgba(245,87,108,.3); transition:transform .2s;" onmouseenter="this.style.transform='translateY(-4px)'" onmouseleave="this.style.transform='translateY(0)'">
            <div style="position:absolute; top:-20px; right:-20px; width:100px; height:100px; background:rgba(255,255,255,.1); border-radius:50%;"></div>
            <div style="position:absolute; bottom:-30px; right:20px; width:70px; height:70px; background:rgba(255,255,255,.08); border-radius:50%;"></div>
            <div style="background:rgba(255,255,255,.25); border-radius:12px; width:44px; height:44px; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div style="font-size:1.6rem; font-weight:800; color:#fff; line-height:1.2; margin-bottom:6px;">{!! formatAmount($pendingFunds, true) !!}</div>
            <div style="font-size:.8rem; color:rgba(255,255,255,.85); font-weight:600; text-transform:uppercase; letter-spacing:.05em;">{{ __('tutor.pending_amount') }}</div>
        </div>

        {{-- Wallet Funds --}}
        <div style="background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%); border-radius:20px; padding:24px; position:relative; overflow:hidden; box-shadow:0 8px 25px rgba(79,172,254,.35); transition:transform .2s;" onmouseenter="this.style.transform='translateY(-4px)'" onmouseleave="this.style.transform='translateY(0)'">
            <div style="position:absolute; top:-20px; right:-20px; width:100px; height:100px; background:rgba(255,255,255,.1); border-radius:50%;"></div>
            <div style="position:absolute; bottom:-30px; right:20px; width:70px; height:70px; background:rgba(255,255,255,.08); border-radius:50%;"></div>
            <div style="background:rgba(255,255,255,.25); border-radius:12px; width:44px; height:44px; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div style="font-size:1.6rem; font-weight:800; color:#fff; line-height:1.2; margin-bottom:6px;">{!! formatAmount($walletBalance, true) !!}</div>
            <div style="font-size:.8rem; color:rgba(255,255,255,.85); font-weight:600; text-transform:uppercase; letter-spacing:.05em;">{{ __('tutor.wallet_funds') }}</div>
        </div>

        {{-- Pending Withdraw --}}
        <div style="background:linear-gradient(135deg,#fa709a 0%,#fee140 100%); border-radius:20px; padding:24px; position:relative; overflow:hidden; box-shadow:0 8px 25px rgba(250,112,154,.3); transition:transform .2s;" onmouseenter="this.style.transform='translateY(-4px)'" onmouseleave="this.style.transform='translateY(0)'">
            <div style="position:absolute; top:-20px; right:-20px; width:100px; height:100px; background:rgba(255,255,255,.1); border-radius:50%;"></div>
            <div style="position:absolute; bottom:-30px; right:20px; width:70px; height:70px; background:rgba(255,255,255,.08); border-radius:50%;"></div>
            <div style="background:rgba(255,255,255,.25); border-radius:12px; width:44px; height:44px; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <div style="font-size:1.6rem; font-weight:800; color:#fff; line-height:1.2; margin-bottom:6px;">{!! formatAmount($withdrawalBalance['pending_withdrawals'], true) !!}</div>
            <div style="font-size:.8rem; color:rgba(255,255,255,.85); font-weight:600; text-transform:uppercase; letter-spacing:.05em;">{{ __('tutor.pending_withdraw_amount') }}</div>
        </div>

    </div>
</div>
