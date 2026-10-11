<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SettingsController;
use Larabuild\Optionbuilder\Http\Controllers\OptionBuilderController;

$routes = Route::controller(OptionBuilderController::class);

if (!empty(config('optionbuilder.url_prefix'))) {
    $routes = $routes->prefix(config('optionbuilder.url_prefix'));
}

if (!empty(config('optionbuilder.route_middleware'))) {
    $routes = $routes->middleware(config('optionbuilder.route_middleware'));
}

$routes->group(function () {
    // Pantalla de ajustes propia de la academia (en español, solo lo necesario); el selector de archivos del tema se conserva.
    Route::get('ajustes', [SettingsController::class, 'index'])->name('optionbuilder');
    Route::post('ajustes/guardar', [SettingsController::class, 'update'])->name('optionbuilder.update-section-settings');
    Route::post('ajustes/restablecer', [SettingsController::class, 'reset'])->name('optionbuilder.reset-section-settings');
    Route::post('option-builder/upload-files', 'uploadFiles')->name('optionbuilder.upload-files');
});
