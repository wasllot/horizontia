<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BusinessReportService;
use Illuminate\Http\Request;

/** Descarga los reportes en CSV (UTF-8 con BOM para que Excel respete los acentos). */
class ReportExportController extends Controller
{
    public function __invoke(Request $request, string $type)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);
        $report = new BusinessReportService((string) $request->query('periodo', '30d'));
        $rows = match ($type) {
            'ventas' => $report->salesCsvRows(),
            'cursos' => $report->coursesCsvRows(),
            'aprendizaje' => $report->learningCsvRows(),
            default => abort(404),
        };

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => self::safe($v), $row));
            }
            fclose($out);
        }, 'horizontia-' . $type . '-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Una celda que empieza con = + - @ se interpretaría como fórmula en Excel: se neutraliza. */
    private static function safe(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}
