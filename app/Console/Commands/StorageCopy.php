<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StorageCopy extends Command
{
    protected $signature = 'storage:copy
        {from : Source disk (e.g. public)}
        {to : Destination disk (e.g. r2 or r2_private)}
        {--dirs=* : Directories to copy (default: whole disk unless --list/--file is used)}
        {--file=* : Individual file paths to copy}
        {--list= : Text file with one path per line to copy}
        {--rewrite-scorm : Move SCORM paths in the DB from storage/scorm/ID/.. to scorm/ID/.. (use when copying scorm/ to a remote private disk)}
        {--force : Overwrite files that already exist at the destination (e.g. to apply new cache headers)}
        {--dry-run : List what would be copied without writing}';

    protected $description = 'Copy files between filesystem disks (e.g. local -> Cloudflare R2). Never deletes the source.';

    public function handle(): int
    {
        $from = Storage::disk($this->argument('from'));
        $to = Storage::disk($this->argument('to'));
        $dry = (bool) $this->option('dry-run');

        $files = (array) $this->option('file');
        if ($list = $this->option('list')) {
            $files = array_merge($files, array_filter(array_map('trim', file($list, FILE_IGNORE_NEW_LINES) ?: [])));
        }
        $dirs = $this->option('dirs') ?: ($files ? [] : ['']);

        $paths = [];
        foreach ($dirs as $dir) {
            foreach ($from->allFiles($dir) as $path) {
                $paths[] = $path;
            }
        }
        foreach ($files as $path) {
            if ($from->exists($path)) {
                $paths[] = $path;
            } else {
                $this->warn("missing in source: $path");
            }
        }

        $copied = $skipped = $failed = 0;

        foreach (array_unique($paths) as $path) {
            if (str_starts_with($path, 'livewire-tmp/')) {
                continue;
            }
            if (!$this->option('force') && $to->exists($path) && $to->size($path) === $from->size($path)) {
                $skipped++;
                continue;
            }
            if ($dry) {
                $this->line("would copy $path");
                $copied++;
                continue;
            }
            try {
                $stream = $from->readStream($path);
                $to->writeStream($path, $stream, ['mimetype' => $from->mimeType($path) ?: 'application/octet-stream'] + (config('filesystems.disks.' . $this->argument('to') . '.options') ?? []));
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $copied++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("FAILED $path: " . $e->getMessage());
            }
        }

        if ($this->option('rewrite-scorm') && !$dry && $failed === 0) {
            $table = (config('courses.db_prefix') ?? 'courses_') . 'curriculums';
            $rows = DB::table($table)->where('type', 'scorm')->where('media_path', 'like', 'storage/scorm/%')->get(['id', 'media_path']);
            foreach ($rows as $row) {
                DB::table($table)->where('id', $row->id)->update(['media_path' => substr($row->media_path, strlen('storage/'))]);
            }
            $this->info('SCORM paths rewritten: ' . $rows->count());
        }

        $this->info(($dry ? 'Dry run: ' : '') . "copied=$copied skipped=$skipped failed=$failed");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
