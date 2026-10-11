<table class="tb-table hz-ct__report" data-testid="report-table">
    <thead><tr><th class="hz-col-main">Sección</th><th>{{ $report['dry'] ? 'Se crearían' : 'Creados' }}</th><th>{{ $report['dry'] ? 'Se actualizarían' : 'Actualizados' }}</th><th>Omitidos</th></tr></thead>
    <tbody>
        @foreach ($report['sections'] as $key => $r)
            <tr>
                <td data-label="Sección"><strong>{{ $sections[$key]['label'] ?? $key }}</strong>@foreach ($r['notes'] as $note)<br><small>{{ $note }}</small>@endforeach</td>
                <td data-label="Creados">{{ $r['created'] }}</td>
                <td data-label="Actualizados">{{ $r['updated'] }}</td>
                <td data-label="Omitidos">{{ $r['skipped'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@foreach ($report['warnings'] as $w)
    <p class="hz-ct__alert">{{ $w }}</p>
@endforeach
