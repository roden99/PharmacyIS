<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\SalesAccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseItemController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\ProductUnitController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\StrengthController;
use App\Http\Controllers\DrugFormController;

Route::get('/login', function () {
    return Inertia::render('Login/Index');
})->name('login');

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('under-construction', function () {
    return Inertia::render('UnderConstruction');
})->middleware(['auth', 'verified'])->name('under-construction');

Route::resource('suppliers', SupplierController::class);

Route::resource('customers', CustomerController::class);
Route::get('customer-accounts', [CustomerAccountController::class, 'index'])->name('customer-accounts.index');
Route::post('customer-accounts', [CustomerAccountController::class, 'store'])->name('customer-accounts.store');
Route::resource('sales-accounts', SalesAccountController::class);

Route::resource('brands', BrandController::class);
Route::resource('strengths', StrengthController::class);
Route::resource('drugforms', DrugFormController::class);
Route::resource('product-units', ProductUnitController::class);
Route::resource('product-types', ProductTypeController::class);
Route::resource('products', ProductController::class);
Route::patch('products/{product}/initial-inventory', [ProductController::class, 'initialInventory'])->name('products.initialInventory');
Route::patch('products/{product}/reorder-level', [ProductController::class, 'reorderLevel'])->name('products.reorderLevel');
Route::get('products/{product}/history', [ProductController::class, 'history'])->name('products.history');
Route::resource('warehouses', WarehouseController::class);
Route::resource('warehouse-items', WarehouseItemController::class);
Route::resource('deliveries', DeliveryController::class);
Route::resource('sales-orders', SalesOrderController::class);
Route::resource('sales-accounts', SalesAccountController::class);





require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';





Route::get('/avatars/shadcn.jpg', function () {
    $path = public_path('avatars/shadcn.jpg');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});
