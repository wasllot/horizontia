<main class="tb-main am-courses-system hz-bn">
    <header class="hz-seo__head">
        <div>
            <h2>Certificados <span class="hz-count">{{ $total }}</span></h2>
            <p>Se emiten solos cuando una persona termina un curso en línea. Cualquiera puede verificarlos con su código en <a href="{{ route('certificates.verify') }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', route('certificates.verify')) }}</a>. Si revocas uno, esa página lo mostrará como revocado.</p>
        </div>
        <input type="search" class="form-control" style="max-width: 280px" wire:model.live.debounce.300ms="search" placeholder="Buscar nombre, curso o código…" aria-label="Buscar certificados">
    </header>

    @if ($revokingId)
        <form class="hz-en__form" wire:submit="revoke" wire:key="revoke-form" data-testid="revoke-form">
            <strong>Revocar certificado</strong>
            <label for="rv-reason">Motivo (se muestra en la página pública)</label>
            <input id="rv-reason" class="form-control" wire:model="reason" maxlength="160" placeholder="Ej. Emitido por error">
            <small class="hz-en__err" aria-live="polite">{{ $errors->first('reason') }}</small>
            <div class="hz-en__formacts">
                <button type="submit" class="hz-btn hz-btn--sm">Revocar</button>
                <button type="button" class="hz-btn hz-btn--ghost hz-btn--sm" wire:click="cancelRevoke">Cancelar</button>
            </div>
        </form>
    @endif

    <div class="am-disputelist_wrap" wire:key="cert-list">
        <div class="am-disputelist">
            @if ($certificates->isNotEmpty())
                <table class="tb-table" data-testid="certificates-table">
                    <thead><tr><th class="hz-col-main">Persona</th><th>Curso</th><th>Código</th><th>Emitido</th><th>Estado</th><th><span class="hz-sr">Acciones</span></th></tr></thead>
                    <tbody>
                        @foreach ($certificates as $c)
                            <tr wire:key="c-{{ $c->id }}">
                                <td data-label="Persona"><strong>{{ $c->student_name }}</strong></td>
                                <td data-label="Curso">{{ $c->course_title }}</td>
                                <td data-label="Código">{{ $c->code }}</td>
                                <td data-label="Emitido">{{ dateEs($c->issued_at) }}</td>
                                <td data-label="Estado">@if ($c->isValid())<x-panel.badge tone="success">Vigente</x-panel.badge>@else<x-panel.badge tone="danger" :dot="false">Revocado</x-panel.badge>@endif</td>
                                <td data-label="Acciones">
                                    <div class="hz-acts">
                                        <a class="hz-act" href="{{ $c->url() }}" target="_blank" rel="noopener" title="Ver" aria-label="Ver certificado"><i class="icon-eye" aria-hidden="true"></i></a>
                                        @if ($c->isValid())
                                            <x-panel.action icon="icon-x-circle" label="Revocar" tone="danger" wire:click="askRevoke({{ $c->id }})" />
                                        @else
                                            <x-panel.action icon="icon-rotate-ccw" label="Restaurar" wire:click="restore({{ $c->id }})" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $certificates->links() }}
            @else
                <div class="hz-empty"><h3>{{ $search !== '' ? 'Sin resultados' : 'Aún no hay certificados' }}</h3><p>{{ $search !== '' ? 'Prueba con otra búsqueda.' : 'Aparecerán cuando alguien termine un curso en línea.' }}</p></div>
            @endif
        </div>
    </div>
</main>
