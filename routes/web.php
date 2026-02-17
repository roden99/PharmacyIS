<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SupplierController;

Route::get('/login', function () {
    return Inertia::render('Login/Index');
})->name('login');

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::resource('suppliers', SupplierController::class);

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';





Route::get('/avatars/shadcn.jpg', function () {
    $path = public_path('avatars/shadcn.jpg');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});
