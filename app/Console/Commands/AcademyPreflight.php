<?php

namespace App\Console\Commands;

use App\Services\Preflight;
use Illuminate\Console\Command;

class AcademyPreflight extends Command
{
    protected $signature = 'academy:preflight {--no-dns : Skip the SPF/DMARC lookups}';

    protected $description = 'Checks that this installation is ready to sell (security, operation, e-mail, payments). Exit code 1 when something is wrong.';

    public function handle(): int
    {
        $checks = (new Preflight(!$this->option('no-dns')))->run();
        $icons = [Preflight::OK => '<info>✔</info>', Preflight::WARN => '<comment>!</comment>', Preflight::FAIL => '<error>✖</error>'];

        $group = null;
        foreach ($checks as $c) {
            if ($c['group'] !== $group) {
                $group = $c['group'];
                $this->newLine();
                $this->line("<options=bold>{$group}</>");
            }
            $this->line(" {$icons[$c['status']]} {$c['label']}" . ($c['detail'] ? " — {$c['detail']}" : ''));
            if ($c['status'] !== Preflight::OK && $c['fix']) {
                $this->line("     → {$c['fix']}");
            }
        }

        $s = Preflight::summary($checks);
        $this->newLine();
        $this->line("{$s['ok']} bien · {$s['warn']} avisos · {$s['fail']} errores");

        return $s['fail'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
