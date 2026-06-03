<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Padosoft\Rebel\EmailOtp\Http\Controllers\EmailOtpController;

/*
 * Web routes (optional) for the passwordless email-OTP login.
 * Loaded by the provider under the prefix/middleware configured in rebel-email-otp.routes.
 * The final paths depend on the prefix (default: account/login).
 */

Route::get('/', [EmailOtpController::class, 'showLogin'])->name('rebel-email-otp.login');
Route::post('/start', [EmailOtpController::class, 'start'])->name('rebel-email-otp.start');
Route::get('/verify', [EmailOtpController::class, 'showVerify'])->name('rebel-email-otp.verify-form');
Route::post('/verify', [EmailOtpController::class, 'verify'])->name('rebel-email-otp.verify');
Route::post('/resend', [EmailOtpController::class, 'resend'])->name('rebel-email-otp.resend');
Route::get('/done', fn () => 'Accesso effettuato')->name('rebel-email-otp.done');
