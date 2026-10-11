<?php

use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\Frontend\SearchController;
use App\Http\Controllers\Impersonate;
use App\Http\Controllers\OpenAiController;
use App\Http\Controllers\SiteController;
use App\Livewire\Frontend\BlogDetails;
use App\Livewire\Frontend\Blogs;
use App\Livewire\Frontend\Checkout;
use App\Livewire\Frontend\ThankYou;
use App\Livewire\Pages\Common\Bookings\UserBooking;
use App\Livewire\Pages\Common\Dispute\Dispute;
use App\Livewire\Pages\Common\Dispute\ManageDispute;
use App\Livewire\Pages\Common\ProfileSettings\AccountSettings;
use App\Livewire\Pages\Common\ProfileSettings\IdentityVerification;
use App\Livewire\Pages\Common\ProfileSettings\PersonalDetails;
use App\Livewire\Pages\Common\ProfileSettings\Resume;
use App\Livewire\Pages\Student\BillingDetail\BillingDetail;
use App\Livewire\Pages\Student\CertificateList;
use App\Livewire\Pages\Student\Favourite\Favourites;
use App\Livewire\Pages\Student\Invoices;
use App\Livewire\Pages\Student\RescheduleSession;
use App\Livewire\Pages\Tutor\ManageAccount\ManageAccount;
use App\Livewire\Pages\Tutor\ManageSessions\ManageSubjects;
use App\Livewire\Pages\Tutor\ManageSessions\MyCalendar;
use App\Livewire\Pages\Tutor\ManageSessions\SessionDetail;
use App\Livewire\Payouts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// URLs antiguas (en inglés): 301 a la URL nueva en español, conservando parámetros y query string.
// Van primero para ganarle a comodines como `tutor/{slug}` o el catch-all del page builder.
foreach (require __DIR__ . '/legacy.php' as $oldUri => $routeName) {
    $route = Route::get($oldUri, fn (Request $request) => redirect()->route($routeName, $request->route()->parameters() + $request->query(), 301));
    if (str_contains($oldUri, '{path}')) {
        $route->where('path', '.*');
    }
}

// Static Home Page Override
Route::get('/', function () {
    $seoService = app(\App\Services\SeoService::class);
    $seoService->reset();
    $seoService
        ->setTitle('Horizontia | Cursos en línea con certificado')
        ->setDescription('Aprende a tu ritmo con cursos en línea de instructores expertos: lecciones en video, evaluaciones, certificado y tutor con IA. Pago seguro con Mercado Pago.')
        ->setKeywords('cursos en línea, academia en línea, cursos con certificado, aprender en línea, instructores expertos, tutor con IA')
        ->setCanonical(request()->fullUrl());

    return view('frontend.home-static', $seoService->getViewData());
})->middleware(['locale', 'maintenance'])->name('home');

Route::get('/eventos', [\App\Http\Controllers\EventsController::class, 'index'])->middleware(['locale', 'maintenance'])->name('events');
Route::view('/contacto', 'frontend.contacto')->middleware(['locale', 'maintenance'])->name('contacto');
Route::view('/nosotros', 'frontend.nosotros')->middleware(['locale', 'maintenance'])->name('about');

