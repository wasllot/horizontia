<?php

namespace App\Console\Commands;

use App\Services\CourseCertificateService;
use Illuminate\Console\Command;

class IssueCertificates extends Command
{
    protected $signature = 'academy:issue-certificates';
    protected $description = 'Emite los certificados de quienes ya terminaron un curso antes de existir esta función (no repite los ya emitidos)';

    public function handle(CourseCertificateService $service): int
    {
        $this->info('Certificados emitidos: ' . $service->backfill());

        return self::SUCCESS;
    }
}
