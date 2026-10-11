<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SettingsRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Larabuild\Optionbuilder\Facades\Settings;

/**
 * Admin > Ajustes. A curated, Spanish screen (see SettingsRegistry) that replaces the theme's generic "Global Settings".
 *
 * Unlike the theme's controller, saving a page only touches the settings that page offers: values of other settings
 * (e.g. ones the code still reads but the screen no longer shows) are never wiped.
 */
class SettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.index', ['pages' => SettingsRegistry::pages()]);
    }

    public function update(Request $request)
    {
        if (isDemoSite()) {
            return $this->demo();
        }
        $page = SettingsRegistry::page((string) $request->input('page'));
        if (!$page) {
            return response()->json(['success' => ['type' => 'error', 'title' => 'Ajustes', 'message' => 'Esa página de ajustes no existe.']], 422);
        }

        parse_str((string) $request->input('data', ''), $form);
        foreach (SettingsRegistry::fieldsOf($page) as $id => ['section' => $section]) {
            if (array_key_exists($id, $form) && $form[$id] !== null) {
                Settings::set($section, $id, $form[$id]);
            } else {
                $this->forget($section, $id);   // cleared fields go back to their default
            }
        }
        $this->flush();

        return response()->json(['success' => ['type' => 'success', 'title' => '¡Listo!', 'message' => 'Ajustes guardados.']]);
    }

    public function reset(Request $request)
    {
        if (isDemoSite()) {
            return $this->demo();
        }
        $page = SettingsRegistry::page((string) $request->input('page'));
        if (!$page) {
            return response()->json(['success' => ['type' => 'error', 'title' => 'Ajustes', 'message' => 'Esa página de ajustes no existe.']], 422);
        }
        foreach (SettingsRegistry::fieldsOf($page) as $id => ['section' => $section]) {
            $this->forget($section, $id);
        }
        $this->flush();

        return response()->json(['success' => ['type' => 'success', 'title' => '¡Listo!', 'message' => 'Se restablecieron los valores originales de esta página.']]);
    }

    private function forget(string $section, string $key): void
    {
        DB::table(config('optionbuilder.db_prefix') . 'settings')->where('section', $section)->where('key', $key)->delete();
    }

    private function flush(): void
    {
        Cache::forget('optionbuilder__settings');
    }

    private function demo()
    {
        return response()->json(['success' => ['type' => 'error', 'title' => __('general.demosite_res_title'), 'message' => __('general.demosite_res_txt')]]);
    }
}
