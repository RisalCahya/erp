<?php

use App\Http\Controllers\BomItemController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialRequirementController;
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionRealizationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('products', ProductController::class);
    Route::resource('materials', MaterialController::class);

    Route::get('/bom', [BomItemController::class, 'index'])->name('bom.index');
    Route::post('/products/{product}/bom-items', [BomItemController::class, 'store'])->name('bom-items.store');
    Route::get('/bom-items/{bomItem}/edit', [BomItemController::class, 'edit'])->name('bom-items.edit');
    Route::put('/bom-items/{bomItem}', [BomItemController::class, 'update'])->name('bom-items.update');
    Route::delete('/bom-items/{bomItem}', [BomItemController::class, 'destroy'])->name('bom-items.destroy');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/receipts', [InventoryController::class, 'storeReceipt'])->name('inventory.receipts.store');
    Route::post('/inventory/adjustments', [InventoryController::class, 'storeAdjustment'])->name('inventory.adjustments.store');
    Route::patch('/inventory/materials/{material}/reorder-level', [InventoryController::class, 'updateReorderLevel'])->name('inventory.reorder-level.update');

    Route::resource('production-plans', ProductionPlanController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('/material-requirements', [MaterialRequirementController::class, 'index'])
        ->name('material-requirements.index');

    Route::get('/production-realizations', [ProductionRealizationController::class, 'index'])->name('production-realizations.index');
    Route::get('/production-realizations/create', [ProductionRealizationController::class, 'create'])->name('production-realizations.create');
    Route::post('/production-realizations', [ProductionRealizationController::class, 'store'])->name('production-realizations.store');
    Route::get('/production-realizations/products/{product}/preview', [ProductionRealizationController::class, 'preview'])
        ->name('production-realizations.products.preview');
    Route::get('/production-realizations/{productionRealization}', [ProductionRealizationController::class, 'show'])
        ->name('production-realizations.show');
    Route::post('/production-realizations/{productionRealization}/post', [ProductionRealizationController::class, 'post'])
        ->name('production-realizations.post');
});
