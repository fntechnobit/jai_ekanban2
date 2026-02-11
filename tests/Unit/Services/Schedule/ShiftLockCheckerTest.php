<?php

namespace Tests\Unit\Services\Schedule;

use App\Models\AssySchedule;
use App\Services\Schedule\ShiftLockChecker;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftLockCheckerTest extends TestCase
{
    use RefreshDatabase;

    private ShiftLockChecker $lockChecker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lockChecker = new ShiftLockChecker();
    }

    /**
     * Test getting shift lock status when no schedules exist
     */
    public function test_get_shift_lock_status_no_schedules()
    {
        $date = Carbon::parse('2025-01-15');
        $conveyorId = 1;
        $maxShifts = 2;

        $status = $this->lockChecker->getShiftLockStatus($date, $conveyorId, $maxShifts);

        $this->assertIsArray($status);
        $this->assertArrayHasKey(1, $status);
        $this->assertArrayHasKey(2, $status);
        $this->assertFalse($status[1], 'Shift 1 should be unlocked');
        $this->assertFalse($status[2], 'Shift 2 should be unlocked');
    }

    /**
     * Test getting shift lock status with one locked shift
     */
    public function test_get_shift_lock_status_one_locked()
    {
        $date = Carbon::parse('2025-01-15');
        $conveyorId = 1;

        // Create a locked schedule for shift 1
        AssySchedule::factory()->create([
            'schedule' => $date,
            'conveyor_id' => $conveyorId,
            'shift' => 1,
            'is_lock' => 1,
        ]);

        // Create an unlocked schedule for shift 2
        AssySchedule::factory()->create([
            'schedule' => $date,
            'conveyor_id' => $conveyorId,
            'shift' => 2,
            'is_lock' => 0,
        ]);

        $status = $this->lockChecker->getShiftLockStatus($date, $conveyorId, 2);

        $this->assertTrue($status[1], 'Shift 1 should be locked');
        $this->assertFalse($status[2], 'Shift 2 should be unlocked');
    }

    /**
     * Test is shift locked method
     */
    public function test_is_shift_locked()
    {
        $date = Carbon::parse('2025-01-15');
        $conveyorId = 1;

        // Create a locked schedule
        AssySchedule::factory()->create([
            'schedule' => $date,
            'conveyor_id' => $conveyorId,
            'shift' => 1,
            'is_lock' => 1,
        ]);

        $this->assertTrue(
            $this->lockChecker->isShiftLocked($date, $conveyorId, 1),
            'Shift 1 should be locked'
        );

        $this->assertFalse(
            $this->lockChecker->isShiftLocked($date, $conveyorId, 2),
            'Shift 2 should not be locked'
        );
    }

    /**
     * Test getting unlocked shifts
     */
    public function test_get_unlocked_shifts()
    {
        $date = Carbon::parse('2025-01-15');
        $conveyorId = 1;

        // Create locked schedule for shift 1
        AssySchedule::factory()->create([
            'schedule' => $date,
            'conveyor_id' => $conveyorId,
            'shift' => 1,
            'is_lock' => 1,
        ]);

        $unlockedShifts = $this->lockChecker->getUnlockedShifts($date, $conveyorId, 3);

        $this->assertIsArray($unlockedShifts);
        $this->assertContains(2, $unlockedShifts, 'Shift 2 should be unlocked');
        $this->assertContains(3, $unlockedShifts, 'Shift 3 should be unlocked');
        $this->assertNotContains(1, $unlockedShifts, 'Shift 1 should be locked');
    }

    /**
     * Test all shifts locked scenario
     */
    public function test_all_shifts_locked()
    {
        $date = Carbon::parse('2025-01-15');
        $conveyorId = 1;

        // Lock both shifts
        AssySchedule::factory()->create([
            'schedule' => $date,
            'conveyor_id' => $conveyorId,
            'shift' => 1,
            'is_lock' => 1,
        ]);

        AssySchedule::factory()->create([
            'schedule' => $date,
            'conveyor_id' => $conveyorId,
            'shift' => 2,
            'is_lock' => 1,
        ]);

        $unlockedShifts = $this->lockChecker->getUnlockedShifts($date, $conveyorId, 2);

        $this->assertEmpty($unlockedShifts, 'All shifts should be locked');
    }
}
