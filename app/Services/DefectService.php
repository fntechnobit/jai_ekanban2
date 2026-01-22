<?php

namespace App\Services;

use App\Models\DefectLog;
use App\Models\KanbanBalance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DefectService
{
    /**
     * Record a defect and reduce balance
     * 
     * @param string $type - 'circuit' or 'shikake'
     * @param int $conveyorId - ID conveyor
     * @param array $params - Parameters based on type (cct_no, cct_code for circuit; master_shikake_id, shikake_type for shikake)
     * @param int $qtyDefect - Amount to reduce from balance
     * @param array $meta - Metadata (date, shift, reason)
     * @return array
     */
    public function recordDefect(
        string $type,
        int $conveyorId,
        array $params,
        int $qtyDefect,
        array $meta
    ): array {
        DB::beginTransaction();

        try {
            Log::info("DefectService: Recording defect", [
                'type' => $type,
                'conveyor_id' => $conveyorId,
                'params' => $params,
                'qty_defect' => $qtyDefect,
                'meta' => $meta
            ]);

            // 1. Get current balance
            if ($type === 'circuit') {
                $balance = KanbanBalance::where([
                    'conveyor_id' => $conveyorId,
                    'type' => 'circuit',
                    'cct_no' => $params['cct_no'],
                    'cct_code' => $params['cct_code'],
                ])->first();
            } else {
                $balance = KanbanBalance::where([
                    'conveyor_id' => $conveyorId,
                    'type' => 'shikake',
                    'master_shikake_id' => $params['master_shikake_id'],
                ])->first();
            }

            if (!$balance) {
                throw new \Exception('Balance record not found. Please verify a schedule first to initialize the balance.');
            }

            $balanceBefore = $balance->sisa;

            // 2. Validate: cannot reduce more than available
            if ($qtyDefect > $balanceBefore) {
                throw new \Exception("Cannot reduce {$qtyDefect} from balance {$balanceBefore}. Maximum reduction is {$balanceBefore}.");
            }

            // 3. Validate: qty must be positive
            if ($qtyDefect <= 0) {
                throw new \Exception("Defect quantity must be greater than zero.");
            }

            // 4. Reduce balance
            $balanceAfter = $balanceBefore - $qtyDefect;
            $balance->update(['sisa' => $balanceAfter]);

            // 5. Log the defect
            DefectLog::create([
                'conveyor_id' => $conveyorId,
                'type' => $type,
                'cct_no' => $params['cct_no'] ?? null,
                'cct_code' => $params['cct_code'] ?? null,
                'master_shikake_id' => $params['master_shikake_id'] ?? null,
                'shikake_type' => $params['shikake_type'] ?? null,
                'defect_date' => $meta['date'],
                'shift' => $meta['shift'],
                'qty_defect' => $qtyDefect,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => $meta['reason'] ?? null,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            Log::info("DefectService: Defect recorded successfully", [
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter
            ]);

            return [
                'success' => true,
                'message' => "Balance reduced from {$balanceBefore} to {$balanceAfter}",
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("DefectService: Failed to record defect", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get current balance for circuit
     * 
     * @param int $conveyorId
     * @param string $cctNo
     * @param string $cctCode
     * @return array|null
     */
    public function getCircuitBalance(int $conveyorId, string $cctNo, string $cctCode): ?array
    {
        $balance = KanbanBalance::where([
            'conveyor_id' => $conveyorId,
            'type' => 'circuit',
            'cct_no' => $cctNo,
            'cct_code' => $cctCode,
        ])->first();

        if (!$balance) {
            return null;
        }

        return [
            'sisa' => $balance->sisa,
            'last_nomor_urut' => $balance->last_nomor_urut,
            'last_schedule_date' => $balance->last_schedule_date?->format('Y-m-d'),
            'last_shift' => $balance->last_shift,
        ];
    }

    /**
     * Get current balance for shikake
     * 
     * @param int $conveyorId
     * @param int $masterShikakeId
     * @return array|null
     */
    public function getShikakeBalance(int $conveyorId, int $masterShikakeId): ?array
    {
        $balance = KanbanBalance::where([
            'conveyor_id' => $conveyorId,
            'type' => 'shikake',
            'master_shikake_id' => $masterShikakeId,
        ])->first();

        if (!$balance) {
            return null;
        }

        return [
            'sisa' => $balance->sisa,
            'last_nomor_urut' => $balance->last_nomor_urut,
            'last_schedule_date' => $balance->last_schedule_date?->format('Y-m-d'),
            'last_shift' => $balance->last_shift,
        ];
    }

    /**
     * Get circuits with balance for a conveyor
     * 
     * @param int $conveyorId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCircuitsWithBalance(int $conveyorId)
    {
        return KanbanBalance::where('conveyor_id', $conveyorId)
            ->where('type', 'circuit')
            ->where('sisa', '>', 0)
            ->orderBy('cct_no')
            ->orderBy('cct_code')
            ->get();
    }

    /**
     * Get shikakes with balance for a conveyor
     * 
     * @param int $conveyorId
     * @param string|null $shikakeType - Filter by process type (BONDER, JOINT, etc.)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getShikakesWithBalance(int $conveyorId, ?string $shikakeType = null)
    {
        $query = KanbanBalance::where('conveyor_id', $conveyorId)
            ->where('type', 'shikake')
            ->where('sisa', '>', 0)
            ->with('shikake');

        return $query->get()->filter(function ($balance) use ($shikakeType) {
            if (!$shikakeType) {
                return true;
            }
            return $balance->shikake && strtoupper($balance->shikake->process) === strtoupper($shikakeType);
        });
    }

    /**
     * Get defect history with filters
     * 
     * @param array $filters - Optional filters (date_from, date_to, conveyor_id, type)
     * @param int $perPage - Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getDefectHistory(array $filters = [], int $perPage = 15)
    {
        $query = DefectLog::with(['conveyor', 'shikake', 'creator'])
            ->orderBy('defect_date', 'desc')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['date_from'])) {
            $query->where('defect_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('defect_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['conveyor_id'])) {
            $query->where('conveyor_id', $filters['conveyor_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['shift'])) {
            $query->where('shift', $filters['shift']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get defect summary for a period
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|null $conveyorId
     * @return array
     */
    public function getDefectSummary(string $dateFrom, string $dateTo, ?int $conveyorId = null): array
    {
        $query = DefectLog::whereBetween('defect_date', [$dateFrom, $dateTo]);

        if ($conveyorId) {
            $query->where('conveyor_id', $conveyorId);
        }

        $total = $query->sum('qty_defect');
        $count = $query->count();
        
        $byType = DefectLog::whereBetween('defect_date', [$dateFrom, $dateTo])
            ->when($conveyorId, fn($q) => $q->where('conveyor_id', $conveyorId))
            ->selectRaw('type, SUM(qty_defect) as total_qty, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->keyBy('type')
            ->toArray();

        return [
            'total_qty' => $total,
            'total_count' => $count,
            'by_type' => $byType,
        ];
    }

    /**
     * Reset balance for a circuit (admin function)
     * 
     * @param int $conveyorId
     * @param string $cctNo
     * @param string $cctCode
     * @param int $newSisa
     * @param string $reason
     * @return array
     */
    public function resetCircuitBalance(int $conveyorId, string $cctNo, string $cctCode, int $newSisa, string $reason): array
    {
        DB::beginTransaction();

        try {
            $balance = KanbanBalance::where([
                'conveyor_id' => $conveyorId,
                'type' => 'circuit',
                'cct_no' => $cctNo,
                'cct_code' => $cctCode,
            ])->first();

            if (!$balance) {
                throw new \Exception('Balance record not found');
            }

            $balanceBefore = $balance->sisa;
            $balance->update(['sisa' => $newSisa]);

            // Log the reset as a special defect entry
            DefectLog::create([
                'conveyor_id' => $conveyorId,
                'type' => 'circuit',
                'cct_no' => $cctNo,
                'cct_code' => $cctCode,
                'defect_date' => now()->toDateString(),
                'shift' => 0, // 0 indicates admin reset
                'qty_defect' => abs($newSisa - $balanceBefore),
                'balance_before' => $balanceBefore,
                'balance_after' => $newSisa,
                'reason' => "[ADMIN RESET] " . $reason,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Balance reset from {$balanceBefore} to {$newSisa}",
                'balance_before' => $balanceBefore,
                'balance_after' => $newSisa,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reset balance for a shikake (admin function)
     * 
     * @param int $conveyorId
     * @param int $masterShikakeId
     * @param int $newSisa
     * @param string $reason
     * @return array
     */
    public function resetShikakeBalance(int $conveyorId, int $masterShikakeId, int $newSisa, string $reason): array
    {
        DB::beginTransaction();

        try {
            $balance = KanbanBalance::where([
                'conveyor_id' => $conveyorId,
                'type' => 'shikake',
                'master_shikake_id' => $masterShikakeId,
            ])->first();

            if (!$balance) {
                throw new \Exception('Balance record not found');
            }

            $balanceBefore = $balance->sisa;
            $shikakeType = $balance->shikake?->process;
            
            $balance->update(['sisa' => $newSisa]);

            // Log the reset as a special defect entry
            DefectLog::create([
                'conveyor_id' => $conveyorId,
                'type' => 'shikake',
                'master_shikake_id' => $masterShikakeId,
                'shikake_type' => $shikakeType,
                'defect_date' => now()->toDateString(),
                'shift' => 0, // 0 indicates admin reset
                'qty_defect' => abs($newSisa - $balanceBefore),
                'balance_before' => $balanceBefore,
                'balance_after' => $newSisa,
                'reason' => "[ADMIN RESET] " . $reason,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Balance reset from {$balanceBefore} to {$newSisa}",
                'balance_before' => $balanceBefore,
                'balance_after' => $newSisa,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
