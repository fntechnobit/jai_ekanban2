<?php

namespace App\Services\Schedule;

use App\Models\MasterConveyor;

class ShiftCapacityCalculator
{
    /**
     * Calculate cutoff distribution for a single shift
     * Divides shift capacity into 4 equal parts (cutoffs 1-4)
     * Cutoff 4 gets any remainder from integer division
     * 
     * @param int $shiftCapacity Total capacity for the shift
     * @return array ['c1' => int, 'c2' => int, 'c3' => int, 'c4' => int, 'total' => int]
     */
    public function calculateCutoffDistribution(int $shiftCapacity): array
    {
        $c1 = (int) floor($shiftCapacity / 4);
        $c2 = (int) floor($shiftCapacity / 4);
        $c3 = (int) floor($shiftCapacity / 4);
        $c4 = $shiftCapacity - ($c1 + $c2 + $c3); // Remainder goes to c4
        
        return [
            'c1' => $c1,
            'c2' => $c2,
            'c3' => $c3,
            'c4' => $c4,
            'total' => $shiftCapacity
        ];
    }
    
    /**
     * Calculate capacities for all shifts based on conveyor config and lock status
     * If shift is locked (is_lock = 1), set all capacities to 0
     * 
     * @param MasterConveyor $conveyor The conveyor configuration
     * @param array $lockStatus Lock status for each shift [1 => bool, 2 => bool]
     * @return array Shift capacities indexed by shift number
     */
    public function calculateShiftCapacities(MasterConveyor $conveyor, array $lockStatus): array
    {
        $maxShifts = $conveyor->shift_qty ?? 2;
        $shiftCapacity = $conveyor->capacity ?? 100;
        $capacities = [];
        
        for ($shift = 1; $shift <= $maxShifts; $shift++) {
            // If shift is locked, set capacity to 0 (skip processing)
            if ($lockStatus[$shift] ?? false) {
                $capacities[$shift] = [
                    'c1' => 0,
                    'c2' => 0,
                    'c3' => 0,
                    'c4' => 0,
                    'total' => 0,
                    'locked' => true
                ];
            } else {
                $capacities[$shift] = array_merge(
                    $this->calculateCutoffDistribution($shiftCapacity),
                    ['locked' => false]
                );
            }
        }
        
        return $capacities;
    }
    
    /**
     * Get total capacity for a shift (sum of all cutoffs)
     * 
     * @param array $shiftCapacity Cutoff distribution for a shift
     * @return int Total capacity
     */
    public function getTotalCapacity(array $shiftCapacity): int
    {
        return $shiftCapacity['total'] ?? 0;
    }

    /**
     * Nominal CO5 capacity shown in the verification form.
     * Formula: round(0.875 × capacity/4). E.g. capacity 100 → round(21.875) = 22.
     * This is a DISPLAY/cap reference; the LAST shift's CO5 is a catch-all and may
     * exceed it (shown as "over" in the form).
     *
     * @param int $shiftCapacity Capacity per shift from conveyor
     * @return int Nominal CO5 capacity
     */
    public function calculateCutoff5Capacity(int $shiftCapacity): int
    {
        return (int) round(0.875 * ($shiftCapacity / 4));
    }

    /**
     * Pre-map CO5 budget per shift.
     *
     * Capacities: CO1-4 = floor(cap/4) each (CO4 gets remainder). CO5 nominal =
     * round(0.875 × cap/4), same for every shift.
     *
     * Fill order & caps:
     *   1-shift: CO1-4 (capped) → CO5 = ALL remaining (catch-all, may exceed nominal)
     *   2-shift: S1 CO1-4 → S2 CO1-4 → S1.CO5 (capped at nominal) → S2.CO5 = ALL
     *            remaining (catch-all). Earlier unlocked shift(s) get the capped CO5;
     *            the LAST unlocked shift's CO5 absorbs everything left.
     *
     * Because the last shift's CO5 is a catch-all, 100% of the listing is always
     * scheduled (nothing dropped).
     *
     * @param array &$shiftCapacities Shift capacities array (modified in place)
     * @param int $shiftCapacity Raw capacity per shift from conveyor
     * @param int $totalQty Total quantity to allocate across all shifts
     * @param int $maxShifts Number of active shifts (kept for signature compat)
     * @return array CO5 needed status per shift [1 => bool, 2 => bool]
     */
    public function preMapCutoff5(array &$shiftCapacities, int $shiftCapacity, int $totalQty, int $maxShifts = 2): array
    {
        $co5Nominal = $this->calculateCutoff5Capacity($shiftCapacity);
        $co5Needed = [];

        // Initialize c5 = 0 for all shifts
        foreach ($shiftCapacities as $shift => $caps) {
            $shiftCapacities[$shift]['c5'] = 0;
            $co5Needed[$shift] = false;
        }

        // Remaining qty after CO1-4 of all unlocked shifts
        $totalCo14 = 0;
        $unlocked  = [];
        foreach ($shiftCapacities as $shift => $caps) {
            if ($caps['locked'] ?? false) continue;
            $totalCo14 += $caps['total'];
            $unlocked[] = $shift;
        }

        $rem = max(0, $totalQty - $totalCo14);
        if ($rem <= 0 || empty($unlocked)) {
            return $co5Needed;
        }

        $lastShift = end($unlocked);

        // Earlier unlocked shifts: CO5 capped at nominal. Last unlocked shift: catch-all.
        foreach ($unlocked as $shift) {
            if ($rem <= 0) break;
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
