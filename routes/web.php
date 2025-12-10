<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\System\UserController;
use App\Http\Controllers\System\UserGroupController;
use App\Http\Controllers\System\MenuController;
use App\Http\Controllers\MasterData\MasterAreaController;
use App\Http\Controllers\MasterData\MasterFamilyController;
use App\Http\Controllers\MasterData\MasterConveyorController;
use App\Http\Controllers\MasterData\MasterMachineController;
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
        Route::get('menus/tree/data', [MenuController::class, 'getTreeData'])->name('menus.tree');
        Route::post('menus/reorder', [MenuController::class, 'reorder'])->name('menus.reorder');
    });

    // Master Data Module Routes
    Route::prefix('master-data')->name('master-data.')->group(function () {
        // Preassy Area Data Management
        Route::resource('master-area', MasterAreaController::class);
        Route::get('master-area/datatable/data', [MasterAreaController::class, 'datatable'])->name('master-area.datatable');

        // Master Family Management
        Route::resource('master-family', MasterFamilyController::class);
        Route::get('master-family/datatable/data', [MasterFamilyController::class, 'datatable'])->name('master-family.datatable');

        // Master Conveyor Management
        Route::resource('master-conveyor', MasterConveyorController::class);
        Route::get('master-conveyor/datatable/data', [MasterConveyorController::class, 'datatable'])->name('master-conveyor.datatable');
        Route::get('master-conveyor/areas/data', [MasterConveyorController::class, 'getAreas'])->name('master-conveyor.areas');
        Route::get('master-conveyor/families/data', [MasterConveyorController::class, 'getFamilies'])->name('master-conveyor.families');

        // Master Machine Management
        Route::resource('master-machine', MasterMachineController::class);
        Route::get('master-machine/datatable/data', [MasterMachineController::class, 'datatable'])->name('master-machine.datatable');
    });
});
