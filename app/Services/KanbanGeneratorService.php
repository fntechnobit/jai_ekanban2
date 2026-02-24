<?php

namespace App\Services;

use App\Enums\ProcessType;
use App\Models\AssySchedule;
use App\Models\AssyScheduleCircuit;
use App\Models\AssyScheduleShikake;
use App\Models\KanbanBalanceCircuit;
use App\Models\KanbanBalanceShikake;
use App\Models\MasterAssy;
use App\Models\MasterCircuit;
use App\Models\MasterConveyor;
use App\Models\MasterShikake;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class KanbanGeneratorService
{
    /**
     * Generate kanban data for a schedule group (conveyor + date + shift)
     * 
     * @param int $conveyorId
     * @param string $date
     * @param int $shift
     * @return array
     */
    public function generateKanbanForSchedule(int $conveyorId, string $date, int $shift): array
    {
        Log::info("KanbanGeneratorService: Starting generation", [
            'conveyor_id' => $conveyorId,
            'date' => $date,
            'shift' => $shift
        ]);

        // Get all schedules for this conveyor, date, and shift ordered by cutoff
        $schedules = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->where('shift', $shift)
            ->orderBy('cutoff')
            ->orderBy('listing_id')
            ->get();

        if ($schedules->isEmpty()) {
            Log::warning("KanbanGeneratorService: No schedules found");
            return [
                'success' => false,
                'message' => 'No schedules found for the specified parameters'
            ];
        }

        $conveyor = MasterConveyor::find($conveyorId);
        if (!$conveyor) {
            return [
                'success' => false,
                'message' => 'Conveyor not found'
            ];
        }

        try {
            DB::beginTransaction();

            // Clear existing kanban data for this schedule group
            $this->clearKanbanData($conveyorId, $date, $shift);

            // Generate circuit kanbans
            $circuitCount = $this->generateCircuitKanbans($conveyorId, $conveyor, $schedules, $date, $shift);

            // Generate shikake kanbans
            $shikakeCount = $this->generateShikakeKanbans($conveyorId, $conveyor, $schedules, $date, $shift);

            DB::commit();

            Log::info("KanbanGeneratorService: Generation complete", [
                'circuit_count' => $circuitCount,
                'shikake_count' => $shikakeCount
            ]);

            return [
                'success' => true,
                'circuit_count' => $circuitCount,
                'shikake_count' => $shikakeCount,
                'message' => "Generated {$circuitCount} circuit and {$shikakeCount} shikake kanbans"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("KanbanGeneratorService: Generation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to generate kanbans: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate circuit kanbans with carry-over logic
     * 
     * @param int $conveyorId
     * @param MasterConveyor $conveyor
     * @param Collection $schedules
     * @param string $date
     * @param int $shift
     * @return int
     */
    private function generateCircuitKanbans(int $conveyorId, MasterConveyor $conveyor, Collection $schedules, string $date, int $shift): int
    {
        $totalKanbans = 0;
        $conveyorCode = $conveyor->conveyor ?? 'CVX';

        // Get unique assy codes from schedules
        $assyCodes = $schedules->pluck('assy')->unique()->filter();

        // Get all circuits linked to these assy codes
        $circuitGroups = $this->getCircuitsForAssyCodes($assyCodes, $conveyorId);

        Log::info("KanbanGeneratorService: Processing circuits", [
            'assy_codes_count' => $assyCodes->count(),
            'circuit_groups_count' => count($circuitGroups)
        ]);

        foreach ($circuitGroups as $circuitKey => $circuitData) {
            // Get or create balance record
            $balance = KanbanBalanceCircuit::findOrCreate(
                $conveyorId,
                $circuitData['master_circuit_id']
            );

            // Calculate kebutuhan per cutoff based on schedule qty and circuit's relevant assy codes
            $schedulesWithKebutuhan = $this->calculateKebutuhanPerCutoff($schedules, $circuitData);

            // Generate kanbans with carry-over logic
            $result = $this->generateKanbanCarryOver(
                $schedulesWithKebutuhan,
                $circuitData['qty_kanban'],
                $balance->sisa,
                $balance->last_nomor_urut
            );

            // Save generated kanbans
            foreach ($result['kanban_list'] as $kanban) {
                AssyScheduleCircuit::create([
                    'assy_schedule_id' => $kanban['assy_schedule_id'],
                    'cct_no' => $circuitData['cct_no'],
                    'cct_code' => $circuitData['cct_code'],
                    'master_circuit_id' => $circuitData['master_circuit_id'],
                    'issue' => $kanban['issue'],
                    'nomor_urut' => $kanban['nomor_urut'],
                    'barcode_kanban' => $this->generateBarcode($circuitData['carline'], $circuitData['cct_code'], $kanban['issue'], $kanban['qty_kanban'], $kanban['nomor_urut']),
                    'qrcode_shikake' => !empty($circuitData['shikake_code'])
                        ? $this->generateBarcode($circuitData['carline'], $circuitData['shikake_code'], $kanban['issue'], $kanban['qty_kanban'], $kanban['nomor_urut'])
                        : null,
                    'release_date' => $date,
                    'qty_listing' => $kanban['qty_listing'],
                    'qty_kanban' => $kanban['qty_kanban'],
                    'cutoff' => $kanban['cutoff'],
                    'is_printed' => false,
                    'print_count' => 0,
                ]);
                $totalKanbans++;
            }

            // Update balance for next generation
            $balance->updateAfterGeneration(
                $result['sisa_akhir'],
                $result['nomor_urut_akhir'],
                $schedules->last()->id,
                $date,
                $shift
            );
        }

        return $totalKanbans;
    }

    /**
     * Generate shikake kanbans with carry-over logic
     * 
     * @param int $conveyorId
     * @param MasterConveyor $conveyor
     * @param Collection $schedules
     * @param string $date
     * @param int $shift
     * @return int
     */
    private function generateShikakeKanbans(int $conveyorId, MasterConveyor $conveyor, Collection $schedules, string $date, int $shift): int
    {
        $totalKanbans = 0;
        $conveyorCode = $conveyor->conveyor ?? 'CVX';

        // Get unique assy codes from schedules
        $assyCodes = $schedules->pluck('assy')->unique()->filter();

        // Get all shikakes linked to these assy codes
        $shikakeGroups = $this->getShikakesForAssyCodes($assyCodes, $conveyorId);

        Log::info("KanbanGeneratorService: Processing shikakes", [
            'assy_codes_count' => $assyCodes->count(),
            'shikake_groups_count' => count($shikakeGroups)
        ]);

        foreach ($shikakeGroups as $shikakeKey => $shikakeData) {
            // Get or create balance record
            $balance = KanbanBalanceShikake::findOrCreate(
                $conveyorId,
                $shikakeData['master_shikake_id']
            );

            // Calculate kebutuhan per cutoff based on schedule qty
            $schedulesWithKebutuhan = $this->calculateKebutuhanPerCutoff($schedules, $shikakeData);

            // Generate kanbans with carry-over logic
            $result = $this->generateKanbanCarryOver(
                $schedulesWithKebutuhan,
                $shikakeData['qty_kanban'],
                $balance->sisa,
                $balance->last_nomor_urut
            );

            // Save generated kanbans
            foreach ($result['kanban_list'] as $kanban) {
                AssyScheduleShikake::create([
                    'assy_schedule_id' => $kanban['assy_schedule_id'],
                    'master_shikake_id' => $shikakeData['master_shikake_id'],
                    'issue' => $kanban['issue'],
                    'nomor_urut' => $kanban['nomor_urut'],
                    'barcode_kanban' => $this->generateBarcode($shikakeData['carline'], $shikakeData['code'], $kanban['issue'], $kanban['qty_kanban'], $kanban['nomor_urut']),
                    'release_date' => $date,
                    'qty_listing' => $kanban['qty_listing'],
                    'qty_kanban' => $kanban['qty_kanban'],
                    'cutoff' => $kanban['cutoff'],
                    'process' => $shikakeData['process'],
                    'is_printed' => false,
                    'print_count' => 0,
                ]);
                $totalKanbans++;
            }

            // Update balance for next generation
            $balance->updateAfterGeneration(
                $result['sisa_akhir'],
                $result['nomor_urut_akhir'],
                $schedules->last()->id,
                $date,
                $shift
            );
        }

        return $totalKanbans;
    }

    /**
     * Core carry-over calculation algorithm
     * Based on jai_filter_kanban implementation
     * 
     * @param array $schedulesWithKebutuhan - Array of [schedule, kebutuhan]
     * @param int $qtyKanban - Lot size (qty per kanban)
     * @param int $sisaSebelumnya - Carry from previous period
     * @param int $lastNomorUrut - Last nomor urut from previous period
     * @return array
     */
    private function generateKanbanCarryOver(array $schedulesWithKebutuhan, int $qtyKanban, int $sisaSebelumnya, int $lastNomorUrut): array
    {
        $sisa = $sisaSebelumnya;
        $nomorUrut = $lastNomorUrut;
        $issueInShift = 0;
        $kanbanList = [];

        // Guard against zero qty_kanban
        if ($qtyKanban <= 0) {
            Log::warning("KanbanGeneratorService: qty_kanban is zero or negative, skipping");
            return [
                'kanban_list' => [],
                'sisa_akhir' => $sisaSebelumnya,
                'nomor_urut_akhir' => $lastNomorUrut,
                'total_issue' => 0,
            ];
        }

        // First pass: calculate total issues in this shift
        $tempSisa = $sisaSebelumnya;
        $totalIssue = 0;
        foreach ($schedulesWithKebutuhan as $item) {
            $kebutuhan = $item['kebutuhan'];
            while ($tempSisa < $kebutuhan) {
                $tempSisa += $qtyKanban;
                $totalIssue++;
            }
            $tempSisa -= $kebutuhan;
        }

        Log::info("KanbanGeneratorService: First pass complete", [
            'total_issue' => $totalIssue,
            'temp_sisa' => $tempSisa
        ]);

        // Second pass: generate kanbans with issue format XXX/YYY
        foreach ($schedulesWithKebutuhan as $item) {
            $schedule = $item['schedule'];
            $kebutuhan = $item['kebutuhan'];

            // Skip if no kebutuhan
            if ($kebutuhan <= 0) {
                continue;
            }

            // Open kanbans until sisa >= kebutuhan
            while ($sisa < $kebutuhan) {
                $sisa += $qtyKanban;
                // Rollover: after 9999 kembali ke 0001 (scoped per conveyor + circuit)
                $nomorUrut = ($nomorUrut >= 9999) ? 1 : $nomorUrut + 1;
                $issueInShift++;

                $kanbanList[] = [
                    'assy_schedule_id' => $schedule->id,
                    'cutoff' => $schedule->cutoff,
                    'qty_listing' => $kebutuhan,
                    'qty_kanban' => $qtyKanban,
                    'issue' => sprintf('%03d/%03d', $issueInShift, $totalIssue),
                    'nomor_urut' => $nomorUrut,
                ];
            }

            // Consume kebutuhan from sisa
            $sisa -= $kebutuhan;
        }

        return [
            'kanban_list' => $kanbanList,
            'sisa_akhir' => $sisa,
            'nomor_urut_akhir' => $nomorUrut,
            'total_issue' => $totalIssue,
        ];
    }

    /**
     * Get circuits linked to assy codes through master_assy pivot
     * 
     * @param Collection $assyCodes
     * @param int $conveyorId
     * @return array
     */
    private function getCircuitsForAssyCodes(Collection $assyCodes, int $conveyorId): array
    {
        $circuitGroups = [];

        // Find master_assy records matching these assy codes
        $assyIds = MasterAssy::whereIn('assy', $assyCodes)->pluck('id');

        if ($assyIds->isEmpty()) {
            return $circuitGroups;
        }

        // Get circuits linked to these assy records AND belonging to this conveyor
        $circuits = MasterCircuit::whereHas('assemblies', function ($query) use ($assyIds) {
            $query->whereIn('master_assy_id', $assyIds);
        })
            ->where('conveyor_id', $conveyorId)
            ->get();

        // Group by cct_no + cct_code (unique key)
        foreach ($circuits as $circuit) {
            $key = $circuit->cct_no . '-' . $circuit->cct_code;
            
            if (!isset($circuitGroups[$key])) {
                $circuitGroups[$key] = [
                    'master_circuit_id' => $circuit->id,
                    'carline' => $circuit->carline ?? '',
                    'cct_no' => $circuit->cct_no,
                    'cct_code' => $circuit->cct_code,
                    'shikake_code' => $circuit->shikake_code,
                    'qty_kanban' => $circuit->qty ?? 1, // Fallback to 1 if not set
                    'released_note' => $circuit->released_note ?? null,
                    'assy_codes' => [],
                ];
            }
            
            // Collect which assy codes this circuit is linked to
            $circuitAssyCodes = $circuit->assemblies->pluck('assy')->toArray();
            $circuitGroups[$key]['assy_codes'] = array_unique(
                array_merge($circuitGroups[$key]['assy_codes'], $circuitAssyCodes)
            );
        }

        return $circuitGroups;
    }

    /**
     * Get shikakes linked to assy codes through master_assy pivot
     * 
     * @param Collection $assyCodes
     * @param int $conveyorId
     * @return array
     */
    private function getShikakesForAssyCodes(Collection $assyCodes, int $conveyorId): array
    {
        $shikakeGroups = [];

        // Find master_assy records matching these assy codes
        $assyIds = MasterAssy::whereIn('assy', $assyCodes)->pluck('id');

        if ($assyIds->isEmpty()) {
            return $shikakeGroups;
        }

        // Get shikakes linked to these assy records AND belonging to this conveyor
        $shikakes = MasterShikake::whereHas('assemblies', function ($query) use ($assyIds) {
            $query->whereIn('master_assy_id', $assyIds);
        })
            ->where('conveyor_id', $conveyorId)
            ->get();

        // Group by master_shikake_id (each shikake is unique)
        foreach ($shikakes as $shikake) {
            $key = $shikake->id;
            
            if (!isset($shikakeGroups[$key])) {
                // Generate a code for the shikake (use process + sequence as identifier)
                $shikakeCode = strtoupper(substr($shikake->process ?? 'SHK', 0, 3)) . '-' . str_pad($shikake->sequence ?? $shikake->id, 3, '0', STR_PAD_LEFT);
                
                $shikakeGroups[$key] = [
                    'master_shikake_id' => $shikake->id,
                    'carline' => $shikake->carline ?? '',
                    'code' => $shikakeCode,
                    'process' => $shikake->process,
                    'qty_kanban' => $shikake->qty ?? 1, // Fallback to 1 if not set
                    'released_note' => $shikake->released_note ?? null,
                    'assy_codes' => [],
                ];
            }
            
            // Collect which assy codes this shikake is linked to
            $shikakeAssyCodes = $shikake->assemblies->pluck('assy')->toArray();
            $shikakeGroups[$key]['assy_codes'] = array_unique(
                array_merge($shikakeGroups[$key]['assy_codes'], $shikakeAssyCodes)
            );
        }

        return $shikakeGroups;
    }

    /**
     * Calculate kebutuhan per cutoff based on schedule qty
     * Kebutuhan = sum of qty for all schedules in the cutoff that match the item's assy codes
     * 
     * @param Collection $schedules
     * @param array $itemData - Circuit or shikake data with assy_codes
     * @return array
     */
    private function calculateKebutuhanPerCutoff(Collection $schedules, array $itemData): array
    {
        $result = [];
        $itemAssyCodes = $itemData['assy_codes'] ?? [];
        
        // Group schedules by cutoff
        $byCutoff = $schedules->groupBy('cutoff');
        
        foreach ($byCutoff as $cutoff => $cutoffSchedules) {
            // Sum qty for schedules whose assycode matches any of the item's assy codes
            $kebutuhan = 0;
            $representativeSchedule = null;
            
            foreach ($cutoffSchedules as $schedule) {
                if (in_array($schedule->assy, $itemAssyCodes) || empty($itemAssyCodes)) {
                    $kebutuhan += $schedule->qty;
                    if (!$representativeSchedule) {
                        $representativeSchedule = $schedule;
                    }
                }
            }
            
            // Only include if there's kebutuhan
            if ($kebutuhan > 0 && $representativeSchedule) {
                $result[] = [
                    'schedule' => $representativeSchedule,
                    'kebutuhan' => $kebutuhan,
                    'cutoff' => $cutoff,
                ];
            }
        }
        
        // Sort by cutoff
        usort($result, fn($a, $b) => $a['cutoff'] <=> $b['cutoff']);
        
        return $result;
    }

    /**
     * Generate barcode kanban.
     * Format: {carline}.{code}.{issue_3d}.{qty}.{nomor_urut_4d}
     * Contoh: T.TD36.002.40.0002
     * Contoh: T.T-AK30.002.40.0002
     * 
     * @param string $carline  - Carline code (misal: T)
     * @param string $code     - CCT code atau shikake code (misal: TD36, T-AK30)
     * @param string $issue    - Issue format XXX/YYY, diambil bagian XXX (misal: 001/005 → 001)
     * @param int    $qty      - Qty per kanban (misal: 40)
     * @param int    $nomorUrut - Nomor urut 4 digit (misal: 0002)
     * @return string
     */
    private function generateBarcode(string $carline, string $code, string $issue, int $qty, int $nomorUrut): string
    {
        // Ambil bagian XXX dari format XXX/YYY
        $issueNumber = explode('/', $issue)[0] ?? '001';

        return sprintf('%s.%s.%s.%d.%04d', $carline, $code, $issueNumber, $qty, $nomorUrut);
    }

    /**
     * Clear existing kanban data for a schedule group
     * 
     * @param int $conveyorId
     * @param string $date
     * @param int $shift
     * @return void
     */
    public function clearKanbanData(int $conveyorId, string $date, int $shift): void
    {
        $scheduleIds = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->where('shift', $shift)
            ->pluck('id');

        if ($scheduleIds->isNotEmpty()) {
            AssyScheduleCircuit::whereIn('assy_schedule_id', $scheduleIds)->delete();
            AssyScheduleShikake::whereIn('assy_schedule_id', $scheduleIds)->delete();
            
            Log::info("KanbanGeneratorService: Cleared existing kanban data", [
                'schedule_ids_count' => $scheduleIds->count()
            ]);
        }
    }

    /**
     * Get kanban summary for a schedule
     * 
     * @param int $conveyorId
     * @param string $date
     * @param int $shift
     * @return array
     */
    public function getKanbanSummary(int $conveyorId, string $date, int $shift): array
    {
        $scheduleIds = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->where('shift', $shift)
            ->pluck('id');

        $circuitCount = AssyScheduleCircuit::whereIn('assy_schedule_id', $scheduleIds)->count();
        $shikakeCount = AssyScheduleShikake::whereIn('assy_schedule_id', $scheduleIds)->count();

        $circuitPrinted = AssyScheduleCircuit::whereIn('assy_schedule_id', $scheduleIds)
            ->where('is_printed', true)
            ->count();
        $shikakePrinted = AssyScheduleShikake::whereIn('assy_schedule_id', $scheduleIds)
            ->where('is_printed', true)
            ->count();

        return [
            'circuit' => [
                'total' => $circuitCount,
                'printed' => $circuitPrinted,
                'pending' => $circuitCount - $circuitPrinted,
            ],
            'shikake' => [
                'total' => $shikakeCount,
                'printed' => $shikakePrinted,
                'pending' => $shikakeCount - $shikakePrinted,
            ],
        ];
    }
}
