<?php

use Illuminate\Support\Facades\Route;
use Modules\LaraPayease\Http\Controllers\StripePaymentController;
use Modules\LaraPayease\Http\Controllers\MercadoPagoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::post('payease/stripe', [StripePaymentController::class, 'prepareCharge'])->name('payease.stripe');
Route::post('mercadopago/webhook', [MercadoPagoController::class, 'webhook'])->name('mercadopago.webhook');
Route::get('pago-pendiente/{order}', [MercadoPagoController::class, 'pending'])->middleware('auth')->whereNumber('order')->name('payment.pending');
Route::get('mercadopago/retorno', [MercadoPagoController::class, 'return'])->middleware('auth')->name('mercadopago.return');
