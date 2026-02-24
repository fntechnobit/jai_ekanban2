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
     * Pre-map CO5 need for each shift based on total qty vs shift capacities
     * Determines which shifts need CO5 and adds c5 capacity to the capacities array
     * 
     * Logic: Process shifts sequentially. If total remaining qty exceeds CO1-CO4
     * capacity for a shift, that shift needs CO5 with additional capacity.
     * 
     * @param array &$shiftCapacities Shift capacities array (modified in place)
     * @param int $shiftCapacity Raw capacity per shift from conveyor
     * @param int $totalQty Total quantity to allocate across all shifts
     * @return array CO5 needed status per shift [1 => bool, 2 => bool]
     */
    public function preMapCutoff5(array &$shiftCapacities, int $shiftCapacity, int $totalQty): array
    {
        $co5Cap = $this->calculateCutoff5Capacity($shiftCapacity);
        $co5Needed = [];
        $remainingQty = $totalQty;

        // Determine CO5 need per shift sequentially
        foreach ($shiftCapacities as $shift => $caps) {
            if ($caps['locked'] ?? false) {
                $co5Needed[$shift] = false;
                $shiftCapacities[$shift]['c5'] = 0;
                continue;
            }

            $co1to4Cap = $caps['total'];

            if ($remainingQty > $co1to4Cap) {
                // This shift needs CO5
                $co5Needed[$shift] = true;
                $shiftCapacities[$shift]['c5'] = $co5Cap;
                $shiftCapacities[$shift]['total'] += $co5Cap;
                $remainingQty -= ($co1to4Cap + $co5Cap);
            } else {
                $co5Needed[$shift] = false;
                $shiftCapacities[$shift]['c5'] = 0;
                $remainingQty -= $co1to4Cap;
            }

            if ($remainingQty <= 0) {
                break;
            }
        }

        // Ensure all shifts have c5 key
        foreach ($shiftCapacities as $shift => $caps) {
            if (!isset($caps['c5'])) {
                $shiftCapacities[$shift]['c5'] = 0;
            }
        }

        return $co5Needed;
    }
}
