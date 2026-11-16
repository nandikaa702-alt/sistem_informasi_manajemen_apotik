<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('guest')->group(function () {
	Route::get('/login', [AuthController::class, 'create'])->name('login');
	Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
	Route::get('/setup', [SetupController::class, 'create'])->name('setup');
	Route::post('/setup', [SetupController::class, 'store'])->middleware('throttle:3,1')->name('setup.store');
});

Route::middleware('auth')->group(function () {
	Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

	Route::middleware('role:admin,apoteker')->group(function () {
		Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
		Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
		Route::resource('categories', CategoryController::class)->except('show');
		Route::resource('suppliers', SupplierController::class)->except('show');
		Route::resource('medicines', MedicineController::class)->except(['show', 'index']);
		Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
		Route::post('/stock', [StockController::class, 'store'])->name('stock.store');
		Route::get('/stock/history', [StockController::class, 'history'])->name('stock.history');
		Route::get('/alerts/low-stock', [StockController::class, 'lowStock'])->name('alerts.low-stock');
		Route::get('/alerts/expiring', [StockController::class, 'expiring'])->name('alerts.expiring');
		Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
		Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
		Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
		Route::post('/purchases/{purchase}/confirm', [PurchaseController::class, 'confirm'])->name('purchases.confirm');
		Route::post('/purchases/orders/{purchaseOrder}/confirm', [PurchaseController::class, 'confirmOrder'])->name('purchases.orders.confirm');
		Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
		Route::get('/reports/export/{format}', [ReportController::class, 'export'])->whereIn('format', ['pdf', 'xlsx'])->name('reports.export');
	});

	Route::get('/medicines', [MedicineController::class, 'index'])
		->middleware('role:admin,apoteker,kasir')
		->name('medicines.index');

	Route::middleware('role:admin,apoteker,kasir')->group(function () {
		Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
		Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
		Route::get('/sales/medicine-search', [SaleController::class, 'search'])->name('sales.medicine-search');
		Route::get('/sales/{saleTransaction}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
		Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
	});

	Route::resource('users', UserController::class)
		->except('show')
		->middleware('role:admin');
});
