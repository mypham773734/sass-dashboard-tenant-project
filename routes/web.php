<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TenantSettingController;
use App\Http\Controllers\CustomAuth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\CheckRoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Setup\SetupController; 

Route::get('/', function(){
    return to_route('dashboard');  
}); 

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
        
        Route::post('/tenant/{id}/switch', [TenantController::class, 'switchTenant'])->name('tenant.switch');

        Route::prefix('tenant/{tenantId}/settings')
            ->name('tenant.settings.')
            ->group(function () {
                Route::get('/{section?}', [TenantSettingController::class, 'index'])->name('index');
                Route::post('/{section}', [TenantSettingController::class, 'update'])->name('update');
            });
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('login.destroy');

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index')->middleware('chooseTenant');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read')->middleware('chooseTenant');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read')->middleware('chooseTenant');
});


// Debug permission 
Route::get('/check-role', [CheckRoleController::class, 'index'])->name('check-role');

// Setup project
// Route::get('/setup', [SetupController::class, 'setup'])->name('setup.project')->middleware('checkSystemAdminNotExists'); 
Route::get('/setup', [SetupController::class, 'setup'])->name('setup.project'); 

Route::get('/test', function(){
    $role = app(\App\Domain\Role\Repositories\RoleRepositoryInterface::class)
            ->findByNameAndTenant('owner', 5);

    echo json_encode($role); 
}); 

Route::get('/save-permission', function(){

}); 