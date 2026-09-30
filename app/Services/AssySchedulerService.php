<?php

namespace App\Services;

use App\Models\AssySchedule;
use App\Models\ListingStage;
use App\Models\MasterConveyor;
use App\Services\Schedule\DayScheduleBuilder;
use App\Services\Schedule\ShiftCapacityCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssySchedulerService
{
    protected $listingSyncService;
    protected $capacityCalculator;
    protected $dayBuilder;

    protected $conveyorSync;

    public function __construct(
        ListingSyncService $listingSyncService,
        ShiftCapacityCalculator $capacityCalculator,
        DayScheduleBuilder $dayBuilder,
        SirepConveyorSyncService $conveyorSync
    ) {
        $this->conveyorSync = $conveyorSync;
        $this->listingSyncService = $listingSyncService;
        $this->capacityCalculator = $capacityCalculator;
        $this->dayBuilder = $dayBuilder;
    }

    /**
     * Sebutan sumber listing untuk pesan ke operator.
     *
     * Pesan galat harus menyebut sumber yang benar-benar dipakai: menyebut
     * "database listing" saat sumbernya API membuat operator memeriksa sistem
     * yang salah.
     *
     * @param  string|null  $source  Nilai dari hasil sinkronisasi; null = ambil dari config
     */
    private function sourceLabel(?string $source = null): string
    {
        $source ??= config('sirep.listing_source');

        return $source === 'db' ? 'database listing lama (PPC)' : 'API SIREP';
    }

    /**
     * Generate assy schedules for the specified date range with cutoff system
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $conveyorId
     * @return array
     */
    public function generateSchedules($startDate, $endDate, $conveyorId = null)
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate   = Carbon::parse($endDate)->endOfDay();

        // ─── STEP 0: Samakan daftar conveyor dengan SIREP lebih dulu ───────────
        // Listing datang per nama conveyor, jadi master harus sudah mencerminkan
        // keadaan SIREP sebelum listing diproses. Tanpa ini, conveyor yang baru
        // dibuat PPC hari ini listingnya akan terbuang karena belum ada padanannya,
        // dan conveyor yang sudah dihapus PPC masih ikut terjadwal.
        $conveyorSync = $this->conveyorSync->sync(true);

        if (!$conveyorSync['success']) {
            return [
                'success'     => false,
                'step_failed' => 'sync_conveyor',
                'message'     => 'Gagal menyamakan daftar conveyor dengan SIREP: '
                    . $conveyorSync['message']
                    . ' Proses generate dihentikan sebelum listing diambil.',
                'sync_detail' => null,
                'generated'   => 0,
            ];
        }

        Log::info('Daftar conveyor disamakan dengan SIREP sebelum generate', [
            'ditambah'      => $conveyorSync['ditambah'],
            'diperbarui'    => $conveyorSync['diperbarui'],
            'dinonaktifkan' => $conveyorSync['dinonaktifkan'],
        ]);

        // ─── STEP 1: Ambil listing dari sumber aktif (API SIREP) → listing_stage ───
        // TANPA transaksi pembungkus di sini. Langkah ini memanggil API SIREP
        // (satu permintaan per conveyor, masing-masing bisa sampai timeout 30s),
        // dan dulu seluruh rangkaian itu berjalan di dalam satu transaksi yang
        // sudah menghapus baris listing_stage — lock-nya tertahan selama seluruh
        // panggilan HTTP, sehingga generate yang berjalan bersamaan saling
        // menunggu sampai lock wait timeout. Kedua langkah di bawah sudah atomik
        // sendiri: delete adalah satu perintah, dan syncListingData membungkus
        // tulisannya dengan DB::transaction.
        try {
            $deleteListingResult = $this->listingSyncService->deleteListingStageData(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            if (!$deleteListingResult['success']) {
                return [
                    'success'     => false,
                    'step_failed' => 'sync_listing',
                    'message'     => 'Gagal membersihkan data listing_stage: ' . $deleteListingResult['message'],
                    'sync_detail' => null,
                    'generated'   => 0,
                ];
            }

            $syncResult = $this->listingSyncService->syncListingData(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            if (!$syncResult['success']) {
                return [
                    'success'     => false,
                    'step_failed' => 'sync_listing',
                    'message'     => 'Gagal mengambil data listing terbaru dari ' . $this->sourceLabel($syncResult['source'] ?? null) . ': '
                        . rtrim((string) ($syncResult['message'] ?? 'Sumber listing tidak tersedia.'), ' .')
                        . '. Proses generate dihentikan dan tidak ada sumber cadangan yang dicoba.',
                    'sync_detail' => null,
                    'generated'   => 0,
                ];
            }

        } catch (\Exception $e) {
            Log::error("Listing sync/clone failed", ['error' => $e->getMessage()]);
            return [
                'success'     => false,
                'step_failed' => 'sync_listing',
                'message'     => 'Tidak dapat terhubung ke ' . $this->sourceLabel() . '. Proses generate dihentikan dan tidak ada sumber cadangan yang dicoba. (' . $e->getMessage() . ')',
                'sync_detail' => null,
                'generated'   => 0,
            ];
        }

        $syncDetail = [
            'total_records' => $syncResult['total_records'] ?? 0,
            'synced'        => $syncResult['synced']        ?? 0,
            'skipped'       => $syncResult['skipped']       ?? 0,
        ];

        // ─── STEP 2: Generate assy schedules from listing_stage ──────────────
        try {
            DB::beginTransaction();

            // Seluruh listing rentang itu, termasuk yang sebagian sudah dipegang jadwal
            // terkunci. Bagian yang sudah dipegang dikurangkan per listing_id oleh
            // DayScheduleBuilder — dulu listing seperti itu dibuang utuh, sehingga
            // potongan milik shift yang belum diverifikasi ikut hilang.
            $listingsQuery = $this->dayBuilder->listingQuery()
                ->whereBetween('listing_date_time', [$startDate, $endDate]);

            if ($conveyorId) {
                $listingsQuery->where('conveyor', function($query) use ($conveyorId) {
                    $query->select('conveyor')
                        ->from('master_conveyor')
                        ->where('id', $conveyorId)
                        ->where('is_active', 1)
                        ->limit(1);
                });
            }

            $listings = $listingsQuery->get();

            if ($listings->isEmpty()) {
                DB::commit();
                return [
                    'success'     => true,
                    'step_failed' => null,
                    'message'     => 'Tidak ada data listing ditemukan untuk rentang tanggal yang dipilih.',
                    'generated'   => 0,
                    'sync_detail' => $syncDetail,
                ];
            }

            // Group listings by date and conveyor
            $groupedListings = $listings->groupBy(function ($listing) {
                return $listing->listing_date_time->format('Y-m-d') . '_' . $listing->conveyor;
            });

            $generatedCount = 0;
            $schedulesToCreate = [];
            $capacityErrors = [];
            $ambangCadangan = [];
            $conveyorErrors = [];
            $belumTerjadwal = [];
            $days = [];

            // Tahap 1: pilih hari yang boleh dijadwalkan, lalu bersihkan jadwalnya yang
            // belum terlindungi. SEMUA hari dibersihkan lebih dulu sebelum satu pun disusun:
            // item yang dipindah antar tanggal lewat layar verifikasi masih memegang
            // listing asalnya, dan bila pembersihan diselang-seling dengan penyusunan,
            // urutan grup menentukan apakah qty itu terhitung dua kali atau hilang.
            foreach ($groupedListings as $groupKey => $groupListings) {
                list($date, $conveyorName) = explode('_', $groupKey, 2);
                
                // Hanya conveyor yang masih terdaftar di SIREP yang dijadwalkan.
                // Yang sudah dinonaktifkan sengaja dilewati: jadwal lamanya tetap ada,
                // tetapi tidak boleh bertambah.
                $conveyor = MasterConveyor::active()->where('conveyor', $conveyorName)->first();

                if (!$conveyor) {
                    $nonaktif = MasterConveyor::where('conveyor', $conveyorName)->first();

                    if ($nonaktif) {
                        $conveyorErrors[$conveyorName] = $conveyorName;
                        Log::warning('Conveyor dilewati: sudah tidak ada di SIREP (nonaktif)', [
                            'conveyor' => $conveyorName,
                        ]);
                    } else {
                        Log::warning("Conveyor not found: {$conveyorName}");
                    }

                    continue;
                }

                $scheduleDate  = Carbon::parse($date);
                $shiftCapacity = (int) ($conveyor->capacity ?? 0);

                // normal_capacity milik SIREP dan wajib ada — ia yang membagi CO1-CO4.
                // Tanpa itu tidak ada dasar menjadwalkan apa pun, dan menebak angka
                // menghasilkan jadwal yang salah diam-diam.
                if ($shiftCapacity <= 0) {
                    $capacityErrors[$conveyorName] = $conveyorName;
                    Log::warning('Conveyor dilewati: kapasitas SIREP belum tersinkron', [
                        'conveyor'      => $conveyorName,
                        'schedule_date' => $scheduleDate->format('Y-m-d'),
                    ]);
                    continue;
                }

                // overtime_capacity boleh belum ada: conveyor itu diperlakukan sebagai
                // tidak punya jatah lembur, sehingga ambangnya jatuh ke normal_capacity.
                // Ia tetap terjadwal, dan hari yang melampaui kapasitas normal pecah jadi
                // dua shift alih-alih menumpuk di CO5.
                if ($this->capacityCalculator->overtimeCapacityIsFallback($conveyor->overtime_capacity)) {
                    $ambangCadangan[$conveyorName] = $conveyorName;
                }

                $this->dayBuilder->clearUnprotected($conveyor, $scheduleDate);

                $days[] = [$conveyor, $scheduleDate, $groupListings];
            }

            // Tahap 2: susun tiap hari. Jumlah shift DIHITUNG dari demand penuh SIREP hari
            // itu (satu shift menampung tepat overtime_capacity); bagian listing yang sudah
            // dipegang shift terkunci dikurangkan, dan sisanya diisi ke shift yang masih
            // kosong. Penanda is_overtime sengaja tidak dipakai — ia baru ditetapkan PPC
            // mendekati hari produksi, sehingga generate untuk tanggal ke depan akan
            // berubah hasilnya tergantung kapan dijalankan.
            foreach ($days as [$conveyor, $scheduleDate, $groupListings]) {
                $plan = $this->dayBuilder->plan($conveyor, $scheduleDate, $groupListings);

                // Demand yang melampaui kapasitas nominal hari itu tetap dijadwalkan
                // (CO5 shift terakhir menampungnya), tetapi harus terlihat supaya bisa
                // diperiksa sebelum jadwal dikunci.
                $shiftCapacity = (int) $conveyor->capacity;
                $nominalHari   = $this->capacityCalculator->nominalDayCapacity(
                    $shiftCapacity, $plan['overtime_capacity'], $plan['max_shifts']
                );

                if ($plan['full_demand'] > $nominalHari) {
                    Log::warning('Listing melampaui kapasitas nominal hari itu', [
                        'conveyor'          => $conveyor->conveyor,
                        'schedule_date'     => $scheduleDate->format('Y-m-d'),
                        'total_qty'         => $plan['full_demand'],
                        'normal_capacity'   => $shiftCapacity,
                        'overtime_capacity' => $plan['overtime_capacity'],
                        'shift_dipakai'     => $plan['max_shifts'],
                        'kapasitas_nominal' => $nominalHari,
                    ]);
                }

                // Hanya terjadi bila semua shift hari itu sudah terkunci, mis. SIREP
                // menambah qty setelah verifikasi. Tidak dibuang diam-diam.
                if ($plan['unallocated'] > 0) {
                    $belumTerjadwal[] = sprintf(
                        '%s %s (%d pcs)',
                        $conveyor->conveyor,
                        $scheduleDate->format('d-m-Y'),
                        $plan['unallocated']
                    );
                    Log::warning('Sebagian listing belum terjadwal: seluruh shift hari itu sudah terkunci', [
                        'conveyor'      => $conveyor->conveyor,
                        'schedule_date' => $scheduleDate->format('Y-m-d'),
                        'full_demand'   => $plan['full_demand'],
                        'unallocated'   => $plan['unallocated'],
                    ]);
                }

                Log::info("CO5 pre-mapping result", [
                    'conveyor_id'      => $conveyor->id,
                    'schedule_date'    => $scheduleDate->format('Y-m-d'),
                    'total_qty'        => $plan['full_demand'],
                    'remaining_qty'    => $plan['remaining'],
                    'max_shifts'       => $plan['max_shifts'],
                    'co5_needed'       => $plan['co5_needed'],
                    'overtime_capacity'=> $plan['overtime_capacity'],
                    'shift_capacities' => $plan['shift_capacities'],
                ]);

                $schedulesToCreate = array_merge($schedulesToCreate, $plan['schedules']);
            }

            // Step 10: Bulk insert all schedules
            if (!empty($schedulesToCreate)) {
                foreach (array_chunk($schedulesToCreate, 500) as $chunk) {
                    AssySchedule::insert($chunk);
                }
                $generatedCount = count($schedulesToCreate);
            }

            DB::commit();

            $message = "Berhasil membuat {$generatedCount} schedule.";

            if ($conveyorSync['ditambah'] || $conveyorSync['dinonaktifkan']) {
                $message .= sprintf(
                    ' Daftar conveyor disamakan dengan SIREP: %d baru, %d dinonaktifkan.',
                    $conveyorSync['ditambah'],
                    $conveyorSync['dinonaktifkan']
                );
            }

            if (!empty($capacityErrors)) {
                $message .= ' Dilewati karena kapasitas SIREP belum tersinkron: '
                    . implode(', ', $capacityErrors) . '.';
            }

            if (!empty($conveyorErrors)) {
                $message .= ' Dilewati karena sudah tidak terdaftar di SIREP: '
                    . implode(', ', $conveyorErrors) . '.';
            }

            if (!empty($ambangCadangan)) {
                $message .= ' Dijadwalkan tanpa jatah lembur karena SIREP belum mengirim '
                    . 'overtime_capacity: ' . implode(', ', $ambangCadangan) . '.';
            }

            if (!empty($belumTerjadwal)) {
                $message .= ' Sebagian listing belum terjadwal karena seluruh shift hari itu sudah '
                    . 'diverifikasi: ' . implode(', ', $belumTerjadwal) . '. Unverify shift terkait '
                    . 'lalu generate ulang untuk memasukkannya.';
            }

            return [
                'success'          => true,
                'step_failed'      => null,
                'message'          => $message,
                'generated'        => $generatedCount,
                'sync_detail'      => $syncDetail,
                'conveyor_sync'    => [
                    'ditambah'      => $conveyorSync['ditambah'],
                    'diperbarui'    => $conveyorSync['diperbarui'],
                    'dinonaktifkan' => $conveyorSync['dinonaktifkan'],
                ],
                'capacity_missing' => array_values($capacityErrors),
                'conveyor_inactive' => array_values($conveyorErrors),
                'ambang_cadangan'   => array_values($ambangCadangan),
                'belum_terjadwal'   => $belumTerjadwal,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Schedule generation failed", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return [
                'success'     => false,
                'step_failed' => 'generate',
                'message'     => 'Gagal melakukan generate schedule: ' . $e->getMessage(),
                'sync_detail' => $syncDetail,
                'generated'   => 0,
            ];
        }
    }

    /**
     * Get schedules for datatable
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function getSchedulesQuery($filters = [])
    {
        $query = AssySchedule::with(['conveyor', 'listingStage'])
            ->orderBy('schedule', 'asc')
            ->orderBy('conveyor_id', 'asc')
            ->orderBy('shift', 'asc');

        // Apply filters
        if (!empty($filters['start_date'])) {
            $query->whereDate('schedule', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('schedule', '<=', $filters['end_date']);
        }

        if (!empty($filters['conveyor_id'])) {
            $query->where('conveyor_id', $filters['conveyor_id']);
        }

        return $query;
    }

    /**
     * Verify a schedule (change status)
     *
     * @param int $scheduleId
     * @return array
     */
    public function verifySchedule($scheduleId)
    {
        try {
            // TODO: Implement verification logic
            $schedule = AssySchedule::findOrFail($scheduleId);
            
            // Update status or is_lock field
            $schedule->is_lock = 1;
            $schedule->updated_by = Auth::id();
            $schedule->save();

            return [
                'success' => true,
                'message' => 'Schedule verified successfully',
            ];
        } catch (\Exception $e) {
            Log::error("Schedule verification failed", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to verify schedule: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete schedule(s) for a date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $conveyorId
     * @return array
     */
    public function deleteSchedules($startDate, $endDate, $conveyorId = null)
    {
        try {
            $query = AssySchedule::whereDate('schedule', '>=', $startDate)
                ->whereDate('schedule', '<=', $endDate);

            if ($conveyorId) {
                $query->where('conveyor_id', $conveyorId);
            }

            $deletedCount = $query->delete();

            return [
                'success' => true,
                'message' => "Deleted {$deletedCount} schedule(s) successfully",
                'deleted' => $deletedCount,
            ];
        } catch (\Exception $e) {
            Log::error("Schedule deletion failed", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to delete schedules: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete existing assy_schedule records for the specified date range
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param int|null $conveyorId
     * @return int Number of deleted records
     */
    private function deleteExistingSchedules($startDate, $endDate, $conveyorId = null)
    {
        $query = AssySchedule::whereBetween('schedule', [$startDate, $endDate]);
        
        if ($conveyorId) {
            $query->where('conveyor_id', $conveyorId);
        }
        
        $deletedCount = $query->delete();
        
        Log::info("Deleted {$deletedCount} existing assy_schedule records for date range", [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'conveyor_id' => $conveyorId
        ]);
        
        return $deletedCount;
    }

    /**
     * Get manage data for a specific conveyor and date
     *
     * @param int $conveyorId
     * @param string $date
     * @return array
     */
    public function getManageData($conveyorId, $date)
    {
        try {
            $date = Carbon::parse($date);
            $conveyor = MasterConveyor::findOrFail($conveyorId);

            // Get existing scheduled items
            $scheduledItems = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $date)
                ->with('listingStage')
                ->orderBy('shift')
                ->orderBy('listing_id')
                ->get();

            // Group scheduled items by shift. Jumlah shift dibaca dari master.
            $shifts = [];
            // Jumlah shift mengikuti jadwal yang benar-benar terbentuk pada tanggal itu.
            $maxShifts = max(1, (int) $scheduledItems->max('shift'));
            $shiftCapacity = (int) ($conveyor->capacity ?? 0);

            // Initialize all shifts
            for ($i = 1; $i <= $maxShifts; $i++) {
                $shifts[$i] = [
                    'total_capacity' => $shiftCapacity,
                    'used_capacity' => 0,
                    'items' => []
                ];
            }

            // Populate shifts with scheduled items
            foreach ($scheduledItems as $item) {
                if (isset($shifts[$item->shift])) {
                    $shifts[$item->shift]['items'][] = [
                        'id' => $item->id,
                        'assy' => $item->assy,
                        'qty' => $item->qty,
                        'listing_id' => $item->listing_id,
                        'listing_date_time' => $item->listingStage ? $item->listingStage->listing_date_time->format('Y-m-d H:i') : ''
                    ];
                    $shifts[$item->shift]['used_capacity'] += $item->qty;
                }
            }

            // Calculate real counts for header
            $totalAssyCount = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $date)
                ->distinct('assy')
                ->count();
                
            $totalListingCount = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $date)
                ->sum('qty');

            return [
                'success' => true,
                'shifts' => $shifts,
                'conveyor' => $conveyor,
                'date' => $date->format('Y-m-d'),
                'total_assy_count' => $totalAssyCount,
                'total_listing_count' => $totalListingCount
            ];
        } catch (\Exception $e) {
            Log::error("Get manage data failed", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to get manage data: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Save manage data changes
     *
     * @param int $conveyorId
     * @param string $date
     * @param array $shifts
     * @return array
     */
    public function saveManageData($conveyorId, $date, $shifts)
    {
        try {
            DB::beginTransaction();

            $date = Carbon::parse($date);
            $conveyor = MasterConveyor::findOrFail($conveyorId);

            // Collect all item IDs that are being updated
            $itemsToUpdateIds = [];
            foreach ($shifts as $shiftNumber => $shiftData) {
                if (!empty($shiftData['items'])) {
                    foreach ($shiftData['items'] as $item) {
                        $itemsToUpdateIds[] = $item['id'];
                    }
                }
            }

            // Only delete existing schedules that are being replaced/updated
            if (!empty($itemsToUpdateIds)) {
                AssySchedule::whereIn('id', $itemsToUpdateIds)->delete();
            }

            $createdCount = 0;

            // Recreate schedules based on new arrangement
            foreach ($shifts as $shiftNumber => $shiftData) {
                if (!empty($shiftData['items'])) {
                    foreach ($shiftData['items'] as $item) {
                        // Get original listing data
                        $type = $item['type'] ?? 'shift'; // Default to 'shift' if not provided

                        if ($type === 'available') {
                            // delete assy schedule from original date
                            AssySchedule::where('id', $item['id'])->delete();
                        }

                        $listingStage = ListingStage::find($item['listing_id']);
                        
                        if ($listingStage) {
                            AssySchedule::create([
                                'schedule' => $date,
                                'conveyor_id' => $conveyorId,
                                'listing_id' => $listingStage->id,
                                'shift' => $shiftNumber,
                                'assycode' => $listingStage->assycode,
                                'assy' => $listingStage->assy,
                                'qty' => $item['qty'], // Use the possibly modified qty
                                'seq' => $listingStage->seq,
                                'mode' => $listingStage->mode,
                                'snp' => $listingStage->snp,
                                'snpa' => $listingStage->snpa,
                                'created_by' => Auth::id(),
                            ]);
                            $createdCount++;
                        }
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Successfully updated schedule with {$createdCount} items",
                'created' => $createdCount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Save manage data failed", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to save manage data: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get available assy data with date range filter and pagination
     *
     * @param int $conveyorId
     * @param string $selectedDate
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAvailableAssyData($conveyorId, $selectedDate, $startDate = null, $endDate = null, $page = 1, $perPage = 20)
    {
        try {
            $selectedDate = Carbon::parse($selectedDate);

            // Default date range: selected date to +7 days
            if (!$startDate) {
                $startDate = $selectedDate->format('Y-m-d');
            }
            if (!$endDate) {
                $endDate = $selectedDate->copy()->addDays(7)->format('Y-m-d');
            }

            // Validate maximum 7-day range
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            if ($start->diffInDays($end) > 7) {
                // Adjust to 7-day range from selected date
                $startDate = $selectedDate->format('Y-m-d');
                $endDate = $selectedDate->copy()->addDays(7)->format('Y-m-d');
            }

            // Get available assy schedules from future dates that can be moved to selected date
            $query = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereBetween('schedule', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                // Only get schedules from dates after the selected date
                ->whereDate('schedule', '!=', $selectedDate)
                // Exclude assy codes already assigned to the selected date
                ->whereNotNull('assycode')
                ->where('assycode', '!=', '')
                ->whereNotNull('assy')
                ->where('assy', '!=', '')
                ->where('qty', '>', 0)
                ->orderBy('schedule')
                ->orderBy('shift')
                ->orderBy('listing_id');

            // Get paginated results
            $totalCount = $query->count();
            $items = $query->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            // Prepare available items
            $available = [];
            foreach ($items as $item) {
                $available[] = [
                    'id' => $item->id,
                    'assy' => $item->assy,
                    'qty' => $item->qty,
                    'schedule_date' => $item->schedule->format('Y-m-d'),
                    'shift' => $item->shift,
                    'listing_id' => $item->listing_id
                ];
            }

            $totalPages = ceil($totalCount / $perPage);

            return [
                'success' => true,
                'available' => $available,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ],
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'selected_date' => $selectedDate->format('Y-m-d')
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Get available assy data failed", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to get available assy data: ' . $e->getMessage(),
            ];
        }
    }
}
