<?php

namespace App\Services\Schedule;

/**
 * Pembagian kapasitas conveyor menjadi shift dan cutoff.
 *
 * Seluruh angka berasal dari API SIREP. Master conveyor tidak lagi menyimpan
 * kapasitas maupun jumlah shift:
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
 * Aturan lengkapnya didokumentasikan di config/sirep.php bagian `capacity`.
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
     * Jatah CO5 pada hari SATU shift = selisih kapasitas lembur dan normal.
     *
     * Diambil langsung dari data SIREP, bukan rumus. Contoh B3-EGI: 160 - 136 = 24.
     * Bila ambangnya cadangan (sama dengan normal_capacity), hasilnya 0 — conveyor
     * itu memang dianggap belum punya jatah lembur.
     */
    public function cutoff5ForSingleShift(int $normalCapacity, ?int $overtimeCapacity): int
    {
        return max(0, (int) ($overtimeCapacity ?? 0) - $normalCapacity);
    }

    /**
     * Jatah CO5 shift PERTAMA pada hari dua shift = 7/8 kapasitas CO normal.
     *
     * Berbeda dari hari satu shift: di sini CO5 bukan penampung terakhir, jadi
     * batasnya memakai rasio, sesuai aturan PPC. Contoh kapasitas 136:
     * 7/8 x 34 = 29,75 -> 30.
     */
    public function calculateCutoff5Capacity(int $normalCapacity): int
    {
        $rasio = (float) config('sirep.capacity.co5_ratio', 7 / 8);
        $raw   = $rasio * ($normalCapacity / 4);

        return (int) (config('sirep.capacity.co5_rounding', 'round') === 'floor'
            ? floor($raw)
            : round($raw));
    }

    /**
     * Nominal CO5 yang berlaku untuk sebuah hari, sesuai jumlah shiftnya.
     * Dipakai layar verifikasi menampilkan batas CO5 yang benar.
     */
    public function cutoff5Nominal(int $normalCapacity, ?int $overtimeCapacity, int $shiftCount): int
    {
        return $shiftCount >= 2
            ? $this->calculateCutoff5Capacity($normalCapacity)
            : $this->cutoff5ForSingleShift($normalCapacity, $overtimeCapacity);
    }

    /**
     * Kapasitas nominal satu hari — dipakai menandai hari yang demand-nya
     * melampaui rencana.
     *
     *   1 shift : overtime_capacity
     *   2 shift : 2 x normal + CO5 shift pertama (CO5 shift terakhir penampung,
     *             jadi tidak ikut dihitung sebagai batas)
     */
    public function nominalDayCapacity(int $normalCapacity, ?int $overtimeCapacity, int $shiftCount): int
    {
        if ($shiftCount >= 2) {
            return (2 * $normalCapacity) + $this->calculateCutoff5Capacity($normalCapacity);
        }

        return max($normalCapacity, (int) ($overtimeCapacity ?? 0));
    }

    /**
     * Susun kapasitas CO1-CO4 untuk setiap shift yang berjalan.
     * Shift yang terkunci (sudah diverifikasi) dinolkan agar tidak ditimpa.
     *
     * @param  array  $lockStatus  [1 => bool, 2 => bool]
     * @return array<int, array{c1:int,c2:int,c3:int,c4:int,total:int,locked:bool}>
     */
    public function calculateShiftCapacities(int $shiftCapacity, array $lockStatus, int $maxShifts): array
    {
        $capacities = [];

        for ($shift = 1; $shift <= $maxShifts; $shift++) {
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
     * Tentukan jatah CO5 tiap shift, sesudah CO1-CO4 SELURUH shift diperhitungkan.
     *
     *   1 shift  : CO5 = sisa (secara aturan tidak akan melampaui
     *              overtime_capacity - normal_capacity, karena di atas itu
     *              harinya sudah dipecah jadi dua shift)
     *   2 shift  : CO5 shift pertama dibatasi 7/8 CO normal,
     *              CO5 shift terakhir menampung seluruh sisa
     *
     * CO5 shift terakhir selalu penampung tanpa batas: membuang baris listing jauh
     * lebih berbahaya daripada mencetak satu cutoff di luar rencana. Layar
     * verifikasi menandai hari yang melampaui nominal sebagai over.
     *
     * @param  array  $shiftCapacities  Diubah di tempat; setiap shift mendapat kunci 'c5'
     * @return array<int, bool>         Shift mana saja yang memakai CO5
     */
    public function preMapCutoff5(array &$shiftCapacities, int $normalCapacity, int $totalQty): array
    {
        $co5Nominal = $this->calculateCutoff5Capacity($normalCapacity);
        $co5Needed  = [];

        $totalCo14 = 0;
        $unlocked  = [];

        foreach ($shiftCapacities as $shift => $caps) {
            $shiftCapacities[$shift]['c5'] = 0;
            $co5Needed[$shift] = false;

            if ($caps['locked'] ?? false) {
                continue;
            }

            $totalCo14 += $caps['total'];
            $unlocked[] = $shift;
        }

        $rem = max(0, $totalQty - $totalCo14);

        if ($rem <= 0 || empty($unlocked)) {
            return $co5Needed;
        }

        $lastShift = end($unlocked);

        foreach ($unlocked as $shift) {
            if ($rem <= 0) {
                break;
            }

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
