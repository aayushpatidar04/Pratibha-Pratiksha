<?php

use App\Http\Controllers\ResidentPortal\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ResidentPortal\BillingController;
use App\Http\Controllers\ResidentPortal\CheckoutRequestController as ResidentCheckoutRequestController;
use App\Http\Controllers\ResidentPortal\ComplaintController;
use App\Http\Controllers\ResidentPortal\DashboardController;
use App\Http\Controllers\ResidentPortal\DocumentController as ResidentDocumentController;
use App\Http\Controllers\ResidentPortal\EmergencyAlertController;
use App\Http\Controllers\ResidentPortal\MessMenuController as ResidentMessMenuController;
use App\Http\Controllers\ResidentPortal\MyStayController;
use App\Http\Controllers\ResidentPortal\NoticeController as ResidentNoticeController;
use App\Http\Controllers\ResidentPortal\LeaveController;
use App\Http\Controllers\ResidentPortal\PaymentController;
use App\Http\Controllers\ResidentPortal\ProfileController as ResidentProfileController;
use App\Http\Controllers\ResidentPortal\RoomChangeRequestController;
use App\Http\Controllers\ResidentPortal\SupportController as ResidentSupportController;
use App\Http\Controllers\ResidentPortal\VehicleController as ResidentVehicleController;
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

            Route::get(
                '/password/first-change',
                [AuthenticatedSessionController::class, 'firstChange']
            )->name('password.first-change');

            Route::post(
                '/password/first-change',
                [AuthenticatedSessionController::class, 'updateFirstChange']
            )->name('password.first-change.update');


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
                    Route::get('/{invoice}/print/en', [BillingController::class, 'previewEnglish'])->name('print.en');
                    Route::get('/{invoice}/pdf/hi', [BillingController::class, 'exportPdfHindi'])->name('pdf.hi');
                    Route::get('/{invoice}/print/hi', [BillingController::class, 'previewHindi'])->name('print.hi');
                    Route::get('/payments/{payment}/receipt', [BillingController::class, 'paymentReceipt'])->name('payments.receipt');
                    Route::post('/{invoice}/payment', [BillingController::class, 'submitPayment'])->name('payment.submit');
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

            Route::prefix('complaints')
                ->name('complaints.')
                ->group(function () {
                    Route::get(
                        '/',
                        [ComplaintController::class, 'index']
                    )->name('index');

                    Route::post(
                        '/',
                        [ComplaintController::class, 'store']
                    )->name('store');

                    Route::get(
                        '/{complaint}',
                        [ComplaintController::class, 'show']
                    )->name('show');

                    Route::post(
                        '/{complaint}/rate',
                        [ComplaintController::class, 'rate']
                    )->name('rate');

                    Route::delete(
                        '/{complaint}',
                        [ComplaintController::class, 'destroy']
                    )->name('destroy');
                });

            Route::prefix('room-change-requests')
                ->name('room-change-requests.')
                ->group(function () {
                    Route::get(
                        '/',
                        [RoomChangeRequestController::class, 'index']
                    )->name('index');

                    Route::post(
                        '/',
                        [RoomChangeRequestController::class, 'store']
                    )->name('store');

                    Route::get(
                        '/{roomChangeRequest}',
                        [RoomChangeRequestController::class, 'show']
                    )->name('show');

                    Route::post(
                        '/{roomChangeRequest}/cancel',
                        [RoomChangeRequestController::class, 'cancel']
                    )->name('cancel');
                });

            Route::prefix('emergency')
                ->name('emergency.')
                ->group(function () {
                    Route::get(
                        '/',
                        [EmergencyAlertController::class, 'index']
                    )->name('index');

                    Route::post(
                        '/',
                        [EmergencyAlertController::class, 'store']
                    )->name('store');

                    Route::get(
                        '/{alert}',
                        [EmergencyAlertController::class, 'show']
                    )->name('show');
                });

            Route::prefix('notices')
                ->name('notices.')
                ->group(function () {
                    Route::get(
                        '/',
                        [ResidentNoticeController::class, 'index']
                    )->name('index');

                    Route::get(
                        '/{notice}',
                        [ResidentNoticeController::class, 'show']
                    )->name('show');

                    Route::post(
                        '/{notice}/acknowledge',
                        [ResidentNoticeController::class, 'acknowledge']
                    )->name('acknowledge');

                    Route::get(
                        '/{notice}/attachments/{attachment}/download',
                        [
                            ResidentNoticeController::class,
                            'downloadAttachment',
                        ]
                    )->name('attachments.download');
                });

            Route::get(
                '/mess-menu',
                [
                    ResidentMessMenuController::class,
                    'index',
                ]
            )->name('mess-menu.index');

            Route::prefix('documents')
                ->name('documents.')
                ->group(function () {
                    Route::get(
                        '/',
                        [
                            ResidentDocumentController::class,
                            'index',
                        ]
                    )->name('index');

                    Route::post(
                        '/',
                        [
                            ResidentDocumentController::class,
                            'store',
                        ]
                    )->name('store');

                    Route::get(
                        '/{document}/download',
                        [
                            ResidentDocumentController::class,
                            'download',
                        ]
                    )->name('download');

                    Route::delete(
                        '/{document}',
                        [
                            ResidentDocumentController::class,
                            'destroy',
                        ]
                    )->name('destroy');
                });

            Route::prefix('profile')
                ->name('profile.')
                ->group(function () {
                    Route::get(
                        '/',
                        [
                            ResidentProfileController::class,
                            'index',
                        ]
                    )->name('index');

                    Route::put(
                        '/',
                        [
                            ResidentProfileController::class,
                            'update',
                        ]
                    )->name('update');

                    Route::post(
                        '/photo',
                        [
                            ResidentProfileController::class,
                            'updatePhoto',
                        ]
                    )->name('photo.update');

                    Route::put(
                        '/password',
                        [
                            ResidentProfileController::class,
                            'updatePassword',
                        ]
                    )->name('password.update');

                    Route::patch(
                        '/{stay}/expected-checkout-date',
                        [
                            ResidentProfileController::class,
                            'updateExpectedCheckoutDate'
                        ]
                    )->name('update-expected-checkout');
                });

            Route::prefix('vehicles')
                ->name('vehicles.')
                ->group(function () {
                    Route::get(
                        '/',
                        [
                            ResidentVehicleController::class,
                            'index',
                        ]
                    )->name('index');

                    Route::post(
                        '/',
                        [
                            ResidentVehicleController::class,
                            'store',
                        ]
                    )->name('store');

                    Route::put(
                        '/{vehicle}',
                        [
                            ResidentVehicleController::class,
                            'update',
                        ]
                    )->name('update');

                    Route::get(
                        '/{vehicle}/rc/download',
                        [
                            ResidentVehicleController::class,
                            'downloadRc',
                        ]
                    )->name('rc.download');

                    Route::delete(
                        '/{vehicle}',
                        [
                            ResidentVehicleController::class,
                            'destroy',
                        ]
                    )->name('destroy');
                });

            Route::prefix('support')
                ->name('support.')
                ->group(function () {
                    Route::get(
                        '/',
                        [
                            ResidentSupportController::class,
                            'index',
                        ]
                    )->name('index');

                    Route::post(
                        '/',
                        [
                            ResidentSupportController::class,
                            'store',
                        ]
                    )->name('store');
                });
                
            Route::prefix('checkout-requests')
                ->name('checkout-requests.')
                ->group(function () {
                    Route::get(
                        '/',
                        [
                            ResidentCheckoutRequestController::class,
                            'index',
                        ]
                    )->name('index');

                    Route::post(
                        '/',
                        [
                            ResidentCheckoutRequestController::class,
                            'store',
                        ]
                    )->name('store');

                    Route::put(
                        '/{checkoutRequest}/cancel',
                        [
                            ResidentCheckoutRequestController::class,
                            'cancel',
                        ]
                    )->name('cancel');

                    Route::get(
                        '/{checkoutRequest}/exit-pass',
                        [
                            ResidentCheckoutRequestController::class,
                            'exitPass',
                        ]
                    )->name(
                        'exit-pass'
                    );
                });

                
        });


    });