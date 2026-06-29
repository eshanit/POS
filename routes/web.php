<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', ProductController::class);
    Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit']);
    Route::resource('suppliers', SupplierController::class)->except(['create', 'show', 'edit']);

    Route::get('/stock/receive', [StockController::class, 'receiveForm'])->name('stock.receive');
    Route::post('/stock/receive', [StockController::class, 'storeReceive'])->name('stock.receive.store');
    Route::get('/stock/adjust', [StockController::class, 'adjustForm'])->name('stock.adjust');
    Route::post('/stock/adjust', [StockController::class, 'storeAdjustment'])->name('stock.adjust.store');
    Route::get('/stock/deduct', [StockController::class, 'deductForm'])->name('stock.deduct');
    Route::post('/stock/deduct', [StockController::class, 'storeDeduct'])->name('stock.deduct.store');

    Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');

    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('/reports/inventory/export', [ReportController::class, 'exportInventory'])->name('reports.inventory.export');
    Route::get('/reports/shrinkage', [ReportController::class, 'shrinkage'])->name('reports.shrinkage');
    Route::get('/reports/shrinkage/export', [ReportController::class, 'exportShrinkage'])->name('reports.shrinkage.export');
    Route::get('/reports/supplier', [ReportController::class, 'supplier'])->name('reports.supplier');
});

require __DIR__.'/settings.php';
