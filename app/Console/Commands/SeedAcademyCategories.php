<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Las clasificaciones de cursos de Horizontia (las eligió el cliente): cinco temáticas, cada una con una subcategoría «General»
 * (el curso pide categoría y subcategoría). Es seguro repetirlo: solo crea las que faltan.
 */
class SeedAcademyCategories extends Command
{
    public const THEMES = ['Bienestar y estilo de vida', 'Desarrollo Profesional y Laboral', 'Tecnología con aplicación práctica', 'Emprendimiento y negocios', 'Ciencias y artes'];

    protected $signature = 'academy:categories {--archive-others : Desactiva las demás categorías que ningún curso usa (nunca borra ni toca las que tienen cursos)}';

    protected $description = 'Crea las cinco temáticas de Horizontia como categorías de cursos';

    public function handle(): int
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'categories';
        $keep = [];
        foreach (self::THEMES as $theme) {
            $parent = DB::table($table)->whereNull('deleted_at')->whereNull('parent_id')->where('slug', Str::slug($theme))->first();
            $parentId = $parent->id ?? DB::table($table)->insertGetId(['name' => $theme, 'slug' => Str::slug($theme), 'parent_id' => null, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            DB::table($table)->where('id', $parentId)->update(['status' => 'active']);

            $childName = $theme . ' · General';
            $child = DB::table($table)->whereNull('deleted_at')->where('parent_id', $parentId)->where('slug', Str::slug($childName))->first();
            $childId = $child->id ?? DB::table($table)->insertGetId(['name' => $childName, 'slug' => Str::slug($childName), 'parent_id' => $parentId, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            DB::table($table)->where('id', $childId)->update(['status' => 'active']);
            array_push($keep, $parentId, $childId);
            $this->line(($parent ? '= ' : '+ ') . $theme);
        }

        if ($this->option('archive-others')) {
            $courses = (config('courses.db_prefix') ?? 'courses_') . 'courses';
            $used = DB::table($courses)->pluck('category_id')->merge(DB::table($courses)->pluck('sub_category_id'))->filter()->unique()->all();
            // Una categoría de la que cuelga una usada (la subcategoría de un curso) también se conserva.
            $parents = DB::table($table)->whereIn('id', $used)->pluck('parent_id')->filter()->all();
            $n = DB::table($table)->whereNull('deleted_at')->whereNotIn('id', array_merge($keep, $used, $parents))->where('status', 'active')->update(['status' => 'deactive', 'updated_at' => now()]);
            $this->info("Categorías desactivadas (sin cursos): {$n}");
        }

        return self::SUCCESS;
    }
}
