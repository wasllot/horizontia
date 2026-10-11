<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Replaces a brand name in every text column of the database (settings, pages, menus, blogs, e-mail templates…).
 * The purchased theme shipped its demo content as "Lernen"; the academy is "Horizontia".
 *
 *   php artisan brand:rename                # Lernen -> Horizontia
 *   php artisan brand:rename --dry-run      # only report
 */
class RenameBrand extends Command
{
    protected $signature = 'brand:rename {from=Lernen} {to=Horizontia} {--dry-run}';

    protected $description = 'Replace a brand name inside every text column of the database';

    private const SKIP_TABLES = ['migrations', 'jobs', 'failed_jobs', 'sessions', 'cache', 'cache_locks', 'password_reset_tokens', 'personal_access_tokens', 'telescope_entries', 'telescope_entries_tags', 'telescope_monitoring', 'notifications'];

    /**
     * Plain text/JSON values are replaced in SQL. PHP-serialized values (`s:6:"Lernen"`) carry string lengths, so they are
     * unserialized, replaced recursively and serialized again; a blind REPLACE() would corrupt them (and break the page).
     */
    private function replaceIn(string $table, string $column, string $from, string $to): void
    {
        $hasId = \Illuminate\Support\Facades\Schema::hasColumn($table, 'id');
        $serialized = $hasId ? DB::table($table)->whereRaw("`{$column}` like binary ?", ["%{$from}%"])->where(fn ($q) => $q->where($column, 'like', 'a:%')->orWhere($column, 'like', 's:%')->orWhere($column, 'like', 'O:%')->orWhere($column, 'like', 'b:%'))->get(['id', $column]) : collect();

        foreach ($serialized as $row) {
            $value = @unserialize($row->{$column});
            if ($value === false && $row->{$column} !== 'b:0;') {
                continue;                                    // looks serialized but is not: leave for the SQL pass
            }
            $swap = function ($v) use (&$swap, $from, $to) {
                return is_string($v) ? str_replace($from, $to, $v) : (is_array($v) ? array_map($swap, $v) : $v);
            };
            DB::table($table)->where('id', $row->id)->update([$column => serialize($swap($value))]);
        }

        $skipIds = $serialized->pluck('id')->all();
        DB::table($table)->whereRaw("`{$column}` like binary ?", ["%{$from}%"])
            ->when($hasId && $skipIds, fn ($q) => $q->whereNotIn('id', $skipIds))
            ->update([$column => DB::raw('replace(`' . $column . '`, ' . DB::getPdo()->quote($from) . ', ' . DB::getPdo()->quote($to) . ')')]);
    }

    public function handle(): int
    {
        [$from, $to] = [$this->argument('from'), $this->argument('to')];
        $db = DB::getDatabaseName();
        $cols = DB::select("select table_name t, column_name c, data_type d from information_schema.columns where table_schema = ? and data_type in ('varchar','char','text','tinytext','mediumtext','longtext','json')", [$db]);

        $total = 0;
        foreach ($cols as $col) {
            if (in_array($col->t, self::SKIP_TABLES, true)) {
                continue;
            }
            $n = DB::table($col->t)->whereRaw("`{$col->c}` like binary ?", ["%{$from}%"])->count();   // case-sensitive: keys like `_lernen` stay
            if ($n === 0) {
                continue;
            }
            $this->line(sprintf('%-34s %-24s %d', $col->t, $col->c, $n));
            $total += $n;
            if (!$this->option('dry-run')) {
                $this->replaceIn($col->t, $col->c, $from, $to);
            }
        }

        if (!$this->option('dry-run')) {
            Cache::flush();
        }
        $this->info(($this->option('dry-run') ? 'Filas con la marca: ' : 'Filas actualizadas: ') . $total);

        return self::SUCCESS;
    }
}
