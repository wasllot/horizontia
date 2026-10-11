<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Services\OnsiteNotifier;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database {--keep= : Days to keep (default ACADEMY_BACKUP_KEEP_DAYS or 14)}';

    protected $description = 'Compressed mysqldump in storage/app/backups (and BACKUP_DISK when set), old copies pruned';

    public function handle(): int
    {
        $db = config('database.connections.' . config('database.default'));
        if (($db['driver'] ?? '') !== 'mysql') {
            $this->error('Solo MySQL/MariaDB está soportado.');

            return self::FAILURE;
        }

        $dir = storage_path('app/backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $file = $dir . '/horizontia-' . now()->format('Ymd-His') . '.sql.gz';

        // The password goes in the environment, never in the command line (visible in `ps`).
        $cmd = sprintf(
            'set -o pipefail; mysqldump --single-transaction --quick --routines --triggers --no-tablespaces --default-character-set=utf8mb4 -h %s -P %s -u %s %s | gzip -9 > %s',
            escapeshellarg((string) $db['host']), escapeshellarg((string) ($db['port'] ?? 3306)), escapeshellarg((string) $db['username']), escapeshellarg((string) $db['database']), escapeshellarg($file)
        );
        $process = Process::fromShellCommandline('bash -c ' . escapeshellarg($cmd), null, ['MYSQL_PWD' => (string) ($db['password'] ?? '')], null, 600);
        $process->run();

        if (!$process->isSuccessful() || !is_file($file) || filesize($file) < 200) {
            @unlink($file);
            $msg = 'Falló el respaldo de la base de datos: ' . trim($process->getErrorOutput());
            Log::critical($msg);
            OnsiteNotifier::alertAdmins('Falló el respaldo de la base de datos', 'El respaldo automático no se pudo crear. Revisa el servidor (mysqldump, espacio en disco).');
            $this->error($msg);

            return self::FAILURE;
        }
        chmod($file, 0600);

        if ($disk = config('academy.backup_disk')) {
            $stream = fopen($file, 'r');
            Storage::disk($disk)->put('backups/' . basename($file), $stream);
            is_resource($stream) && fclose($stream);
        }

        $keep = (int) ($this->option('keep') ?: config('academy.backup_keep_days'));
        foreach (glob($dir . '/horizontia-*.sql.gz') ?: [] as $old) {
            if (filemtime($old) < now()->subDays($keep)->timestamp) {
                @unlink($old);
            }
        }

        Cache::forever('heartbeat:backup', now()->toIso8601String());
        $this->info('Respaldo creado: ' . basename($file) . ' (' . round(filesize($file) / 1024) . ' KB)');

        return self::SUCCESS;
    }
}
