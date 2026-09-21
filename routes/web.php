<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetLogController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('locations', LocationController::class);
});

Route::middleware(['auth', 'role:super_admin,it_staff'])->group(function () {
    Route::resource('assets', AssetController::class)->except(['show']);
    Route::patch('/assets/{asset}/update-status', [AssetController::class, 'updateStatus'])->name('assets.update-status');
    Route::get('/assets-export', [AssetController::class, 'export'])->name('assets.export');
    Route::post('/assets-import', [AssetController::class, 'import'])->name('assets.import');
    Route::post('/assets/{asset}/checkout', [AssetLogController::class, 'checkout'])->name('assets.checkout');
    Route::post('/assets/{asset}/checkin', [AssetLogController::class, 'checkin'])->name('assets.checkin');
    Route::post('/assets/{asset}/maintenance-logs', [MaintenanceLogController::class, 'store'])->name('maintenance-logs.store');
    Route::put('/maintenance-logs/{maintenanceLog}', [MaintenanceLogController::class, 'update'])->name('maintenance-logs.update');
    Route::get('/barcode/{asset}/print', [BarcodeController::class, 'print'])->name('barcode.print');
    Route::post('/barcode/print-batch', [BarcodeController::class, 'printBatch'])->name('barcode.print-batch');
    Route::get('/scan/mobile', [ScanController::class, 'mobile'])->name('scan.mobile');
    Route::get('/scan/desktop', [ScanController::class, 'desktop'])->name('scan.desktop');
    Route::post('/scan/lookup', [ScanController::class, 'lookup'])->name('scan.lookup');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
});

require __DIR__.'/auth.php';