<div id="solicitar" class="am-request-section" style="padding: 70px 0 90px; background-color: #fff;" data-testid="access-form">
    <div class="container">
        <div class="row align-items-start">
            <div class="col-lg-5 mb-5 mb-lg-0">
                <h2 style="font-size: 2.3rem; font-weight: 800; color: #000; margin-bottom: 18px;">Pide tu acceso a la academia</h2>
                <p style="font-size: 1.08rem; color: #555; line-height: 1.6; margin-bottom: 26px;">Para cuidar la calidad y la seguridad de todos, las cuentas se crean después de revisar cada solicitud. Cuéntanos quién eres y te respondemos por correo.</p>

                <ol style="list-style: none; padding: 0; margin: 0; display: grid; gap: 18px;">
                    @foreach ([['1', 'Envía tu solicitud', 'Elige si quieres aprender o enseñar y llena el formulario.'], ['2', 'La revisamos', 'Normalmente en uno o dos días hábiles; te avisamos por correo.'], ['3', 'Crea tu contraseña', 'Con el enlace del correo activas tu cuenta y entras.']] as [$n, $t, $d])
                        <li style="display: flex; gap: 14px; align-items: flex-start;">
                            <span style="flex: none; width: 38px; height: 38px; border-radius: 50%; background: #F4C430; color: #000; display: grid; place-items: center; font-weight: 800;">{{ $n }}</span>
                            <span><strong style="display: block; color: #000; font-size: 1.05rem;">{{ $t }}</strong><span style="color: #666;">{{ $d }}</span></span>
                        </li>
                    @endforeach
                </ol>
                <p style="margin-top: 28px; color: #666;">¿Ya tienes cuenta? <a href="{{ route('login') }}" style="color: #14213d; font-weight: 700; border-bottom: 2px solid #F2D07F; text-decoration: none;">Inicia sesión</a></p>
            </div>

            <div class="col-lg-7 pl-lg-5">
                <div style="background: #f9f9f9; padding: 36px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                    @if ($sent)
                        <div data-testid="access-sent" style="text-align: center; padding: 20px 0;">
                            <div style="width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 50%; background: #e6f4ea; color: #137333; display: grid; place-items: center; font-size: 30px;">✓</div>
                            <h3 style="font-size: 1.6rem; font-weight: 800; color: #000; margin-bottom: 10px;">¡Solicitud enviada!</h3>
                            <p style="color: #555; max-width: 44ch; margin: 0 auto 20px;">Te escribiremos al correo que indicaste en cuanto la revisemos. Si no lo ves en unos minutos, revisa tu carpeta de spam.</p>
                            <a href="{{ route('courses.search-courses') }}" class="am-btn" style="background-color: #F4C430; color: #000; border-radius: 50px; padding: 12px 30px; font-weight: 700;">Mientras tanto, explora los cursos</a>
                        </div>
                    @else
                        <form wire:submit.prevent="submit" class="am-themeform" novalidate>
                            <fieldset style="border: 0; padding: 0; margin: 0 0 22px;">
                                <legend style="font-weight: 700; color: #000; font-size: 1rem; margin-bottom: 10px;">¿Qué quieres hacer?</legend>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    @foreach (['student' => ['<path d="m22 10-10-5L2 10l10 5 10-5z"/><path d="M6 12v5c3 2.5 9 2.5 12 0v-5"/>', 'Quiero aprender', 'Acceso como estudiante'], 'tutor' => ['<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>', 'Quiero enseñar', 'Acceso como instructor']] as $key => [$icon, $title, $sub])
                                        <button type="button" wire:click="choose('{{ $key }}')" aria-pressed="{{ $type === $key ? 'true' : 'false' }}" data-testid="type-{{ $key }}"
                                                style="text-align: left; padding: 16px; border-radius: 14px; cursor: pointer; background: {{ $type === $key ? '#fff8de' : '#fff' }}; border: 2px solid {{ $type === $key ? '#F4C430' : '#e5e7eb' }};">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#14213d" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display: block; margin-bottom: 6px;">{!! $icon !!}</svg>
                                            <strong style="display: block; color: #000;">{{ $title }}</strong>
                                            <small style="color: #666;">{{ $sub }}</small>
                                        </button>
                                    @endforeach
                                </div>
                            </fieldset>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="ra-name" style="font-weight: 600; color: #000; margin-bottom: 8px;">Nombre completo</label>
                                    <input id="ra-name" type="text" wire:model="name" class="form-control" autocomplete="name" placeholder="Tu nombre y apellido" style="border-radius: 10px; padding: 12px 20px; border: 1px solid #ddd; width: 100%;">
                                    @error('name') <span class="ra-err">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="ra-email" style="font-weight: 600; color: #000; margin-bottom: 8px;">Correo electrónico</label>
                                    <input id="ra-email" type="email" wire:model="email" class="form-control" autocomplete="email" placeholder="tucorreo@ejemplo.com" style="border-radius: 10px; padding: 12px 20px; border: 1px solid #ddd; width: 100%;">
                                    @error('email') <span class="ra-err">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 form-group" style="margin-top: 20px;">
                                    <label for="ra-phone" style="font-weight: 600; color: #000; margin-bottom: 8px;">Teléfono <small style="font-weight: 400; color: #888;">(opcional)</small></label>
                                    <input id="ra-phone" type="tel" wire:model="phone" class="form-control" autocomplete="tel" placeholder="55 1234 5678" style="border-radius: 10px; padding: 12px 20px; border: 1px solid #ddd; width: 100%;">
                                    @error('phone') <span class="ra-err">{{ $message }}</span> @enderror
                                </div>
                                @if ($type === 'tutor')
                                    <div class="col-md-6 form-group" style="margin-top: 20px;">
                                        <label for="ra-link" style="font-weight: 600; color: #000; margin-bottom: 8px;">Sitio o LinkedIn <small style="font-weight: 400; color: #888;">(opcional)</small></label>
                                        <input id="ra-link" type="url" wire:model="link" class="form-control" placeholder="https://" style="border-radius: 10px; padding: 12px 20px; border: 1px solid #ddd; width: 100%;">
                                        @error('link') <span class="ra-err">{{ $message }}</span> @enderror
                                    </div>
                                @endif
                                <div class="col-12 form-group" style="margin-top: 20px;">
                                    <label for="ra-msg" style="font-weight: 600; color: #000; margin-bottom: 8px;">{{ $type === 'tutor' ? 'Cuéntanos sobre tu experiencia y qué te gustaría enseñar' : '¿Qué te gustaría aprender?' }} @if ($type !== 'tutor')<small style="font-weight: 400; color: #888;">(opcional)</small>@endif</label>
                                    <textarea id="ra-msg" wire:model="message" class="form-control" rows="4" maxlength="1500" placeholder="{{ $type === 'tutor' ? 'Tu trayectoria, los temas que dominas y el tipo de cursos que imaginas…' : 'Temas o cursos que te interesan, tu objetivo…' }}" style="border-radius: 10px; padding: 15px 20px; border: 1px solid #ddd; width: 100%; resize: vertical;"></textarea>
                                    @error('message') <span class="ra-err">{{ $message }}</span> @enderror
                                </div>

                                {{-- Señuelo anti-bots --}}
                                <div style="position: absolute; left: -9999px;" aria-hidden="true"><label>No llenar<input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label></div>

                                @if (recaptchaEnabled('contact'))
                                    <div class="col-12 form-group" style="margin-top: 20px;" wire:ignore>
                                        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                                        <div class="g-recaptcha" data-sitekey="{{ recaptchaSiteKey() }}" data-callback="onAccessRecaptcha"></div>
                                        <script>function onAccessRecaptcha(r) { @this.set('recaptchaToken', r); }</script>
                                    </div>
                                    @error('recaptchaToken') <div class="col-12"><span class="ra-err">{{ $message }}</span></div> @enderror
                                @endif

                                <div class="col-12" style="margin-top: 20px;">
                                    <label style="display: flex; gap: 10px; align-items: flex-start; font-weight: 400; color: #555; cursor: pointer;">
                                        <input type="checkbox" wire:model="consent" style="margin-top: 4px;">
                                        <span>Acepto el <a href="{{ url('/aviso-de-privacidad') }}" target="_blank" rel="noopener" style="color: #14213d; font-weight: 600;">aviso de privacidad</a> y que me contacten por correo sobre mi solicitud.</span>
                                    </label>
                                    @error('consent') <span class="ra-err">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-12" style="margin-top: 26px;">
                                    <button type="submit" class="am-btn" wire:loading.attr="disabled" wire:target="submit" data-testid="access-submit"
                                            style="background-color: #F4C430; color: #000; border-radius: 50px; padding: 15px 40px; font-weight: 700; border: none; cursor: pointer; min-width: 220px;">
                                        <span wire:loading.remove wire:target="submit">Enviar solicitud</span>
                                        <span wire:loading wire:target="submit">Enviando…</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <style>.ra-err { color: #d93025; font-size: .85rem; font-weight: 600; display: block; margin-top: 5px; }</style>
</div>
