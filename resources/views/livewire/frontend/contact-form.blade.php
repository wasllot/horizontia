<div id="contacto" class="am-contact-section ht-contact" data-testid="contact">
    <style>
        .ht-contact { padding: clamp(56px, 7vw, 96px) 0; background: #fff; }
        .ht-contact__grid { max-width: 1240px; margin: 0 auto; padding: 0 clamp(16px, 3vw, 24px); display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: clamp(28px, 5vw, 64px); align-items: start; }
        .ht-contact h2 { margin: 0 0 14px; font-size: clamp(1.9rem, 3.8vw, 2.7rem); line-height: 1.15; color: #14213d; }
        .ht-contact__lead { margin: 0 0 28px; font-size: 1.1rem; line-height: 1.65; color: #4b5563; }
        .ht-contact__ways li { list-style: none !important; } .ht-contact__ways li::marker { content: none; } .ht-contact__ways li::before { content: none !important; }
        .ht-contact__ways { list-style: none; margin: 0 0 26px; padding: 0; display: grid; gap: 14px; }
        .ht-contact__way { display: flex; gap: 16px; align-items: center; padding: 16px 18px; border: 1px solid #eceff5; border-radius: 16px; background: #fff; color: inherit; text-decoration: none; transition: transform .25s, box-shadow .25s, border-color .25s; }
        a.ht-contact__way:hover { transform: translateY(-3px); box-shadow: 0 14px 30px rgba(20, 33, 61, .10); border-color: #F4C430; }
        .ht-contact__ico { flex: none; width: 48px; height: 48px; border-radius: 14px; background: #F4C430; display: grid; place-items: center; transition: transform .3s; }
        a.ht-contact__way:hover .ht-contact__ico { transform: rotate(-8deg) scale(1.08); }
        .ht-contact__way strong { display: block; color: #14213d; font-size: 1.02rem; }
        .ht-contact__way span { color: #5b6478; font-size: .95rem; word-break: break-word; }
        .ht-contact__faq { padding: 18px 20px; border-radius: 16px; background: #f9f9f9; font-size: .98rem; color: #4b5563; }
        .ht-contact__faq a { color: #14213d; font-weight: 700; border-bottom: 2px solid #F2D07F; text-decoration: none; }

        .ht-contact__card { padding: clamp(24px, 4vw, 40px); border-radius: 24px; background: #f9f9f9; box-shadow: 0 16px 44px rgba(20, 33, 61, .08); }
        .ht-contact__topics { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 22px; }
        .ht-contact__topics legend { width: 100%; margin: 0 0 8px; font-weight: 700; color: #14213d; font-size: 1rem; }
        .ht-contact__chip { padding: 8px 16px; border: 1.5px solid #dfe3ec; border-radius: 999px; background: #fff; color: #14213d; font-weight: 600; font-size: .92rem; cursor: pointer; transition: all .2s; }
        .ht-contact__chip:hover { border-color: #14213d; transform: translateY(-1px); }
        .ht-contact__chip[aria-pressed=true] { background: #14213d; border-color: #14213d; color: #fff; }
        .ht-contact__row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .ht-contact__field { margin: 0 0 18px; }
        .ht-contact__field label { display: flex; justify-content: space-between; margin: 0 0 7px; font-weight: 600; color: #14213d; }
        .ht-contact__field label small { color: #9aa2b5; font-weight: 400; }
        .ht-contact__field :is(input, textarea) { width: 100%; padding: 13px 16px; border: 1.5px solid #dfe3ec; border-radius: 12px; background: #fff; font: inherit; transition: border-color .2s, box-shadow .2s; }
        .ht-contact__field textarea { resize: vertical; min-height: 140px; }
        .ht-contact__field.has-error :is(input, textarea) { border-color: #d93025; }
        .ht-contact__err { display: block; min-height: 1.1em; margin-top: 5px; color: #d93025; font-size: .85rem; font-weight: 600; }
        .ht-contact__send { display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-width: 220px; padding: 15px 36px; border: 0; border-radius: 999px; background: #F4C430; color: #000; font-weight: 700; cursor: pointer; }
        .ht-contact__send[disabled] { opacity: .65; cursor: progress; }
        .ht-contact__spin { width: 16px; height: 16px; border: 2px solid rgba(0, 0, 0, .25); border-top-color: #000; border-radius: 50%; animation: ht-spin .7s linear infinite; }
        @keyframes ht-spin { to { transform: rotate(360deg); } }
        .ht-contact__ok { text-align: center; padding: 28px 8px; animation: ht-ok-in .6s cubic-bezier(.2, .8, .2, 1); }
        .ht-contact__tick { width: 72px; height: 72px; margin: 0 auto 18px; border-radius: 50%; background: #e6f4ea; display: grid; place-items: center; }
        .ht-contact__tick path { stroke-dasharray: 30; stroke-dashoffset: 30; animation: ht-draw .6s .25s ease forwards; }
        @keyframes ht-draw { to { stroke-dashoffset: 0; } }
        @keyframes ht-ok-in { from { opacity: 0; transform: scale(.94); } to { opacity: 1; transform: none; } }
        @media (max-width: 960px) { .ht-contact__grid { grid-template-columns: 1fr; } }
        @media (max-width: 560px) { .ht-contact__row { grid-template-columns: 1fr; } }
    </style>

    <div class="ht-contact__grid">
        <div class="ht-rise">
            <h2>Escríbenos, te ayudamos</h2>
            <p class="ht-contact__lead">¿Tienes una pregunta, una sugerencia o necesitas ayuda con tu cuenta? Elige el tema, cuéntanos qué pasa y nuestro equipo te responde por correo, normalmente en menos de 24 horas hábiles.</p>

            <ul class="ht-contact__ways">
                <li><a class="ht-contact__way" href="mailto:{{ $contactEmail }}">
                    <span class="ht-contact__ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#14213d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
                    <span><strong>Correo electrónico</strong><span>{{ $contactEmail }}</span></span></a></li>
                @if ($whatsapp)
                    <li><a class="ht-contact__way" href="{{ $whatsapp }}" target="_blank" rel="noopener">
                        <span class="ht-contact__ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#14213d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg></span>
                        <span><strong>WhatsApp</strong><span>Escríbenos directamente</span></span></a></li>
                @endif
                <li><div class="ht-contact__way">
                    <span class="ht-contact__ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#14213d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <span><strong>Tiempo de respuesta</strong><span>Menos de 24 horas hábiles (lunes a viernes)</span></span></div></li>
                @if ($address)
                    <li><div class="ht-contact__way">
                        <span class="ht-contact__ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#14213d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                        <span><strong>Dirección</strong><span>{{ $address }}</span></span></div></li>
                @endif
            </ul>

            <p class="ht-contact__faq">¿Buscas algo rápido? Quizá ya está en las <a href="{{ route('legal.faq') }}">preguntas frecuentes</a>. @if (\App\Services\AccessRequestService::enabled())Y si quieres entrar a la academia, <a href="{{ route('access.request') }}">solicita tu acceso</a>.@endif</p>
        </div>

        <div class="ht-contact__card ht-rise" style="--d: .12s">
            @if ($sent)
                <div class="ht-contact__ok" data-testid="contact-sent" role="status">
                    <div class="ht-contact__tick"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#137333" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.2 4.2L19 7"/></svg></div>
                    <h3 style="margin: 0 0 10px; font-size: 1.6rem; color: #14213d;">¡Mensaje enviado!</h3>
                    <p style="margin: 0 auto 22px; max-width: 42ch; color: #4b5563;">Gracias por escribirnos. Te responderemos a <strong>{{ $email ?: 'tu correo' }}</strong> lo antes posible.</p>
                    <button type="button" class="ht-contact__chip" wire:click="again">Enviar otro mensaje</button>
                </div>
            @else
                <form wire:submit.prevent="submit" novalidate data-testid="contact-form">
                    <fieldset class="ht-contact__topics" style="border: 0; padding: 0;">
                        <legend>¿Sobre qué nos escribes?</legend>
                        @foreach ($topics as $key => [$label])
                            <button type="button" class="ht-contact__chip" wire:click="pick('{{ $key }}')" aria-pressed="{{ $topic === $key ? 'true' : 'false' }}" data-testid="topic-{{ $key }}">{{ $label }}</button>
                        @endforeach
                    </fieldset>

                    <div class="ht-contact__row">
                        <div class="ht-contact__field @error('name') has-error @enderror">
                            <label for="ct-name">Nombre completo</label>
                            <input id="ct-name" type="text" wire:model="name" autocomplete="name" placeholder="Tu nombre" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}">
                            <span class="ht-contact__err" role="alert">{{ $errors->first('name') }}</span>
                        </div>
                        <div class="ht-contact__field @error('email') has-error @enderror">
                            <label for="ct-email">Correo electrónico</label>
                            <input id="ct-email" type="email" wire:model="email" autocomplete="email" placeholder="tucorreo@ejemplo.com" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                            <span class="ht-contact__err" role="alert">{{ $errors->first('email') }}</span>
                        </div>
                    </div>
                    <div class="ht-contact__field @error('subject') has-error @enderror">
                        <label for="ct-subject">Asunto</label>
                        <input id="ct-subject" type="text" wire:model="subject" maxlength="150" placeholder="¿En qué podemos ayudarte?">
                        <span class="ht-contact__err" role="alert">{{ $errors->first('subject') }}</span>
                    </div>
                    <div class="ht-contact__field @error('message') has-error @enderror" x-data="{ n: {{ mb_strlen($message) }} }">
                        <label for="ct-message">Mensaje <small><span x-text="n">{{ mb_strlen($message) }}</span> / 3000</small></label>
                        <textarea id="ct-message" wire:model="message" x-on:input="n = $event.target.value.length" rows="5" maxlength="3000" placeholder="Cuéntanos con detalle cómo podemos ayudarte…"></textarea>
                        <span class="ht-contact__err" role="alert">{{ $errors->first('message') }}</span>
                    </div>

                    <div style="position: absolute; left: -9999px;" aria-hidden="true"><label>No llenar<input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label></div>

                    @if (recaptchaEnabled('contact'))
                        <div class="ht-contact__field" wire:ignore>
                            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                            <div class="g-recaptcha" data-sitekey="{{ recaptchaSiteKey() }}" data-callback="onContactRecaptchaComplete"></div>
                            <script>function onContactRecaptchaComplete(r) { @this.set('recaptchaToken', r); }</script>
                        </div>
                        <span class="ht-contact__err" role="alert">{{ $errors->first('recaptchaToken') }}</span>
                    @endif

                    <button type="submit" class="ht-contact__send" wire:loading.attr="disabled" wire:target="submit" data-testid="contact-send">
                        <span wire:loading.remove wire:target="submit">Enviar mensaje</span>
                        <span wire:loading wire:target="submit" class="ht-contact__spin" aria-hidden="true"></span><span wire:loading wire:target="submit">Enviando…</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
