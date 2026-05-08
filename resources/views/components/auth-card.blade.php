<div class="am-main-login">
    <div class="am-auth-page">
        <div class="am-login-left"
            style="background-image: url('{{ asset('images/nuevo/sign-in-horizontia.jpg') }}'); background-size: cover; background-position: center; position: relative;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.2);">
            </div>
            <div
                style="position: relative; z-index: 2; padding: 60px; height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    {{ $logo }}
                </div>
                <div class="am-login-left_title" style="color: #fff;">
                    <h2 style="font-size: 3rem; font-weight: 800; line-height: 1.1; margin-bottom: 20px;">
                        {{ __('auth.login_left_h2') }}</h2>
                    <span
                        style="font-size: 1.25rem; font-weight: 400; opacity: 0.9;">{{ __('auth.login_left_span') }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="am-login-right">
        {{ $formHeader }}
        {{ $slot }}
    </div>
</div>