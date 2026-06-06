<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\CustomAuth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\CheckRoleController;
use App\Http\Controllers\Admin\UserController;



// English route
// require __DIR__.'/english.php';


Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,15')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('/tenant', TenantController::class);

        Route::resource('/project', ProjectController::class)->middleware('chooseTenant');

        Route::resource('/task', TaskController::class)->middleware('chooseTenant');

        Route::resource('/user', UserController::class)->middleware('chooseTenant');

        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index')->middleware('chooseTenant');

        Route::get('/profile',           [ProfileController::class, 'show'])->name('profile.show');
        Route::post('/profile',          [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');
        Route::post('/tenant/{id}/switch', function (int $id) {
            session(['current_tenant_id' => $id]);
            return redirect()->route('dashboard')->with('success', 'Workspace switched.');
        })->name('tenant.switch');
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('login.destroy');
});


// Debug permission 
Route::get('/check-role', [CheckRoleController::class, 'index'])->name('check-role');
