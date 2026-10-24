@if (!empty($onboarding))
    <section class="ht-onb" data-testid="onboarding" aria-labelledby="ht-onb-title">
        <style>
            .ht-onb { margin: 0 0 22px; padding: 20px 22px; border-radius: 18px; background: linear-gradient(135deg, #fffbe6, #fff); border: 1px solid #f1df8a; }
            .ht-onb__head { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; justify-content: space-between; margin-bottom: 14px; }
            .ht-onb__head h2 { margin: 0 0 2px; font-size: 1.15rem; color: #14213d; }
            .ht-onb__head p { margin: 0; color: #5b6478; font-size: .92rem; }
            .ht-onb__bar { width: 160px; height: 8px; border-radius: 99px; background: #eadf9c; overflow: hidden; }
            .ht-onb__bar i { display: block; height: 100%; border-radius: inherit; background: #14213d; transition: width .6s ease; }
            .ht-onb__list { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }
            .ht-onb__list li { display: grid; gap: 4px; padding: 12px 14px; border-radius: 12px; background: #fff; border: 1.5px solid #e6eaf2; }
            .ht-onb__list li.is-next { border-color: #fed304; box-shadow: 0 6px 16px rgba(254, 211, 4, .25); }
            .ht-onb__list li.is-done { background: #f1faf4; border-color: #cfe9d8; }
            .ht-onb__list strong { display: flex; gap: 8px; align-items: center; color: #14213d; font-size: .95rem; }
            .ht-onb__list li.is-done strong { color: #14703b; }
            .ht-onb__list small { color: #5b6478; line-height: 1.4; }
            .ht-onb__ico { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #c5cde0; flex: 0 0 20px; display: inline-flex; align-items: center; justify-content: center; }
            .ht-onb__list li.is-done .ht-onb__ico { background: #1a8a4a; border-color: #1a8a4a; color: #fff; }
            .ht-onb a.ht-onb__cta { margin-top: 6px; justify-self: start; padding: 6px 14px; border-radius: 9px; background: #fed304; color: #14213d; font-weight: 700; font-size: .85rem; text-decoration: none; }
            .ht-onb__close { border: 0; background: none; color: #5b6478; font-size: .85rem; text-decoration: underline; cursor: pointer; }
        </style>
        <div class="ht-onb__head">
            <div>
                <h2 id="ht-onb-title">Tus primeros pasos</h2>
                <p>{{ $onboarding['done'] }} de {{ $onboarding['total'] }} listos · lo que sigue te lleva a tu primer certificado.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="ht-onb__bar" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $onboarding['total'] }}" aria-valuenow="{{ $onboarding['done'] }}" aria-label="Primeros pasos completados"><i style="width: {{ round($onboarding['done'] / $onboarding['total'] * 100) }}%"></i></div>
                <button type="button" class="ht-onb__close" wire:click="dismissOnboarding" data-testid="onboarding-dismiss">Ocultar</button>
            </div>
        </div>
        <ol class="ht-onb__list">
            @foreach ($onboarding['steps'] as $step)
                <li @class(['is-done' => $step['done'], 'is-next' => !$step['done'] && $step['key'] === $onboarding['next']]) data-testid="onboarding-{{ $step['key'] }}">
                    <strong><span class="ht-onb__ico" aria-hidden="true">@if ($step['done'])<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>@endif</span>{{ $step['title'] }}<span class="hz-sr">{{ $step['done'] ? ' (hecho)' : ' (pendiente)' }}</span></strong>
                    <small>{{ $step['hint'] }}</small>
                    @if (!$step['done'] && $step['key'] === $onboarding['next'])
                        <a class="ht-onb__cta" href="{{ $step['url'] }}">{{ $step['cta'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </section>
@endif
