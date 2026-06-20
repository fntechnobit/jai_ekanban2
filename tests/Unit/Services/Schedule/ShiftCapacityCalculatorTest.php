<?php

namespace Tests\Unit\Services\Schedule;

use App\Models\MasterConveyor;
use App\Services\Schedule\ShiftCapacityCalculator;
use PHPUnit\Framework\TestCase;

class ShiftCapacityCalculatorTest extends TestCase
{
    private ShiftCapacityCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ShiftCapacityCalculator();
    }

    /** Build a MasterConveyor instance (no DB) for capacity math. */
    private function makeConveyor(int $shiftQty, int $capacity): MasterConveyor
    {
        return new MasterConveyor([
            'shift_start' => 1,
            'shift_qty'   => $shiftQty,
            'capacity'    => $capacity,
        ]);
    }

    // ───────────────────────── calculateCutoffDistribution ─────────────────────────

    public function test_calculate_cutoff_distribution_basic()
    {
        $distribution = $this->calculator->calculateCutoffDistribution(100);

        $this->assertEquals(25, $distribution['c1']);
        $this->assertEquals(25, $distribution['c2']);
        $this->assertEquals(25, $distribution['c3']);
        $this->assertEquals(25, $distribution['c4']);
    }

    public function test_calculate_cutoff_distribution_with_remainder()
    {
        $distribution = $this->calculator->calculateCutoffDistribution(101);

        $this->assertEquals(25, $distribution['c1']);
        $this->assertEquals(25, $distribution['c2']);
        $this->assertEquals(25, $distribution['c3']);
        $this->assertEquals(26, $distribution['c4'], 'C4 should get remainder (26)');
    }

    public function test_calculate_cutoff_distribution_with_large_remainder()
    {
        $distribution = $this->calculator->calculateCutoffDistribution(103);

        $this->assertEquals(25, $distribution['c1']);
        $this->assertEquals(28, $distribution['c4'], 'C4 should get remainder (28)');
    }

    public function test_calculate_cutoff_distribution_small_capacity()
    {
        $distribution = $this->calculator->calculateCutoffDistribution(10);

        $this->assertEquals(2, $distribution['c1']);
        $this->assertEquals(4, $distribution['c4'], 'C4 should get remainder (4)');
    }

    // ───────────────────────── calculateShiftCapacities ─────────────────────────

    public function test_calculate_shift_capacities_single_shift()
    {
        $capacities = $this->calculator->calculateShiftCapacities($this->makeConveyor(1, 100), []);

        $this->assertArrayHasKey(1, $capacities);
        $this->assertArrayNotHasKey(2, $capacities, 'Single-shift conveyor must not produce shift 2');
        $this->assertEquals(25, $capacities[1]['c1']);
        $this->assertEquals(25, $capacities[1]['c4']);
        $this->assertFalse($capacities[1]['locked']);
    }

    public function test_calculate_shift_capacities_two_shifts()
    {
        $capacities = $this->calculator->calculateShiftCapacities($this->makeConveyor(2, 100), []);

        $this->assertCount(2, $capacities);
        $this->assertArrayHasKey(1, $capacities);
        $this->assertArrayHasKey(2, $capacities);
        $this->assertEquals($capacities[1], $capacities[2], 'Both shifts share the same CO1-4 distribution');
    }

    public function test_calculate_shift_capacities_custom_capacity()
    {
        $capacities = $this->calculator->calculateShiftCapacities($this->makeConveyor(2, 80), []);

        $this->assertEquals(20, $capacities[1]['c1']);
        $this->assertEquals(20, $capacities[1]['c4']);
    }

    public function test_locked_shift_gets_zero_capacity()
    {
        // Shift 2 locked → its capacity is zeroed out
        $capacities = $this->calculator->calculateShiftCapacities($this->makeConveyor(2, 100), [2 => true]);

        $this->assertEquals(0, $capacities[2]['total']);
        $this->assertTrue($capacities[2]['locked']);
        $this->assertEquals(100, $capacities[1]['total']);
    }

    // ───────────────────────── CO5 nominal capacity ─────────────────────────

    public function test_co5_nominal_capacity_is_round_of_0875()
    {
        // 0.875 × (100/4) = 21.875 → round = 22 (matches spec case A/D/E)
        $this->assertEquals(22, $this->calculator->calculateCutoff5Capacity(100));
    }

    // ───────────────── preMapCutoff5 — business cases A–F (cap 100) ─────────────────
    // CO5 nominal = round(0.875×25) = 22. Earlier shift CO5 capped at 22; LAST shift
    // CO5 is a catch-all (all remaining, may exceed 22).

    /** Case A: 1 shift, listing 150 → CO5 = all remaining = 50 (catch-all). */
    public function test_case_A_single_shift_catch_all()
    {
        $caps = $this->calculator->calculateShiftCapacities($this->makeConveyor(1, 100), []);
        $this->calculator->preMapCutoff5($caps, 100, 150, 1);

        $this->assertEquals(50, $caps[1]['c5']);
    }

    /** Case B: 1 shift, listing 100 → no CO5. */
    public function test_case_B_single_shift_exact_fit()
    {
        $caps = $this->calculator->calculateShiftCapacities($this->makeConveyor(1, 100), []);
        $this->calculator->preMapCutoff5($caps, 100, 100, 1);

        $this->assertEquals(0, $caps[1]['c5']);
    }

    /** Case C: 2 shift, listing 200 → exact fit in CO1-4, no CO5. */
    public function test_case_C_two_shift_exact_fit()
    {
        $caps = $this->calculator->calculateShiftCapacities($this->makeConveyor(2, 100), []);
        $this->calculator->preMapCutoff5($caps, 100, 200, 2);

        $this->assertEquals(0, $caps[1]['c5']);
        $this->assertEquals(0, $caps[2]['c5']);
    }

    /** Case D: 2 shift, listing 220 → S1.CO5 = 20 (≤22), S2.CO5 = 0. */
    public function test_case_D_two_shift_small_overflow_on_s1()
    {
        $caps = $this->calculator->calculateShiftCapacities($this->makeConveyor(2, 100), []);
        $this->calculator->preMapCutoff5($caps, 100, 220, 2);

        $this->assertEquals(20, $caps[1]['c5'], 'S1.CO5 takes the 20 overflow (under its 22 cap)');
        $this->assertEquals(0, $caps[2]['c5']);
    }

    /** Case E: 2 shift, listing 250 → S1.CO5 = 22 (capped), S2.CO5 = 28 (catch-all, over). */
    public function test_case_E_two_shift_s1_capped_s2_catch_all()
    {
        $caps = $this->calculator->calculateShiftCapacities($this->makeConveyor(2, 100), []);
        $this->calculator->preMapCutoff5($caps, 100, 250, 2);

        $this->assertEquals(22, $caps[1]['c5'], 'S1.CO5 capped at nominal 22');
        $this->assertEquals(28, $caps[2]['c5'], 'S2.CO5 catch-all takes the remaining 28 (over 22)');
    }

    /** Case F: 2 shift, listing 180 → fits within CO1-4, no CO5. */
    public function test_case_F_two_shift_partial_co4()
    {
        $caps = $this->calculator->calculateShiftCapacities($this->makeConveyor(2, 100), []);
        $this->calculator->preMapCutoff5($caps, 100, 180, 2);

        $this->assertEquals(0, $caps[1]['c5']);
        $this->assertEquals(0, $caps[2]['c5']);
    }

    /** Locked last shift → catch-all falls back to the last UNLOCKED shift (shift 1). */
    public function test_premap_locked_last_shift_falls_back_to_shift1_catch_all()
    {
        $caps = $this->calculator->calculateShiftCapacities($this->makeConveyor(2, 100), [2 => true]);
        // Only shift 1 base (100); total 140 → 40 remaining all goes to shift 1 CO5 (catch-all)
        $this->calculator->preMapCutoff5($caps, 100, 140, 2);

        $this->assertEquals(40, $caps[1]['c5']);
        $this->assertEquals(0, $caps[2]['c5']);
    }
}
