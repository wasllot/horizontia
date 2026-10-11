<?php

namespace App\Console\Commands;

use App\Services\ConfigTransfer\ConfigTransfer;
use App\Services\ConfigTransfer\ConfigTransferException;
use Illuminate\Console\Command;

class ImportSiteConfig extends Command
{
    protected $signature = 'academy:import {file : Exported JSON file}
        {--sections= : Comma separated sections (default: all of the file except sensitive ones)}
        {--mode=skip : skip (only add what is missing) or overwrite} {--dry-run : Show what would happen without saving}';

    protected $description = 'Imports a configuration file made with academy:export (or the admin screen).';

    public function handle(ConfigTransfer $svc): int
    {
        $file = $this->argument('file');
        if (!is_file($file)) {
            $this->error("No existe el archivo: {$file}");

            return self::FAILURE;
        }
        try {
            $payload = $svc->parse((string) file_get_contents($file));
        } catch (ConfigTransferException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $labels = ConfigTransfer::sections();
        $sections = $this->option('sections')
            ? array_map('trim', explode(',', (string) $this->option('sections')))
            : collect($svc->summarize($payload))->filter(fn ($s, $k) => !$labels[$k]['sensitive'])->keys()->all();
        $mode = $this->option('mode') === 'overwrite' ? ConfigTransfer::MODE_OVERWRITE : ConfigTransfer::MODE_SKIP;

        $report = $svc->import($payload, $sections, $mode, ['dry' => (bool) $this->option('dry-run')]);

        $this->info($report['dry'] ? 'Vista previa (no se guardó nada):' : 'Importado:');
        foreach ($report['sections'] as $k => $r) {
            $this->line(sprintf('  %-32s creados %d · actualizados %d · omitidos %d', $labels[$k]['label'], $r['created'], $r['updated'], $r['skipped']));
            foreach ($r['notes'] as $n) {
                $this->line("      {$n}");
            }
        }
        foreach ($report['warnings'] as $w) {
            $this->warn($w);
        }

        return self::SUCCESS;
    }
}
