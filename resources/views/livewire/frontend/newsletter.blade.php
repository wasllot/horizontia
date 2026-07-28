<div class="am-newsletter" style="margin: 2rem 0 4rem; padding: 3rem 4rem; background-color: #F4C430; border-radius: 20px; box-shadow: 0 15px 35px rgba(244, 196, 48, 0.2); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 30px;">
    <div class="am-newsletter-text" style="flex: 1; min-width: 300px;">
        <h3 style="font-size: 2.2rem; font-weight: 800; color: #000; margin-bottom: 10px; line-height: 1.2;">¡Únete a nuestra comunidad!</h3>
        <p style="font-size: 1.1rem; color: #222; margin: 0; font-weight: 500;">Recibe las últimas novedades, cursos y ofertas exclusivas directamente en tu correo.</p>
    </div>
    
    <div class="am-newsletter-form" style="flex: 1; min-width: 300px; max-width: 500px;">
        <form wire:submit.prevent="subscribe" style="display: flex; gap: 10px; width: 100%; position: relative;">
            <input type="email" wire:model="email" class="form-control" placeholder="Tu correo electrónico" required 
                   style="border-radius: 50px; padding: 15px 25px; width: 100%; border: none; font-size: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); outline: none;">
            
            <button type="submit" class="am-btn" wire:loading.attr="disabled"
                    style="border-radius: 50px; padding: 15px 35px; background-color: #000; color: #fff !important; font-weight: 700; border: none; cursor: pointer; white-space: nowrap; display: flex; align-items: center; justify-content: center; min-width: 150px; transition: background-color 0.3s ease;">
                <span wire:loading.remove>Suscribirme</span>
                <span wire:loading style="display: none;">Enviando...</span>
            </button>
        </form>
        
        @if(setting('_general.enable_recaptcha') == '1')
            <div style="margin-top: 15px;" wire:ignore>
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <div class="g-recaptcha" data-sitekey="{{ setting('_general.recaptcha_site_key') }}" data-callback="onNewsletterRecaptchaComplete"></div>
                <script>
                    function onNewsletterRecaptchaComplete(response) {
                        @this.set('recaptchaToken', response);
                    }
                </script>
            </div>
            @error('recaptchaToken') 
                <span style="color: #d93025; font-weight: 700; display: block; margin-top: 10px; padding-left: 15px; font-size: 0.95rem;">
                    {{ $message }}
                </span> 
            @enderror
        @endif
        @error('email') 
            <span style="color: #d93025; font-weight: 700; display: block; margin-top: 10px; padding-left: 15px; font-size: 0.95rem;">
                {{ $message }}
            </span> 
        @enderror
        @if($successMessage)
            <div style="background-color: #000; color: #F4C430; font-weight: 600; padding: 12px 20px; border-radius: 50px; margin-top: 15px; display: inline-block;">
                <i class="am-icon-check-circle-01" style="margin-right: 8px;"></i> {{ $successMessage }}
            </div>
        @endif
    </div>
</div>
