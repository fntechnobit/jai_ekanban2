<?php

namespace Tests\Unit\Services\Schedule;

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

    /**
     * Test basic cutoff distribution calculation
     */
    public function test_calculate_cutoff_distribution_basic()
    {
        $distribution = $this->calculator->calculateCutoffDistribution(100);

        $this->assertEquals(25, $distribution['c1'], 'C1 should be 25');
        $this->assertEquals(25, $distribution['c2'], 'C2 should be 25');
        $this->assertEquals(25, $distribution['c3'], 'C3 should be 25');
        $this->assertEquals(25, $distribution['c4'], 'C4 should be 25');
    }

    /**
     * Test cutoff distribution with remainder
     */
    public function test_calculate_cutoff_distribution_with_remainder()
    {
        $distribution = $this->calculator->calculateCutoffDistribution(101);

        $this->assertEquals(25, $distribution['c1'], 'C1 should be 25');
        $this->assertEquals(25, $distribution['c2'], 'C2 should be 25');
        $this->assertEquals(25, $distribution['c3'], 'C3 should be 25');
        $this->assertEquals(26, $distribution['c4'], 'C4 should get remainder (26)');
    }

    /**
     * Test cutoff distribution with larger remainder
     */
    public function test_calculate_cutoff_distribution_with_large_remainder()
    {
        $distribution = $this->calculator->calculateCutoffDistribution(103);

        $this->assertEquals(25, $distribution['c1'], 'C1 should be 25');
        $this->assertEquals(25, $distribution['c2'], 'C2 should be 25');
        $this->assertEquals(25, $distribution['c3'], 'C3 should be 25');
        $this->assertEquals(28, $distribution['c4'], 'C4 should get remainder (28)');
    }

    /**
     * Test cutoff distribution for small capacity
     */
    public function test_calculate_cutoff_distribution_small_capacity()
    {
        $distribution = $this->calculator->calculateCutoffDistribution(10);

        $this->assertEquals(2, $distribution['c1'], 'C1 should be 2');
        $this->assertEquals(2, $distribution['c2'], 'C2 should be 2');
        $this->assertEquals(2, $distribution['c3'], 'C3 should be 2');
        $this->assertEquals(4, $distribution['c4'], 'C4 should get remainder (4)');
    }

    /**
     * Test shift capacities calculation for single shift
     */
    public function test_calculate_shift_capacities_single_shift()
    {
        $capacities = $this->calculator->calculateShiftCapacities(1, 100);

        $this->assertArrayHasKey(1, $capacities, 'Should have shift 1');
        $this->assertEquals(25, $capacities[1]['c1']);
        $this->assertEquals(25, $capacities[1]['c2']);
        $this->assertEquals(25, $capacities[1]['c3']);
        $this->assertEquals(25, $capacities[1]['c4']);
    }

    /**
     * Test shift capacities calculation for two shifts
     */
    public function test_calculate_shift_capacities_two_shifts()
    {
        $capacities = $this->calculator->calculateShiftCapacities(2, 100);

        $this->assertCount(2, $capacities, 'Should have 2 shifts');
        $this->assertArrayHasKey(1, $capacities, 'Should have shift 1');
        $this->assertArrayHasKey(2, $capacities, 'Should have shift 2');

        // Both shifts should have same cutoff distribution
        $this->assertEquals($capacities[1], $capacities[2]);
    }

    /**
     * Test shift capacities with different capacity value
     */
    public function test_calculate_shift_capacities_custom_capacity()
    {
        $capacities = $this->calculator->calculateShiftCapacities(2, 80);

        $this->assertEquals(20, $capacities[1]['c1']);
        $this->assertEquals(20, $capacities[1]['c2']);
        $this->assertEquals(20, $capacities[1]['c3']);
        $this->assertEquals(20, $capacities[1]['c4']);
    }
}
