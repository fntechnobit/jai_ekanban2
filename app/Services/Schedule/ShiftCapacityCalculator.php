<?php

namespace App\Services\Schedule;

/**
 * Pembagian kapasitas conveyor menjadi shift dan cutoff.
 *
 * Seluruh angka berasal dari API SIREP. Master conveyor tidak lagi menyimpan
 * kapasitas maupun jumlah shift — hanya shift mulai untuk hari satu shift
 * (lihat runningShifts()):
 *
 *   normal_capacity    kapasitas satu shift tanpa lembur
 *   overtime_capacity  kapasitas satu shift dengan lembur — sekaligus AMBANG
 *                      pemecahan shift
 *
 * Satu shift menampung tepat `overtime_capacity` (CO1-CO4 = normal, CO5 = selisihnya),
 * sehingga aturan "qty melebihi overtime_capacity berarti dua shift" konsisten dengan
 * kapasitas yang benar-benar tersedia — tidak ada celah di antara keduanya.
 *
 * Penanda `is_overtime` pada baris listing TIDAK dipakai untuk keputusan apa pun.
 * Ia ditetapkan PPC mendekati hari produksi, sehingga pada tanggal ke depan mayoritas
 * masih bernilai 0 yang berarti "belum ditetapkan", bukan "tidak lembur". Memakainya
 * membuat hasil generate berubah-ubah tergantung kapan dijalankan.
 *
 * Aturan lengkapnya didokumentasikan di config/sirep.php bagian "Kapasitas & jumlah shift".
 */
class ShiftCapacityCalculator
{
    /** Batas keras jumlah shift. Tidak ada shift 3 di lapangan. */
    public const MAX_SHIFT = 2;

    /**
     * Bagi kapasitas satu shift menjadi CO1-CO4.
     *
     * Sisa pembagian bulat masuk ke CO1, sehingga cutoff pertama yang menampung
     * paling besar. Contoh kapasitas 135: 36/33/33/33.
     *
     * @return array{c1:int,c2:int,c3:int,c4:int,total:int}
     */
    public function calculateCutoffDistribution(int $shiftCapacity): array
    {
        $bagi = (int) floor($shiftCapacity / 4);

        return [
            'c1'    => $shiftCapacity - (3 * $bagi),
            'c2'    => $bagi,
            'c3'    => $bagi,
            'c4'    => $bagi,
            'total' => $shiftCapacity,
        ];
    }

    /**
     * Ambang yang benar-benar dipakai: `overtime_capacity` dari SIREP bila ada,
     * kalau tidak jatuh ke `normal_capacity`.
     *
     * Artinya conveyor yang belum punya angka lembur diperlakukan sebagai TIDAK
     * punya jatah lembur — asumsi paling jujur untuk data yang belum ada. Ia tetap
     * dapat dijadwalkan, dan hari yang melampaui kapasitas normal pecah menjadi dua
     * shift alih-alih menumpuk di CO5.
     *
     * Alternatif "paksa satu shift" sengaja tidak dipakai: pada data nyata itu
     * menghasilkan CO5 sampai 14x cutoff normal (B1-J42U, qty 720 pada kapasitas
     * 160) — jadwal yang mustahil dikerjakan namun tampak wajar di layar.
     */
    public function effectiveOvertimeCapacity(?int $normalCapacity, ?int $overtimeCapacity): int
    {
        $ot = (int) ($overtimeCapacity ?? 0);

        return $ot > 0 ? $ot : max(0, (int) ($normalCapacity ?? 0));
    }

    /** Apakah ambang yang dipakai berasal dari cadangan, bukan dari SIREP. */
    public function overtimeCapacityIsFallback(?int $overtimeCapacity): bool
    {
        return (int) ($overtimeCapacity ?? 0) <= 0;
    }

    /**
     * Jumlah shift yang berjalan.
     *
     *   qty > ambang  ->  2 shift
     *   selain itu    ->  1 shift
     *
     * Ambang di sini sudah efektif — pemanggil melewatkannya lewat
     * effectiveOvertimeCapacity(). Conveyor tanpa `normal_capacity` sama sekali
     * tetap dilewati oleh pemanggil sebelum sampai ke sini.
     */
    public function resolveShiftCount(?int $overtimeCapacity, int $totalQty): int
    {
        $ambang = (int) ($overtimeCapacity ?? 0);

        if ($ambang <= 0) {
            return 1;
        }

        return $totalQty > $ambang ? self::MAX_SHIFT : 1;
    }

