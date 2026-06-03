<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Padosoft\Rebel\EmailOtp\Http\Controllers\EmailOtpController;

/*
 * Route web (opzionali) del login passwordless email-OTP.
 * Caricate dal provider sotto il prefix/middleware configurati in rebel-email-otp.routes.
 * I path finali dipendono dal prefix (default: account/login).
 */

Route::get('/', [EmailOtpController::class, 'showLogin'])->name('rebel-email-otp.login');
Route::post('/start', [EmailOtpController::class, 'start'])->name('rebel-email-otp.start');
Route::get('/verify', [EmailOtpController::class, 'showVerify'])->name('rebel-email-otp.verify-form');
Route::post('/verify', [EmailOtpController::class, 'verify'])->name('rebel-email-otp.verify');
Route::post('/resend', [EmailOtpController::class, 'resend'])->name('rebel-email-otp.resend');
Route::get('/done', fn () => 'Accesso effettuato')->name('rebel-email-otp.done');
