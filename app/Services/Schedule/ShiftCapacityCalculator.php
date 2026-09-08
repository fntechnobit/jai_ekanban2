<?php

namespace App\Services\Schedule;

/**
 * Pembagian kapasitas conveyor menjadi shift dan cutoff.
 *
 * Kapasitas berasal dari SIREP (`normal_capacity`); jumlah shift adalah DATA MASTER
 * (`master_conveyor.shift_qty`), bukan hasil hitungan. Penanda `is_overtime` dari
 * SIREP hanya menentukan boleh atau tidaknya CO5 dibuka.
 *
 * Aturan lengkapnya didokumentasikan di config/sirep.php bagian `capacity`.
 */
class ShiftCapacityCalculator
{
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
     * Nominal CO5 = 7/8 (87,5%) kapasitas CO normal.
     *
     * Batas ini berlaku untuk shift yang BUKAN shift terakhir. CO5 shift terakhir
     * adalah penampung dan boleh melampauinya.
     */
    public function calculateCutoff5Capacity(int $shiftCapacity): int
    {
        $rasio = (float) config('sirep.capacity.co5_ratio', 7 / 8);
        $raw   = $rasio * ($shiftCapacity / 4);

        return (int) (config('sirep.capacity.co5_rounding', 'round') === 'floor'
            ? floor($raw)
            : round($raw));
    }

    /**
     * Ambang kapasitas satu shift bila lembur dibuka. Dipakai layar verifikasi
     * untuk menandai hari yang demand-nya melampaui rencana.
     */
    public function calculateOvertimeCapacity(int $shiftCapacity): int
    {
        return $shiftCapacity + $this->calculateCutoff5Capacity($shiftCapacity);
    }

    /** Batas keras jumlah shift. Tidak ada shift 3 di lapangan. */
    public const MAX_SHIFT = 2;

    /**
     * Jumlah shift yang berjalan — DIBACA dari master, tidak dihitung dari volume.
     *
     * Dijepit ke 1..MAX_SHIFT. Batasnya konstanta, bukan config, supaya salah setel
     * env tidak dapat menghasilkan shift 3 yang tidak ada padanannya di lapangan.
     *
     * @param  int|null  $shiftQtyMaster  master_conveyor.shift_qty
     */
    public function resolveShiftCount(?int $shiftQtyMaster): int
    {
        $maks = min(self::MAX_SHIFT, max(1, (int) config('sirep.capacity.max_shift', self::MAX_SHIFT)));

        return max(1, min((int) ($shiftQtyMaster ?? 1), $maks));
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
     * Tentukan jatah CO5 tiap shift, sesudah CO1-4 SELURUH shift diperhitungkan.
     *
     * CO5 hanya dibuka bila SIREP menyatakan hari itu lembur. Tanpa lembur, satu
     * shift berhenti di kapasitas normal dan kelebihannya mengalir ke CO1 shift
     * berikutnya — bukan ke CO5.
     *
     * Batasnya:
     *   shift bukan terakhir : CO5 <= nominal (7/8 CO normal)
     *   shift terakhir       : CO5 = seluruh sisa, tanpa batas
     *
     * Bila hari itu TIDAK lembur tetapi listing tetap tidak muat di seluruh shift,
     * CO5 shift terakhir tetap dipakai sebagai penampung: membuang baris listing
     * jauh lebih berbahaya daripada mencetak satu cutoff di luar rencana. Layar
     * verifikasi menandai hari seperti itu sebagai over tanpa OT.
     *
     * @param  array  $shiftCapacities  Diubah di tempat; setiap shift mendapat kunci 'c5'
     * @return array<int, bool>         Shift mana saja yang memakai CO5
     */
    public function preMapCutoff5(array &$shiftCapacities, int $shiftCapacity, int $totalQty, bool $isOvertime): array
    {
        $co5Nominal = $this->calculateCutoff5Capacity($shiftCapacity);
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

        if (!$isOvertime) {
            // CO5 tertutup. Sisa hanya mungkin ada bila seluruh shift sudah penuh —
            // tampung di shift terakhir supaya tidak ada listing yang hilang.
            $shiftCapacities[$lastShift]['c5'] = $rem;
            $shiftCapacities[$lastShift]['total'] += $rem;
            $co5Needed[$lastShift] = true;

            return $co5Needed;
        }

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