    /**
     * Jatah CO5 satu shift = selisih kapasitas lembur dan normal.
     *
     * Diambil langsung dari data SIREP, bukan rumus, dan sama untuk setiap shift:
     * satu shift lembur menampung tepat `overtime_capacity`. Contoh B3-EGI:
     * 160 - 136 = 24, B3-ENG: 96 - 78 = 18. Bila ambangnya cadangan (sama dengan
     * normal_capacity), hasilnya 0 — conveyor itu memang dianggap belum punya
     * jatah lembur.
     *
     * Aturan lama "CO5 shift pertama = 7/8 cutoff normal" sudah tidak dipakai:
     * shift lembur kini diisi penuh sampai overtime_capacity (lihat preMapCutoff5).
     */
    public function cutoff5Nominal(int $normalCapacity, ?int $overtimeCapacity): int
    {
        return max(0, (int) ($overtimeCapacity ?? 0) - $normalCapacity);
    }

    /**
     * Apakah hari itu lembur: demand PENUH tidak muat di kapasitas normal seluruh
     * shift yang berjalan.
     *
     *   1 shift : qty > normal          (B3-ENG: 79-96)
     *   2 shift : qty > 2 x normal      (B3-ENG: di atas 156)
     *
     * Diturunkan dari qty, bukan dari penanda is_overtime SIREP — penanda itu baru
     * ditetapkan PPC mendekati hari produksi (lihat docblock kelas), sehingga hasil
     * generate untuk tanggal ke depan akan berubah tergantung kapan dijalankan.
     */
    public function isOvertimeDay(int $normalCapacity, int $shiftCount, int $fullDemand): bool
    {
        return $fullDemand > max(1, $shiftCount) * $normalCapacity;
    }

    /**
     * Kapasitas nominal satu hari — ambang tunggal untuk menandai hari yang
     * demand-nya melampaui rencana.
     *
     *   nominal = jumlah shift x ( normal_capacity + CO5 nominal )
     *           = jumlah shift x overtime_capacity
     *
     * SETIAP shift dihitung punya jatah CO5 yang sama. "Penampung" pada CO5 shift
     * terakhir adalah cara alokasi menaruh kelebihan, bukan pernyataan bahwa shift
     * terakhir tidak punya jatah lembur — jadi tandanya menyala tepat ketika CO5
     * shift terakhir melewati jatah nominalnya, yaitu saat penampung benar-benar
     * dipakai di luar rencana.
     *
     * @param  bool  $overtimeDeclared  Penanda lembur SIREP untuk hari itu. Bila
     *         lembur belum dinyatakan, CO5 dianggap tidak tersedia sehingga
     *         ambangnya hanya shift x kapasitas normal — memakai ambang ber-CO5
     *         akan menyembunyikan hari yang sebenarnya over.
     */
    public function nominalDayCapacity(
        int $normalCapacity,
        ?int $overtimeCapacity,
        int $shiftCount,
        bool $overtimeDeclared = true
    ): int {
        $shiftCount = max(1, $shiftCount);

        if (!$overtimeDeclared) {
            return $shiftCount * $normalCapacity;
        }

        return $shiftCount * (
            $normalCapacity + $this->cutoff5Nominal($normalCapacity, $overtimeCapacity)
        );
    }

    /**
     * Shift yang berjalan hari itu, urut pengisian.
     *
     *   2 shift : [1, 2] — selalu mulai dari shift 1, shift_start tidak dipakai
     *   1 shift : [shift_start] dari master conveyor
     *
     * Nilai di luar 1..MAX_SHIFT diperlakukan sebagai 1, sama dengan bawaan kolomnya.
     *
     * @return array<int, int>
     */
    public function runningShifts(int $shiftCount, int $shiftStart = 1): array
    {
        if ($shiftCount >= self::MAX_SHIFT) {
            return range(1, self::MAX_SHIFT);
        }

        return [($shiftStart >= 1 && $shiftStart <= self::MAX_SHIFT) ? $shiftStart : 1];
    }

