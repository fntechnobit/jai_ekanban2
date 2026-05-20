<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DefectController;
use App\Http\Controllers\System\UserController;
use App\Http\Controllers\System\UserGroupController;
use App\Http\Controllers\System\MenuController;
use App\Http\Controllers\System\ListingSyncController;
use App\Http\Controllers\System\DatabaseBackupController;
use App\Http\Controllers\Schedule\AssySchedulerController;
use App\Http\Controllers\Schedule\ScheduleVerificationController;
use App\Http\Controllers\Schedule\EkanbanCircuitController;
use App\Http\Controllers\Schedule\EkanbanShikakeController;
use App\Http\Controllers\MasterData\MasterAreaController;
use App\Http\Controllers\MasterData\MasterCarlineController;
use App\Http\Controllers\MasterData\MasterFamilyController;
use App\Http\Controllers\MasterData\MasterConveyorController;
use App\Http\Controllers\MasterData\MasterMachineController;
use App\Http\Controllers\MasterData\MasterShikakeController;
use App\Http\Controllers\MasterData\MasterCircuitController;
use App\Http\Controllers\AdditionController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
})->name('home');

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
    Route::get('/dashboard/cutting-datatable', [DashboardController::class, 'getCuttingDatatable'])->name('dashboard.cutting-datatable');
    Route::get('/dashboard/shikake-datatable', [DashboardController::class, 'getShikakeDatatable'])->name('dashboard.shikake-datatable');
    Route::get('/dashboard/sync-status', [DashboardController::class, 'syncStatus'])->name('dashboard.sync-status');
    Route::post('/dashboard/generate', [DashboardController::class, 'generate'])->name('dashboard.generate');

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

        // Listing Synchronization
        Route::get('listing-sync', [ListingSyncController::class, 'index'])->name('listing-sync.index');
        Route::post('listing-sync/sync', [ListingSyncController::class, 'sync'])->name('listing-sync.sync');
        Route::get('listing-sync/statistics', [ListingSyncController::class, 'statistics'])->name('listing-sync.statistics');

        // Database Backup
        Route::get('database-backup', [DatabaseBackupController::class, 'index'])->name('database-backup.index');
        Route::get('database-backup/download', [DatabaseBackupController::class, 'download'])->name('database-backup.download');
    });

    // Master Data Module Routes
    Route::prefix('master-data')->name('master-data.')->group(function () {
        // Area Data Management
        Route::resource('master-area', MasterAreaController::class);
        Route::get('master-area/datatable/data', [MasterAreaController::class, 'datatable'])->name('master-area.datatable');

        // Carline Data Management
        Route::resource('master-carline', MasterCarlineController::class);
        Route::get('master-carline/datatable/data', [MasterCarlineController::class, 'datatable'])->name('master-carline.datatable');

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

        // Master Shikake Management
        Route::get('master-shikake/datatable', [MasterShikakeController::class, 'datatable'])->name('master-shikake.datatable');
        Route::get('master-shikake/import-form', [MasterShikakeController::class, 'importForm'])->name('master-shikake.import-form');
        Route::post('master-shikake/import', [MasterShikakeController::class, 'import'])->name('master-shikake.import');
        Route::get('master-shikake/download-template', [MasterShikakeController::class, 'downloadTemplate'])->name('master-shikake.download-template');
        Route::post('master-shikake/remove-by-conveyor', [MasterShikakeController::class, 'removeByConveyor'])->name('master-shikake.remove-by-conveyor');
        Route::resource('master-shikake', MasterShikakeController::class);

        // Master Circuit Management
        Route::get('master-circuit/datatable', [MasterCircuitController::class, 'datatable'])->name('master-circuit.datatable');
        Route::get('master-circuit/import-form', [MasterCircuitController::class, 'importForm'])->name('master-circuit.import-form');
        Route::post('master-circuit/import', [MasterCircuitController::class, 'import'])->name('master-circuit.import');
        Route::get('master-circuit/download-template', [MasterCircuitController::class, 'downloadTemplate'])->name('master-circuit.download-template');
        Route::post('master-circuit/remove-by-conveyor', [MasterCircuitController::class, 'removeByConveyor'])->name('master-circuit.remove-by-conveyor');
        Route::post('master-circuit/{id}/upload-drawing', [MasterCircuitController::class, 'uploadDrawing'])->name('master-circuit.upload-drawing');
        Route::resource('master-circuit', MasterCircuitController::class);
    });

    // Schedule Module Routes
    Route::prefix('schedule')->name('schedule.')->group(function () {
        // Assy Scheduler
        Route::get('assy-scheduler', [AssySchedulerController::class, 'index'])->name('assy-scheduler.index');
        Route::get('assy-scheduler/datatable', [AssySchedulerController::class, 'datatable'])->name('assy-scheduler.datatable');
        Route::get('assy-scheduler/list', [AssySchedulerController::class, 'getAssyScheduleList'])->name('assy-scheduler.assy-schedule-list');
        Route::post('assy-scheduler/generate', [AssySchedulerController::class, 'generate'])->name('assy-scheduler.generate');
        Route::post('assy-scheduler/{id}/verify', [AssySchedulerController::class, 'verify'])->name('assy-scheduler.verify');
        Route::delete('assy-scheduler', [AssySchedulerController::class, 'destroy'])->name('assy-scheduler.destroy');
        Route::get('assy-scheduler/manage-data', [AssySchedulerController::class, 'manageData'])->name('assy-scheduler.manage-data');
        Route::post('assy-scheduler/save-manage', [AssySchedulerController::class, 'saveManage'])->name('assy-scheduler.save-manage');
        Route::post('assy-scheduler/available-assy', [AssySchedulerController::class, 'availableAssyData'])->name('assy-scheduler.available-assy');
        
        // Schedule Verification
        Route::get('schedule-verification', [ScheduleVerificationController::class, 'index'])->name('schedule-verification.index');
        Route::get('schedule-verification/datatable', [ScheduleVerificationController::class, 'datatable'])->name('schedule-verification.datatable');
        Route::get('schedule-verification/details', [ScheduleVerificationController::class, 'details'])->name('schedule-verification.details');
        Route::get('schedule-verification/available-assy', [ScheduleVerificationController::class, 'availableAssyData'])->name('schedule-verification.available-assy');
        Route::get('schedule-verification/available-dates', [ScheduleVerificationController::class, 'availableDates'])->name('schedule-verification.available-dates');
        Route::post('schedule-verification/save', [ScheduleVerificationController::class, 'save'])->name('schedule-verification.save');
        Route::post('schedule-verification/verify', [ScheduleVerificationController::class, 'verify'])->name('schedule-verification.verify');
        Route::get('schedule-verification/preview-unverify', [ScheduleVerificationController::class, 'previewUnverify'])->name('schedule-verification.preview-unverify');
        Route::post('schedule-verification/unverify', [ScheduleVerificationController::class, 'unverify'])->name('schedule-verification.unverify');
        Route::post('schedule-verification/reset-balance', [ScheduleVerificationController::class, 'resetBalance'])->name('schedule-verification.reset-balance');
        
        // eKanban Circuit
        Route::get('ekanban-circuit/print-machine', [EkanbanCircuitController::class, 'printMachine'])->name('ekanban-circuit.print-machine');
        Route::get('ekanban-circuit/print-preview', [EkanbanCircuitController::class, 'printPreview'])->name('ekanban-circuit.print-preview');
        Route::post('ekanban-circuit/print', [EkanbanCircuitController::class, 'print'])->name('ekanban-circuit.print');
        Route::get('ekanban-circuit/machines-by-conveyor', [EkanbanCircuitController::class, 'getMachinesByConveyor'])->name('ekanban-circuit.machines-by-conveyor');
        
        // eKanban Shikake
        Route::get('ekanban-shikake/print-machine', [EkanbanShikakeController::class, 'printMachine'])->name('ekanban-shikake.print-machine');
        Route::get('ekanban-shikake/print-preview', [EkanbanShikakeController::class, 'printPreview'])->name('ekanban-shikake.print-preview');
        Route::post('ekanban-shikake/print', [EkanbanShikakeController::class, 'print'])->name('ekanban-shikake.print');
        Route::get('ekanban-shikake/machines-by-conveyor', [EkanbanShikakeController::class, 'getMachinesByConveyor'])->name('ekanban-shikake.machines-by-conveyor');
    });

    // Defect Module Routes
    Route::prefix('defect')->name('defect.')->group(function () {
        // Defect Cutting
        Route::get('cutting', [DefectController::class, 'cuttingIndex'])->name('cutting.index');
        Route::get('cutting/datatable', [DefectController::class, 'cuttingDatatable'])->name('cutting.datatable');
        Route::post('cutting', [DefectController::class, 'cuttingStore'])->name('cutting.store');
        Route::get('cutting/circuits', [DefectController::class, 'getCircuits'])->name('cutting.circuits');
        Route::get('cutting/balance', [DefectController::class, 'getCircuitBalance'])->name('cutting.balance');
        
        // Defect Shikake
        Route::get('shikake', [DefectController::class, 'shikakeIndex'])->name('shikake.index');
        Route::get('shikake/datatable', [DefectController::class, 'shikakeDatatable'])->name('shikake.datatable');
        Route::post('shikake', [DefectController::class, 'shikakeStore'])->name('shikake.store');
        Route::get('shikake/list', [DefectController::class, 'getShikakes'])->name('shikake.list');
        Route::get('shikake/balance', [DefectController::class, 'getShikakeBalance'])->name('shikake.balance');
        
        // Defect History
        Route::get('history', [DefectController::class, 'history'])->name('history');
        Route::get('history/datatable', [DefectController::class, 'historyDatatable'])->name('history.datatable');
        Route::get('summary', [DefectController::class, 'getSummary'])->name('summary');
    });

    // Addition Module Routes
    Route::prefix('addition')->name('addition.')->group(function () {
        // Add Cutting
        Route::get('cutting', [AdditionController::class, 'cuttingIndex'])->name('cutting.index');
        Route::get('cutting/datatable', [AdditionController::class, 'cuttingDatatable'])->name('cutting.datatable');
        Route::post('cutting', [AdditionController::class, 'cuttingStore'])->name('cutting.store');
        Route::get('cutting/circuits', [AdditionController::class, 'getCircuits'])->name('cutting.circuits');
        Route::get('cutting/balance', [AdditionController::class, 'getCircuitBalance'])->name('cutting.balance');

        // Add Shikake
        Route::get('shikake', [AdditionController::class, 'shikakeIndex'])->name('shikake.index');
        Route::get('shikake/datatable', [AdditionController::class, 'shikakeDatatable'])->name('shikake.datatable');
        Route::post('shikake', [AdditionController::class, 'shikakeStore'])->name('shikake.store');
        Route::get('shikake/list', [AdditionController::class, 'getShikakes'])->name('shikake.list');
        Route::get('shikake/balance', [AdditionController::class, 'getShikakeBalance'])->name('shikake.balance');

        // Add History
        Route::get('history', [AdditionController::class, 'history'])->name('history');
        Route::get('history/datatable', [AdditionController::class, 'historyDatatable'])->name('history.datatable');
        Route::get('summary', [AdditionController::class, 'getSummary'])->name('summary');
    });
});
