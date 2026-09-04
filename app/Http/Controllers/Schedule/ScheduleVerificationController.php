<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\AssySchedule;
use App\Models\AssyScheduleCircuit;
use App\Models\AssyScheduleShikake;
use App\Models\KanbanBalanceCircuit;
use App\Models\KanbanBalanceShikake;
use App\Models\KanbanGenerationLog;
use App\Models\MasterConveyor;
use App\Services\ScheduleVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class ScheduleVerificationController extends Controller
{
    protected $scheduleVerificationService;

    public function __construct(ScheduleVerificationService $scheduleVerificationService)
    {
        $this->scheduleVerificationService = $scheduleVerificationService;
    }

    /**
     * Display the schedule verification page
     */
    public function index()
    {
        $conveyors = MasterConveyor::orderBy('conveyor', 'asc')->get();

        return view('schedule.schedule_verification.index', compact('conveyors'));
    }

    /**
     * Get datatable data for schedule verification
     */
    public function datatable(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $conveyorId = $request->input('conveyor_id');
        $status = $request->input('status');

        $schedules = $this->scheduleVerificationService->getDatatableQuery($startDate, $endDate, $conveyorId, $status);

        return DataTables::of($schedules)
            ->addIndexColumn()
            ->addColumn('conveyor_name', function ($schedule) {
                return $schedule->conveyor_name ?? '-';
            })
            ->addColumn('dates', function ($schedule) {
                return Carbon::parse($schedule->schedule_date)->format('Y-m-d');
            })
            ->addColumn('shift_name', function ($schedule) {
                return 'Shift ' . $schedule->shift;
            })
            ->addColumn('capacity', function ($schedule) {
                // Kapasitas milik SIREP. Tampilkan kapan terakhir ditarik, dan tandai
                // dengan jelas bila belum pernah tersinkron — jadwal conveyor seperti itu
                // dilewati saat generate.
                if (empty($schedule->capacity)) {
                    return '<span class="badge bg-danger" title="Kapasitas belum pernah ditarik dari SIREP. '
                        . 'Conveyor ini dilewati saat generate.">belum sinkron</span>';
                }

                $judul = 'Kapasitas SIREP ' . number_format($schedule->capacity) . '/shift';
                if (!empty($schedule->overtime_capacity)) {
                    $judul .= ' · overtime SIREP ' . number_format($schedule->overtime_capacity);
                }
                $judul .= $schedule->capacity_synced_at
                    ? ' · disinkron ' . $schedule->capacity_synced_at
                    : ' · waktu sinkron tidak tercatat';

                $waktu = $schedule->capacity_synced_at
                    ? '<br><small class="text-muted" style="font-size:.72rem">' . e($schedule->capacity_synced_at) . '</small>'
                    : '';

                return '<span title="' . e($judul) . '">' . number_format($schedule->capacity) . '</span>' . $waktu;
            })
            ->addColumn('sirep_info', function ($schedule) {
                // Penanda lembur SIREP + kapan listing hari itu ditarik dari API.
                if ($schedule->is_overtime === null) {
                    $badge = '<span class="badge bg-light text-dark" title="Tidak ada baris listing SIREP untuk tanggal ini">tanpa listing</span>';
                } elseif ($schedule->is_overtime) {
                    $badge = '<span class="badge bg-warning text-dark" title="SIREP menyatakan hari ini lembur — CO5 dibuka">OT: ya</span>';
                } else {
                    $badge = '<span class="badge bg-secondary" title="SIREP tidak menyatakan lembur — CO5 tertutup">OT: tidak</span>';
                }

                $sumber = $schedule->listing_source
                    ? ' <small class="text-muted" style="font-size:.7rem">' . e(strtoupper($schedule->listing_source)) . '</small>'
                    : '';

                $waktu = $schedule->listing_synced_at
                    ? '<br><small class="text-muted" style="font-size:.72rem" title="Waktu listing ini ditarik dari API SIREP">'
                        . e($schedule->listing_synced_at) . '</small>'
                    : '';

                return $badge . $sumber . $waktu;
            })
            ->addColumn('listing', function ($schedule) {
                if (!$schedule->has_assy && empty($schedule->total_listing)) return '0 (0)';
                $txt = number_format($schedule->total_listing) . ' (' . ($schedule->assy_count ?? 0) . ')';
                if (!empty($schedule->over_without_overtime)) {
                    // Demand tidak muat di kapasitas normal, tapi PPC tidak menyatakan lembur.
                    // Ini pertentangan data SIREP, bukan sekadar hari sibuk.
                    $title = 'Demand ' . number_format($schedule->listing_demand ?? 0)
                        . ' melebihi kapasitas normal ' . number_format($schedule->nominal_total ?? 0)
                        . ' (' . ($schedule->shift ?? 1) . ' shift x kapasitas), padahal SIREP tidak menyatakan lembur. '
                        . 'Kelebihan tetap dijadwalkan di CO5 shift terakhir. '
                        . 'Periksa kapasitas conveyor di SIREP atau konfirmasi lembur ke PPC.';
                    $txt .= ' <span class="badge bg-danger" title="' . e($title) . '">! over tanpa OT</span>';
                } elseif (!empty($schedule->is_over_capacity)) {
                    $title = 'Terjadwal ' . number_format($schedule->scheduled_qty ?? 0)
                        . ' dari ' . number_format($schedule->total_listing) . ' (melebihi kapasitas)';
                    $txt .= ' <span class="badge bg-warning text-dark" title="' . e($title) . '">! over</span>';
                }
                return $txt;
            })
            ->addColumn('assy', function ($schedule) {
                return $schedule->has_assy ? ($schedule->assy_list ?: '-') : '-';
            })
            ->addColumn('status', function ($schedule) {
                if (!$schedule->has_assy) {
                    return '<span class="badge bg-secondary">No Data</span>';
                }
                if ($schedule->is_lock == 1) {
                    return '<span class="badge bg-success">Verified</span>';
                }
                return '<span class="badge bg-danger">Pending</span>';
            })
            ->addColumn('action', function ($schedule) {
                if ($schedule->has_assy && $schedule->is_lock == 1) {
                    // Verified — show Detail + Unverify
                    return '<div class="btn-group" role="group">
                        <button type="button" class="btn btn-soft-info btn-sm btn-detail" 
                            data-conveyor-id="' . $schedule->conveyor_id . '" 
                            data-date="' . $schedule->schedule_date . '" 
                            data-shift="' . $schedule->shift . '">
                            <i class="ti ti-eye"></i> Detail
                        </button>
                        <button type="button" class="btn btn-soft-warning btn-sm btn-unverify" 
                            data-conveyor-id="' . $schedule->conveyor_id . '" 
                            data-date="' . $schedule->schedule_date . '" 
                            data-shift="' . $schedule->shift . '">
                            <i class="ti ti-lock-open"></i> Unverify
                        </button>
                    </div>';
                }
                // Pending OR No Data — show Verify button (allows opening modal to drag-in from other dates)
                return '<div class="btn-group" role="group">
                    <button type="button" class="btn btn-soft-success btn-sm btn-verify" 
                        data-conveyor-id="' . $schedule->conveyor_id . '" 
                        data-date="' . $schedule->schedule_date . '" 
                        data-shift="' . $schedule->shift . '">
                        <i class="ti ti-check"></i> Verify
                    </button>
                </div>';
            })
            ->rawColumns(['capacity', 'sirep_info', 'listing', 'status', 'action'])
            ->make(true);
    }

    /**
     * Get verification details for a specific schedule
     */
    public function details(Request $request)
    {
        $conveyorId = $request->input('conveyor_id');
        $date = $request->input('date');
        $shift = $request->input('shift');

        $result = $this->scheduleVerificationService->getVerificationDetails($conveyorId, $date, $shift);

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    /**
     * Get available assy data for drag and drop
     */
    public function availableAssyData(Request $request)
    {
        $conveyorId = $request->input('conveyor_id');
        $date = $request->input('date');
        $shift = $request->input('shift');

        $result = $this->scheduleVerificationService->getAvailableAssyData($conveyorId, $date, $shift);

        return response()->json($result);
    }

    /**
     * Get available dates (H to H+10) that have schedules for a conveyor
     */
    public function availableDates(Request $request)
    {
        $conveyorId = $request->input('conveyor_id');
        $currentDate = $request->input('current_date');
        $currentShift = $request->input('current_shift');
        $daysRange = $request->input('days_range', 10);

        $result = $this->scheduleVerificationService->getAvailableDates(
            $conveyorId, $currentDate, $currentShift, $daysRange
        );

        return response()->json($result);
    }

    /**
     * Save verification changes
     */
    public function save(Request $request)
    {
        $request->validate([
            'conveyor_id' => 'required|integer',
            'date' => 'required|date',
            'shift' => 'required|integer',
            'schedules' => 'nullable|array',
            'schedules.*.id' => 'required|integer',
            'schedules.*.cutoff' => 'required|integer',
            'schedules.*.qty' => 'required|integer|min:1',
            'new_items' => 'nullable|array',
            'new_items.*.assy' => 'required|string',
            'new_items.*.cutoff' => 'required|integer',
            'new_items.*.qty' => 'required|integer|min:1',
        ]);

        $result = $this->scheduleVerificationService->saveVerification(
            $request->input('conveyor_id'),
            $request->input('date'),
            $request->input('shift'),
            $request->input('schedules', []),
            $request->input('new_items', [])
        );

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    /**
     * Verify a schedule - lock it for specific conveyor, date and shift
     */
    public function verify(Request $request)
    {
        $data = $request->json()->all();
        
        $request->validate([
            'conveyor_id' => 'required|integer',
            'date' => 'required|date',
            'shift' => 'required|integer',
            'cutoffs' => 'nullable|array',
        ]);

        $result = $this->scheduleVerificationService->verifySchedule(
            $data['conveyor_id'] ?? $request->input('conveyor_id'),
            $data['date'] ?? $request->input('date'),
            $data['shift'] ?? $request->input('shift'),
            $data['cutoffs'] ?? $request->input('cutoffs', [])
        );

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    /**
     * Preview unverify impact - show which transferred items will be restored or lost.
     */
    public function previewUnverify(Request $request)
    {
        $request->validate([
            'conveyor_id' => 'required|integer',
            'date' => 'required|date',
            'shift' => 'required|integer',
        ]);

        $result = $this->scheduleVerificationService->previewUnverify(
            $request->input('conveyor_id'),
            $request->input('date'),
            $request->input('shift')
        );

        return response()->json($result);
    }

    /**
     * Unverify a schedule - unlock it for specific conveyor, date and shift
     */
    public function unverify(Request $request)
    {
        $request->validate([
            'conveyor_id' => 'required|integer',
            'date' => 'required|date',
            'shift' => 'required|integer',
        ]);

        $result = $this->scheduleVerificationService->unverifySchedule(
            $request->input('conveyor_id'),
            $request->input('date'),
            $request->input('shift')
        );

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    /**
     * Reset kanban balance (sisa & nomor_urut) to zero.
     *
     * Two modes (parameter `reset_mode`):
     *  - 'full' (default): also clears all generated kanbans and unverifies all
     *    schedules to ensure full consistency. Verified schedules must be
     *    re-verified afterwards.
     *  - 'balance_only': hanya menolkan sisa dan menghapus ledger generate.
     *    `last_nomor_urut` dipertahankan agar barcode tidak bertabrakan. Kanban
     *    and verification status are left untouched.
     *    WARNING: if generated kanbans still exist, the next generation will
     *    restart nomor_urut from 0 and may collide with existing barcodes.
     */
    public function resetBalance(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|in:RESET SEMUA BALANCE',
            'conveyor_id' => 'nullable|integer|exists:master_conveyor,id',
            'reset_mode' => 'nullable|in:full,balance_only',
        ]);

        $balanceOnly = $request->input('reset_mode') === 'balance_only';

        try {
            DB::beginTransaction();

            $conveyorId = $request->input('conveyor_id');

            if ($conveyorId) {
                // Reset only for the selected conveyor
                $circuitCount = KanbanBalanceCircuit::where('conveyor_id', $conveyorId)->count();
                $shikakeCount = KanbanBalanceShikake::where('conveyor_id', $conveyorId)->count();

                // 1. Reset balance to zero
                // `last_nomor_urut` SENGAJA tidak dinolkan: ia adalah 4 angka terakhir
                // barcode kanban. Menurunkannya membuat barcode baru bertabrakan dengan
                // kanban yang sudah tercetak dan beredar di lapangan.
                KanbanBalanceCircuit::where('conveyor_id', $conveyorId)->update(['sisa' => 0]);
                KanbanBalanceShikake::where('conveyor_id', $conveyorId)->update(['sisa' => 0]);

                // Ledger generate harus ikut dibersihkan. Bila tidak, saldo 0 akan selamanya
                // bertentangan dengan riwayat delta-nya, dan pembalikan (unverify) berikutnya
                // akan mengurangi saldo yang sudah tidak ada — inilah sumber saldo melorot.
                $hapusLedger = KanbanGenerationLog::where('conveyor_id', $conveyorId)->delete();

                $deletedCircuitKanbans = 0;
                $deletedShikakeKanbans = 0;
                $unverifiedCount = 0;

                if (!$balanceOnly) {
                    // 2. Delete all generated kanbans for this conveyor
                    $scheduleIds = AssySchedule::where('conveyor_id', $conveyorId)->pluck('id');
                    if ($scheduleIds->isNotEmpty()) {
                        $deletedCircuitKanbans = AssyScheduleCircuit::whereIn('assy_schedule_id', $scheduleIds)->delete();
                        $deletedShikakeKanbans = AssyScheduleShikake::whereIn('assy_schedule_id', $scheduleIds)->delete();
                    }

                    // 3. Unverify all verified schedules for this conveyor
                    $unverifiedCount = AssySchedule::where('conveyor_id', $conveyorId)
                        ->where('is_lock', 1)
                        ->update([
                            'is_lock' => 0,
                            'verified_at' => null,
                            'verified_by' => null,
                            'updated_by' => auth()->id(),
                            'updated_at' => now(),
                        ]);
                }

                $conveyor = MasterConveyor::find($conveyorId);
                $conveyorName = $conveyor ? $conveyor->conveyor : $conveyorId;

                DB::commit();

                Log::warning('KANBAN BALANCE RESET: ' . ($balanceOnly ? 'Balance-only' : 'Full') . ' reset for conveyor ' . $conveyorName . ' by user ' . auth()->id(), [
                    'conveyor_id' => $conveyorId,
                    'reset_mode' => $balanceOnly ? 'balance_only' : 'full',
                    'circuit_balance_records' => $circuitCount,
                    'shikake_balance_records' => $shikakeCount,
                    'deleted_circuit_kanbans' => $deletedCircuitKanbans,
                    'deleted_shikake_kanbans' => $deletedShikakeKanbans,
                    'unverified_schedules' => $unverifiedCount,
                    'ledger_dihapus' => $hapusLedger,
                ]);

                $message = $balanceOnly
                    ? "Reset SALDO SAJA berhasil untuk conveyor {$conveyorName}. "
                        . "{$circuitCount} balance circuit dan {$shikakeCount} balance shikake di-reset ke 0. "
                        . "Kanban & status verifikasi tidak diubah. Nomor urut barcode dipertahankan."
                    : "Reset berhasil untuk conveyor {$conveyorName}. "
                        . "{$circuitCount} balance circuit dan {$shikakeCount} balance shikake di-reset ke 0. "
                        . "{$deletedCircuitKanbans} kanban circuit dan {$deletedShikakeKanbans} kanban shikake dihapus. "
                        . "{$unverifiedCount} schedule di-unverify.";

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            } else {
                // Reset all
                $circuitCount = KanbanBalanceCircuit::count();
                $shikakeCount = KanbanBalanceShikake::count();

                // 1. Reset all balances to zero
                // Lihat catatan pada cabang per-conveyor: nomor urut tidak dinolkan.
                KanbanBalanceCircuit::query()->update(['sisa' => 0]);
                KanbanBalanceShikake::query()->update(['sisa' => 0]);

                $hapusLedger = KanbanGenerationLog::query()->delete();

                $deletedCircuitKanbans = 0;
                $deletedShikakeKanbans = 0;
                $unverifiedCount = 0;

                if (!$balanceOnly) {
                    // 2. Delete all generated kanbans
                    $deletedCircuitKanbans = AssyScheduleCircuit::query()->delete();
                    $deletedShikakeKanbans = AssyScheduleShikake::query()->delete();

                    // 3. Unverify all verified schedules
                    $unverifiedCount = AssySchedule::where('is_lock', 1)
                        ->update([
                            'is_lock' => 0,
                            'verified_at' => null,
                            'verified_by' => null,
                            'updated_by' => auth()->id(),
                            'updated_at' => now(),
                        ]);
                }

                DB::commit();

                Log::warning('KANBAN BALANCE RESET: ' . ($balanceOnly ? 'Balance-only' : 'Full') . ' reset ALL by user ' . auth()->id(), [
                    'reset_mode' => $balanceOnly ? 'balance_only' : 'full',
                    'circuit_balance_records' => $circuitCount,
                    'shikake_balance_records' => $shikakeCount,
                    'deleted_circuit_kanbans' => $deletedCircuitKanbans,
                    'deleted_shikake_kanbans' => $deletedShikakeKanbans,
                    'unverified_schedules' => $unverifiedCount,
                    'ledger_dihapus' => $hapusLedger,
                ]);

                $message = $balanceOnly
                    ? "Reset SALDO SAJA berhasil. "
                        . "{$circuitCount} balance circuit dan {$shikakeCount} balance shikake di-reset ke 0. "
                        . "Kanban & status verifikasi tidak diubah. Nomor urut barcode dipertahankan."
                    : "Reset berhasil. "
                        . "{$circuitCount} balance circuit dan {$shikakeCount} balance shikake di-reset ke 0. "
                        . "{$deletedCircuitKanbans} kanban circuit dan {$deletedShikakeKanbans} kanban shikake dihapus. "
                        . "{$unverifiedCount} schedule di-unverify.";

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('KANBAN BALANCE RESET FAILED: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Reset gagal: ' . $e->getMessage(),
            ], 500);
        }
    }
}