    /**
     * Susun kapasitas CO1-CO4 untuk setiap shift yang berjalan (lihat runningShifts()).
     * Shift yang terkunci (sudah diverifikasi) dinolkan agar tidak ditimpa.
     *
     * @param  array  $lockStatus  [1 => bool, 2 => bool]
     * @param  int    $shiftStart  master_conveyor.shift_start — hanya berlaku pada hari satu shift
     * @return array<int, array{c1:int,c2:int,c3:int,c4:int,total:int,locked:bool}>
     */
    public function calculateShiftCapacities(int $shiftCapacity, array $lockStatus, int $maxShifts, int $shiftStart = 1): array
    {
        $capacities = [];

        foreach ($this->runningShifts($maxShifts, $shiftStart) as $shift) {
            if ($lockStatus[$shift] ?? false) {
                $capacities[$shift] = [
                    'c1' => 0, 'c2' => 0, 'c3' => 0, 'c4' => 0,
                    'total' => 0, 'locked' => true,
                ];
                continue;
            }

            $capacities[$shift] = $this->calculateCutoffDistribution($shiftCapacity) + ['locked' => false];
        }

        return $capacities;
    }

    public function getTotalCapacity(array $shiftCapacity): int
    {
        return $shiftCapacity['total'] ?? 0;
    }

    /**
     * Tentukan jatah CO5 tiap shift. Shift diisi BERURUTAN — CO1 sampai CO5 shift 1
     * dulu, baru shift 2 — sehingga CO5 sebuah shift terisi sebelum CO1 shift berikutnya.
     *
     *   Hari lembur : shift selain yang terakhir mendapat CO5 = overtime - normal,
     *                 jadi shift 1 penuh sampai overtime_capacity sebelum shift 2 diisi.
     *   Hari normal : shift selain yang terakhir tidak membuka CO5 — demand muat di
     *                 CO1-CO4 seluruh shift.
     *   Shift terakhir yang tidak terkunci: CO5 = seluruh sisa (penampung).
     *
     * Contoh B3-ENG (normal 78, overtime 96):
     *   qty 176 (lembur) -> S1 78 + CO5 18 = 96 · S2 78 + CO5 2 = 80
     *   qty 140 (normal) -> S1 78 · S2 62
     *
     * CO5 shift terakhir selalu penampung tanpa batas: membuang baris listing jauh
     * lebih berbahaya daripada mencetak satu cutoff di luar rencana. Layar
     * verifikasi menandai hari yang melampaui nominal sebagai over.
     *
     * @param  array  $shiftCapacities  Diubah di tempat; setiap shift mendapat kunci 'c5'
     * @param  int    $remaining        Qty yang belum dipegang jadwal mana pun
     * @param  bool   $overtimeDay      isOvertimeDay() atas demand PENUH hari itu
     * @return array<int, bool>         Shift mana saja yang memakai CO5
     */
    public function preMapCutoff5(
        array &$shiftCapacities,
        int $normalCapacity,
        ?int $overtimeCapacity,
        int $remaining,
        bool $overtimeDay
    ): array {
        $co5Nominal = $overtimeDay ? $this->cutoff5Nominal($normalCapacity, $overtimeCapacity) : 0;
        $co5Needed  = [];
        $unlocked   = [];

        foreach ($shiftCapacities as $shift => $caps) {
            $shiftCapacities[$shift]['c5'] = 0;
            $co5Needed[$shift] = false;

            if (!($caps['locked'] ?? false)) {
                $unlocked[] = $shift;
            }
        }

        if (empty($unlocked)) {
            return $co5Needed;
        }

        $lastShift = end($unlocked);
        $rem       = max(0, $remaining);

        foreach ($unlocked as $shift) {
            // Sisa sesudah CO1-CO4 shift ini terisi.
            $rem   = max(0, $rem - $shiftCapacities[$shift]['total']);
            $alloc = ($shift === $lastShift) ? $rem : min($rem, $co5Nominal);

            if ($alloc > 0) {
                $shiftCapacities[$shift]['c5'] = $alloc;
                $shiftCapacities[$shift]['total'] += $alloc;
                $co5Needed[$shift] = true;
                $rem -= $alloc;
            }
        }

        return $co5Needed;
    }
}
