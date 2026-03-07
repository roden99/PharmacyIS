<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseItemController;
use App\Http\Controllers\DeliveryController;

Route::get('/login', function () {
    return Inertia::render('Login/Index');
})->name('login');

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('under-construction', function () {
    return Inertia::render('UnderConstruction');
})->middleware(['auth', 'verified'])->name('under-construction');

Route::resource('suppliers', SupplierController::class);

Route::resource('customers', CustomerController::class);

Route::resource('brands', BrandController::class);
Route::resource('products', ProductController::class);
Route::resource('warehouses', WarehouseController::class);
Route::resource('warehouse-items', WarehouseItemController::class);
Route::resource('deliveries', DeliveryController::class);





require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';





Route::get('/avatars/shadcn.jpg', function () {
    $path = public_path('avatars/shadcn.jpg');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});
