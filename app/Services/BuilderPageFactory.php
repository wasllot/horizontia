<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Larabuild\Pagebuilder\Models\Page;

/**
 * Builds larabuild pages from plain PHP arrays, so pages can be seeded/tested without the visual editor.
 *
 *   BuilderPageFactory::upsert('nosotros', 'Nosotros', 'Nosotros', 'Descripción', [
 *       ['ht-hero', ['heading' => 'Hola']],
 *       ['ht-faq', ['items' => [['question' => '¿…?', 'answer' => '…']]]],
 *   ]);
 *
 * Every block becomes one full-width grid (12x1) holding one component, which is what the editor produces too.
 */
class BuilderPageFactory
{
    /** @param array<int, array{0:string,1?:array}> $blocks */
    public static function settings(array $blocks): array
    {
        $sectionData = [];
        $grids = [];

        foreach ($blocks as $position => $block) {
            [$section, $values] = [$block[0], $block[1] ?? []];
            $gridId = Str::random(10);
            $componentId = Str::random(10);

            $sectionData[$componentId] = ['settings' => self::encode($values)];
            $sectionData[$gridId] = ['styles' => [
                'content_width' => 'full_width', 'width-height-type' => 'px', 'width' => '', 'height' => '', 'min-width' => '', 'max-width' => '',
                'min-height' => '', 'max-height' => '', 'margin-type' => 'px', 'margin-top' => '', 'margin-right' => '', 'margin-bottom' => '',
                'margin-left' => '', 'padding-type' => 'px', 'padding-top' => '', 'padding-right' => '', 'padding-bottom' => '', 'padding-left' => '',
                'z-index' => '', 'classes' => '', 'background-size' => '', 'background-position' => '',
            ]];
            $grids[] = [
                'grid' => '12x1',
                'position' => $position + 1,
                'grid_id' => $gridId,
                'data' => [[['id' => $componentId, 'section_id' => $section, 'position' => 0]]],
            ];
        }

        return ['section_data' => $sectionData, 'grids' => $grids];
    }

    /** Create or fully replace the page identified by $slug. */
    public static function upsert(string $slug, string $name, string $title, ?string $description, array $blocks, string $status = 'published'): Page
    {
        $page = Page::firstOrNew(['slug' => $slug]);
        $page->fill([
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'settings' => self::settings($blocks),
        ])->save();

        Cache::forget('pagebuilder__pageData_' . $page->id);

        return $page;
    }

    /** Values -> the {value, is_array} envelope larabuild reads back through pagesetting(). */
    private static function encode(array $values): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $rows = [];
                foreach (array_values($value) as $row) {
                    $rows[Str::random(10)] = $row;
                }
                $out[$key] = ['value' => $rows, 'is_array' => '1'];
            } else {
                $out[$key] = ['value' => $value, 'is_array' => 0];
            }
        }

        return $out;
    }
}
