<div style="background:#fff; border-radius:20px; box-shadow:0 2px 20px rgba(0,0,0,.07); border:1px solid #f0f0f0; padding:28px; margin-top:24px;">
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px; margin-bottom:24px;">
        <div>
            <h2 style="font-size:1.15rem; font-weight:700; color:#1a1a2e; margin:0 0 4px;">{{ __('tutor.earning_details') }}</h2>
            <p style="font-size:.8rem; color:#aaa; margin:0;">Vista mensual de tus ingresos</p>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <span style="font-size:.8rem; color:#888;">{{ __('tutor.filter_by') }}</span>
            <div style="position:relative;" wire:ignore>
                <input type="text"
                    style="border:1.5px solid #e8e8e8; border-radius:10px; padding:8px 14px; font-size:.85rem; color:#444; background:#fafafa; outline:none; cursor:pointer; min-width:160px; transition:border .2s;"
                    id="calendar-month-year">
            </div>
        </div>
    </div>
    <div wire:ignore style="position:relative; height:220px;">
        <canvas id="am-themechart"></canvas>
    </div>
</div>
