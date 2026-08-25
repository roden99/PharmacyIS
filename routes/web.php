<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\ReturnToSupplierController;
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
use App\Http\Controllers\StoreInventoryController;
use App\Http\Controllers\ReturnGoodStockController;
use App\Http\Controllers\TransferStockController;
use App\Http\Controllers\PosDeliveryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PosDashboardController;
use App\Http\Controllers\ExpirationController;
use App\Http\Controllers\SalesAgentController;
use App\Http\Controllers\CarryItemController;
use App\Http\Controllers\UserController;

Route::get('/login', function () {
    return Inertia::render('Login/Index');
})->name('login');

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('dashboard/chart-data', [DashboardController::class, 'chartData'])->middleware(['auth'])->name('dashboard.chart-data');

Route::get('under-construction', function () {
    return Inertia::render('UnderConstruction');
})->middleware(['auth', 'verified'])->name('under-construction');

Route::resource('suppliers', SupplierController::class);
Route::get('pos-suppliers', [SupplierController::class, 'posIndex'])->name('pos-suppliers.index');

Route::resource('customers', CustomerController::class);
Route::get('customer-accounts', [CustomerAccountController::class, 'index'])->name('customer-accounts.index');
Route::post('customer-accounts', [CustomerAccountController::class, 'store'])->name('customer-accounts.store');
Route::get('customer-accounts/by-customer/{customerId}', [CustomerAccountController::class, 'accountsByCustomer'])->name('customer-accounts.by-customer');
Route::get('customer-accounts/{id}/ledger', [CustomerAccountController::class, 'ledger'])->name('customer-accounts.ledger');
Route::get('customer-accounts/{id}/unpaid-orders', [CustomerAccountController::class, 'unpaidOrders'])->name('customer-accounts.unpaid-orders');
Route::get('customer-accounts/{id}/orders-for-payment/{paymentId}', [CustomerAccountController::class, 'ordersForPayment'])->name('customer-accounts.orders-for-payment');
Route::post('customer-accounts/{id}/payments', [CustomerAccountController::class, 'storePayment'])->name('customer-accounts.payments.store');
Route::patch('customer-accounts/{id}/forward-balance', [CustomerAccountController::class, 'setForwardBalance'])->name('customer-accounts.forward-balance');
Route::post('customer-accounts/{id}/invoices', [CustomerAccountController::class, 'storeInvoice'])->name('customer-accounts.invoices.store');
Route::patch('customer-accounts/{csaId}/invoices/{invoiceId}', [CustomerAccountController::class, 'updateInvoice'])->name('customer-accounts.invoices.update');
Route::patch('customer-accounts/{csaId}/payments/{paymentId}', [CustomerAccountController::class, 'updatePayment'])->name('customer-accounts.payments.update');
Route::delete('customer-accounts/{csaId}/invoices/{invoiceId}', [CustomerAccountController::class, 'destroyInvoice'])->name('customer-accounts.invoices.destroy');
Route::delete('customer-accounts/{csaId}/payments/{paymentId}', [CustomerAccountController::class, 'destroyPayment'])->name('customer-accounts.payments.destroy');
Route::post('customer-accounts/invoices/{invoiceId}/payments', [CustomerAccountController::class, 'storeInvoicePayment'])->name('customer-accounts.invoices.payments.store');
Route::patch('customer-accounts/invoices/{invoiceId}/payments/{paymentId}', [CustomerAccountController::class, 'updateInvoicePayment'])->name('customer-accounts.invoices.payments.update');
Route::delete('customer-accounts/invoices/{invoiceId}/payments/{paymentId}', [CustomerAccountController::class, 'destroyInvoicePayment'])->name('customer-accounts.invoices.payments.destroy');
Route::resource('sales-accounts', SalesAccountController::class);

