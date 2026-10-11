<?php

namespace App\Console\Commands;

use App\Services\ConfigTransfer\ConfigTransfer;
use Illuminate\Console\Command;

class ExportSiteConfig extends Command
{
    protected $signature = 'academy:export {file? : Output path (default storage/app/exports/horizontia-config-DATE.json)}
        {--sections= : Comma separated sections (default: the recommended ones)} {--credentials : Include secret keys and credentials}';

    protected $description = 'Exports the site configuration (pages, menus, SEO, settings, templates…) to a JSON file. Never courses, blogs or users.';

    public function handle(ConfigTransfer $svc): int
    {
        $all = ConfigTransfer::sections();
        $sections = $this->option('sections')
            ? array_map('trim', explode(',', (string) $this->option('sections')))
            : collect($all)->filter(fn ($s) => $s['default'])->keys()->all();
        if ($bad = array_diff($sections, array_keys($all))) {
            $this->error('Secciones desconocidas: ' . implode(', ', $bad) . '. Válidas: ' . implode(', ', array_keys($all)));

            return self::FAILURE;
        }

        $payload = $svc->export($sections, ['credentials' => (bool) $this->option('credentials')]);
        $file = $this->argument('file') ?: storage_path('app/exports/horizontia-config-' . now()->format('Ymd-His') . '.json');
        @mkdir(dirname($file), 0700, true);
        file_put_contents($file, $svc->toJson($payload));
        @chmod($file, 0600);

        $this->info('Exportado: ' . $file);
        foreach ($svc->summarize($payload) as $k => $s) {
            $this->line("  {$s['label']}: {$s['count']}");
        }
        if ($payload['meta']['includes_credentials']) {
            $this->warn('El archivo contiene credenciales: guárdalo con cuidado.');
        }

        return self::SUCCESS;
    }
}
