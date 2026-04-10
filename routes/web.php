<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomAuth\AuthenticatedSessionController;
use App\Http\Controllers\TenantController;

require __DIR__ . '/auth.php';

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

Route::middleware('guest')->group(function () {
    Route::get('/custom-login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/custom-login', [AuthenticatedSessionController::class, 'store'])->name('custom-login.store');
});

Route::middleware('auth')->group(function () {
    Route::prefix('admin')->group(function(){
        Route::get('/dashboard', function () {
            return view('admin.pages.dashboard.index');
            // echo "dashboard page"; 
            // // dd(session()->all()); 
            // // dd(session()->getId());
    
            // dd(base64_decode('eyJfdG9rZW4iOiJ5ZkZnelBtOHVSaDlQNUlvQnZmYlFtQVRja2FDNm1NRHlYQjZLT1phIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9kYXNoYm9hcmQiLCJyb3V0ZSI6ImRhc2hib2FyZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjozfQ==')); 
        })->name('dashboard');
    
    
        Route::post('/custom-logout', [AuthenticatedSessionController::class, 'destroy'])->name('custom-login.destroy');
    
        Route::resource('/tenant', TenantController::class);
    });
});
