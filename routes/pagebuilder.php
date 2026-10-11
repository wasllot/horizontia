<?php

use App\Http\Controllers\BuilderPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
| Pages are edited in Admin > Páginas (GrapesJS visual editor, routes in routes/admin.php).
| The package's old block-builder admin routes were removed on purpose; this catch-all only serves published pages
| (visual-editor pages and legacy block pages) through BuilderPageController.
*/
Route::get('/{any}', [BuilderPageController::class, 'show'])->where('any', '.*')->name('pagebuilder.page');
