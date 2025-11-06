<?php

use App\Http\Controllers\MailController;
use App\Http\Controllers\MicrosoftAuthController;
use App\Http\Controllers\WhcSupplierBlogController;
use App\Http\SystemController;
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

    Route::name('whc.supplier.blog.')->group(function () {
        Route::get('/whc-supplier-blog', [WhcSupplierBlogController::class, 'index'])->name('index');
        Route::post('/whc-supplier-blog/sync', [WhcSupplierBlogController::class, 'sync'])->name('sync');

        Route::post('/whc-supplier-blog/{blog}/create-in-magento', [WhcSupplierBlogController::class, 'createInMagento'])->name('create-in-magento');
        Route::post('/whc-supplier-blog/{blog}/activate-in-magento', [WhcSupplierBlogController::class, 'activateInMagento'])->name('activate-in-magento');
        Route::post('/whc-supplier-blog/{blog}/deactivate-in-magento', [WhcSupplierBlogController::class, 'deactivateInMagento'])->name('deactivate-in-magento');
        Route::post('/whc-supplier-blog/{blog}/update-in-magento', [WhcSupplierBlogController::class, 'updateInMagento'])->name('update-in-magento');
        Route::post('/whc-supplier-blog/{blog}/update-as-sold', [WhcSupplierBlogController::class, 'updateBlogAsSold'])->name('update-as-sold');

        Route::get('/whc-files', [WhcSupplierBlogController::class, 'showFile'])->name('file-show');
    });

    Route::post('/system/run-migrations', [SystemController::class, 'runMigration'])->name('system.run-migrations');

});

Route::redirect('/', '/whc-supplier-blog');

require __DIR__.'/auth.php';
