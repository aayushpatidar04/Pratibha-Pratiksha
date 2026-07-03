<?php

use App\Http\Controllers\BuildingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\WhatsappController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Infrastructure
    Route::get('/infrastructure/buildings', [BuildingController::class, 'index'])->name('buildings.index');
    Route::post('/infrastructure/buildings', [BuildingController::class, 'store'])->name('buildings.store');
    Route::put('/infrastructure/buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update');
    Route::delete('/infrastructure/buildings/{building}', [BuildingController::class, 'destroy'])->name('buildings.destroy');

    Route::get('/infrastructure/floors', [FloorController::class, 'index'])->name('floors.index');
    Route::post('/infrastructure/floors', [FloorController::class, 'store'])->name('floors.store');
    Route::put('/infrastructure/floors/{floor}', [FloorController::class, 'update'])->name('floors.update');
    Route::delete('/infrastructure/floors/{floor}', [FloorController::class, 'destroy'])->name('floors.destroy');

    Route::get('/infrastructure/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::post('/infrastructure/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::put('/infrastructure/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/infrastructure/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

    // Residents
    Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index');
    Route::post('/residents', [ResidentController::class, 'store'])->name('residents.store');
    Route::put('/residents/{resident}', [ResidentController::class, 'update'])->name('residents.update');
    Route::delete('/residents/{resident}', [ResidentController::class, 'destroy'])->name('residents.destroy');

    // WhatsApp
    Route::get('/whatsapp', [WhatsappController::class, 'index'])->name('whatsapp.index');
    Route::post('/whatsapp', [WhatsappController::class, 'store'])->name('whatsapp.store');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Placeholder pages for modules not yet built (kept from original nav)
    Route::get('/{any}', function ($any) {
        return Inertia::render('Placeholder', ['path' => '/'.$any]);
    })->where('any', '(analytics|admin|checkinout|mess|billing|support/.*|reports|gate|tracking|disciplinary)');
});

require __DIR__.'/auth.php';