foreach (\App\Http\Controllers\PageController::PAGES as $routeName => [$uri]) {
    if (in_array($routeName, \App\Http\Controllers\PageController::DEDICATED, true)) {
        continue;
    }
    Route::get('/' . $uri, [\App\Http\Controllers\BuilderPageController::class, 'show'])->defaults('any', $uri)->middleware(['locale', 'maintenance'])->name($routeName);
}
Route::get('/curso/{slug}/informacion/{page}', [\App\Http\Controllers\BuilderPageController::class, 'course'])->middleware(['locale', 'maintenance'])->name('courses.info-page');
Route::get('/recordatorios/baja/{user}', function (\Illuminate\Http\Request $request, \App\Models\User $user) {
    \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['cart_reminders_off_at' => now()]);

    return response()->view('emails.cart-reminders-off');
})->middleware(['signed', 'throttle:20,1'])->name('cart-reminders.off');
Route::get('/recordatorios-de-avance/baja/{user}', function (\Illuminate\Http\Request $request, \App\Models\User $user) {
    \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['progress_reminders_off_at' => now()]);

    return response()->view('emails.progress-reminders-off');
})->middleware(['signed', 'throttle:20,1'])->name('progress-reminders.off');
Route::get('/identidad/{verification}/{field}', [\App\Http\Controllers\IdentityFileController::class, 'show'])->whereNumber('verification')->middleware(['auth', 'verified'])->name('identity.file');
Route::get('/panel/pulso', \App\Http\Controllers\PanelPulseController::class)->middleware(['auth', 'verified'])->name('panel.pulse');
Route::get('/verificar-certificado', [\App\Http\Controllers\CertificateController::class, 'verify'])->middleware('throttle:60,1')->name('certificates.verify');
Route::get('/certificado/{code}', [\App\Http\Controllers\CertificateController::class, 'show'])->where('code', '[A-Za-z0-9\-]{3,40}')->middleware('throttle:60,1')->name('certificates.show');
Route::middleware(['locale', 'maintenance'])->group(function () {
    Route::get('/rutas', [\App\Http\Controllers\LearningPathController::class, 'index'])->name('paths.index');
    Route::get('/ruta/{slug}', [\App\Http\Controllers\LearningPathController::class, 'show'])->name('paths.show');
});
Route::post('/aula-posicion', [\App\Http\Controllers\LessonPositionController::class, 'store'])->middleware(['auth', 'verified', 'throttle:240,1'])->name('courses.lesson-position');
Route::post('/zoom/webhook', [\App\Http\Controllers\ZoomWebhookController::class, 'handle'])->middleware('throttle:120,1')->name('zoom.webhook');
Route::get('/manifest.webmanifest', function () {
    abort_unless(config('academy.pwa', true), 404);
    $name = setting('_general.site_name') ?: config('app.name');

    return response()->json([
        'name' => $name, 'short_name' => \Illuminate\Support\Str::limit($name, 12, ''), 'description' => 'Tus cursos, tu avance y tus certificados en un solo lugar.',
        'lang' => 'es', 'dir' => 'ltr', 'start_url' => '/mis-cursos?origen=app', 'scope' => '/', 'id' => '/', 'display' => 'standalone', 'orientation' => 'portrait',
        'theme_color' => '#14213d', 'background_color' => '#fffdf6',
        'icons' => [
            ['src' => asset('images/pwa/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => asset('images/pwa/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => asset('images/pwa/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
        'shortcuts' => [
            ['name' => 'Mis cursos', 'url' => '/mis-cursos?origen=app', 'icons' => [['src' => asset('images/pwa/icon-192.png'), 'sizes' => '192x192']]],
            ['name' => 'Mis certificados', 'url' => '/estudiante/certificados?origen=app', 'icons' => [['src' => asset('images/pwa/icon-192.png'), 'sizes' => '192x192']]],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=3600'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
})->name('pwa.manifest');
Route::get('/sw.js', function () {
    abort_unless(config('academy.pwa', true), 404);

    return response(file_get_contents(public_path('js/sw.js')), 200, ['Content-Type' => 'application/javascript; charset=utf-8', 'Service-Worker-Allowed' => '/', 'Cache-Control' => 'no-cache']);
})->name('pwa.sw');
Route::get('/sin-conexion', fn () => response()->view('pwa.offline')->header('Cache-Control', 'no-store'))->name('pwa.offline');
Route::middleware(['auth', 'verified'])->group(function () {
    // Sustituye al chat del paquete LaraGuppy (ver AppServiceProvider): mismo nombre de ruta para que todos los enlaces sigan funcionando.
    Route::get('/messenger', \App\Livewire\Pages\Common\Messenger\Messenger::class)->name('laraguppy.messenger');
    Route::get('/mensajes/archivo/{message}', [\App\Http\Controllers\ChatFileController::class, 'show'])->whereNumber('message')->name('chat.file');
});
Route::get('/robots.txt', [\App\Http\Controllers\PageController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [\App\Http\Controllers\PageController::class, 'sitemap'])->name('sitemap');

Route::get('auth/{provider}', [SocialController::class, 'redirect'])->name('social.redirect');
Route::get('auth/{provider}/callback', [SocialController::class, 'callback'])->name('social.callback');

Route::middleware(['locale', 'maintenance'])->group(function () {
    Route::get('buscar-tutores', [SearchController::class, 'findTutors'])->middleware('marketplace:courses.search-courses')->name('find-tutors');
    Route::get('/blog', Blogs::class)->name('blogs');
    Route::get('/blog/{slug}', BlogDetails::class)->name('blog-details');

    Route::middleware(['auth', 'verified', 'onlineUser'])->group(function () {
        Route::post('/openai/submit', [OpenAiController::class, 'submit'])->name('openai.submit');
        Route::post('favourite-tutor', [SearchController::class, 'favouriteTutor'])->middleware('marketplace')->name('favourite-tutor');
        Route::get('cerrar-sesion', [SiteController::class, 'logout'])->name('logout');
        Route::get('usuario/confirmar-identidad/{id}', [PersonalDetails::class, 'confirmParentVerification'])->name('confirm-identity');
        Route::get('google/callback', [SiteController::class, 'getGoogleToken']);
        Route::middleware('role:tutor|student')->get('finalizar-compra', Checkout::class)->name('checkout');
        Route::middleware('role:tutor|student')->get('gracias/{id}', ThankYou::class)->name('thank-you');

        Route::middleware('role:tutor')->prefix('tutor')->name('tutor.')->group(function () {
            Route::get('panel', ManageAccount::class)->middleware('marketplace:courses.tutor.courses')->name('dashboard');
            Route::get('pagos', Payouts::class)->middleware('marketplace')->name('payouts');
            Route::get('perfil', fn() => redirect()->route('tutor.profile.personal-details'))->name('profile');

            Route::prefix('perfil')->name('profile.')->group(function () {
                Route::get('datos-personales', PersonalDetails::class)->name('personal-details');
                Route::get('ajustes-de-cuenta', AccountSettings::class)->name('account-settings');
                Route::prefix('curriculum')->name('resume.')->group(function () {
                    Route::get('educacion', Resume::class)->name('education');
                    Route::get('experiencia', Resume::class)->name('experience');
                    Route::get('certificados', Resume::class)->name('certificate');
                });
                Route::get('identificacion', IdentityVerification::class)->name('identification');
            });
            Route::prefix('reservas')->name('bookings.')->middleware('marketplace')->group(function () {
                Route::get('gestionar-materias', ManageSubjects::class)->name('subjects');
                Route::get('gestionar-sesiones', MyCalendar::class)->name('manage-sessions');
                Route::get('detalle-sesion/{date}', SessionDetail::class)->name('session-detail');
                Route::get('proximas-reservas', UserBooking::class)->name('upcoming-bookings');
            });
            Route::get('facturas', Invoices::class)->name('invoices');
            Route::get('disputas', Dispute::class)->middleware('marketplace')->name('disputes');
            Route::get('gestionar-disputa/{id}', ManageDispute::class)->middleware('marketplace')->name('manage-dispute');
        });

        Route::middleware('role:student')->prefix('estudiante')->name('student.')->group(function () {
            Route::get('perfil', fn() => redirect()->route('student.profile.personal-details'))->name('profile');
            Route::prefix('perfil')->name('profile.')->group(function () {
                Route::get('datos-personales', PersonalDetails::class)->name('personal-details');
                Route::get('ajustes-de-cuenta', AccountSettings::class)->name('account-settings');
                Route::get('identificacion', IdentityVerification::class)->name('identification');
            });
            Route::get('reservas', UserBooking::class)->middleware('marketplace:courses.course-list')->name('bookings');
            Route::get('facturas', Invoices::class)->name('invoices');
            Route::get('datos-de-facturacion', BillingDetail::class)->name('billing-detail');
            Route::get('favoritos', Favourites::class)->middleware('marketplace')->name('favourites');
            Route::get('reprogramar-sesion/{id}', RescheduleSession::class)->middleware('marketplace')->name('reschedule-session');
            Route::get('completar-reserva/{id}', [SiteController::class, 'completeBooking'])->middleware('marketplace')->name('complete-booking');
            Route::get('certificados', CertificateList::class)->name('certificate-list');
            Route::get('disputas', Dispute::class)->middleware('marketplace')->name('disputes');
            Route::get('gestionar-disputa/{id}', ManageDispute::class)->middleware('marketplace')->name('manage-dispute');
        });
    });

    Route::post('/remove-cart', [SiteController::class, 'removeCart']);

    Route::get('tutor/{slug}', [SearchController::class, 'tutorDetail'])->middleware('marketplace:courses.search-courses')->name('tutor-detail');
    Route::middleware('auth')->get('{gateway}/procesar-pago', [SiteController::class, 'processPayment'])->name('payment.process');
    Route::get('finalizar-compra/cancelado', function () {
        $user = auth()->user();
        $routeName = $user?->hasRole('tutor') ? 'tutor.invoices' : ($user?->hasRole('student') ? 'student.invoices' : 'home');
        return redirect()->route($routeName)->with('payment_cancel', __('general.payment_cancelled_desc'));
    })->name('checkout.cancel');
    Route::post('payfast/webhook', [SiteController::class, 'payfastWebhook'])->name('payfast.webhook');
    Route::post('pago/exito', [SiteController::class, 'paymentSuccess'])->name('post.success');
    Route::get('pago/exito', [SiteController::class, 'paymentSuccess'])->name('get.success');
    Route::post('switch-lang', [SiteController::class, 'switchLang'])->name('switch-lang');
    Route::post('switch-currency', [SiteController::class, 'switchCurrency'])->name('switch-currency');
    Route::get('salir-de-suplantacion', [Impersonate::class, 'exitImpersonate'])->name('exit-impersonate');
    Route::get('pagar/{id}', [SiteController::class, 'preparePayment'])->name('pay');

    require __DIR__ . '/auth.php';
    require __DIR__ . '/admin.php';
    require __DIR__ . '/optionbuilder.php';
    if (!request()->is('api/*')) {
        require __DIR__ . '/pagebuilder.php';
    }
});
