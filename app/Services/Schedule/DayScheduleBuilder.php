<?php

namespace App\Services\Schedule;

use App\Models\AssySchedule;
use App\Models\ListingStage;
use App\Models\MasterConveyor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Penyusun jadwal satu hari (tanggal × conveyor) dari listing_stage.
 *
 * Dipakai generate DAN unverify, supaya keduanya membagi shift dengan aturan yang sama.
 *
 * Prinsipnya: baris assy_schedule yang masih ada memegang bagian listingnya, dan shift
 * yang masih punya baris tidak diisi lagi. Hanya SISA listing yang dialokasikan, dan
 * hanya ke shift yang masih kosong.
 *
 * Dua kekeliruan versi sebelumnya yang ditutup di sini (kasus B3-ENG 30-09-2026):
 *
 *  1. Listing dilewati SELURUHNYA begitu satu potongannya terkunci. Listing yang terbelah
 *     ke dua shift (7YA1E 112 = 56 di S1 + 56 di S2) kehilangan potongan milik shift yang
 *     belum diverifikasi — setelah S2 diverifikasi, generate berikutnya menyisakan S1 = 22
 *     dari seharusnya 78.
 *  2. Jumlah shift dihitung dari listing yang TERSISA, bukan demand penuh hari itu.
 *     Setelah S1 diverifikasi, sisa demand muat di satu shift, S1 sudah terkunci, sehingga
 *     sisa tidak punya tempat dan S2 hilang dari daftar — hari itu tampak "verified 1 shift".
 */
class DayScheduleBuilder
{
    protected ShiftCapacityCalculator $capacityCalculator;
    protected ListingAllocator $listingAllocator;
    protected ScheduleCleanupService $scheduleCleanup;

    public function __construct(
        ShiftCapacityCalculator $capacityCalculator,
        ListingAllocator $listingAllocator,
        ScheduleCleanupService $scheduleCleanup
    ) {
        $this->capacityCalculator = $capacityCalculator;
        $this->listingAllocator   = $listingAllocator;
        $this->scheduleCleanup    = $scheduleCleanup;
    }

    /**
     * Listing yang layak dijadwalkan, urut FIFO sesuai PPC.
     *
     * Urutan dalam satu tanggal × conveyor mengikuti `seq` SIREP — seq terkecil
     * dialokasikan lebih dulu. jai_ekanban memakai id_listing, tetapi id dari API
     * SIREP tidak mengikuti urutan produksi: B3-ENG 07-10-2026 7YA1E seq 1 ber-id 7818,
     * 7YA1B seq 2 ber-id 7817, sehingga 7YA1B ikut naik ke shift 1. id_listing
     * hanya pemecah seri.
     *
     * Sengaja TIDAK menyaring listing yang sudah punya jadwal terkunci: bagian yang
     * sudah dipegang dikurangkan per listing_id di plan(), bukan dibuang utuh.
     */
    public function listingQuery(): Builder
    {
        return ListingStage::query()
            ->whereNotNull('assycode')
            ->where('assycode', '!=', '')
            ->whereNotNull('assy')
            ->where('assy', '!=', '')
            ->where('qty', '>', 0)
            ->orderBy('seq', 'asc')
            ->orderBy('id_listing', 'asc');
    }

    /**
     * Hapus jadwal hari itu yang belum terlindungi (belum terkunci dan belum ber-kanban).
     * Sesudahnya, setiap baris yang masih ada untuk tanggal × conveyor itu adalah baris
     * terlindungi.
     */
    public function clearUnprotected(MasterConveyor $conveyor, Carbon $date): int
    {
        $day = $date->copy()->startOfDay();

        return $this->scheduleCleanup->deleteUnlockedSchedulesInRange($day, $day, $conveyor->id);
    }

    /**
     * Susun jadwal untuk bagian hari yang belum dipegang siapa pun. Tidak menulis
     * apa pun — baris hasilnya dikembalikan untuk di-insert pemanggil.
     *
     * Setiap baris assy_schedule yang MASIH ADA dianggap memegang qty listingnya, dan
     * setiap shift yang masih punya baris pada tanggal itu tidak diisi lagi. Generate
     * memanggil clearUnprotected() lebih dulu, jadi yang tersisa hanya baris terlindungi;
     * unverify tidak, jadi shift lain — terkunci maupun pending — tetap utuh.
     *
     * @param  Collection  $listings  Listing satu tanggal × conveyor, urut FIFO
     */
    public function plan(MasterConveyor $conveyor, Carbon $date, Collection $listings): array
    {
        return $this->allocate(
            $listings,
            $this->heldQuantities($listings),
            $this->occupiedShifts($conveyor->id, $date),
            (int) ($conveyor->capacity ?? 0),
            $conveyor->overtime_capacity,
            $conveyor->id,
            $date->format('Y-m-d')
        );
    }

