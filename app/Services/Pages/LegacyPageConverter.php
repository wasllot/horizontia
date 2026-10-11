<?php

namespace App\Services\Pages;

use App\Models\SitePage;
use Larabuild\Pagebuilder\Models\Page;

/**
 * Converts a page of the old block builder into visual-editor content (HTML + CSS) so it can be edited with
 * GrapesJS. The legacy `settings` are kept untouched (the page can be rolled back by clearing gjs_html).
 * Dynamic data inside legacy blocks is frozen as static markup; use the editor's "Cursos destacados" block to replace it.
 */
class LegacyPageConverter
{
    public function convert(SitePage $site): bool
    {
        if ($site->usesVisualEditor() || empty($site->settings['grids'] ?? null)) {
            return false;
        }
        $legacy = Page::find($site->id);
        if (!$legacy) {
            return false;
        }

        setCurrentPageId($legacy->id);
        $html = view('pagebuilder::components.page-components', ['page' => $legacy])->render();

        $site->forceFill([
            'gjs_html' => PageHtmlSanitizer::html($html),
            'gjs_css' => '',
            'edited_at' => now(),
        ])->save();

        return true;
    }
}
