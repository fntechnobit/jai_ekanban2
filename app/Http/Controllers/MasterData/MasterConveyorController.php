<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\MasterConveyorService;
use App\Services\SirepConveyorSyncService;
use App\Http\Requests\MasterConveyorRequest;
use App\Helpers\ResponseHelper;
use App\Models\MasterArea;
use App\Models\MasterFamily;
use Illuminate\Http\Request;

class MasterConveyorController extends Controller
{
    protected $masterConveyorService;

    public function __construct(MasterConveyorService $masterConveyorService)
    {
        $this->masterConveyorService = $masterConveyorService;

        $this->middleware('check.menu:master_conveyor,can_read')->only(['index', 'datatable', 'show', 'syncPreview']);
        // Menulis kapasitas = mengubah master, jadi ikut izin update.
        $this->middleware('check.menu:master_conveyor,can_update')->only(['syncApply']);
        $this->middleware('check.menu:master_conveyor,can_update')->only(['edit', 'update']);
        $this->middleware('check.menu:master_conveyor,can_delete')->only(['destroy']);
    }

    public function index()
    {
        $areas = MasterArea::orderBy('area')->get();
        $families = MasterFamily::orderBy('family')->get();
        return view('master_data.master_conveyor.index', compact('areas', 'families'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $areaId = $request->get('area_id');
            $familyId = $request->get('family_id');
            $status = $request->get('status');
            return $this->masterConveyorService->getDatatable($areaId, $familyId, $status);
        }
    }

    /**
     * Pratinjau sinkronisasi kapasitas dari SIREP — tidak menulis apa pun.
     * Dipisah dari syncApply agar user bisa melihat dampaknya sebelum menerapkan.
     */
    public function syncPreview(SirepConveyorSyncService $syncer)
    {
        $result = $syncer->sync(false);

        return $result['success']
            ? ResponseHelper::success($result, $result['message'])
            : ResponseHelper::error($result['message'], 422);
    }

    /** Terapkan sinkronisasi kapasitas dari SIREP ke master_conveyor. */
    public function syncApply(SirepConveyorSyncService $syncer)
    {
        $result = $syncer->sync(true);

        return $result['success']
            ? ResponseHelper::success($result, $result['message'])
            : ResponseHelper::error($result['message'], 422);
    }

    /**
     * Conveyor tidak dapat dibuat manual — daftarnya milik SIREP.
     * Route-nya dipertahankan agar permintaan lama menerima penjelasan, bukan 404.
     */
    public function store()
    {
        return ResponseHelper::error(
            'Conveyor tidak dapat ditambahkan manual. Daftar conveyor berasal dari API SIREP — '
            . 'gunakan tombol "Sync Conveyor SIREP" untuk menariknya.',
            422
        );
    }

    public function create()
    {
        return $this->store();
    }

    public function show($id)
    {
        $conveyor = $this->masterConveyorService->findById($id);
        $conveyor->family_ids = $conveyor->families->pluck('id')->toArray();
        // Tanggal disiapkan di sini supaya tampilan tidak perlu mengurai format ISO.
        $conveyor->capacity_synced_label = $conveyor->capacity_synced_at
            ? $conveyor->capacity_synced_at->format('d M Y H:i')
            : null;
        $conveyor->deactivated_label = $conveyor->deactivated_at
            ? $conveyor->deactivated_at->format('d M Y H:i')
            : null;
        return ResponseHelper::success($conveyor);
    }

    public function edit($id)
    {
        $conveyor = $this->masterConveyorService->findById($id);
        $conveyor->family_ids = $conveyor->families->pluck('id')->toArray();
        // Tanggal disiapkan di sini supaya tampilan tidak perlu mengurai format ISO.
        $conveyor->capacity_synced_label = $conveyor->capacity_synced_at
            ? $conveyor->capacity_synced_at->format('d M Y H:i')
            : null;
        $conveyor->deactivated_label = $conveyor->deactivated_at
            ? $conveyor->deactivated_at->format('d M Y H:i')
            : null;
        return ResponseHelper::success($conveyor);
    }

    public function update(MasterConveyorRequest $request, $id)
    {
        try {
            $conveyor = $this->masterConveyorService->findById($id);
            $this->masterConveyorService->update($conveyor, $request->validated());
            return ResponseHelper::success($conveyor, 'Conveyor updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Penghapusan manual ditutup: conveyor yang tidak ada lagi di SIREP
     * dinonaktifkan otomatis saat sinkronisasi, bukan dihapus, supaya jadwal
     * dan kanban lamanya tetap dapat ditelusuri.
     */
    public function destroy($id)
    {
        return ResponseHelper::error(
            'Conveyor tidak dapat dihapus. Conveyor yang sudah tidak ada di SIREP akan '
            . 'dinonaktifkan sendiri saat sinkronisasi.',
            422
        );
    }

    public function destroyLegacy($id)
    {
        try {
            $conveyor = $this->masterConveyorService->findById($id);
            $this->masterConveyorService->delete($conveyor);
            return ResponseHelper::success(null, 'Conveyor deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
