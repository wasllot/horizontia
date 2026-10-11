<?php

namespace App\Console\Commands;

use App\Models\SitePage;
use App\Services\Pages\LegacyPageConverter;
use Illuminate\Console\Command;

class PagesToVisualEditor extends Command
{
    protected $signature = 'pages:to-editor {--slug= : Convert only this page}';

    protected $description = 'Convert pages of the old block builder into visual-editor (GrapesJS) pages';

    public function handle(LegacyPageConverter $converter): int
    {
        $pages = SitePage::whereNull('gjs_html')->where('slug', '!=', '/')->when($this->option('slug'), fn ($q, $s) => $q->where('slug', $s))->get();
        $done = 0;
        foreach ($pages as $page) {
            if ($converter->convert($page)) {
                $this->info("+ {$page->slug}");
                $done++;
            }
        }
        $this->line("Páginas convertidas: {$done}");

        return self::SUCCESS;
    }
}
