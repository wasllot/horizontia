<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;


$roleInfo = getUserRole();

$role = !empty($roleInfo) ? $roleInfo['roleName'] : '';

$disputeId = request()->route('id') ?? null;
Breadcrumbs::for('tutor.dashboard', function (BreadcrumbTrail $trail) {
    $trail->push(__('general.dashboard'), route('tutor.dashboard'));
    $trail->push(__('general.my_earnings'), route('tutor.dashboard'));
});

if($role != 'admin'){
    Breadcrumbs::for($role.'.profile', function (BreadcrumbTrail $trail) use ($role): void {
        $trail->push(__('general.profile_settings'), route($role.'.profile.personal-details'));
    });

    Breadcrumbs::for($role.'.profile.personal-details', function (BreadcrumbTrail $trail) use ($role): void {
        $trail->parent($role.'.profile');
        $trail->push(__('profile.personal_details'), route($role.'.profile.personal-details'));
    });

    Breadcrumbs::for($role.'.profile.account-settings', function (BreadcrumbTrail $trail) use ($role): void {
        $trail->parent($role.'.profile');
        $trail->push(__('profile.account_settings'), route($role.'.profile.account-settings'));
    });
}

Breadcrumbs::for('tutor.profile.resume.education', function (BreadcrumbTrail $trail) {
    $trail->parent('tutor.profile');
    $trail->push(__('education.title'), route('tutor.profile.resume.education'));
});

Breadcrumbs::for('tutor.profile.resume.experience', function (BreadcrumbTrail $trail) {
    $trail->parent('tutor.profile');
    $trail->push(__('experience.title'), route('tutor.profile.resume.experience'));
});

Breadcrumbs::for('tutor.profile.resume.certificate', function (BreadcrumbTrail $trail) {
    $trail->parent('tutor.profile');
    $trail->push(__('certificate.certificate_wards'), route('tutor.profile.resume.certificate'));
});

Breadcrumbs::for($role.'.profile.identification', function (BreadcrumbTrail $trail) use($role){
    $trail->parent($role.'.profile');
    $trail->push(__('identity.title'), route($role.'.profile.identification'));
});


Breadcrumbs::for('tutor.bookings', function (BreadcrumbTrail $trail) {
    $trail->push(__('general.manage_bookings'), route('tutor.bookings.subjects'));
});

Breadcrumbs::for('student.tuition-settings', function (BreadcrumbTrail $trail) {
    $trail->push(__('sidebar.tuition_settings'), route('student.tuition-settings'));
});

Breadcrumbs::for('tutor.bookings.subjects', function (BreadcrumbTrail $trail) {
    $trail->parent('tutor.bookings');
    $trail->push(__('subject.subject_title'), route('tutor.bookings.subjects'));
});

Breadcrumbs::for('tutor.bookings.tuition-settings', function (BreadcrumbTrail $trail) {
    $trail->parent('tutor.bookings');
    $trail->push(__('sidebar.tuition_settings'), route('tutor.bookings.tuition-settings'));
});

Breadcrumbs::for('tutor.bookings.manage-sessions', function (BreadcrumbTrail $trail) {
    $trail->parent('tutor.bookings');
    $trail->push(__('calendar.title'), route('tutor.bookings.manage-sessions'));
});

Breadcrumbs::for('tutor.bookings.upcoming-bookings', function (BreadcrumbTrail $trail) {
    $trail->parent('tutor.bookings');
    $trail->push(__('sidebar.upcomming_bookings'), route('tutor.bookings.upcoming-bookings'));
});

Breadcrumbs::for('tutor.bookings.session-detail', function (BreadcrumbTrail $trail) {
    $trail->parent('tutor.bookings');
    $trail->push(__('calendar.title'), route('tutor.bookings.manage-sessions'));
});

Breadcrumbs::for('student.billing-detail', function (BreadcrumbTrail $trail) {
    $trail->push(__('sidebar.billing_detail'), route('student.billing-detail'));
});

Breadcrumbs::for('student.bookings', function (BreadcrumbTrail $trail) {
    $trail->push(__('sidebar.bookings'), route('student.bookings'));
});

Breadcrumbs::for($role.'.invoices', function (BreadcrumbTrail $trail) use ($role) {
    $trail->push(__('sidebar.invoices'), route($role.'.invoices'));
});

Breadcrumbs::for('student.favourites', function (BreadcrumbTrail $trail) {
    $trail->push(__('sidebar.favourites'), route('student.favourites'));
});
Breadcrumbs::for('student.reschedule-session', function (BreadcrumbTrail $trail) {
    $trail->push(__('sidebar.bookings'), route('student.bookings'));
    $trail->push(__('calendar.reschedule_session'));
});

