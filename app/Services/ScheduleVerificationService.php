<?php

namespace App\Services;

use App\Models\AssySchedule;
use App\Models\ListingStage;
use App\Models\MasterConveyor;
use App\Services\Schedule\ShiftCapacityCalculator;
use App\Services\Schedule\ShiftLockChecker;
use App\Services\Schedule\ListingAllocator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScheduleVerificationService
{
    protected KanbanGeneratorService $kanbanGenerator;
    protected ShiftCapacityCalculator $capacityCalculator;
    protected ShiftLockChecker $lockChecker;
    protected ListingAllocator $listingAllocator;

    public function __construct(
        KanbanGeneratorService $kanbanGenerator,
        ShiftCapacityCalculator $capacityCalculator,
        ShiftLockChecker $lockChecker,
        ListingAllocator $listingAllocator
    ) {
        $this->kanbanGenerator = $kanbanGenerator;
        $this->capacityCalculator = $capacityCalculator;
        $this->lockChecker = $lockChecker;
        $this->listingAllocator = $listingAllocator;
    }

    /**
     * Get datatable query for schedule verification.
     * Generates ALL date×conveyor×shift combinations for "active" conveyors
     * (those with any assy_schedule data in the range), then LEFT JOINs with actual
     * assy data so gap dates appear as "No Data" rows.
     */
    public function getDatatableQuery($startDate = null, $endDate = null, $conveyorId = null, $status = null)
    {
        // --- Step 1: Determine date range ---
        $start = $startDate ? Carbon::parse($startDate) : Carbon::today();
        $end   = $endDate   ? Carbon::parse($endDate)   : Carbon::today()->addDays(10);

        // --- Step 2: Get ACTIVE conveyors (those with any assy_schedule in range) ---
        $conveyorQuery = DB::table('assy_schedule AS a')
            ->join('master_conveyor AS mc', 'a.conveyor_id', '=', 'mc.id')
            ->whereNull('mc.deleted_at')
            // Conveyor yang sudah tidak ada di SIREP tidak lagi muncul untuk diverifikasi.
            ->where('mc.is_active', 1)
            ->whereRaw('DATE(a.schedule) BETWEEN ? AND ?', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->select('mc.id AS conveyor_id', 'mc.conveyor AS conveyor_name', 'mc.capacity',
                'mc.overtime_capacity', 'mc.capacity_synced_at')
            ->distinct();

        if ($conveyorId) {
            $conveyorQuery->where('mc.id', $conveyorId);
        }

        $activeConveyors = $conveyorQuery->get();

        if ($activeConveyors->isEmpty()) {
            return collect();
        }

        // --- Step 3: Get actual assy_schedule aggregated data for the range ---
        $assyQuery = DB::table('assy_schedule')
            ->selectRaw('DATE(schedule) AS schedule_date, conveyor_id, shift, GROUP_CONCAT(DISTINCT assy ORDER BY assy SEPARATOR ", ") AS assy_list, SUM(qty) AS total_listing, COUNT(DISTINCT assy) AS assy_count, MAX(is_lock) AS is_lock, MIN(id) AS first_id, MAX(verified_capacity) AS verified_capacity, MAX(verified_is_overtime) AS verified_is_overtime, MAX(verified_listing_synced_at) AS verified_listing_synced_at, MAX(verified_listing_source) AS verified_listing_source')
            ->whereRaw('DATE(schedule) BETWEEN ? AND ?', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->groupByRaw('DATE(schedule), conveyor_id, shift');

        if ($conveyorId) {
            $assyQuery->where('conveyor_id', $conveyorId);
        }

        // Index actual data by "date|conveyor_id|shift" for O(1) lookup.
        // $shiftMap mencatat shift mana saja yang benar-benar terbentuk pada tiap
        // tanggal × conveyor — inilah pengganti master_conveyor.shift_qty.
        $assyData = [];
        $shiftMap = [];
        foreach ($assyQuery->get() as $row) {
            $key = $row->schedule_date . '|' . $row->conveyor_id . '|' . $row->shift;
            $assyData[$key] = $row;

            $shiftMap[$row->schedule_date . '|' . $row->conveyor_id][] = (int) $row->shift;
        }
        foreach ($shiftMap as $k => $shifts) {
            $shifts = array_values(array_unique($shifts));
            sort($shifts);
            $shiftMap[$k] = $shifts;
        }

        // --- Step 3b: Get raw SIREP listing demand per date×conveyor from listing_stage ---
        // The "Listing" column must reflect the true SIREP demand, independent of conveyor
        // capacity. assy_schedule only holds what fit within capacity, so the difference
        // (overflow) is surfaced on the last shift and flagged as over-capacity.
        $demandQuery = DB::table('listing_stage AS ls')
            ->join('master_conveyor AS mc', 'mc.conveyor', '=', 'ls.conveyor')
            ->whereNull('mc.deleted_at')
            ->where('mc.is_active', 1)
            ->whereRaw('DATE(ls.listing_date_time) BETWEEN ? AND ?', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotNull('ls.assy')->where('ls.assy', '!=', '')
            ->where('ls.qty', '>', 0)
            ->selectRaw('DATE(ls.listing_date_time) AS d, mc.id AS conveyor_id, SUM(ls.qty) AS demand');

        if ($conveyorId) {
            $demandQuery->where('mc.id', $conveyorId);
        }

        $demandMap = [];
        foreach ($demandQuery->groupByRaw('DATE(ls.listing_date_time), mc.id')->get() as $row) {
            $demandMap[$row->d . '|' . $row->conveyor_id] = (int) $row->demand;
        }

        // --- Step 3c: penanda lembur SIREP dan waktu pengambilan listing ---
        // is_overtime seragam untuk satu (tanggal × conveyor), jadi MAX() sudah mewakili.
        $otQuery = DB::table('listing_stage AS ls')
            ->join('master_conveyor AS mc', 'mc.conveyor', '=', 'ls.conveyor')
            ->whereNull('mc.deleted_at')
            ->where('mc.is_active', 1)
            ->whereRaw('DATE(ls.listing_date_time) BETWEEN ? AND ?', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->selectRaw('DATE(ls.listing_date_time) AS d, mc.id AS conveyor_id, MAX(ls.is_overtime) AS is_overtime, MAX(ls.synced_at) AS synced_at, MIN(ls.source) AS source');

        if ($conveyorId) {
            $otQuery->where('mc.id', $conveyorId);
        }

        $sirepMap = [];
        foreach ($otQuery->groupByRaw('DATE(ls.listing_date_time), mc.id')->get() as $row) {
            $sirepMap[$row->d . '|' . $row->conveyor_id] = $row;
        }

        // --- Step 4: Generate full grid: all dates × active conveyors × shifts ---
        $rows = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');

            foreach ($activeConveyors as $conv) {
                // Shift tidak lagi berasal dari master. Yang ditampilkan adalah shift yang
                // benar-benar terbentuk saat generate — sehingga tidak ada lagi baris shift
                // kosong untuk hari yang memang hanya berjalan satu shift.
                $shiftsHere = $shiftMap[$dateStr . '|' . $conv->conveyor_id] ?? [];

                // Tanggal tanpa jadwal sama sekali tetap muncul satu baris agar celah
                // penjadwalan tetap terlihat, bukan hilang diam-diam dari layar.
                $noData = empty($shiftsHere);
                if ($noData) {
                    $shiftsHere = [1];
                }

                $lastShift = max($shiftsHere);
                $shiftQty  = count($shiftsHere);

                // Total qty actually scheduled (capped) for this date×conveyor across all shifts
                $scheduledAll = 0;
                foreach ($shiftsHere as $s) {
                    $scheduledAll += (int) ($assyData[$dateStr . '|' . $conv->conveyor_id . '|' . $s]->total_listing ?? 0);
                }

                // True SIREP demand (= scheduled, since CO5 catch-all schedules 100%).
                $demand = $demandMap[$dateStr . '|' . $conv->conveyor_id] ?? $scheduledAll;
                // Hari melampaui kapasitas bila demand > jumlah shift YANG BERJALAN
                // × (kapasitas + CO5 nominal).
                $sirepInfo  = $sirepMap[$dateStr . '|' . $conv->conveyor_id] ?? null;
                $dayOvertime = $sirepInfo ? (bool) $sirepInfo->is_overtime : false;

                $capacity   = (int) $conv->capacity;
                $co5Nominal = $this->capacityCalculator->calculateCutoff5Capacity($capacity);
                // Ambang nominal harus ikut penanda lembur SIREP. Tanpa lembur CO5 tidak
                // tersedia, jadi kapasitas nominal hari itu hanya shift × kapasitas —
                // memakai ambang bershift-CO5 akan menyembunyikan hari yang sebenarnya over.
                $nominalTotal = $shiftQty * ($capacity + ($dayOvertime ? $co5Nominal : 0));
                $overCapDay   = ($capacity > 0 && $scheduledAll > 0 && $demand > $nominalTotal);
                // Over TANPA penanda lembur: data SIREP saling bertentangan — demand tidak
                // muat di kapasitas normal, tapi PPC tidak menyatakan lembur.
                $overNoOtDay  = ($overCapDay && !$dayOvertime);

                foreach ($shiftsHere as $s) {
                    $key   = $dateStr . '|' . $conv->conveyor_id . '|' . $s;
                    $assy  = $assyData[$key] ?? null;

                    $hasAssy  = $assy !== null ? 1 : 0;
                    $isLock   = $assy ? (int) $assy->is_lock : 0;

                    // Apply status filter
                    if ($status === 'verified'  && !($hasAssy && $isLock == 1)) continue;
                    if ($status === 'pending'   && !($hasAssy && $isLock == 0)) continue;
                    if ($status === 'no_data'   && $hasAssy) continue;
                    // Default view: every status that actually has schedule data
                    // (verified + pending), excluding the "No Data" gap rows.
                    if ($status === 'with_data' && !$hasAssy) continue;

                    $scheduledShift = $assy ? (int) $assy->total_listing : 0;
                    // Defensive: if demand somehow exceeds scheduled, surface it on the last shift
                    $extra          = ($s === $lastShift) ? max(0, $demand - $scheduledAll) : 0;
                    $displayListing = $scheduledShift + $extra;
                    $isOverCapRow   = ($s === $lastShift && $overCapDay);

                    // Baris yang sudah verified membekukan Cap/OT/API Time pada nilai SIREP
                    // saat verifikasi (lihat verifySchedule()), bukan nilai SIREP terkini —
                    // jadi resync SIREP setelahnya tidak membuat jadwal final tampak berubah.
                    // Baris lock lama (sebelum snapshot ini ada) tetap fallback ke nilai terkini.
                    $hasSnapshot = $assy && $isLock == 1 && $assy->verified_capacity !== null;

                    $rowCapacity   = $hasSnapshot ? (int) $assy->verified_capacity : $conv->capacity;
                    $rowIsOvertime = $hasSnapshot
                        ? (bool) $assy->verified_is_overtime
                        : ($sirepInfo ? (bool) $sirepInfo->is_overtime : null);
                    $rowSyncedAt   = $hasSnapshot ? $assy->verified_listing_synced_at : ($sirepInfo->synced_at ?? null);
                    $rowSource     = $hasSnapshot ? $assy->verified_listing_source : ($sirepInfo->source ?? null);

                    $rows[] = (object) [
                        'schedule_date'    => $dateStr,
                        'conveyor_id'      => $conv->conveyor_id,
                        'conveyor_name'    => $conv->conveyor_name,
                        'capacity'         => $rowCapacity,
                        'shift'            => $s,
                        'total_listing'    => $displayListing,
                        'scheduled_qty'    => $scheduledShift,
                        'is_over_capacity' => $isOverCapRow ? 1 : 0,
                        // Asal-usul angka SIREP, ditampilkan agar user tahu kapan
                        // kapasitas dan listing ini terakhir ditarik dari API.
                        'is_overtime'        => $rowIsOvertime,
                        'over_without_overtime' => ($s === $lastShift && $overNoOtDay) ? 1 : 0,
                        'nominal_total'      => $nominalTotal,
                        'listing_demand'     => $demand,
                        'listing_synced_at'  => $rowSyncedAt
                            ? Carbon::parse($rowSyncedAt)->format('d M y H:i:s')
                            : null,
                        'listing_source'     => $rowSource,
                        'capacity_synced_at' => $conv->capacity_synced_at
                            ? Carbon::parse($conv->capacity_synced_at)->format('d M Y H:i')
                            : null,
                        'overtime_capacity'  => $conv->overtime_capacity ? (int) $conv->overtime_capacity : null,
                        'assy_count'       => $assy ? (int) $assy->assy_count : 0,
                        'assy_list'        => $assy ? ($assy->assy_list ?? '') : '',
                        'is_lock'          => $isLock,
                        'first_id'         => $assy ? $assy->first_id : null,
                        'has_assy'         => $hasAssy,
                    ];
                }
            }

            $current->addDay();
        }

        return collect($rows);
    }

    /**
     * Asal-usul data SIREP untuk satu (tanggal × conveyor).
     *
     * Layar verifikasi memakai angka yang datang dari dua waktu pengambilan berbeda:
     * kapasitas ditarik `sirep:sync-conveyor`, sedangkan listing dan penanda lembur
     * ditarik sinkronisasi listing. Keduanya ditampilkan terpisah agar user tahu
     * angka mana yang mungkin sudah basi.
     *
     * @return array<string, mixed>
     */
    protected function sirepMeta(MasterConveyor $conveyor, $date): array
    {
        $capacity = (int) ($conveyor->capacity ?? 0);

        $listing = ListingStage::where('conveyor', $conveyor->conveyor)
            ->whereDate('listing_date_time', $date)
            ->selectRaw('MAX(is_overtime) AS is_overtime, MAX(synced_at) AS synced_at, MIN(source) AS source, COUNT(*) AS baris')
            ->first();

        $adaListing = $listing && (int) $listing->baris > 0;
        $isOvertime = $adaListing ? (bool) $listing->is_overtime : null;

        return [
            'capacity'            => $capacity ?: null,
            'overtime_capacity'   => $conveyor->overtime_capacity ? (int) $conveyor->overtime_capacity : null,
            'co5_nominal'         => $capacity > 0 ? $this->capacityCalculator->calculateCutoff5Capacity($capacity) : null,
            'capacity_synced_at'  => $conveyor->capacity_synced_at?->format('d M Y H:i'),
            'capacity_is_synced'  => $conveyor->hasSyncedCapacity() && $conveyor->capacity_synced_at !== null,
            'sirep_code'          => $conveyor->sirepName(),
            'is_overtime'         => $isOvertime,
            'listing_synced_at'   => $adaListing && $listing->synced_at
                ? Carbon::parse($listing->synced_at)->format('d M Y H:i')
                : null,
            // Nilai mentah (bukan string terformat) — dipakai untuk snapshot
            // verifikasi, supaya bisa diformat ulang konsisten dengan tampilan list.
            'listing_synced_at_raw' => $adaListing ? $listing->synced_at : null,
            'listing_source'      => $adaListing ? $listing->source : null,
            'listing_rows'        => $adaListing ? (int) $listing->baris : 0,
        ];
    }

    /**
     * Get available dates (H to H+days_range) that have unverified schedules for a conveyor
     * Used by the right panel date selector in verification modal
     */
    public function getAvailableDates($conveyorId, $currentDate, $currentShift, $daysRange = 10)
    {
        $endDate = Carbon::parse($currentDate)->addDays((int) $daysRange)->format('Y-m-d');

        $rows = DB::select("
            SELECT 
                DATE(schedule) AS schedule_date,
                shift,
                COUNT(*) AS item_count,
                SUM(qty) AS total_qty
            FROM assy_schedule
            WHERE conveyor_id = ?
              AND DATE(schedule) >= ?
              AND DATE(schedule) <= ?
              AND is_lock = 0
              AND verified_at IS NULL
              AND NOT (DATE(schedule) = ? AND shift = ?)
            GROUP BY DATE(schedule), shift
            ORDER BY DATE(schedule) ASC, shift ASC
        ", [$conveyorId, $currentDate, $endDate, $currentDate, $currentShift]);

        return [
            'success' => true,
            'data' => $rows
        ];
    }

    /**
     * Get verification details for a specific schedule
     */
    public function getVerificationDetails($conveyorId, $date, $shift)
    {
        // Get the conveyor
        $conveyor = MasterConveyor::find($conveyorId);
        
        if (!$conveyor) {
            return [
                'success' => false,
                'message' => 'Conveyor not found'
            ];
        }

        // Get all schedules for this conveyor, date, and shift
        $schedules = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->where('shift', $shift)
            ->orderBy('cutoff', 'asc')
            ->orderBy('listing_id', 'asc')
            ->get();

        // Jadwal yang sudah verified membekukan kapasitas/OT/listing pada nilai SIREP
        // saat diverifikasi (lihat verifySchedule()). Form Detail HARUS memakai
        // snapshot itu, bukan nilai SIREP terkini — supaya histori tetap terbaca dan
        // bisa ditelusuri meski SIREP di-resync setelahnya. Baris lock lama (sebelum
        // snapshot ini ada) fallback ke nilai terkini seperti sebelumnya.
        $firstSchedule = $schedules->first();
        $hasSnapshot   = $firstSchedule && $firstSchedule->is_lock == 1 && $firstSchedule->verified_capacity !== null;

        // Calculate capacities
        $capacity = $hasSnapshot
            ? (int) $firstSchedule->verified_capacity
            : (int) ($conveyor->capacity ?? 0);
        $normalCutOffCapacity = round($capacity / 4, 2);
        // CO5 nominal capacity = round(0.875 × capacity/4), same on every shift's CO5.
        // The LAST shift's CO5 is a catch-all and may exceed this nominal (Used > Cap → "over").
        // Jumlah shift diambil dari jadwal yang benar-benar terbentuk pada tanggal ini,
        // bukan dari nilai statis master yang sudah dihapus.
        $shiftsOnDate = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->distinct()
            ->pluck('shift')
            ->map(fn ($s) => (int) $s)
            ->sort()
            ->values();

        $shiftQty        = max(1, $shiftsOnDate->count());
        $lastShift       = (int) ($shiftsOnDate->last() ?? $shift);
        $cutOff5Capacity = (float) $this->capacityCalculator->calculateCutoff5Capacity($capacity);

        $scheduledAll = (int) AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->sum('qty');

        if ($hasSnapshot) {
            // Dibekukan: seluruh angka SIREP (demand, OT, waktu/sumber tarik listing)
            // diambil dari snapshot verifikasi, bukan query live ke listing_stage.
            $listingDemand = (int) ($firstSchedule->verified_listing_demand ?? $scheduledAll);
            $dayOvertime   = (bool) $firstSchedule->verified_is_overtime;
            $sirepMeta = [
                'capacity'            => $capacity ?: null,
                'overtime_capacity'   => $conveyor->overtime_capacity ? (int) $conveyor->overtime_capacity : null,
                'co5_nominal'         => $capacity > 0 ? $this->capacityCalculator->calculateCutoff5Capacity($capacity) : null,
                'capacity_synced_at'  => $conveyor->capacity_synced_at?->format('d M Y H:i'),
                'capacity_is_synced'  => $conveyor->hasSyncedCapacity() && $conveyor->capacity_synced_at !== null,
                'sirep_code'          => $conveyor->sirepName(),
                'is_overtime'         => $dayOvertime,
                'listing_synced_at'   => $firstSchedule->verified_listing_synced_at
                    ? $firstSchedule->verified_listing_synced_at->format('d M Y H:i')
                    : null,
                'listing_source'      => $firstSchedule->verified_listing_source,
                'listing_rows'        => $firstSchedule->verified_listing_source ? 1 : 0,
            ];
        } else {
            // SIREP demand & scheduled total (equal now, since CO5 catch-all schedules 100%).
            $listingDemand = (int) ListingStage::where('conveyor', $conveyor->conveyor)
                ->whereDate('listing_date_time', $date)
                ->where('qty', '>', 0)
                ->sum('qty');
            // Asal-usul angka yang dipakai layar ini, supaya user tahu data SIREP mana
            // yang sedang dilihat dan sesegar apa.
            $sirepMeta   = $this->sirepMeta($conveyor, $date);
            $dayOvertime = (bool) ($sirepMeta['is_overtime'] ?? false);
        }

        // Ambang nominal ikut penanda lembur: tanpa lembur CO5 tidak tersedia,
        // jadi kapasitas nominal hari itu hanya shift × kapasitas.
        $nominalTotal   = $shiftQty * ($capacity + ($dayOvertime ? (int) $cutOff5Capacity : 0));
        $overflow       = max(0, $listingDemand - $nominalTotal);
        $isOverCapacity = ($shift == $lastShift && $listingDemand > $nominalTotal);
        // Over tanpa penanda lembur = data SIREP saling bertentangan.
        $overNoOvertime = ($isOverCapacity && !$dayOvertime);

        $sirepMeta['over_without_overtime'] = $overNoOvertime;
        $sirepMeta['nominal_total']         = $nominalTotal;
        $sirepMeta['overflow']              = $overflow;
        $sirepMeta['shift_berjalan']        = $shiftQty;

        if ($schedules->isEmpty()) {
            // Return success with empty cut-offs so the modal can open
            // (allows user to drag data from other dates into this empty slot)
            return [
                'success'               => true,
                'conveyor_id'           => $conveyorId,
                'conveyor'              => $conveyor->conveyor,
                'date'                  => $date,
                'shift'                 => $shift,
                'capacity'              => $capacity,
                'normal_cutoff_capacity'=> $normalCutOffCapacity,
                'cutoff5_capacity'      => $cutOff5Capacity,
                'assy_count'            => 0,
                'total_listing'         => 0,
                'scheduled_qty'         => 0,
                'scheduled_all'         => $scheduledAll,
                'listing_demand'        => $listingDemand,
                'overflow'              => $overflow,
                'is_over_capacity'      => $isOverCapacity,
                'sirep'                 => $sirepMeta,
                'cut_offs'              => array_map(fn($i) => ['cutoff' => $i, 'items' => []], range(1, 5)),
                'is_empty'              => true,
            ];
        }

        // Group by cut off
        $cutOffs = $schedules->groupBy('cutoff')->map(function ($items, $cutoff) {
            return [
                'cutoff' => $cutoff,
                'items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'listing_id' => $item->listing_id,
                        'assy' => $item->assy,
                        'assycode' => $item->assycode,
                        'qty' => $item->qty,
                        'cutoff' => $item->cutoff,
                        'seq' => $item->seq,
                        'plt' => $item->plt,
                        'mode' => $item->mode,
                        'snp' => $item->snp,
                        'snpa' => $item->snpa,
                        'transferred_from_date' => $item->transferred_from_date
                            ? Carbon::parse($item->transferred_from_date)->format('Y-m-d')
                            : null,
                        'transferred_from_shift'  => $item->transferred_from_shift,
                        'transferred_from_cutoff' => $item->transferred_from_cutoff,
                    ];
                })->values()->toArray()
            ];
        })->values()->toArray();

        // Always ensure Cut Off 5 exists
        $hasCutOff5 = false;
        foreach ($cutOffs as $co) {
            if ($co['cutoff'] == 5) {
                $hasCutOff5 = true;
                break;
            }
        }
        
        if (!$hasCutOff5) {
            $cutOffs[] = [
                'cutoff' => 5,
                'items' => []
            ];
        }
        
        // Sort by cutoff
        usort($cutOffs, function($a, $b) {
            return $a['cutoff'] - $b['cutoff'];
        });

        // Get unique assy count
        $assyCount = $schedules->pluck('assy')->unique()->count();
        $totalListing = $schedules->sum('qty');

        return [
            'success' => true,
            'conveyor_id' => $conveyorId,
            'conveyor' => $conveyor->conveyor,
            'date' => $date,
            'shift' => $shift,
            'capacity' => $capacity,
            'normal_cutoff_capacity' => round($normalCutOffCapacity, 2),
            'cutoff5_capacity' => $cutOff5Capacity,
            'assy_count' => $assyCount,
            'total_listing' => $totalListing,
            'scheduled_qty' => $totalListing,
            'scheduled_all' => $scheduledAll,
            'listing_demand' => $listingDemand,
            'overflow' => $overflow,
            'is_over_capacity' => $isOverCapacity,
            'sirep' => $sirepMeta,
            'cut_offs' => $cutOffs
        ];
    }

    /**
     * Get available assy data for a specific date and shift
     * IMPORTANT: Only returns UNVERIFIED schedules (verified_at IS NULL AND is_lock = 0)
     */
    public function getAvailableAssyData($conveyorId, $date, $shift)
    {
        \Log::info("getAvailableAssyData called", [
            'conveyor_id' => $conveyorId,
            'date' => $date,
            'shift' => $shift
        ]);

        // Get schedules for specific date and shift (or all shifts), grouped by cut-off
        // CRITICAL FILTERS:
        // 1. is_lock = 0 (unlocked schedules)
        // 2. verified_at IS NULL (not verified yet)
        $query = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)  // Use whereDate for datetime column
            ->where('is_lock', 0)
            ->where(function($q) {
                $q->whereNull('verified_at')
                  ->orWhere('verified_at', '');
            });
        
        // Only filter by shift if not 'all'
        if ($shift !== 'all' && $shift !== null && $shift !== '') {
            $query->where('shift', $shift);
        }
        
        // Log the actual SQL query
        \Log::info("SQL Query", [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);
        
        // SORT ORDER: shift (ASC) -> cutoff (ASC) -> listing_id (ASC = urutan dari listing sumber)
        $schedules = $query->select('id', 'assy', 'qty', 'cutoff', 'shift', 'listing_id', 'assycode', 'seq', 'plt', 'mode', 'snp', 'snpa', 'verified_at', 'is_lock')
            ->orderBy('shift', 'asc')
            ->orderBy('cutoff', 'asc')
            ->orderBy('listing_id', 'asc')
            ->get();

        \Log::info("Query executed", [
            'total_records' => $schedules->count(),
            'sample_first' => $schedules->first() ? [
                'id' => $schedules->first()->id,
                'assy' => $schedules->first()->assy,
                'verified_at' => $schedules->first()->verified_at,
                'is_lock' => $schedules->first()->is_lock
            ] : null
        ]);

        // CRITICAL: Additional filter in collection to ensure no verified data
        $schedules = $schedules->filter(function($item) {
            $isUnverified = is_null($item->verified_at) || $item->verified_at === '';
            $isUnlocked = $item->is_lock == 0;
            
            if (!$isUnverified || !$isUnlocked) {
                \Log::warning("Filtering out verified/locked item", [
                    'id' => $item->id,
                    'assy' => $item->assy,
                    'verified_at' => $item->verified_at,
                    'is_lock' => $item->is_lock
                ]);
            }
            
            return $isUnverified && $isUnlocked;
        });

        // Group by cutoff while maintaining shift order
        // Items are already sorted by shift -> cutoff -> assy from query
        $grouped = $schedules->groupBy('cutoff')->map(function($items, $cutoff) {
            return [
                'cutoff' => $cutoff,
                'items' => $items->sortBy([
                    ['shift', 'asc'],
                    ['listing_id', 'asc']
                ])->map(function($item) {
                    return [
                        'id' => $item->id,
                        'assy' => $item->assy,
                        'qty' => $item->qty,
                        'shift' => $item->shift,
                        'listing_id' => $item->listing_id,
                        'assycode' => $item->assycode,
                        'seq' => $item->seq,
                        'plt' => $item->plt,
                        'mode' => $item->mode,
                        'snp' => $item->snp,
                        'snpa' => $item->snpa,
                        'verified_at' => $item->verified_at,
                        'is_lock' => $item->is_lock,
                    ];
                })->values()
            ];
        })->sortBy(function($group) {
            // Sort groups by the minimum shift number in each cutoff group
            return $group['items']->min('shift') ?? 99;
        })->values();

        \Log::info("Final result", [
            'grouped_count' => $grouped->count(),
            'total_items' => $grouped->sum(function($g) { return count($g['items']); })
        ]);

        return [
            'success' => true,
            'data' => $grouped
        ];
    }

    /**
     * Save verification changes
     */
    public function saveVerification($conveyorId, $date, $shift, $schedules = [], $newItems = [])
    {
        try {
            DB::beginTransaction();

            // Update existing schedules
            if (!empty($schedules)) {
                $this->updateExistingSchedules($schedules);
            }

            // Create new items (dragged from available)
            if (!empty($newItems)) {
                $this->createNewSchedules($conveyorId, $date, $shift, $newItems);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Schedule updated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to save changes: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update existing schedules
     */
    private function updateExistingSchedules($schedules)
    {
        foreach ($schedules as $scheduleData) {
            AssySchedule::where('id', $scheduleData['id'])
                ->update([
                    'cutoff' => $scheduleData['cutoff'],
                    'qty' => $scheduleData['qty'],
                    'updated_by' => Auth::id(),
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * Create new schedules from dragged items
     */
    private function createNewSchedules($conveyorId, $date, $shift, $newItems)
    {
        foreach ($newItems as $item) {
            $listingId = null;
            $assyCode = null;
            $seq = 0;
            $plt = 0;
            $mode = 0;
            $snp = 0;
            $snpa = 0;
            $transferredFromDate    = null;
            $transferredFromShift   = null;
            $transferredFromCutoff  = null;
            $transferredFromListing = null;
            
            // Check if this item has source_id (dragged from available)
            if (isset($item['source_id'])) {
                // Find and update the original item by deducting the quantity
                $sourceItem = AssySchedule::find($item['source_id']);

                if ($sourceItem) {
                    // Copy fields from source item
                    $listingId = $sourceItem->listing_id;
                    $assyCode = $sourceItem->assycode;
                    $seq = $sourceItem->seq ?? 0;
                    $plt = $sourceItem->plt ?? 0;
                    $mode = $sourceItem->mode ?? 0;
                    $snp = $sourceItem->snp ?? 0;
                    $snpa = $sourceItem->snpa ?? 0;

                    $transferredFromDate = $sourceItem->transferred_from_date
                        ? Carbon::parse($sourceItem->transferred_from_date)->format('Y-m-d')
                        : Carbon::parse($sourceItem->schedule)->format('Y-m-d');
                    $transferredFromShift   = $sourceItem->transferred_from_shift  ?? $sourceItem->shift;
                    $transferredFromCutoff  = $sourceItem->transferred_from_cutoff ?? $sourceItem->cutoff;
                    $transferredFromListing = $sourceItem->transferred_from_listing_id ?? $sourceItem->listing_id;
                    
                    // Deduct quantity from source
                    $this->deductSourceQuantity($sourceItem, $item['qty']);
                }
            }

            // Create new schedule item in current date/shift
            AssySchedule::create([
                'schedule' => $date,
                'conveyor_id' => $conveyorId,
                'listing_id' => $listingId ?? 0,
                'shift' => $shift,
                'assycode' => $assyCode ?? '',
                'cutoff' => $item['cutoff'],
                'assy' => $item['assy'],
                'qty' => $item['qty'],
                'seq' => $seq,
                'plt' => $plt,
                'mode' => $mode,
                'snp' => $snp,
                'snpa' => $snpa,
                'transferred_from_date'       => $transferredFromDate,
                'transferred_from_shift'      => $transferredFromShift,
                'transferred_from_cutoff'     => $transferredFromCutoff,
                'transferred_from_listing_id' => $transferredFromListing,
                'is_lock' => 0,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Deduct quantity from source item or delete if no quantity remains
     */
    private function deductSourceQuantity($sourceItem, $deductQty)
    {
        $newSourceQty = $sourceItem->qty - $deductQty;
        
        if ($newSourceQty > 0) {
            // Update remaining quantity in source
            $sourceItem->qty = $newSourceQty;
            $sourceItem->updated_by = Auth::id();
            $sourceItem->save();
        } else {
            // Delete only if no quantity remains
            $sourceItem->delete();
        }
    }

    /**
     * Verify schedule - save changes and lock the schedule for specific conveyor, date, and shift
     */
    public function verifySchedule($conveyorId, $date, $shift, $cutoffs = [])
    {
        try {
            DB::beginTransaction();

            $date = Carbon::parse($date);

            Log::info("verifySchedule called", [
                'conveyor_id' => $conveyorId,
                'date' => $date->format('Y-m-d'),
                'shift' => $shift,
                'cutoffs_count' => count($cutoffs),
                'cutoffs' => $cutoffs
            ]);

            // Step 1: Save any pending changes if cutoffs data is provided
            if (!empty($cutoffs)) {
                // Get existing schedules keyed by ID to preserve original data
                $existingSchedules = AssySchedule::where('conveyor_id', $conveyorId)
                    ->whereDate('schedule', $date)
                    ->where('shift', $shift)
                    ->get()
                    ->keyBy('id');

                Log::info("Existing schedules found", [
                    'count' => $existingSchedules->count(),
                    'ids' => $existingSchedules->keys()->toArray()
                ]);

                // Delete existing schedules for this shift
                if ($existingSchedules->isNotEmpty()) {
                    AssySchedule::whereIn('id', $existingSchedules->keys()->toArray())->delete();
                }

                // Recreate schedules based on cutoffs data
                foreach ($cutoffs as $cutoffData) {
                    $cutoffNumber = (int) ($cutoffData['cutoff'] ?? 0);
                    
                    Log::info("Processing cutoff", [
                        'cutoff' => $cutoffNumber,
                        'items_count' => count($cutoffData['items'] ?? [])
                    ]);
                    
                    if (!empty($cutoffData['items'])) {
                        foreach ($cutoffData['items'] as $item) {
                            // Handle items dragged from available
                            $type = $item['type'] ?? 'current';
                            $itemIdRaw = $item['id'] ?? 0;
                            $itemId = is_numeric($itemIdRaw) ? (int) $itemIdRaw : 0;
                            $sourceId = isset($item['source_id']) && !empty($item['source_id']) ? (int) $item['source_id'] : null;
                            
                            // Check if this is a new item from available (has source_id OR id starts with "new_")
                            $isFromAvailable = ($type === 'available') || 
                                               ($sourceId !== null) || 
                                               (is_string($itemIdRaw) && strpos($itemIdRaw, 'new_') === 0);
                            
                            Log::info("Processing item", [
                                'itemIdRaw' => $itemIdRaw,
                                'itemId' => $itemId,
                                'type' => $type,
                                'source_id' => $sourceId,
                                'isFromAvailable' => $isFromAvailable,
                                'qty' => $item['qty'] ?? 0,
                                'assy' => $item['assy'] ?? '',
                                'found_in_existing' => $existingSchedules->has($itemId)
                            ]);
                            
                            if ($isFromAvailable && $sourceId) {
                                // Get source item to copy data from
                                $sourceItem = AssySchedule::find($sourceId);
                                
                                if ($sourceItem) {
                                    Log::info("Creating schedule from source", ['source_id' => $sourceId]);
                                    
                                    // Capture source position BEFORE deduction.
                                    // If source item was itself transferred from another place,
                                    // propagate that original origin so the trail is preserved.
                                    $originDate    = $sourceItem->transferred_from_date
                                        ? Carbon::parse($sourceItem->transferred_from_date)->format('Y-m-d')
                                        : Carbon::parse($sourceItem->schedule)->format('Y-m-d');
                                    $originShift   = $sourceItem->transferred_from_shift  ?? $sourceItem->shift;
                                    $originCutoff  = $sourceItem->transferred_from_cutoff ?? $sourceItem->cutoff;
                                    $originListing = $sourceItem->transferred_from_listing_id ?? $sourceItem->listing_id;

                                    // Create new schedule from source
                                    AssySchedule::create([
                                        'schedule' => $date,
                                        'conveyor_id' => $conveyorId,
                                        'listing_id' => $sourceItem->listing_id,
                                        'shift' => $shift,
                                        'assycode' => $sourceItem->assycode,
                                        'assy' => $sourceItem->assy,
                                        'qty' => $item['qty'],
                                        'cutoff' => $cutoffNumber,
                                        'seq' => $sourceItem->seq ?? 0,
                                        'plt' => $sourceItem->plt ?? 0,
                                        'mode' => $sourceItem->mode ?? 0,
                                        'snp' => $sourceItem->snp ?? 0,
                                        'snpa' => $sourceItem->snpa ?? 0,
                                        'transferred_from_date'       => $originDate,
                                        'transferred_from_shift'      => $originShift,
                                        'transferred_from_cutoff'     => $originCutoff,
                                        'transferred_from_listing_id' => $originListing,
                                        'created_by' => Auth::id(),
                                        'updated_by' => Auth::id(),
                                    ]);
                                    
                                    // Deduct quantity from source or delete if depleted
                                    $this->deductSourceQuantity($sourceItem, $item['qty']);
                                } else {
                                    Log::warning("Source item not found", ['source_id' => $sourceId]);
                                }
                            } else {
                                // Regular item - use original schedule data if available
                                $originalSchedule = $existingSchedules->get($itemId);
                                
                                if ($originalSchedule) {
                                    // Use data from original schedule
                                    AssySchedule::create([
                                        'schedule' => $date,
                                        'conveyor_id' => $conveyorId,
                                        'listing_id' => $originalSchedule->listing_id,
                                        'shift' => $shift,
                                        'assycode' => $originalSchedule->assycode,
                                        'assy' => $originalSchedule->assy,
                                        'qty' => $item['qty'], // Use possibly modified qty
                                        'cutoff' => $cutoffNumber,
                                        'seq' => $originalSchedule->seq ?? 0,
                                        'plt' => $originalSchedule->plt ?? 0,
                                        'mode' => $originalSchedule->mode ?? 0,
                                        'snp' => $originalSchedule->snp ?? 0,
                                        'snpa' => $originalSchedule->snpa ?? 0,
                                        'created_by' => Auth::id(),
                                        'updated_by' => Auth::id(),
                                    ]);
                                    Log::info("Created schedule from original", ['itemId' => $itemId]);
                                } else {
                                    // Fallback: try to find by listing_id
                                    $listingId = (int) ($item['listing_id'] ?? 0);
                                    $listingStage = $listingId ? \App\Models\ListingStage::find($listingId) : null;
                                    
                                    Log::info("Fallback to listing_stage", [
                                        'listing_id' => $listingId,
                                        'found' => $listingStage ? true : false
                                    ]);
                                    
                                    if ($listingStage) {
                                        AssySchedule::create([
                                            'schedule' => $date,
                                            'conveyor_id' => $conveyorId,
                                            'listing_id' => $listingStage->id,
                                            'shift' => $shift,
                                            'assycode' => $listingStage->assycode,
                                            'assy' => $listingStage->assy,
                                            'qty' => $item['qty'],
                                            'cutoff' => $cutoffNumber,
                                            'seq' => $listingStage->seq ?? 0,
                                            'plt' => $listingStage->plt ?? 0,
                                            'mode' => $listingStage->mode ?? 0,
                                            'snp' => $listingStage->snp ?? 0,
                                            'snpa' => $listingStage->snpa ?? 0,
                                            'created_by' => Auth::id(),
                                            'updated_by' => Auth::id(),
                                        ]);
                                        Log::info("Created schedule from listing_stage", ['listing_id' => $listingId]);
                                    } else {
                                        Log::warning("Skipping item - no valid data found", ['item' => $item]);
                                    }
                                    // Skip items without valid data (same as saveManageData behavior)
                                }
                            }
                        }
                    }
                }
            }

            // Step 2: Lock all schedules for this conveyor, date, and shift.
            // Snapshot the SIREP capacity/OT/listing-sync values *as they stand right
            // now* onto every row, so the list screen can keep showing what this
            // schedule was actually verified against even after a later SIREP resync
            // changes the live values.
            $conveyorForSnapshot = MasterConveyor::find($conveyorId);
            $sirepSnapshot = $conveyorForSnapshot
                ? $this->sirepMeta($conveyorForSnapshot, $date)
                : [];
            $listingDemandSnapshot = $conveyorForSnapshot
                ? (int) ListingStage::where('conveyor', $conveyorForSnapshot->conveyor)
                    ->whereDate('listing_date_time', $date)
                    ->where('qty', '>', 0)
                    ->sum('qty')
                : null;

            $affected = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $date)
                ->where('shift', $shift)
                ->update([
                    'is_lock' => 1,
                    'verified_at' => now(),
                    'verified_by' => Auth::id(),
                    'verified_capacity' => $sirepSnapshot['capacity'] ?? null,
                    'verified_is_overtime' => $sirepSnapshot['is_overtime'] ?? null,
                    'verified_listing_synced_at' => $sirepSnapshot['listing_synced_at_raw'] ?? null,
                    'verified_listing_source' => $sirepSnapshot['listing_source'] ?? null,
                    'verified_listing_demand' => $listingDemandSnapshot,
                    'updated_by' => Auth::id(),
                    'updated_at' => now()
                ]);

            // Step 3: Generate kanban data for circuits and shikakes
            $kanbanResult = $this->kanbanGenerator->generateKanbanForSchedule(
                $conveyorId,
                $date->format('Y-m-d'),
                $shift
            );

            if (!$kanbanResult['success']) {
                // Rollback entire transaction if kanban generation fails
                // Schedule should NOT be locked without corresponding kanbans
                throw new \RuntimeException('Kanban generation failed: ' . ($kanbanResult['message'] ?? 'Unknown error'));
            }

            DB::commit();

            $message = "Schedule verified successfully. {$affected} records locked.";
            if ($kanbanResult['success']) {
                $message .= " Generated {$kanbanResult['circuit_count']} circuit and {$kanbanResult['shikake_count']} shikake kanbans.";
            }

            return [
                'success' => true,
                'message' => $message,
                'affected' => $affected,
                'kanban_data' => $kanbanResult
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to verify schedule: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Preview unverify side effects: which transferred items can be restored to their origin,
     * and which ones will be lost because the origin schedule has already been verified.
     */
    public function previewUnverify($conveyorId, $date, $shift)
    {
        $date = Carbon::parse($date)->format('Y-m-d');

        $transferredItems = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->where('shift', $shift)
            ->whereNotNull('transferred_from_date')
            ->get();

        $restorable = [];
        $lost       = [];

        foreach ($transferredItems as $item) {
            $originDate  = Carbon::parse($item->transferred_from_date)->format('Y-m-d');
            $originShift = (int) $item->transferred_from_shift;

            $isOriginVerified = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $originDate)
                ->where('shift', $originShift)
                ->where('is_lock', 1)
                ->exists();

            $info = [
                'assy'          => $item->assy,
                'assycode'      => $item->assycode,
                'qty'           => $item->qty,
                'origin_date'   => $originDate,
                'origin_shift'  => $originShift,
                'origin_cutoff' => (int) $item->transferred_from_cutoff,
            ];

            if ($isOriginVerified) {
                $lost[] = $info;
            } else {
                $restorable[] = $info;
            }
        }

        return [
            'success'    => true,
            'restorable' => $restorable,
            'lost'       => $lost,
            'has_warning'=> count($lost) > 0,
            'has_transfer' => count($restorable) + count($lost) > 0,
        ];
    }

    /**
     * Restore transferred items back to their origin schedule group.
     * If origin is still unverified, qty is merged into an existing origin record
     * or a new record is recreated. If origin has been verified, the item is lost
     * (skipped). Must be called BEFORE deleting the current schedule group records.
     */
    private function restoreTransferredItemsToOrigin($conveyorId, $dateStr, $shift)
    {
        $transferredItems = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $dateStr)
            ->where('shift', $shift)
            ->whereNotNull('transferred_from_date')
            ->get();

        $restoredCount = 0;
        $lostCount     = 0;

        foreach ($transferredItems as $item) {
            $originDate    = Carbon::parse($item->transferred_from_date)->format('Y-m-d');
            $originShift   = (int) $item->transferred_from_shift;
            $originCutoff  = (int) ($item->transferred_from_cutoff ?? 0);
            $originListing = (int) ($item->transferred_from_listing_id ?? $item->listing_id);

            $isOriginVerified = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $originDate)
                ->where('shift', $originShift)
                ->where('is_lock', 1)
                ->exists();

            if ($isOriginVerified) {
                $lostCount++;
                Log::info('Unverify: origin already verified, item lost', [
                    'conveyor_id' => $conveyorId,
                    'assy'        => $item->assy,
                    'qty'         => $item->qty,
                    'origin'      => "$originDate shift $originShift CO$originCutoff",
                ]);
                continue;
            }

            // Merge into existing origin record with same listing/cutoff if present
            $existing = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $originDate)
                ->where('shift', $originShift)
                ->where('cutoff', $originCutoff)
                ->where('listing_id', $originListing)
                ->where('is_lock', 0)
                ->first();

            if ($existing) {
                $existing->qty = (int) $existing->qty + (int) $item->qty;
                $existing->updated_by = Auth::id();
                $existing->save();
            } else {
                AssySchedule::create([
                    'schedule'    => $originDate,
                    'conveyor_id' => $conveyorId,
                    'listing_id'  => $originListing,
                    'shift'       => $originShift,
                    'assycode'    => $item->assycode,
                    'assy'        => $item->assy,
                    'qty'         => $item->qty,
                    'cutoff'      => $originCutoff,
                    'seq'         => $item->seq ?? 0,
                    'plt'         => $item->plt ?? 0,
                    'mode'        => $item->mode ?? 0,
                    'snp'         => $item->snp ?? 0,
                    'snpa'        => $item->snpa ?? 0,
                    'is_lock'     => 0,
                    'created_by'  => Auth::id(),
                    'updated_by'  => Auth::id(),
                ]);
            }

            $restoredCount++;
        }

        return [
            'restored_count' => $restoredCount,
            'lost_count'     => $lostCount,
        ];
    }

    /**
     * Kurangi demand yang sudah dipegang shift LAIN pada tanggal+conveyor yang sama
     * dari rem_qty listing.
     *
     * Unverify menghapus dan membangun ulang satu shift saja; baris pada shift lain tetap
     * utuh. Tanpa pengurangan ini, pembangunan ulang akan mengalokasikan seluruh demand
     * harian ke satu shift dan menghitung ganda qty yang masih dipegang shift lain.
     *
     * Schedule yang listing_id-nya milik tanggal lain (item hasil transfer) tidak akan
     * ketemu di sini dan diabaikan — memang bukan bagian dari demand tanggal ini.
     *
     * @param \Illuminate\Support\Collection $listings Listing dengan rem_qty sudah diinisialisasi
     */
    private function deductOtherShiftsFromListings($listings, $conveyorId, $dateStr, $exceptShift): void
    {
        $consumed = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $dateStr)
            ->where('shift', '!=', $exceptShift)
            ->whereNotNull('listing_id')
            ->groupBy('listing_id')
            ->selectRaw('listing_id, SUM(qty) AS used')
            ->pluck('used', 'listing_id');

        if ($consumed->isEmpty()) {
            return;
        }

        $byId = $listings->keyBy('id');

        foreach ($consumed as $listingId => $used) {
            $listing = $byId->get($listingId);
            if (!$listing) {
                continue;
            }
            $listing->rem_qty = max(0, (int) ($listing->rem_qty ?? 0) - (int) $used);
        }
    }

    /**
     * Unverify schedule - unlock the schedule for specific conveyor, date, and shift.
     * Reverses balance, clears kanbans, then regenerates schedules from listing_stage
     * to restore the pre-verification state.
     */
    public function unverifySchedule($conveyorId, $date, $shift)
    {
        try {
            DB::beginTransaction();

            $date = Carbon::parse($date);
            $dateStr = $date->format('Y-m-d');

            // Step 0: Restore transferred items back to their origin (if origin still unverified)
            $restoreResult = $this->restoreTransferredItemsToOrigin($conveyorId, $dateStr, $shift);

            // Step 1: Reverse balance contributions from existing kanbans BEFORE clearing
            $this->kanbanGenerator->reverseBalanceForScheduleGroup($conveyorId, $dateStr, $shift);

            // Step 2: Clear generated kanbans for this schedule group
            $this->kanbanGenerator->clearKanbanData($conveyorId, $dateStr, $shift);

            // Step 3: Delete current assy_schedule records for this conveyor/date/shift
            $deletedCount = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $dateStr)
                ->where('shift', $shift)
                ->delete();

            Log::info("unverifySchedule: Deleted schedules", [
                'conveyor_id' => $conveyorId,
                'date' => $dateStr,
                'shift' => $shift,
                'deleted' => $deletedCount,
            ]);

            // Step 4: Regenerate from listing_stage (restore original allocation)
            $conveyor = MasterConveyor::find($conveyorId);
            $regeneratedCount = 0;

            if ($conveyor) {
                // JANGAN saring dengan listing_stage.shift di sini. Engine generate
                // mengelompokkan listing per tanggal+conveyor saja lalu menyebarnya ke tiap
                // shift berdasarkan kapasitas, jadi sebuah shift sering seluruhnya diisi
                // luberan dari baris yang ditandai shift lain oleh SIREP. Menyaring per shift
                // membuat shift tersebut tidak menemukan listing apa pun dan tersangkut di
                // status "No Data" setelah unverify.
                $listings = ListingStage::where('conveyor', $conveyor->conveyor)
                    ->whereDate('listing_date_time', $dateStr)
                    ->whereNotNull('assycode')
                    ->where('assycode', '!=', '')
                    ->whereNotNull('assy')
                    ->where('assy', '!=', '')
                    ->where('qty', '>', 0)
                    ->orderBy('id_listing', 'asc')
                    ->orderBy('seq', 'asc')
                    ->get();

                if ($listings->isNotEmpty()) {
                    // Initialize rem_qty tracking
                    $this->listingAllocator->initializeListings($listings);

                    // Jumlah shift yang berjalan ditentukan dari demand PENUH hari itu —
                    // sama seperti engine generate. Memakai sisa setelah pengurangan akan
                    // menyusutkan hari 2-shift jadi 1 shift dan shift target tak pernah dibangun.
                    $fullDemand    = (int) $listings->sum('qty');
                    $sirepOvertime = (bool) $listings->contains(fn ($l) => (bool) ($l->is_overtime ?? false));
                    $maxShifts     = $this->capacityCalculator->resolveShiftCount(
                        (int) $conveyor->capacity,
                        $fullDemand,
                        $sirepOvertime
                    );

                    // Hanya shift ini yang dihapus; shift lain masih memegang bagiannya,
                    // jadi demand itu tidak boleh dialokasikan untuk kedua kalinya.
                    $this->deductOtherShiftsFromListings($listings, $conveyorId, $dateStr, $shift);
                    $remainingQty = (int) $listings->sum('rem_qty');

                    // Bangun ulang shift ini saja: shift lain diperlakukan terkunci.
                    $shiftLockStatus = [];
                    for ($s = 1; $s <= max(2, $maxShifts); $s++) {
                        $shiftLockStatus[$s] = ((int) $s !== (int) $shift);
                    }

                    // Calculate cutoff capacities
                    $shiftCapacities = $this->capacityCalculator->calculateShiftCapacities(
                        (int) $conveyor->capacity,
                        $shiftLockStatus,
                        $maxShifts
                    );

                    if ($remainingQty > 0 && isset($shiftCapacities[$shift])) {
                        // Pre-map CO5 untuk shift ini saja. Ia satu-satunya shift yang tidak
                        // terkunci, jadi CO5-nya berperan catch-all — sisa demand tidak terbuang.
                        $targetCaps = [$shift => $shiftCapacities[$shift]];
                        $this->capacityCalculator->preMapCutoff5(
                            $targetCaps, (int) $conveyor->capacity, $remainingQty
                        );

                        // Allocate to shift (CO1-4 then CO5)
                        $allocationResult = $this->listingAllocator->allocateToShift(
                            $listings,
                            $targetCaps[$shift],
                            $shift,
                            $conveyor->id,
                            $dateStr
                        );

                        // Bulk insert
                        if (!empty($allocationResult['schedules'])) {
                            foreach (array_chunk($allocationResult['schedules'], 500) as $chunk) {
                                AssySchedule::insert($chunk);
                            }
                            $regeneratedCount = count($allocationResult['schedules']);
                        }
                    }

                    Log::info("unverifySchedule: Regenerated from listing_stage", [
                        'listings_found' => $listings->count(),
                        'listing_demand' => $fullDemand,
                        'max_shifts' => $maxShifts,
                        'remaining_qty' => $remainingQty,
                        'schedules_created' => $regeneratedCount,
                    ]);
                } else {
                    Log::warning("unverifySchedule: No listing_stage rows for this date/conveyor", [
                        'conveyor' => $conveyor->conveyor,
                        'date' => $dateStr,
                    ]);
                }
            }

            DB::commit();

            $message = "Schedule berhasil di-unverify. {$deletedCount} record dihapus, {$regeneratedCount} record di-regenerate dari data listing asli.";
            if (!empty($restoreResult['restored_count'])) {
                $message .= " {$restoreResult['restored_count']} item transfer dikembalikan ke jadwal asal.";
            }
            if (!empty($restoreResult['lost_count'])) {
                $message .= " {$restoreResult['lost_count']} item transfer HILANG karena jadwal asal sudah diverifikasi.";
            }

            return [
                'success' => true,
                'message' => $message,
                'affected' => $regeneratedCount,
                'restored_count' => $restoreResult['restored_count'] ?? 0,
                'lost_count'     => $restoreResult['lost_count'] ?? 0,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("unverifySchedule failed", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return [
                'success' => false,
                'message' => 'Failed to unverify schedule: ' . $e->getMessage()
            ];
        }
    }
}
