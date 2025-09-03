<?php

use App\Http\Controllers\MailController;
use App\Http\Controllers\MicrosoftAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::name('ms.')->group(function () {
        Route::get('/auth/ms/callback', [MicrosoftAuthController::class, 'callback'])->name('callback');
        Route::get('/auth/ms/redirect', [MicrosoftAuthController::class, 'redirectToMicrosoft'])->name('redirect');
        Route::get('/auth/ms/token', [MicrosoftAuthController::class, 'requestAccessToken'])->name('token');
    });

    Route::name('mail.')->group(function () {
        Route::get('/email', [MailController::class, 'index'])->name('index');
        Route::get('/email/{message}', [MailController::class, 'show'])->name('show');
        Route::post('/email/sync', [MailController::class, 'sync'])->name('sync');
        Route::post('/email/{message}/replay', [MailController::class, 'reply'])->name('reply');
        Route::post('/email/{message}/forward', [MailController::class, 'forward'])->name('forward');
    });
});


require __DIR__ . '/auth.php';