    /**
     * Inti alokasi — murni, tanpa database.
     *
     * @param  Collection       $listings         Listing satu hari, urut FIFO; diberi rem_qty
     * @param  array<int, int>  $held             Qty yang sudah dipegang jadwal lain, per listing_id
     * @param  array<int, int>  $occupiedShifts   Shift yang sudah punya jadwal — tidak diisi lagi
     * @return array{
     *     schedules: array<int, array<string, mixed>>,
     *     full_demand: int, remaining: int, unallocated: int, max_shifts: int,
     *     overtime_capacity: int, shift_capacities: array, co5_needed: array
     * }
     */
    public function allocate(
        Collection $listings,
        array $held,
        array $occupiedShifts,
        int $normalCapacity,
        ?int $overtimeCapacity,
        int $conveyorId,
        string $scheduleDate
    ): array {
        $this->listingAllocator->initializeListings($listings);

        // Jumlah shift mengikuti demand PENUH hari itu, termasuk bagian yang sudah
        // terkunci. Memakai sisanya saja menyusutkan hari dua shift menjadi satu.
        $fullDemand  = (int) $listings->sum('qty');
        $overtimeCap = $this->capacityCalculator->effectiveOvertimeCapacity($normalCapacity, $overtimeCapacity);
        $maxShifts   = $this->capacityCalculator->resolveShiftCount($overtimeCap, $fullDemand);

        // Bagian yang sudah dipegang jadwal lain tidak dialokasikan untuk kedua kalinya.
        foreach ($listings as $listing) {
            $listing->rem_qty = max(0, (int) $listing->rem_qty - (int) ($held[$listing->id] ?? 0));
        }

        $remaining = (int) $listings->sum('rem_qty');

        $lockStatus = [];
        for ($shift = 1; $shift <= ShiftCapacityCalculator::MAX_SHIFT; $shift++) {
            $lockStatus[$shift] = in_array($shift, $occupiedShifts);
        }

        // Lembur atau tidak juga dari demand PENUH: hari yang sebagian shiftnya sudah
        // diverifikasi tetap dibagi dengan aturan yang sama seperti saat masih kosong.
        $overtimeDay = $this->capacityCalculator->isOvertimeDay($normalCapacity, $maxShifts, $fullDemand);

        $shiftCapacities = $this->capacityCalculator->calculateShiftCapacities($normalCapacity, $lockStatus, $maxShifts);
        $co5Needed       = $this->capacityCalculator->preMapCutoff5(
            $shiftCapacities, $normalCapacity, $overtimeCap, $remaining, $overtimeDay
        );

        // Shift diisi berurutan, CO1 → CO5 per shift (jatah CO5 dari preMapCutoff5):
        //   lembur : S1.CO1-CO5 sampai overtime_capacity → S2.CO1-CO5 (CO5 = sisa)
        //   normal : S1.CO1-CO4 sampai normal_capacity  → S2.CO1-CO4
        // Pada hari satu shift ini sama dengan CO1 → CO4 → CO5.
        $schedules = [];

        foreach ($shiftCapacities as $shift => $caps) {
            if ($listings->sum('rem_qty') <= 0) {
                break;
            }

            if ($caps['locked'] ?? false) {
                continue;
            }

            $result = $this->listingAllocator->allocateToShift(
                $listings, $caps, $shift, $conveyorId, $scheduleDate
            );
            $schedules = array_merge($schedules, $result['schedules']);
        }

        return [
            'schedules'         => $schedules,
            'full_demand'       => $fullDemand,
            'remaining'         => $remaining,
            'overtime_day'      => $overtimeDay,
            // Tidak nol hanya bila semua shift hari itu sudah terisi, mis. SIREP menambah
            // qty setelah verifikasi. Dilaporkan, bukan dibuang diam-diam.
            'unallocated'       => (int) $listings->sum('rem_qty'),
            'max_shifts'        => $maxShifts,
            'overtime_capacity' => $overtimeCap,
            'shift_capacities'  => $shiftCapacities,
            'co5_needed'        => $co5Needed,
        ];
    }

    /**
     * Qty yang masih dipegang baris assy_schedule, per listing_id — dari tanggal mana pun,
     * sehingga item yang sudah dipindah ke tanggal lain lewat layar verifikasi ikut dihitung.
     *
     * @return array<int, int>
     */
    public function heldQuantities(Collection $listings): array
    {
        $ids = $listings->pluck('id')->filter()->all();

        if (empty($ids)) {
            return [];
        }

        return AssySchedule::whereIn('listing_id', $ids)
            ->groupBy('listing_id')
            ->selectRaw('listing_id, SUM(qty) AS held')
            ->pluck('held', 'listing_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
    }

    /**
     * Shift yang masih punya baris jadwal pada tanggal × conveyor itu.
     *
     * @return array<int, int>
     */
    public function occupiedShifts(int $conveyorId, Carbon $date): array
    {
        return AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date->format('Y-m-d'))
            ->distinct()
            ->pluck('shift')
            ->map(fn ($shift) => (int) $shift)
            ->all();
    }
}