Route::resource('brands', BrandController::class);
Route::resource('strengths', StrengthController::class);
Route::resource('drugforms', DrugFormController::class);
Route::resource('product-units', ProductUnitController::class);
Route::resource('product-types', ProductTypeController::class);
Route::resource('products', ProductController::class);
Route::get('pos-items', [ProductController::class, 'posItems'])->name('pos-items.index');
Route::post('pos-items', [ProductController::class, 'storePosItem'])->name('pos-items.store');
Route::patch('pos-items/{product}', [ProductController::class, 'updatePosItem'])->name('pos-items.update');
Route::delete('pos-items/{product}', [ProductController::class, 'destroyPosItem'])->name('pos-items.destroy');
Route::patch('products/{product}/initial-inventory', [ProductController::class, 'initialInventory'])->name('products.initialInventory');
Route::patch('products/{product}/reorder-level', [ProductController::class, 'reorderLevel'])->name('products.reorderLevel');
Route::get('products/{product}/history', [ProductController::class, 'history'])->name('products.history');
Route::get('products/{product}/pricing-history', [ProductController::class, 'pricingHistory'])->name('products.pricing-history');
Route::post('products/{product}/lots', [ProductController::class, 'storeLot'])->name('products.lots.store');
Route::get('products/{product}/lots/all', [ProductController::class, 'getLots'])->name('products.lots.index');
Route::patch('products/{product}/lots/{lot}', [ProductController::class, 'updateLot'])->name('products.lots.update');
Route::delete('products/{product}/lots/{lot}', [ProductController::class, 'destroyLot'])->name('products.lots.destroy');
Route::resource('warehouses', WarehouseController::class);
Route::resource('warehouse-items', WarehouseItemController::class);
Route::resource('deliveries', DeliveryController::class);
Route::get('sales-orders/overdue-report', [SalesOrderController::class, 'overdueReport'])->middleware(['auth'])->name('sales-orders.overdue-report');
Route::resource('sales-orders', SalesOrderController::class);
Route::post('sales-orders/{salesOrder}/rgs', [ReturnGoodStockController::class, 'store'])->middleware(['auth'])->name('sales-orders.rgs.store');
Route::post('return-good-stocks', [ReturnGoodStockController::class, 'storeStandalone'])->middleware(['auth'])->name('return-good-stocks.store');
Route::resource('return-good-stocks', ReturnGoodStockController::class)->only(['index', 'show', 'update', 'destroy']);
Route::resource('return-to-suppliers', ReturnToSupplierController::class)->only(['index', 'show', 'store', 'destroy'])->middleware(['auth']);
Route::resource('sales-accounts', SalesAccountController::class);

Route::get('store-inventory', [StoreInventoryController::class, 'index'])->name('store-inventory.index');
Route::patch('store-inventory/{product}/pos-qty', [StoreInventoryController::class, 'updatePosQty'])->name('store-inventory.updatePosQty');
Route::patch('store-inventory/{product}/selling-price', [StoreInventoryController::class, 'posSellingPrice'])->name('store-inventory.sellingPrice');
Route::get('store-inventory/{product}/history', [StoreInventoryController::class, 'history'])->name('store-inventory.history');
Route::get('store-inventory/{product}/pricing-history', [StoreInventoryController::class, 'pricingHistory'])->name('store-inventory.pricing-history');


Route::get('store-inventory/init-pos-products', [StoreInventoryController::class, 'initPosProducts'])->name('store-inventory.init-pos-products');
Route::post('store-inventory/bulk-init-pos-qty', [StoreInventoryController::class, 'bulkInitPosQty'])->name('store-inventory.bulk-init-pos-qty');

Route::get('expirations', [ExpirationController::class, 'index'])->name('expirations.index');

Route::get('products/{product}/multiplier', [ProductController::class, 'multiplier'])->name('products.multiplier');
Route::get('products/{product}/lots', [ProductController::class, 'productLots'])->name('products.lots');
Route::resource('transfer-stocks', TransferStockController::class)->only(['index', 'store', 'show', 'destroy']);
Route::resource('pos-deliveries', PosDeliveryController::class)->only(['index', 'store', 'show', 'destroy']);

Route::get('pos-products', [StoreInventoryController::class, 'posProducts'])->name('pos-products.index');
Route::get('pos-products/{product}/lots', [StoreInventoryController::class, 'posProductLots'])->name('pos-products.lots.index');
Route::post('pos-products/{product}/lots', [StoreInventoryController::class, 'storePosProductLot'])->name('pos-products.lots.store');
Route::patch('pos-products/{product}/lots/{lot}', [StoreInventoryController::class, 'updatePosProductLot'])->name('pos-products.lots.update');
Route::delete('pos-products/{product}/lots/{lot}', [StoreInventoryController::class, 'destroyPosProductLot'])->name('pos-products.lots.destroy');
Route::get('pos-dashboard', [PosDashboardController::class, 'index'])->name('pos-dashboard.index');
Route::resource('pos', PosController::class)->only(['index', 'store', 'show', 'destroy']);

Route::resource('sales-agents', SalesAgentController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('carry-items', CarryItemController::class)->only(['index', 'store', 'show', 'destroy']);
Route::patch('carry-item-details/{detail}/return', [CarryItemController::class, 'returnDetail'])->name('carry-item-details.return');

Route::resource('users', UserController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware('admin');

Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
Route::get('payments/{id}/details', [PaymentController::class, 'details'])->name('payments.details');





require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';





Route::get('/avatars/shadcn.jpg', function () {
    $path = public_path('avatars/shadcn.jpg');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});
