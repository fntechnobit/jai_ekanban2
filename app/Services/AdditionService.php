<?php

namespace App\Services;

use App\Imports\CuttingStoImport;
use App\Models\AdditionLogCircuit;
use App\Models\AdditionLogShikake;
use App\Models\KanbanBalanceCircuit;
use App\Models\KanbanBalanceShikake;
use App\Models\MasterCircuit;
use App\Models\MasterShikake;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class AdditionService
{
    /**
     * Get datatable for cutting/circuit with balance info (Addition context).
     */
    public function getCuttingDatatable($conveyorId = null, $type = null, $areaId = null)
    {
        $query = MasterCircuit::query()
            ->join('master_conveyor', 'master_circuit.conveyor_id', '=', 'master_conveyor.id')
            ->leftJoin('kanban_balance_circuit', function ($join) {
                $join->on('kanban_balance_circuit.master_circuit_id', '=', 'master_circuit.id')
                     ->on('kanban_balance_circuit.conveyor_id', '=', 'master_circuit.conveyor_id');
            })
            ->whereNull('master_circuit.deleted_at')
            ->whereNull('master_conveyor.deleted_at')
            ->select([
                'master_circuit.id',
                'master_circuit.conveyor_id',
                'master_circuit.type',
                'master_circuit.carline',
                'master_conveyor.conveyor as conveyor_name',
                'master_circuit.cct_no',
                'master_circuit.cct_code',
                'master_circuit.shikake_code',
                'master_circuit.family',
                'master_circuit.qty',
                'master_circuit.machine',
                'master_circuit.sequence',
                DB::raw('COALESCE(kanban_balance_circuit.sisa, 0) as balance'),
            ]);

        if ($conveyorId) {
            $query->where('master_circuit.conveyor_id', $conveyorId);
        }
        if ($type) {
            $query->where('master_circuit.type', $type);
        }
        if ($areaId) {
            $query->where('master_conveyor.master_area_id', $areaId);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('type_badge', function ($row) {
                return $row->type === 'CUTTING_TWIST'
                    ? '<span class="badge bg-info">TWS</span>'
                    : '<span class="badge bg-primary">CCT</span>';
            })
            ->addColumn('balance_display', function ($row) {
                $balance = (int) $row->balance;
                if ($balance > 0) {
                    return '<span class="badge bg-success">' . number_format($balance) . '</span>';
                }
                return '<span class="badge bg-secondary">0</span>';
            })
            ->addColumn('action', function ($row) {
                $balance  = (int) $row->balance;
                // Jumlah shift tidak lagi tersimpan per conveyor; dropdown entri manual
                // memakai batas atas yang sama dengan engine penjadwalan.
                $shiftQty = (int) config('sirep.capacity.max_shift', 2);
                return '<button type="button" class="btn btn-success btn-sm btn-addition" '
                     . 'data-id="' . $row->id . '" '
                     . 'data-conveyor-id="' . $row->conveyor_id . '" '
                     . 'data-conveyor="' . e($row->conveyor_name) . '" '
                     . 'data-cct-no="' . e($row->cct_no) . '" '
                     . 'data-cct-code="' . e($row->cct_code) . '" '
                     . 'data-balance="' . $balance . '" '
                     . 'data-shift-qty="' . $shiftQty . '">'
                     . '<i class="fa-solid fa-circle-plus me-1"></i> Add'
                     . '</button>';
            })
            ->rawColumns(['type_badge', 'balance_display', 'action'])
            ->make(true);
    }

    /**
     * Get datatable for shikake with balance info (Addition context).
     */
    public function getShikakeDatatable($conveyorId = null, $processType = null, $areaId = null)
    {
        $query = MasterShikake::query()
            ->join('master_conveyor', 'master_shikake.conveyor_id', '=', 'master_conveyor.id')
            ->leftJoin('kanban_balance_shikake', function ($join) {
                $join->on('kanban_balance_shikake.master_shikake_id', '=', 'master_shikake.id')
                     ->on('kanban_balance_shikake.conveyor_id', '=', 'master_shikake.conveyor_id');
            });
        MasterShikake::joinIdentifierTables($query);
        $query->whereNull('master_shikake.deleted_at')
            ->whereNull('master_conveyor.deleted_at')
            ->select(array_merge([
                'master_shikake.id',
                'master_shikake.conveyor_id',
                'master_shikake.process',
                'master_shikake.carline',
                'master_conveyor.conveyor as conveyor_name',
                'master_shikake.machine',
                'master_shikake.qty',
                'master_shikake.family',
                'master_shikake.sequence',
                DB::raw('COALESCE(kanban_balance_shikake.sisa, 0) as balance'),
            ], MasterShikake::identifierSelectColumns()));

        if ($conveyorId) {
            $query->where('master_shikake.conveyor_id', $conveyorId);
        }
        if ($processType) {
            $query->where('master_shikake.process', $processType);
        }
        if ($areaId) {
            $query->where('master_conveyor.master_area_id', $areaId);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('process_badge', function ($row) {
                $colors = [
                    'BONDER'    => 'bg-primary',
                    'DBL CRIMP' => 'bg-warning text-dark',
                    'JOINT'     => 'bg-success',
                    'SHIELD'    => 'bg-info',
                    'TWIST'     => 'bg-secondary',
                ];
                $color = $colors[strtoupper($row->process)] ?? 'bg-dark';
                return '<span class="badge ' . $color . '">' . e($row->process) . '</span>';
            })
            ->addColumn('kode_shikake', function ($row) {
                return MasterShikake::resolveIdentifier($row);
            })
            ->addColumn('balance_display', function ($row) {
                $balance = (int) $row->balance;
                if ($balance > 0) {
                    return '<span class="badge bg-success">' . number_format($balance) . '</span>';
                }
                return '<span class="badge bg-secondary">0</span>';
            })
            ->addColumn('action', function ($row) {
                $balance  = (int) $row->balance;
                // Jumlah shift tidak lagi tersimpan per conveyor; dropdown entri manual
                // memakai batas atas yang sama dengan engine penjadwalan.
                $shiftQty = (int) config('sirep.capacity.max_shift', 2);
                return '<button type="button" class="btn btn-success btn-sm btn-addition" '
                     . 'data-id="' . $row->id . '" '
                     . 'data-conveyor-id="' . $row->conveyor_id . '" '
                     . 'data-conveyor="' . e($row->conveyor_name) . '" '
                     . 'data-process="' . e($row->process) . '" '
                     . 'data-machine="' . e($row->machine) . '" '
                     . 'data-balance="' . $balance . '" '
                     . 'data-shift-qty="' . $shiftQty . '">'
                     . '<i class="fa-solid fa-circle-plus me-1"></i> Add'
                     . '</button>';
            })
            ->rawColumns(['process_badge', 'balance_display', 'action'])
            ->make(true);
    }

    /**
     * Get datatable for addition history (combined circuit + shikake).
     */
    public function getHistoryDatatable($type = 'circuit', $filters = [])
    {
        if ($type === 'shikake') {
            $query = AdditionLogShikake::with(['conveyor', 'masterShikake', 'creator'])
                ->select('addition_log_shikake.*');

            if (!empty($filters['shikake_type'])) {
                $query->where('shikake_type', $filters['shikake_type']);
            }
        } else {
            $query = AdditionLogCircuit::with(['conveyor', 'masterCircuit', 'creator'])
                ->select('addition_log_circuit.*');
        }

        if (!empty($filters['date_from'])) {
            $query->where('addition_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('addition_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['conveyor_id'])) {
            $query->where('conveyor_id', $filters['conveyor_id']);
        }
        if (!empty($filters['shift'])) {
            $query->where('shift', $filters['shift']);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('date_display', function ($row) {
                return $row->addition_date ? $row->addition_date->format('Y-m-d') : '-';
            })
            ->addColumn('shift_display', function ($row) {
                if ($row->shift == 0) {
                    return '<span class="badge bg-secondary">Admin</span>';
                }
                return '<span class="badge bg-info">S' . $row->shift . '</span>';
            })
            ->addColumn('conveyor_display', function ($row) {
                return $row->conveyor?->conveyor ?? '-';
            })
            ->addColumn('code_display', function ($row) use ($type) {
                if ($type === 'shikake') {
                    return $row->masterShikake?->machine ?? "SHK-{$row->master_shikake_id}";
                }
                if ($row->masterCircuit) {
                    return $row->masterCircuit->cct_no . ' - ' . $row->masterCircuit->cct_code;
                }
                return "Circuit #{$row->master_circuit_id}";
            })
            ->addColumn('type_display', function ($row) {
                if (isset($row->shikake_type) && $row->shikake_type) {
                    $colors = [
                        'BONDER'    => 'bg-primary',
                        'DBL_CRIMP' => 'bg-warning text-dark',
                        'JOINT'     => 'bg-success',
                        'SHIELD'    => 'bg-info',
                        'TWIST'     => 'bg-secondary',
                    ];
                    $color = $colors[$row->shikake_type] ?? 'bg-dark';
                    return '<span class="badge ' . $color . '">' . $row->shikake_type . '</span>';
                }
                return '-';
            })
            ->addColumn('qty_display', function ($row) {
                return '<span class="text-success fw-bold">+' . number_format($row->qty_addition) . '</span>';
            })
            ->addColumn('reason_display', function ($row) {
                if ($row->reason) {
                    $short = \Illuminate\Support\Str::limit($row->reason, 30);
                    return '<span data-bs-toggle="tooltip" title="' . e($row->reason) . '">' . e($short) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('creator_name', function ($row) {
                return $row->creator?->name ?? '-';
            })
            ->orderColumn('date_display', function ($query, $order) {
                $query->orderBy('addition_date', $order);
            })
            ->rawColumns(['shift_display', 'type_display', 'qty_display', 'reason_display'])
            ->make(true);
    }

    /**
     * Record an addition and increase balance.
     *
     * @param string $type        'circuit' or 'shikake'
     * @param int    $conveyorId
     * @param array  $params      master_circuit_id OR master_shikake_id + shikake_type
     * @param int    $qtyAddition Amount to add to balance
     * @param array  $meta        date, shift, reason
     */
    public function recordAddition(
        string $type,
        int $conveyorId,
        array $params,
        int $qtyAddition,
        array $meta
    ): array {
        DB::beginTransaction();

        try {
            Log::info('AdditionService: Recording addition', [
                'type'         => $type,
                'conveyor_id'  => $conveyorId,
                'params'       => $params,
                'qty_addition' => $qtyAddition,
                'meta'         => $meta,
            ]);

            if ($qtyAddition <= 0) {
                throw new \Exception('Addition quantity must be greater than zero.');
            }

            // 1. Get or create balance record
            if ($type === 'circuit') {
                $balance = KanbanBalanceCircuit::where([
                    'conveyor_id'      => $conveyorId,
                    'master_circuit_id' => $params['master_circuit_id'],
                ])->first();
            } else {
                $balance = KanbanBalanceShikake::where([
                    'conveyor_id'       => $conveyorId,
                    'master_shikake_id' => $params['master_shikake_id'],
                ])->first();
            }

            if (!$balance) {
                throw new \Exception('Balance record not found. Please verify a schedule first to initialize the balance.');
            }

            $balanceBefore = $balance->sisa;
            $balanceAfter  = $balanceBefore + $qtyAddition;

            // 2. Increase balance
            $balance->update(['sisa' => $balanceAfter]);

            // 3. Log the addition
            if ($type === 'circuit') {
                AdditionLogCircuit::create([
                    'conveyor_id'       => $conveyorId,
                    'master_circuit_id' => $params['master_circuit_id'],
                    'addition_date'     => $meta['date'],
                    'shift'             => $meta['shift'],
                    'qty_addition'      => $qtyAddition,
                    'balance_before'    => $balanceBefore,
                    'balance_after'     => $balanceAfter,
                    'reason'            => $meta['reason'] ?? null,
                    'created_by'        => Auth::id(),
                ]);
            } else {
                AdditionLogShikake::create([
                    'conveyor_id'       => $conveyorId,
                    'master_shikake_id' => $params['master_shikake_id'],
                    'shikake_type'      => $params['shikake_type'] ?? null,
                    'addition_date'     => $meta['date'],
                    'shift'             => $meta['shift'],
                    'qty_addition'      => $qtyAddition,
                    'balance_before'    => $balanceBefore,
                    'balance_after'     => $balanceAfter,
                    'reason'            => $meta['reason'] ?? null,
                    'created_by'        => Auth::id(),
                ]);
            }

            DB::commit();

            Log::info('AdditionService: Addition recorded successfully', [
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
            ]);

            return [
                'success'        => true,
                'message'        => "Balance increased from {$balanceBefore} to {$balanceAfter}",
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdditionService: Failed to record addition', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse an STO circuit scan history file (jai_sto_wip export) and match
     * its CCT Codes against the selected conveyor's circuits, WITHOUT writing
     * anything to the database. Used to render a preview before commit.
     */
    public function previewCuttingSto(string $filePath, int $conveyorId): array
    {
        $importer = new CuttingStoImport($conveyorId);
        $matched = $importer->parse($filePath);
        $notFoundCodes = $importer->getNotFoundCodes();

        return [
            'total_rows'      => $importer->getTotalRows(),
            'matched'         => $matched,
            'matched_count'   => count($matched),
            'total_qty'       => array_sum(array_column($matched, 'qty')),
            'not_found_codes' => $notFoundCodes,
            'not_found_count' => count($notFoundCodes),
        ];
    }

    /**
     * Apply a batch of previously previewed rows as balance additions for the
     * selected conveyor, date and shift. Intended to be called once per chunk
     * so the caller can render incremental progress.
     *
     * @param array $items Each: ['master_circuit_id' => int, 'cct_code' => string, 'qty' => int]
     */
    public function commitCuttingAdditions(int $conveyorId, string $date, int $shift, array $items): array
    {
        $successCount = 0;
        $errors = [];

        foreach ($items as $item) {
            $result = $this->recordAddition(
                'circuit',
                $conveyorId,
                ['master_circuit_id' => $item['master_circuit_id']],
                $item['qty'],
                [
                    'date'   => $date,
                    'shift'  => $shift,
                    'reason' => 'Import STO Circuit Scan History (CCT ' . $item['cct_code'] . ')',
                ]
            );

            if ($result['success']) {
                $successCount++;
            } else {
                $errors[] = "{$item['cct_code']}: {$result['message']}";
            }
        }

        return [
            'success'       => true,
            'success_count' => $successCount,
            'failed_count'  => count($items) - $successCount,
            'errors'        => $errors,
        ];
    }

    /**
     * Get current balance for a circuit.
     */
    public function getCircuitBalance(int $conveyorId, int $masterCircuitId): ?array
    {
        $balance = KanbanBalanceCircuit::where([
            'conveyor_id'       => $conveyorId,
            'master_circuit_id' => $masterCircuitId,
        ])->first();

        if (!$balance) {
            return null;
        }

        return [
            'sisa'               => $balance->sisa,
            'last_nomor_urut'    => $balance->last_nomor_urut,
            'last_schedule_date' => $balance->last_schedule_date?->format('Y-m-d'),
            'last_shift'         => $balance->last_shift,
        ];
    }

    /**
     * Get current balance for a shikake.
     */
    public function getShikakeBalance(int $conveyorId, int $masterShikakeId): ?array
    {
        $balance = KanbanBalanceShikake::where([
            'conveyor_id'       => $conveyorId,
            'master_shikake_id' => $masterShikakeId,
        ])->first();

        if (!$balance) {
            return null;
        }

        return [
            'sisa'               => $balance->sisa,
            'last_nomor_urut'    => $balance->last_nomor_urut,
            'last_schedule_date' => $balance->last_schedule_date?->format('Y-m-d'),
            'last_shift'         => $balance->last_shift,
        ];
    }

    /**
     * Get circuits with their balance for a conveyor (all circuits, not only with sisa > 0).
     */
    public function getCircuitsWithBalance(int $conveyorId)
    {
        return KanbanBalanceCircuit::where('conveyor_id', $conveyorId)
            ->with('masterCircuit')
            ->orderBy('master_circuit_id')
            ->get();
    }

    /**
     * Get shikakes with their balance for a conveyor and optional type filter.
     */
    public function getShikakesWithBalance(int $conveyorId, ?string $shikakeType = null)
    {
        $query = KanbanBalanceShikake::where('conveyor_id', $conveyorId)
            ->with('masterShikake');

        return $query->get()->filter(function ($balance) use ($shikakeType) {
            if (!$shikakeType) {
                return true;
            }
            return $balance->masterShikake
                && strtoupper($balance->masterShikake->process) === strtoupper($shikakeType);
        });
    }

    /**
     * Get circuit addition summary for a period.
     */
    public function getCircuitAdditionSummary(string $dateFrom, string $dateTo, ?int $conveyorId = null): array
    {
        $query = AdditionLogCircuit::whereBetween('addition_date', [$dateFrom, $dateTo]);

        if ($conveyorId) {
            $query->where('conveyor_id', $conveyorId);
        }

        return [
            'total_qty'   => (clone $query)->sum('qty_addition'),
            'total_count' => (clone $query)->count(),
        ];
    }

    /**
     * Get shikake addition summary for a period.
     */
    public function getShikakeAdditionSummary(string $dateFrom, string $dateTo, ?int $conveyorId = null): array
    {
        $query = AdditionLogShikake::whereBetween('addition_date', [$dateFrom, $dateTo]);

        if ($conveyorId) {
            $query->where('conveyor_id', $conveyorId);
        }

        $total = (clone $query)->sum('qty_addition');
        $count = (clone $query)->count();

        $byType = AdditionLogShikake::whereBetween('addition_date', [$dateFrom, $dateTo])
            ->when($conveyorId, fn ($q) => $q->where('conveyor_id', $conveyorId))
            ->selectRaw('shikake_type, SUM(qty_addition) as total_qty, COUNT(*) as count')
            ->groupBy('shikake_type')
            ->get()
            ->keyBy('shikake_type')
            ->toArray();

        return [
            'total_qty'   => $total,
            'total_count' => $count,
            'by_type'     => $byType,
        ];
    }
}
