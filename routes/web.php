<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\System\UserController;
use App\Http\Controllers\System\UserGroupController;
use App\Http\Controllers\System\MenuController;
use App\Http\Controllers\System\MasterAreaController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', function () {
    return redirect('/dashboard');
});

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // System Module Routes
    Route::prefix('system')->name('system.')->group(function () {
        // Users Management
        Route::resource('users', UserController::class);
        Route::get('users/datatable/data', [UserController::class, 'datatable'])->name('users.datatable');

        // User Groups Management
        Route::resource('user-groups', UserGroupController::class);
        Route::get('user-groups/datatable/data', [UserGroupController::class, 'datatable'])->name('user-groups.datatable');
        Route::get('user-groups/{id}/permissions', [UserGroupController::class, 'permissions'])->name('user-groups.permissions');
        Route::put('user-groups/{id}/permissions', [UserGroupController::class, 'updatePermissions'])->name('user-groups.permissions.update');

        // Menus Management
        Route::resource('menus', MenuController::class);
        Route::get('menus/datatable/data', [MenuController::class, 'datatable'])->name('menus.datatable');

        // Preassy Area Data Management
        Route::resource('master-area', MasterAreaController::class);
        Route::get('master-area/datatable/data', [MasterAreaController::class, 'datatable'])->name('master-area.datatable');
    });
});
