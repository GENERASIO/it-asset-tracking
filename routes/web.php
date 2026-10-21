<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AgentTokenController;
use App\Http\Controllers\BastController;
use App\Http\Controllers\SoftwareLicenseController;
use App\Http\Controllers\PriceReferenceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetLogController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

// Halaman publik (tanpa login) yang dibuka waktu scan QR di label aset - info dasar saja.
Route::get('/scan/{asset}', [AssetController::class, 'publicInfo'])->name('assets.public-info');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:super_admin,it_staff'])->group(function () {
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('locations', LocationController::class)->except(['show']);
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('agent-tokens', AgentTokenController::class)->only(['index', 'store', 'destroy']);
    Route::get('agent-tokens/download/{os}', [AgentTokenController::class, 'downloadPackage'])->name('agent-tokens.download');
    Route::get('agent-tokens/updater/windows', [AgentTokenController::class, 'downloadWindowsUpdater'])->name('agent-tokens.updater');
});

Route::middleware(['auth', 'role:super_admin,it_staff'])->group(function () {
    Route::resource('assets', AssetController::class)->except(['show']);
    Route::delete('/assets/{asset}/photos/{photo}', [AssetController::class, 'destroyPhoto'])->name('assets.photos.destroy');
    Route::patch('/assets/{asset}/update-status', [AssetController::class, 'updateStatus'])->name('assets.update-status');
    Route::post('/assets/{asset}/toggle-lost', [AssetController::class, 'toggleLost'])->name('assets.toggle-lost');
    Route::get('/assets-export', [AssetController::class, 'export'])->name('assets.export');
    Route::post('/assets-import', [AssetController::class, 'import'])->name('assets.import');
    Route::get('/assets-search-suggestions', [AssetController::class, 'searchSuggestions'])->name('assets.search-suggestions');
    Route::post('/assets/{asset}/checkout', [AssetLogController::class, 'checkout'])->name('assets.checkout');
    Route::post('/assets/{asset}/checkin', [AssetLogController::class, 'checkin'])->name('assets.checkin');
    Route::post('/assets/{asset}/maintenance-logs', [MaintenanceLogController::class, 'store'])->name('maintenance-logs.store');
    Route::put('/maintenance-logs/{maintenanceLog}', [MaintenanceLogController::class, 'update'])->name('maintenance-logs.update');
    Route::get('/barcode/{asset}/print', [BarcodeController::class, 'print'])->name('barcode.print');
    Route::post('/barcode/print-batch', [BarcodeController::class, 'printBatch'])->name('barcode.print-batch');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
    Route::get('/price-references', [PriceReferenceController::class, 'index'])->name('price-references.index');
    Route::post('/price-references', [PriceReferenceController::class, 'store'])->name('price-references.store');
    Route::put('/price-references/{priceReference}', [PriceReferenceController::class, 'update'])->name('price-references.update');
    Route::delete('/price-references/{priceReference}', [PriceReferenceController::class, 'destroy'])->name('price-references.destroy');
    Route::get('/software-licenses', [SoftwareLicenseController::class, 'index'])->name('software-licenses.index');
    Route::post('/software-licenses', [SoftwareLicenseController::class, 'store'])->name('software-licenses.store');
    Route::get('/software-licenses/{entitlement}/edit', [SoftwareLicenseController::class, 'edit'])->name('software-licenses.edit');
    Route::put('/software-licenses/{entitlement}', [SoftwareLicenseController::class, 'update'])->name('software-licenses.update');
    Route::delete('/software-licenses/{entitlement}', [SoftwareLicenseController::class, 'destroy'])->name('software-licenses.destroy');
    Route::get('/bast', [BastController::class, 'index'])->name('bast.index');
    Route::get('/bast/create', [BastController::class, 'create'])->name('bast.create');
    Route::post('/bast', [BastController::class, 'store'])->name('bast.store');
    Route::get('/bast/{bast}', [BastController::class, 'show'])->name('bast.show');
    Route::get('/bast/{bast}/pdf', [BastController::class, 'pdf'])->name('bast.pdf');
    Route::post('/bast/{bast}/signed', [BastController::class, 'uploadSigned'])->name('bast.signed.upload');
    Route::get('/bast/{bast}/signed', [BastController::class, 'signedFile'])->name('bast.signed.show');
    Route::delete('/bast/{bast}', [BastController::class, 'destroy'])->name('bast.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
});

require __DIR__.'/auth.php';