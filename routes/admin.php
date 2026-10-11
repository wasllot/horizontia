<?php

use App\Http\Controllers\Admin\GeneralController;
use App\Livewire\Pages\Admin\Blogs\BlogCategories;
use App\Livewire\Pages\Admin\Blogs\Blogs;
use App\Livewire\Pages\Admin\Blogs\CreateBlog;
use App\Livewire\Pages\Admin\Blogs\UpdateBlog;
use App\Livewire\Pages\Admin\Bookings\Bookings;
use App\Livewire\Pages\Admin\Dispute\Dispute;
use App\Livewire\Pages\Admin\Dispute\ManageDispute;
use App\Livewire\Pages\Admin\EmailTemplates\EmailTemplates;
use App\Livewire\Pages\Admin\IdentityVerification\IdentityVerification;
use App\Livewire\Pages\Admin\Insights\Insights;
use App\Livewire\Pages\Admin\Invoices\Invoices;
use App\Livewire\Pages\Admin\NotificationTemplates\NotificationTemplates;
use App\Livewire\Pages\Admin\Packages\ManagePackages;
use App\Livewire\Pages\Admin\Packages\InstalledPackages;
use App\Livewire\Pages\Admin\Payments\CommissionSettings;
use App\Livewire\Pages\Admin\Payments\PaymentMethods;
use App\Livewire\Pages\Admin\Payments\WithdrawRequest;
use App\Livewire\Pages\Admin\Profile\AdminProfile;
use App\Livewire\Pages\Admin\Taxonomy\Languages;
use App\Livewire\Pages\Admin\Taxonomy\SubjectGroups;
use App\Livewire\Pages\Admin\Taxonomy\Subjects;
use App\Livewire\Pages\Admin\Upgrade\Upgrade;
use App\Livewire\Pages\Admin\Users\Users;
use App\Livewire\Pages\Admin\Seo\SeoManager;
use App\Livewire\Pages\Admin\Leads\Leads;
use Illuminate\Support\Facades\Route;



Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/estadisticas', Insights::class)->name('insights');
    Route::get('/perfil', AdminProfile::class)->name('profile');
    Route::get('/menus', \App\Livewire\Pages\Admin\Menu\MenuBuilder::class)->name('manage-menus');
    Route::get('/seo', SeoManager::class)->name('seo');
    // Page editor (GrapesJS)
    Route::get('/paginas', \App\Livewire\Pages\Admin\Pages\PagesManager::class)->name('pages');
    Route::get('/paginas/{page}/editor', [\App\Http\Controllers\Admin\PageEditorController::class, 'editor'])->name('pages.editor');
    Route::get('/paginas/{page}/proyecto', [\App\Http\Controllers\Admin\PageEditorController::class, 'project'])->name('pages.project');
    Route::put('/paginas/{page}/proyecto', [\App\Http\Controllers\Admin\PageEditorController::class, 'save'])->name('pages.save');
    Route::put('/paginas/{page}/datos', [\App\Http\Controllers\Admin\PageEditorController::class, 'meta_update'])->name('pages.meta');
    Route::get('/paginas/{page}/historial', [\App\Http\Controllers\Admin\PageEditorController::class, 'revisions'])->name('pages.revisions');
    Route::get('/paginas/{page}/historial/{revision}', [\App\Http\Controllers\Admin\PageEditorController::class, 'restore'])->name('pages.restore');
    Route::get('/paginas/{page}/bloque-vista', [\App\Http\Controllers\Admin\PageEditorController::class, 'blockPreview'])->name('pages.block-preview');
    Route::post('/paginas/medios', [\App\Http\Controllers\Admin\PageEditorController::class, 'media'])->name('pages.media');
    Route::get('rutas-de-aprendizaje', \App\Livewire\Pages\Admin\LearningPaths\LearningPaths::class)->name('learning-paths');
    Route::get('certificados', \App\Livewire\Pages\Admin\Certificates\Certificates::class)->name('certificates');
    Route::get('reportes', \App\Livewire\Pages\Admin\Reports\Reports::class)->name('reports');
    Route::get('reportes/exportar/{type}', \App\Http\Controllers\Admin\ReportExportController::class)->whereIn('type', ['ventas', 'cursos', 'aprendizaje'])->name('reports.export');
    Route::get('estudio-de-contenido', \App\Livewire\Pages\Common\ContentStudio\ContentStudio::class)->name('content-studio');
    Route::get('comunidad', \App\Livewire\Pages\Admin\Community\Moderation::class)->name('community');
    Route::get('solicitudes-de-acceso', \App\Livewire\Pages\Admin\AccessRequests\AccessRequests::class)->name('access-requests');
    Route::get('cupones', \App\Livewire\Pages\Admin\Coupons\Coupons::class)->name('coupons');
    Route::get('/estado-del-sistema', \App\Livewire\Pages\Admin\SystemStatus\SystemStatus::class)->name('system-status');
    Route::get('/banners-de-paginas', \App\Livewire\Pages\Admin\PageBanners\PageBanners::class)->name('page-banners');
    Route::get('/paginas-de-curso', \App\Livewire\Pages\Admin\CoursePages\CoursePages::class)->name('course-pages');
    Route::get('/blog', Blogs::class)->name('blog-listing');
    Route::get('/blog/crear', CreateBlog::class)->name('create-blog');
    Route::get('/blog/editar/{id}', UpdateBlog::class)->name('update-blog');
    Route::get('/blog/categorias', BlogCategories::class)->name('blog-categories');
    Route::prefix('taxonomias')->name('taxonomy.')->group(function () {
        Route::get('idiomas', Languages::class)->name('languages');
        Route::get('materias', Subjects::class)->middleware('marketplace')->name('subjects');
        Route::get('grupos-de-materias', SubjectGroups::class)->middleware('marketplace')->name('subject-groups');
    });
    Route::get('comisiones',   CommissionSettings::class)->middleware('marketplace')->name('commission-settings');
    Route::get('metodos-de-pago',       PaymentMethods::class)->name('payment-methods');
    Route::get('solicitudes-de-retiro',     WithdrawRequest::class)->middleware('marketplace')->name('withdraw-requests');

    Route::get('usuarios',          Users::class)->name('users');
    Route::get('verificacion-de-identidad',          IdentityVerification::class)->name('identity-verification');
    Route::get('reservas',          Bookings::class)->middleware('marketplace')->name('bookings');
    Route::get('facturas',          Invoices::class)->name('invoices');
    Route::get('contactos',             Leads::class)->name('leads');
    Route::get('exportar-importar', \App\Livewire\Pages\Admin\ConfigTransfer\ConfigTransferPage::class)->name('config-transfer');
    Route::get('ajustes-de-correo',    EmailTemplates::class)->name('email-settings');
    Route::get('ajustes-de-notificaciones', NotificationTemplates::class)->name('notification-settings');
    // NOTE: this link is emailed to admins (see NotificationService::approve_identity_link) but
    // previously pointed at a Livewire "controller action" (Users::approveUserIdentity) that does
    // not exist, causing a 500 (BadMethodCallException via Livewire's magic __call) for every
    // admin who clicked it. The real approve action lives on the identity-verification listing
    // page (IdentityVerification::verifiedTemplate, invoked via wire:click), which has no support
    // for deep-linking/pre-selecting a single user id, so route this link to that working page
    // instead of a non-existent action rather than guess at new deep-link business logic.
    Route::get('usuarios/aprobar-identidad/{id}', function () {
        return redirect()->route('admin.identity-verification');
    })->name('approve-user-identity');
    Route::get('actualizar', Upgrade::class)->name('upgrade');
    Route::get('actualizar/registros', [App\Http\Controllers\Admin\GeneralController::class, 'getUpgradeLogs'])->name('upgrade-logs');

    Route::post('update-sass-style',    [App\Http\Controllers\Admin\GeneralController::class, 'updateSaas']);
    Route::group(['prefix' => 'paquetes', 'as' => 'packages.'], function () {
        Route::get('', ManagePackages::class)->name('index');
        Route::get('instalados', InstalledPackages::class)->name('installed');
        Route::post('upload', [GeneralController::class, 'uploadAddon'])->name('upload');
    });
    Route::get('disputas', Dispute::class)->middleware('marketplace')->name('disputes');
    Route::get('gestionar-disputa/{id}', ManageDispute::class)->middleware('marketplace')->name('manage-dispute');
    Route::get('limpiar-cache', [GeneralController::class, 'clearCache'])->name('clear-cache');
    Route::post('update-smtp-settings', [GeneralController::class, 'updateSMTPSettings'])->name('update-smtp-settings');
    Route::post('update-social-login-settings', [GeneralController::class, 'updateSocialLoginSettings'])->name('update-social-login-settings');
});
