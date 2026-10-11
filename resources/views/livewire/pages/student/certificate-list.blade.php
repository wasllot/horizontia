<div class="am-dbbox am-invoicelist_wrap">
    <div class="am-dbbox_content am-invoicelist">
        <div class="am-dbbox_title">
            @slot('title')
                Mis certificados
            @endslot
            <h2>Mis certificados</h2>
        </div>
        <p class="hz-cert__hint">Se emiten solos cuando terminas un curso. Cada uno tiene un código con el que cualquiera puede comprobar que es auténtico en <a href="{{ route('certificates.verify') }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', route('certificates.verify')) }}</a>.</p>
        @if ($certificates->isNotEmpty())
            <div class="am-invoicetable">
                <table class="am-table @if(setting('_general.table_responsive') == 'yes') am-table-responsive @endif" data-testid="certificates-table">
                    <thead><tr><th>Curso</th><th>Emitido</th><th>Código</th><th>Acciones</th></tr></thead>
                    <tbody>
                        @foreach ($certificates as $c)
                            <tr wire:key="cert-{{ $c->id }}">
                                <td data-label="Curso"><span>{{ $c->course_title }}</span>@unless ($c->isValid()) <em>(revocado)</em>@endunless</td>
                                <td data-label="Emitido"><span>{{ dateEs($c->issued_at) }}</span></td>
                                <td data-label="Código"><span>{{ $c->code }}</span></td>
                                <td data-label="Acciones">
                                    <div class="am-invoicetable_actions">
                                        <a href="{{ $c->url() }}" target="_blank" rel="noopener" class="am-custom-tooltip" aria-label="Ver certificado de {{ $c->course_title }}">
                                            <span class="am-tooltip-text"><span>Ver / imprimir</span></span><i class="am-icon-eye-open-01"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-no-record :image="asset('images/payouts.png')" title="Aún no tienes certificados" description="Termina un curso al 100 % y tu certificado aparecerá aquí automáticamente." />
        @endif
    </div>
</div>
