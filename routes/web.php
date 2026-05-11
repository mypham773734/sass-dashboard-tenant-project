<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomAuth\AuthenticatedSessionController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\ProjectController; 



// English route
require __DIR__.'/english.php';


Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/', function () {
            return view('admin.pages.dashboard.index');
        })->name('dashboard');

        Route::resource('/tenant', TenantController::class);
        Route::resource('/project', ProjectController::class)->middleware('chooseTenant');
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('login.destroy');
});