Breadcrumbs::for('laraguppy.messenger', function (BreadcrumbTrail $trail) use ($role) {
    if($role == 'tutor'){
        $trail->push(__('general.dashboard'), route('tutor.dashboard'));
    }
    $trail->push(__('sidebar.messages'), route('laraguppy.messenger'));
});

Breadcrumbs::for('tutor.payouts', function (BreadcrumbTrail $trail) use ($role) {
    $trail->push(__('general.dashboard'), route('tutor.dashboard'));
    $trail->push(__('tutor.payouts_history'), route('tutor.payouts'));
});
Breadcrumbs::for($role.'.disputes', function (BreadcrumbTrail $trail) use ($role) {
    if($role == 'admin'){
        $trail->parent('admin.insights');
    }
    $trail->push(__('sidebar.disputes_system'), route($role.'.disputes'));
});

Breadcrumbs::for($role.'.manage-dispute', function (BreadcrumbTrail $trail) use ($role, $disputeId) {
    $trail->parent($role.'.disputes');
    $trail->push(__('sidebar.manage-dispute'), route($role.'.manage-dispute', ['id' => $disputeId]));
});

Breadcrumbs::for('admin.insights', function (BreadcrumbTrail $trail) {
    $trail->push(__('general.insights'), route('admin.insights'));
});

Breadcrumbs::for('admin.manage-menus', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.manage_menus'), route('admin.manage-menus'));
});

Breadcrumbs::for('optionbuilder', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.global_settings'), route('optionbuilder'));
});

Breadcrumbs::for('pagebuilder', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.site_pages'), route('pagebuilder'));
});

Breadcrumbs::for('admin.email-settings', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.email_settings'), route('admin.email-settings'));
});

Breadcrumbs::for('admin.taxonomy.languages', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.languages'), route('admin.taxonomy.languages'));
});

Breadcrumbs::for('admin.taxonomy.subjects', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.subjects'), route('admin.taxonomy.subjects'));
});

Breadcrumbs::for('admin.taxonomy.subject-groups', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.subject_groups'), route('admin.taxonomy.subject-groups'));
});

Breadcrumbs::for('ltu.translation.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.translations'), route('ltu.translation.index'));
});

Breadcrumbs::for('admin.packages', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.packages'), route('admin.packages'));
});

Breadcrumbs::for('admin.upgrade', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.upgrade'), route('admin.upgrade'));
});

Breadcrumbs::for('admin.users', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.users'), route('admin.users'));
});

Breadcrumbs::for('admin.identity-verification', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.identity_verification'), route('admin.identity-verification'));
});

Breadcrumbs::for('admin.bookings', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.bookings'), route('admin.bookings'));
});

Breadcrumbs::for('admin.withdraw-requests', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.withdraw_requests'), route('admin.withdraw-requests'));
});

Breadcrumbs::for('admin.commission-settings', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.commission_settings'), route('admin.commission-settings'));
});

Breadcrumbs::for('admin.payment-methods', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.payment_methods'), route('admin.payment-methods'));
});

Breadcrumbs::for('admin.create-blog', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.create_blog'), route('admin.create-blog'));
});

Breadcrumbs::for('admin.blog-listing', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.blog_listing'), route('admin.blog-listing'));
});

Breadcrumbs::for('admin.blog-categories', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.blog_categories'), route('admin.blog-categories'));
});

if(\Nwidart\Modules\Facades\Module::has('forumwise') &&\Nwidart\Modules\Facades\Module::isEnabled('forumwise')){
    Breadcrumbs::for('forums', function (BreadcrumbTrail $trail) use ($role) {
        if($role == 'admin'){       
            $trail->parent('admin.insights');
        }
        $trail->push(__('general.forums'), route('forums'));
    });
}

Breadcrumbs::for('admin.profile', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.insights');
    $trail->push(__('general.profile'), route('admin.profile'));
});

if(\Nwidart\Modules\Facades\Module::has('starup') &&\Nwidart\Modules\Facades\Module::isEnabled('starup')){
    Breadcrumbs::for('badge-list', function (BreadcrumbTrail $trail) {
        $trail->parent('admin.insights');
        $trail->push(__('starup::starup.badges'), route('badge-list'));
    });
}
    
if(\Nwidart\Modules\Facades\Module::has('forumwise') &&\Nwidart\Modules\Facades\Module::isEnabled('forumwise')){
    Breadcrumbs::for('categories', function (BreadcrumbTrail $trail) {
        $trail->parent('forums');
        $trail->push(__('general.categories'), route('categories'));
    });
}
