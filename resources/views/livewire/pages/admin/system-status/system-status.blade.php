<div class="hz-ss">
    <header class="hz-seo__head">
        <div>
            <h2>Estado del sistema</h2>
            <p>Revisión de lo que debe estar en orden antes de vender de verdad: seguridad, tareas automáticas, correo y cobros. Lo marcado como «pendiente para producción» es normal mientras desarrollas.</p>
        </div>
        <div class="hz-en__tools">
            <button type="button" class="hz-btn hz-btn--secondary hz-btn--sm" wire:click="$refresh"><i class="icon-refresh-cw" aria-hidden="true"></i> Volver a revisar</button>
            <button type="button" class="hz-btn hz-btn--sm" wire:click="backupNow" wire:loading.attr="disabled" wire:target="backupNow" data-testid="backup-now"><i class="icon-database" aria-hidden="true"></i> Respaldar ahora</button>
        </div>
    </header>

    @if ($backupMessage)<p class="hz-ss__msg" role="status">{{ $backupMessage }}</p>@endif

    <div class="hz-ss__summary" data-testid="ss-summary">
        <span class="hz-ss__pill is-ok">{{ $summary['ok'] }} bien</span>
        <span class="hz-ss__pill is-warn">{{ $summary['warn'] }} avisos</span>
        <span class="hz-ss__pill is-fail">{{ $summary['fail'] }} errores</span>
    </div>

    @foreach ($groups as $group => $items)
        <section class="hz-ss__group">
            <h3>{{ $group }}</h3>
            <ul>
                @foreach ($items as $c)
                    <li class="hz-ss__row is-{{ $c['status'] }}" data-testid="check-{{ $c['key'] }}">
                        <span class="hz-ss__icon" aria-hidden="true">{{ ['ok' => '✔', 'warn' => '!', 'fail' => '✖'][$c['status']] }}</span>
                        <div>
                            <strong>{{ $c['label'] }}</strong>
                            @if ($c['detail'])<small>{{ $c['detail'] }}</small>@endif
                            @if ($c['status'] !== 'ok' && $c['fix'])<small class="hz-ss__fix">→ {{ $c['fix'] }}</small>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
