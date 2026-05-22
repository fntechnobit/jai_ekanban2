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
     * Calculate cutoff 5 capacity using SP formula
     * 
     * @param int $shiftCapacity Capacity per shift from conveyor
     * @return int CO5 capacity
     */
    public function calculateCutoff5Capacity(int $shiftCapacity): int
    {
        return (int) floor(0.875 * ($shiftCapacity / 4));
    }

    /**
     * Pre-map CO5 need for each shift based on total qty vs shift capacities.
     *
     * Allocation order:
     *   2-shift: S1 CO1-4 → S2 CO1-4 → S1 CO5 → S2 CO5
     *   1-shift: CO1-4 → CO5 (sequential, single shift)
     *
     * CO5 is only activated when totalQty exceeds the combined CO1-4 capacity
     * of all shifts. CO5 capacity = floor(0.875 × shiftCapacity/4).
     *
     * @param array &$shiftCapacities Shift capacities array (modified in place)
     * @param int $shiftCapacity Raw capacity per shift from conveyor
     * @param int $totalQty Total quantity to allocate across all shifts
     * @param int $maxShifts Number of active shifts (1 or 2)
     * @return array CO5 needed status per shift [1 => bool, 2 => bool]
     */
    public function preMapCutoff5(array &$shiftCapacities, int $shiftCapacity, int $totalQty, int $maxShifts = 2): array
    {
        $co5Cap = $this->calculateCutoff5Capacity($shiftCapacity);
        $co5Needed = [];

        // Initialize c5 = 0 for all shifts
        foreach ($shiftCapacities as $shift => $caps) {
            $shiftCapacities[$shift]['c5'] = 0;
            $co5Needed[$shift] = false;
        }

        if ($maxShifts >= 2) {
            // 2-shift: simulate Phase 1 (S1 CO1-4 + S2 CO1-4), then assign CO5 to shifts with overflow
            $totalCo14 = 0;
            foreach ($shiftCapacities as $caps) {
                if (!($caps['locked'] ?? false)) $totalCo14 += $caps['total'];
            }
            $rem = max(0, $totalQty - $totalCo14);
            $co5S2Cap = (int) floor($shiftCapacity / 4); // S2 CO5: full capacity/4, no 0.875x penalty

            // S1.CO5: capped at 0.875x CO1-4. S2.CO5: capped at floor(capacity/4)
            $isFirstCo5 = true;
            foreach ($shiftCapacities as $shift => $caps) {
                if ($rem <= 0) break;
                if ($caps['locked'] ?? false) continue;

                $co5Needed[$shift] = true;
                if ($isFirstCo5) {
                    // S1.CO5: dibatasi 0.875x (capped)
                    $shiftCapacities[$shift]['c5'] = $co5Cap;
                    $shiftCapacities[$shift]['total'] += $co5Cap;
                    $rem = max(0, $rem - $co5Cap);
                    $isFirstCo5 = false;
                } else {
                    // S2.CO5: dibatasi floor(capacity/4), bukan semua sisa
                    $alloc = min($rem, $co5S2Cap);
                    $shiftCapacities[$shift]['c5'] = $alloc;
                    $shiftCapacities[$shift]['total'] += $alloc;
                    $rem = 0;
                }
            }
        } else {
            // 1-shift: CO5 dibatasi floor(capacity/4), bukan semua sisa
            $co5S2Cap = (int) floor($shiftCapacity / 4);
            foreach ($shiftCapacities as $shift => $caps) {
                if ($caps['locked'] ?? false) continue;
                $co5Amount = min(max(0, $totalQty - $caps['total']), $co5S2Cap);
                if ($co5Amount > 0) {
                    $co5Needed[$shift] = true;
                    $shiftCapacities[$shift]['c5'] = $co5Amount;
                    $shiftCapacities[$shift]['total'] += $co5Amount;
                }
                break; // Only one active shift
            }
        }

        return $co5Needed;
    }
}
