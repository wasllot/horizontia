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
use App\Livewire\Pages\Admin\Menu\ManageMenu;
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
use Illuminate\Support\Facades\Route;



Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/insights', Insights::class)->name('insights');
    Route::get('/profile', AdminProfile::class)->name('profile');
    Route::get('/manage-menus', ManageMenu::class)->name('manage-menus');
    Route::get('/blogs', Blogs::class)->name('blog-listing');
    Route::get('/blogs/create', CreateBlog::class)->name('create-blog');
    Route::get('/blogs/update/{id}', UpdateBlog::class)->name('update-blog');
    Route::get('/blog-categories', BlogCategories::class)->name('blog-categories');
    Route::prefix('taxonomies')->name('taxonomy.')->group(function () {
        Route::get('languages', Languages::class)->name('languages');
        Route::get('subjects', Subjects::class)->name('subjects');
        Route::get('subject-groups', SubjectGroups::class)->name('subject-groups');
        Route::get('subjects', Subjects::class)->name('subjects');
        Route::get('subject-groups', SubjectGroups::class)->name('subject-groups');
    });
    Route::get('commission-settings',   CommissionSettings::class)->name('commission-settings');
    Route::get('payment-methods',       PaymentMethods::class)->name('payment-methods');
    Route::get('withdraw-requests',     WithdrawRequest::class)->name('withdraw-requests');

    Route::get('users',          Users::class)->name('users');
    Route::get('identity-verification',          IdentityVerification::class)->name('identity-verification');
    Route::get('bookings',          Bookings::class)->name('bookings');
    Route::get('invoices',          Invoices::class)->name('invoices');
    Route::get('email-settings',    EmailTemplates::class)->name('email-settings');
    Route::get('notification-settings', NotificationTemplates::class)->name('notification-settings');
    Route::get('users/approve-identity/{id}', [Users::class, 'approveUserIdentity'])->name('approve-user-identity');
    Route::get('upgrade', Upgrade::class)->name('upgrade');
    Route::get('upgrade/logs', [App\Http\Controllers\Admin\GeneralController::class, 'getUpgradeLogs'])->name('upgrade-logs');

    Route::post('update-sass-style',    [App\Http\Controllers\Admin\GeneralController::class, 'updateSaas']);
    Route::group(['prefix' => 'packages', 'as' => 'packages.'], function () {
        Route::get('', ManagePackages::class)->name('index');
        Route::get('installed', InstalledPackages::class)->name('installed');
        Route::post('upload', [GeneralController::class, 'uploadAddon'])->name('upload');
    });
    Route::get('disputes', Dispute::class)->name('disputes');
    Route::get('manage-dispute/{id}', ManageDispute::class)->name('manage-dispute');
    Route::get('clear-cache', [GeneralController::class, 'clearCache'])->name('clear-cache');
    Route::post('update-smtp-settings', [GeneralController::class, 'updateSMTPSettings'])->name('update-smtp-settings');
    Route::post('update-social-login-settings', [GeneralController::class, 'updateSocialLoginSettings'])->name('update-social-login-settings');
});
