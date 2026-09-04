<?php

namespace App\Services\Schedule;

/**
 * Pembagian kapasitas conveyor menjadi shift dan cutoff.
 *
 * Seluruh angka berasal dari SIREP: `capacity` adalah `normal_capacity` hasil
 * `sirep:sync-conveyor`, dan `isOvertime` adalah flag `is_overtime` pada baris
 * listing. Master conveyor tidak lagi menyimpan kapasitas maupun jumlah shift.
 *
 * Aturan lengkapnya didokumentasikan di config/sirep.php bagian `capacity`.
 */
class ShiftCapacityCalculator
{
    /**
     * Bagi kapasitas satu shift menjadi CO1-CO4.
     * Sisa pembagian bulat masuk ke CO4.
     *
     * @return array{c1:int,c2:int,c3:int,c4:int,total:int}
     */
    public function calculateCutoffDistribution(int $shiftCapacity): array
    {
        $c1 = (int) floor($shiftCapacity / 4);
        $c2 = $c1;
        $c3 = $c1;
        $c4 = $shiftCapacity - ($c1 + $c2 + $c3);

        return [
            'c1'    => $c1,
            'c2'    => $c2,
            'c3'    => $c3,
            'c4'    => $c4,
            'total' => $shiftCapacity,
        ];
    }

    /**
     * Nominal CO5 = rasio × kapasitas CO normal (kapasitas/4).
     *
     * Aturan PPC: kapasitas CO5 maksimum 7/8 (87,5%) dari CO normal.
     * Ini batas CO5 untuk shift yang BUKAN shift terakhir; CO5 shift terakhir
     * adalah catch-all dan boleh melampauinya.
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
     * Kapasitas efektif satu shift — inilah yang menentukan kapan shift berikutnya dibuka.
     *
     * CO5 hanya ikut dihitung bila PPC menyatakan hari itu lembur; tanpa lembur
     * satu shift berhenti tepat di kapasitas normal dan kelebihannya pindah shift.
     * Ini SEMATA menentukan jumlah shift — pengisian CO5 sendiri tidak lagi
     * bergantung flag lembur, lihat preMapCutoff5().
     */
    public function effectiveShiftCapacity(int $shiftCapacity, bool $isOvertime): int
    {
        return $shiftCapacity + ($isOvertime ? $this->calculateCutoff5Capacity($shiftCapacity) : 0);
    }

    /**
     * Ambang "over capacity" satu hari, dipakai layar verifikasi untuk menandai
     * hari yang demand-nya melampaui seluruh shift yang berjalan.
     */
    public function calculateOvertimeCapacity(int $shiftCapacity): int
    {
        return $this->effectiveShiftCapacity($shiftCapacity, true);
    }

    /**
     * Jumlah shift yang berjalan pada satu (tanggal × conveyor).
     *
     *   jumlah shift = ceil(qty listing / kapasitas efektif satu shift)
     *
     * dibatasi `sirep.capacity.max_shift`. Bila demand melampaui batas itu,
     * kelebihannya diserap CO5 shift terakhir sebagai catch-all sehingga 100%
     * listing tetap terjadwal — tidak ada yang terbuang diam-diam.
     *
     * @param  int   $shiftCapacity  normal_capacity dari SIREP
     * @param  int   $totalQty       Total qty listing untuk tanggal × conveyor tersebut
     * @param  bool  $isOvertime     Flag is_overtime dari baris listing SIREP
     */
    public function resolveShiftCount(int $shiftCapacity, int $totalQty, bool $isOvertime): int
    {
        $maxShift = max(1, (int) config('sirep.capacity.max_shift', 2));

        if ($shiftCapacity <= 0) {
            return 1;
        }

        $perShift = $this->effectiveShiftCapacity($shiftCapacity, $isOvertime);
        $needed   = (int) ceil($totalQty / max(1, $perShift));

        return max(1, min($needed, $maxShift));
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
     * Tentukan jatah CO5 tiap shift, sesudah CO1-4 seluruh shift diperhitungkan.
     *
     * Pengisiannya sama baik PPC menyatakan lembur maupun tidak. Bila listing tidak
     * muat di CO1-4 seluruh shift yang berjalan, CO5 dibuka sebagai LEMBUR IMPLISIT —
     * membuang baris listing jauh lebih berbahaya daripada mencetak cutoff yang belum
     * dinyatakan PPC. Hari seperti itu tetap ditandai "over tanpa OT" di layar
     * verifikasi supaya diperiksa manual.
     *
     * Batasnya:
     *   shift bukan terakhir : CO5 <= nominal (87,5% CO normal)
     *   shift terakhir       : CO5 = seluruh sisa (catch-all, boleh melampaui nominal)
     *
     * @param  array  $shiftCapacities  Diubah di tempat; setiap shift mendapat kunci 'c5'
     * @return array<int, bool>         Shift mana saja yang memakai CO5
     */
    public function preMapCutoff5(array &$shiftCapacities, int $shiftCapacity, int $totalQty): array
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

        // Shift awal dibatasi nominal; sisanya dibebankan ke CO5 shift terakhir.
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
