<?php

use App\Http\Controllers\ResidentPortal\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ResidentPortal\BillingController;
use App\Http\Controllers\ResidentPortal\DashboardController;
use App\Http\Controllers\ResidentPortal\MyStayController;
use App\Http\Controllers\ResidentPortal\LeaveController;
use App\Http\Controllers\ResidentPortal\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('resident')
    ->name('resident.')
    ->group(function () {
        Route::middleware('guest:resident')
            ->group(function () {
                Route::get(
                    '/login',
                    [AuthenticatedSessionController::class, 'create']
                )->name('login');

                Route::post(
                    '/login',
                    [AuthenticatedSessionController::class, 'store']
                )->name('login.store');
            });

        Route::middleware([
            'auth:resident',
            'resident.portal.enabled',
        ])->group(function () {
            Route::get(
                '/dashboard',
                [DashboardController::class, 'index']
            )->name('dashboard');

            Route::get(
                '/my-stay',
                [MyStayController::class, 'index']
            )->name('my-stay.index');

            Route::post(
                '/logout',
                [AuthenticatedSessionController::class, 'destroy']
            )->name('logout');

            Route::prefix('billing')
                ->name('billing.')
                ->group(function () {
                    Route::get(
                        '/',
                        [BillingController::class, 'index']
                    )->name('index');
    
                    Route::get(
                        '/{invoice}',
                        [BillingController::class, 'show']
                    )->name('show');
    
                    Route::get('/{invoice}/pdf/en', [BillingController::class, 'exportPdfEnglish'])->name('pdf.en');
                    Route::get('/{invoice}/pdf/hi', [BillingController::class, 'exportPdfHindi'])->name('pdf.hi');
                    Route::get('/{invoice}/print/hi', [BillingController::class, 'previewHindi'])->name('print.hi');
                    Route::get('/payments/{payment}/receipt', [BillingController::class, 'paymentReceipt'])->name('payments.receipt');
                });
    
            Route::get(
                '/payments',
                [PaymentController::class, 'index']
            )->name('payments.index');
    
            Route::get('/payments/{payment}',
                [PaymentController::class,'show']
            )->name('payments.show');

            Route::prefix('leaves')
                ->name('leaves.')
                ->group(function () {
                    Route::get(
                        '/',
                        [LeaveController::class, 'index']
                    )->name('index');

                    Route::post(
                        '/',
                        [LeaveController::class, 'store']
                    )->name('store');

                    Route::get(
                        '/{residentLeave}',
                        [LeaveController::class, 'show']
                    )->name('show');

                    Route::post(
                        '/{residentLeave}/cancel',
                        [LeaveController::class, 'cancel']
                    )->name('cancel');
                });
        });


    });