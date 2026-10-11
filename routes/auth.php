<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    // Volt::route('register', 'pages.auth.register')->name('register');
    // Nadie se registra solo: se pide acceso y el equipo lo aprueba (ver App\Services\AccessRequestService).
    Route::redirect('/registro', '/solicitar-acceso')->name('register');
    Route::view('solicitar-acceso', 'frontend.solicitar-acceso')->middleware(['locale', 'maintenance'])->name('access.request');
    Volt::route('iniciar-sesion', 'pages.auth.login')->name('login');
    Volt::route('olvide-mi-contrasena', 'pages.auth.forgot-password')->name('password.request');
    Volt::route('restablecer-contrasena/{token}', 'pages.auth.reset-password')->name('password.reset');
});

// Enlace del correo de bienvenida: de un solo uso, vence en 7 días; lleva a elegir la contraseña.
Route::get('activar-cuenta/{request}', function (\Illuminate\Http\Request $http, int $request) {
    $access = \App\Models\AccessRequest::where('status', 'approved')->whereNotNull('user_id')->findOrFail($request);
    if ($access->activated_at) {
        return redirect()->route('password.request')->with('status', 'Este enlace ya se usó. Si no recuerdas tu contraseña, pide una nueva aquí con tu correo.');
    }
    $user = \App\Models\User::findOrFail($access->user_id);
    $access->update(['activated_at' => now()]);

    return redirect()->route('password.reset', ['token' => \Illuminate\Support\Facades\Password::broker()->createToken($user), 'email' => $user->email]);
})->middleware(['signed', 'throttle:20,1'])->name('account.activate');

Volt::route('perfil-social', 'pages.auth.social-profile')->name('social-profile');

Route::middleware('auth')->group(function () {

    
    Volt::route('verificar-correo', 'pages.auth.verify-email')->name('verification.notice');
    Route::get('verificar-correo/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Volt::route('confirmar-contrasena', 'pages.auth.confirm-password')->name('password.confirm');
});
