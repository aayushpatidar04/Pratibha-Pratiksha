<?php

use App\Http\Controllers\AcademicDetailsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CheckInOutController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DisciplinaryActionController;
use App\Http\Controllers\EmergencyAlertController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\GatePassController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\MessMenuController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RazorpayController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResidentAmenityOverrideController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\RoomChangeRequestController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WhatsappController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard has no module gate — every logged-in user can see the overview.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index')
        ->middleware('permission:analytics,view');
    Route::get('/analytics/occupancy/heatmap', [AnalyticsController::class, 'occupancyHeatmap'])->name('analytics.occupancy.heatmap')
        ->middleware('permission:analytics,view');

    // Infrastructure
    Route::get('/infrastructure/buildings', [BuildingController::class, 'index'])->name('buildings.index')->middleware('permission:buildings,view');
    Route::post('/infrastructure/buildings', [BuildingController::class, 'store'])->name('buildings.store')->middleware('permission:buildings,create');
    Route::put('/infrastructure/buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update')->middleware('permission:buildings,edit');
    Route::delete('/infrastructure/buildings/{building}', [BuildingController::class, 'destroy'])->name('buildings.destroy')->middleware('permission:buildings,delete');

    Route::get('/infrastructure/floors', [FloorController::class, 'index'])->name('floors.index')->middleware('permission:floors,view');
    Route::post('/infrastructure/floors', [FloorController::class, 'store'])->name('floors.store')->middleware('permission:floors,create');
    Route::put('/infrastructure/floors/{floor}', [FloorController::class, 'update'])->name('floors.update')->middleware('permission:floors,edit');
    Route::delete('/infrastructure/floors/{floor}', [FloorController::class, 'destroy'])->name('floors.destroy')->middleware('permission:floors,delete');

    Route::get('/infrastructure/rooms', [RoomController::class, 'index'])->name('rooms.index')->middleware('permission:rooms,view');
    Route::post('/infrastructure/rooms', [RoomController::class, 'store'])->name('rooms.store')->middleware('permission:rooms,create');
    Route::put('/infrastructure/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update')->middleware('permission:rooms,edit');
    Route::delete('/infrastructure/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy')->middleware('permission:rooms,delete');

    Route::get('/infrastructure/inventory', [InventoryController::class, 'index'])->name('inventory.index')->middleware('permission:inventory,view');
    Route::post('/infrastructure/inventory', [InventoryController::class, 'store'])->name('inventory.store')->middleware('permission:inventory,create');
    Route::put('/infrastructure/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update')->middleware('permission:inventory,edit');
    Route::delete('/infrastructure/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy')->middleware('permission:inventory,delete');

    // Residents
    Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index')->middleware('permission:residents,view');
    Route::get('/residents/past', [ResidentController::class, 'past'])->name('residents.past')->middleware('permission:residents,view');
    Route::post('/residents/bulk-upload', [ResidentController::class, 'bulkUpload'])->name('residents.bulk-upload')->middleware('permission:residents,create');
    Route::post('/residents', [ResidentController::class, 'store'])->name('residents.store')->middleware('permission:residents,create');
    Route::put('/residents/{resident}', [ResidentController::class, 'update'])->name('residents.update')->middleware('permission:residents,edit');
    Route::delete('/residents/{resident}', [ResidentController::class, 'destroy'])->name('residents.destroy')->middleware('permission:residents,delete');

    // Residents > KYC
    Route::get('/residents/kyc', [KycController::class, 'index'])->name('kyc.index')->middleware('permission:kyc,view');
    Route::get('/residents/kyc/settings', [KycController::class, 'settings'])->name('kyc.settings')->middleware('permission:kyc_settings,view');
    Route::put('/residents/kyc/settings', [KycController::class, 'updateSettings'])->name('kyc.settings.update')->middleware('permission:kyc_settings,edit');
    Route::post('/requirements', [KycController::class, 'storeRequirement'])->name('residents.kyc.requirements.store');
    Route::delete('/requirements/{requirement}', [KycController::class, 'destroyRequirement'])->name('residents.kyc.requirements.destroy');

    Route::post('/residents/{resident}/documents', [DocumentController::class, 'store'])->name('documents.store')->middleware('permission:kyc,create');
    Route::put('/documents/{document}', [DocumentController::class, 'updateStatus'])->name('documents.update')->middleware('permission:kyc,edit');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy')->middleware('permission:kyc,edit');

    // Residents > Academic Details
    Route::get('/residents/academic-details', [AcademicDetailsController::class, 'index'])->name('academics.index')->middleware('permission:academics,view');
    Route::put('/residents/academic-details/{resident}', [AcademicDetailsController::class, 'update'])->name('academics.update')->middleware('permission:academics,edit');

    // Residents > Student Vehicles
    Route::get('/residents/vehicles', [VehicleController::class, 'index'])->name('vehicles.index')->middleware('permission:student_vehicles,view');
    Route::post('/residents/vehicles', [VehicleController::class, 'store'])->name('vehicles.store')->middleware('permission:student_vehicles,create');
    Route::put('/residents/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update')->middleware('permission:student_vehicles,edit');
    Route::delete('/residents/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy')->middleware('permission:student_vehicles,delete');

    // Residents > Room Change Requests
    Route::get('/residents/room-change-requests', [RoomChangeRequestController::class, 'index'])->name('room-change-requests.index')->middleware('permission:room_change_requests,view');
    Route::post('/residents/room-change-requests', [RoomChangeRequestController::class, 'store'])->name('room-change-requests.store')->middleware('permission:room_change_requests,create');
    Route::put('/residents/room-change-requests/{roomChangeRequest}/approve', [RoomChangeRequestController::class, 'approve'])->name('room-change-requests.approve')->middleware('permission:room_change_requests,edit');
    Route::put('/residents/room-change-requests/{roomChangeRequest}/reject', [RoomChangeRequestController::class, 'reject'])->name('room-change-requests.reject')->middleware('permission:room_change_requests,edit');

    // Check-In / Check-Out (room allotment)
    Route::get('/checkinout', [CheckInOutController::class, 'index'])->name('checkinout.index')->middleware('permission:checkinout,view');
    Route::post('/checkinout/allot-room', [CheckInOutController::class, 'allotRoom'])->name('checkinout.allot');
    Route::post('/checkinout/{stay}/confirm-checkin', [CheckInOutController::class, 'confirmCheckin'])->name('checkinout.confirm-checkin');
    Route::post('/checkinout/{stay}/checkout-review', [CheckInOutController::class, 'reviewCheckout'])->name('checkinout.checkout-review');

    Route::put('/residents/{resident}/stay-dates', [ResidentController::class, 'updateStayDates'])->name('residents.stay-dates.update')->middleware('permission:residents,edit');
    Route::get('/residents/bulk-upload/template/csv', [ResidentController::class, 'downloadBulkCsvTemplate'])->name('residents.bulk.template.csv');
    Route::get('/residents/bulk-upload/template/excel', [ResidentController::class, 'downloadBulkExcelTemplate'])->name('residents.bulk.template.xlsx');

    // Billing
    Route::prefix('billing')->group(function () {
        // Main billing
        Route::get('/', [BillingController::class, 'index'])->name('billing.index')->middleware('permission:billing,view');
        Route::post('/', [BillingController::class, 'store'])->name('billing.store')->middleware('permission:billing,create');
        Route::post('/{invoice}/payments', [BillingController::class, 'recordPayment'])->name('billing.payments.store')->middleware('permission:billing,edit');
        Route::post('/{invoice}/waive-late-fee', [BillingController::class, 'waiveLateFee'])->name('billing.waive-late-fee')->middleware('permission:billing,edit');
        Route::delete('/{invoice}', [BillingController::class, 'destroy'])->name('billing.destroy')->middleware('permission:billing,delete');
        Route::patch('/{invoice}/restore', [BillingController::class, 'restore'])->name('billing.restore')->middleware('permission:billing,delete');
        Route::get('/{invoice}/pdf/en', [BillingController::class, 'exportPdfEnglish'])->name('billing.pdf.en');
        Route::get('/{invoice}/pdf/hi', [BillingController::class, 'exportPdfHindi'])->name('billing.pdf.hi');
        Route::get('/{invoice}/print/hi', [BillingController::class, 'previewHindi'])->name('billing.print.hi');
        Route::get('/payments/{payment}/receipt', [BillingController::class, 'paymentReceipt'])->name('billing.payments.receipt')->middleware('permission:billing,view');

        // Resident payment history
        Route::get('/resident/{resident}/history', [BillingController::class, 'residentHistory'])->name('billing.resident.history')->middleware('permission:billing,view');

        // Monthly config
        Route::get('/config', [BillingController::class, 'configIndex'])->name('billing.config.index')->middleware('permission:billing,view');
        Route::get('/config/create', [BillingController::class, 'configCreate'])->name('billing.config.create')->middleware('permission:billing,create');
        Route::post('/config', [BillingController::class, 'configStore'])->name('billing.config.store')->middleware('permission:billing,create');
        Route::delete('/config/{config}', [BillingController::class, 'destroyConfig'])->name('billing.config.destroy')->middleware('permission:billing,delete');

        // Auto generate
        Route::get('/config/{config}/preview', [BillingController::class, 'autoGenerate'])->name('billing.config.preview')->middleware('permission:billing,create');
        Route::post('/config/{config}/generate', [BillingController::class, 'confirmGenerate'])->name('billing.config.confirm-generate')->middleware('permission:billing,create');
    });

    Route::prefix('residents')->group(function () {
        Route::get('/{resident}/amenities', [ResidentAmenityOverrideController::class, 'edit'])->name('residents.amenity-override.edit')->middleware('permission:residents,edit');
        Route::put('/{resident}/amenities', [ResidentAmenityOverrideController::class, 'update'])->name('residents.amenity-override.update')->middleware('permission:residents,edit');
    });

    // Hostel Mess
    Route::get('/mess', [MessMenuController::class, 'index'])->name('mess.index')->middleware('permission:mess,view');
    Route::post('/mess', [MessMenuController::class, 'store'])->name('mess.store')->middleware('permission:mess,create');
    Route::delete('/mess/{messMenu}', [MessMenuController::class, 'destroy'])->name('mess.destroy')->middleware('permission:mess,delete');

    // WhatsApp
    Route::get('/whatsapp', [WhatsappController::class, 'index'])->name('whatsapp.index')->middleware('permission:whatsapp,view');
    Route::post('/whatsapp', [WhatsappController::class, 'store'])->name('whatsapp.store')->middleware('permission:whatsapp,create');

    // Student Support
    Route::get('/support/complaints', [ComplaintController::class, 'index'])->name('complaints.index')->middleware('permission:complaints,view');
    Route::post('/support/complaints', [ComplaintController::class, 'store'])->name('complaints.store')->middleware('permission:complaints,create');
    Route::put('/support/complaints/{complaint}', [ComplaintController::class, 'update'])->name('complaints.update')->middleware('permission:complaints,edit');
    Route::delete('/support/complaints/{complaint}', [ComplaintController::class, 'destroy'])->name('complaints.destroy')->middleware('permission:complaints,delete');

    Route::get('/support/leaves', [LeaveRequestController::class, 'index'])->name('leaves.index')->middleware('permission:leaves,view');
    Route::post('/support/leaves', [LeaveRequestController::class, 'store'])->name('leaves.store')->middleware('permission:leaves,create');
    Route::put('/support/leaves/{leave}', [LeaveRequestController::class, 'update'])->name('leaves.update')->middleware('permission:leaves,edit');
    Route::delete('/support/leaves/{leave}', [LeaveRequestController::class, 'destroy'])->name('leaves.destroy')->middleware('permission:leaves,delete');

    Route::get('/support/emergency', [EmergencyAlertController::class, 'index'])->name('emergency.index')->middleware('permission:emergency,view');
    Route::post('/support/emergency', [EmergencyAlertController::class, 'store'])->name('emergency.store')->middleware('permission:emergency,create');
    Route::put('/support/emergency/{alert}', [EmergencyAlertController::class, 'update'])->name('emergency.update')->middleware('permission:emergency,edit');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('permission:reports,view');

    // Gate Management
    Route::get('/gate', [GatePassController::class, 'index'])->name('gate.index')->middleware('permission:gate,view');
    Route::post('/gate', [GatePassController::class, 'store'])->name('gate.store')->middleware('permission:gate,create');
    Route::put('/gate/{gatePass}', [GatePassController::class, 'update'])->name('gate.update')->middleware('permission:gate,edit');

    // Student Tracking
    Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index')->middleware('permission:tracking,view');
    Route::post('/tracking', [TrackingController::class, 'store'])->name('tracking.store')->middleware('permission:tracking,create');

    // Disciplinary Action
    Route::get('/disciplinary', [DisciplinaryActionController::class, 'index'])->name('disciplinary.index')->middleware('permission:disciplinary,view');
    Route::post('/disciplinary', [DisciplinaryActionController::class, 'store'])->name('disciplinary.store')->middleware('permission:disciplinary,create');
    Route::put('/disciplinary/{disciplinaryAction}', [DisciplinaryActionController::class, 'update'])->name('disciplinary.update')->middleware('permission:disciplinary,edit');

    // Admin / Users & Permissions — super admin only, everywhere, gated via admin_users module.
    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index')->middleware('permission:admin_users,view');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store')->middleware('permission:admin_users,create');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update')->middleware('permission:admin_users,edit');
    Route::put('/admin/users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('admin.users.permissions')->middleware('permission:admin_users,edit');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy')->middleware('permission:admin_users,delete');
    Route::redirect('/admin', '/admin/users');

    Route::get('/registrations', [RegistrationController::class, 'index'])->name('admin.registrations.index');
    Route::get('/registrations/{application}', [RegistrationController::class, 'show'])->name('admin.registrations.show');
    Route::post('/registrations/{application}/approve', [RegistrationController::class, 'approve'])->name('admin.registrations.approve');
    Route::post('/registrations/{application}/reject', [RegistrationController::class, 'reject'])->name('admin.registrations.reject');
    Route::post('/registrations/{application}/mark-cash-paid', [RegistrationController::class, 'markCashPaid'])->name('admin.registrations.cash-paid');

    // Profile — every authenticated user manages their own, regardless of module permissions.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('register')->name('register.')->group(function () {
    Route::get('/', [RegistrationController::class, 'showQR'])->name('qr');
    Route::get('/form', [RegistrationController::class, 'showForm'])->name('form');
    Route::post('/form', [RegistrationController::class, 'store'])->name('store');
    Route::get('/success/{application}', [RegistrationController::class, 'success'])->name('success');
});

// Razorpay Payment Routes
Route::post('/razorpay/create-order', [RazorpayController::class, 'createOrder'])->name('razorpay.order');
Route::post('/razorpay/verify', [RazorpayController::class, 'verifyPayment'])->name('razorpay.verify');

require __DIR__ . '/auth.php';