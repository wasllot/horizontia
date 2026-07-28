<div id="contacto" class="am-contact-section" style="padding: 80px 0; background-color: #fff;">
    <div class="container">
        <div class="row align-items-center">
            
            <div class="col-lg-5 mb-5 mb-lg-0">
                <h2 style="font-size: 2.5rem; font-weight: 800; color: #000; margin-bottom: 20px;">Ponte en contacto con nosotros</h2>
                <p style="font-size: 1.1rem; color: #555; margin-bottom: 30px; line-height: 1.6;">¿Tienes alguna pregunta, sugerencia o necesitas asistencia técnica? Completa el formulario y nuestro equipo te responderá a la brevedad posible.</p>
                
                <ul style="list-style: none; padding: 0; margin-bottom: 0;">
                    <li style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="width: 50px; height: 50px; background-color: #F4C430; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                            <i class="am-icon-email" style="font-size: 20px; color: #000;"></i>
                        </div>
                        <div>
                            <strong style="display: block; font-size: 1.1rem; color: #000;">Correo Electrónico</strong>
                            <a href="mailto:soporte@horizontia.test" style="color: #555; text-decoration: none;">soporte@horizontia.test</a>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="col-lg-7 pl-lg-5">
                <div style="background: #f9f9f9; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                    <form wire:submit.prevent="submit" class="am-themeform">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label style="font-weight: 600; color: #000; margin-bottom: 8px;">Nombre Completo</label>
                                <input type="text" wire:model="name" class="form-control" placeholder="Tu nombre" 
                                       style="border-radius: 10px; padding: 12px 20px; border: 1px solid #ddd; width: 100%;">
                                @error('name') <span style="color: #d93025; font-size: 0.85rem; font-weight: 600; display: block; margin-top: 5px;">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="col-md-6 form-group">
                                <label style="font-weight: 600; color: #000; margin-bottom: 8px;">Correo Electrónico</label>
                                <input type="email" wire:model="email" class="form-control" placeholder="tucorreo@ejemplo.com"
                                       style="border-radius: 10px; padding: 12px 20px; border: 1px solid #ddd; width: 100%;">
                                @error('email') <span style="color: #d93025; font-size: 0.85rem; font-weight: 600; display: block; margin-top: 5px;">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="col-12 form-group" style="margin-top: 20px;">
                                <label style="font-weight: 600; color: #000; margin-bottom: 8px;">Asunto</label>
                                <input type="text" wire:model="subject" class="form-control" placeholder="¿En qué podemos ayudarte?"
                                       style="border-radius: 10px; padding: 12px 20px; border: 1px solid #ddd; width: 100%;">
                                @error('subject') <span style="color: #d93025; font-size: 0.85rem; font-weight: 600; display: block; margin-top: 5px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-12 form-group" style="margin-top: 20px;">
                                <label style="font-weight: 600; color: #000; margin-bottom: 8px;">Mensaje</label>
                                <textarea wire:model="message" class="form-control" rows="5" placeholder="Escribe tu mensaje aquí..."
                                          style="border-radius: 10px; padding: 15px 20px; border: 1px solid #ddd; width: 100%; resize: vertical;"></textarea>
                                @error('message') <span style="color: #d93025; font-size: 0.85rem; font-weight: 600; display: block; margin-top: 5px;">{{ $message }}</span> @enderror
                            </div>

                            @if(setting('_general.enable_recaptcha') == '1')
                            <div class="col-12 form-group" style="margin-top: 20px;" wire:ignore>
                                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                                <div class="g-recaptcha" data-sitekey="{{ setting('_general.recaptcha_site_key') }}" data-callback="onContactRecaptchaComplete"></div>
                                <script>
                                    function onContactRecaptchaComplete(response) {
                                        @this.set('recaptchaToken', response);
                                    }
                                </script>
                                @error('recaptchaToken') <span style="color: #d93025; font-size: 0.85rem; font-weight: 600; display: block; margin-top: 5px;">{{ $message }}</span> @enderror
                            </div>
                            @endif

                            <div class="col-12" style="margin-top: 30px;">
                                <button type="submit" class="am-btn" wire:loading.attr="disabled" wire:target="submit"
                                        style="background-color: #F4C430; color: #000; border-radius: 50px; padding: 15px 40px; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; min-width: 200px; transition: all 0.3s ease;">
                                    <span wire:loading.remove wire:target="submit">Enviar Mensaje</span>
                                    <span wire:loading wire:target="submit" style="display: none;">Enviando...</span>
                                </button>
                                
                                @if($successMessage)
                                    <div style="background-color: #e6f4ea; color: #137333; padding: 15px; border-radius: 10px; margin-top: 20px; font-weight: 600;">
                                        <i class="am-icon-check-circle-01" style="margin-right: 5px;"></i> {{ $successMessage }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
