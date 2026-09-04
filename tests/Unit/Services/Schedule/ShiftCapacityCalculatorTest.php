<?php

namespace Tests\Unit\Services\Schedule;

use App\Services\Schedule\ShiftCapacityCalculator;
use Tests\TestCase;

class ShiftCapacityCalculatorTest extends TestCase
{
    private ShiftCapacityCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ShiftCapacityCalculator();
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
        $this->assertEquals(26, $distribution['c4'], 'C4 should get remainder (26)');
    }

    public function test_calculate_cutoff_distribution_small_capacity()
    {
        $distribution = $this->calculator->calculateCutoffDistribution(10);

        $this->assertEquals(2, $distribution['c1']);
        $this->assertEquals(4, $distribution['c4'], 'C4 should get remainder (4)');
    }

    // ───────────────────────── CO5 nominal ─────────────────────────

    public function test_co5_nominal_adalah_7_per_8_co_normal()
    {
        // 7/8 × (100/4) = 21.875 → round = 22
        $this->assertEquals(22, $this->calculator->calculateCutoff5Capacity(100));
        // Kasus acuan PPC: 7/8 × (136/4) = 29.75 → round = 30
        $this->assertEquals(30, $this->calculator->calculateCutoff5Capacity(136));
    }

    /** Rasio dapat diubah lewat config bila aturan PPC berubah. */
    public function test_rasio_co5_dapat_diubah_lewat_config()
    {
        config(['sirep.capacity.co5_ratio' => 0.85]);

        $this->assertEquals(29, $this->calculator->calculateCutoff5Capacity(136));
    }

    public function test_effective_shift_capacity_depends_on_overtime()
    {
        config(['sirep.capacity.co5_ratio' => 7 / 8]);

        $this->assertEquals(136, $this->calculator->effectiveShiftCapacity(136, false));
        $this->assertEquals(166, $this->calculator->effectiveShiftCapacity(136, true));
    }

    // ───────────────────────── resolveShiftCount ─────────────────────────

    public function test_shift_count_uses_normal_capacity_when_not_overtime()
    {
        // 160 / 136 = 1.18 → 2 shift
        $this->assertEquals(2, $this->calculator->resolveShiftCount(136, 160, false));
        // Tepat penuh satu shift
        $this->assertEquals(1, $this->calculator->resolveShiftCount(136, 136, false));
    }

    public function test_shift_count_uses_capacity_plus_co5_when_overtime()
    {
        config(['sirep.capacity.co5_ratio' => 7 / 8]);

        // 160 / 166 = 0.96 → 1 shift
        $this->assertEquals(1, $this->calculator->resolveShiftCount(136, 160, true));
        // 310 / 166 = 1.87 → 2 shift
        $this->assertEquals(2, $this->calculator->resolveShiftCount(136, 310, true));
    }

    public function test_shift_count_is_clamped_to_max_shift()
    {
        config(['sirep.capacity.max_shift' => 2]);

        // 1000 / 136 = 7.4 → dibatasi 2
        $this->assertEquals(2, $this->calculator->resolveShiftCount(136, 1000, false));
    }

    public function test_shift_count_never_below_one()
    {
        $this->assertEquals(1, $this->calculator->resolveShiftCount(136, 0, false));
        $this->assertEquals(1, $this->calculator->resolveShiftCount(0, 500, true));
    }

    // ───────────────────────── calculateShiftCapacities ─────────────────────────

    public function test_single_shift_does_not_produce_shift_two()
    {
        $capacities = $this->calculator->calculateShiftCapacities(100, [], 1);

        $this->assertArrayHasKey(1, $capacities);
        $this->assertArrayNotHasKey(2, $capacities);
        $this->assertFalse($capacities[1]['locked']);
    }

    public function test_locked_shift_gets_zero_capacity()
    {
        $capacities = $this->calculator->calculateShiftCapacities(100, [2 => true], 2);

        $this->assertEquals(0, $capacities[2]['total']);
        $this->assertTrue($capacities[2]['locked']);
        $this->assertEquals(100, $capacities[1]['total']);
    }

    // ────────────── Tiga skenario acuan dari PPC (kapasitas 136) ──────────────

    /** qty 160, lembur YA → 1 shift, CO1-4 = 34, CO5 = 24. */
    public function test_ppc_case_1_overtime_single_shift()
    {
        $shifts = $this->calculator->resolveShiftCount(136, 160, true);
        $caps   = $this->calculator->calculateShiftCapacities(136, [], $shifts);
        $this->calculator->preMapCutoff5($caps, 136, 160);

        $this->assertEquals(1, $shifts);
        $this->assertEquals([34, 34, 34, 34], [$caps[1]['c1'], $caps[1]['c2'], $caps[1]['c3'], $caps[1]['c4']]);
        $this->assertEquals(24, $caps[1]['c5']);
    }

    /** qty 160, lembur TIDAK → 2 shift, CO5 tertutup; sisa 24 masuk CO1-4 shift 2. */
    public function test_ppc_case_2_no_overtime_spills_to_next_shift()
    {
        $shifts = $this->calculator->resolveShiftCount(136, 160, false);
        $caps   = $this->calculator->calculateShiftCapacities(136, [], $shifts);
        $this->calculator->preMapCutoff5($caps, 136, 160);

        $this->assertEquals(2, $shifts);
        $this->assertEquals(0, $caps[1]['c5'], 'Tanpa lembur CO5 tidak boleh terbuka');
        $this->assertEquals(0, $caps[2]['c5'], 'Tanpa lembur CO5 tidak boleh terbuka');
        $this->assertEquals(34, $caps[2]['c1'], 'Sisa 24 mengalir ke CO1 shift 2');
    }

    /**
     * qty 310, lembur YA → 2 shift: S1.CO5 = 30 (nominal 7/8), S2.CO5 = 8 (sisa).
     */
    public function test_ppc_case_3_overtime_two_shifts()
    {
        config(['sirep.capacity.co5_ratio' => 7 / 8]);

        $shifts = $this->calculator->resolveShiftCount(136, 310, true);
        $caps   = $this->calculator->calculateShiftCapacities(136, [], $shifts);
        $this->calculator->preMapCutoff5($caps, 136, 310);

        $this->assertEquals(2, $shifts);
        $this->assertEquals(30, $caps[1]['c5'], 'S1.CO5 dibatasi nominal 7/8 = 30');
        $this->assertEquals(8, $caps[2]['c5'], 'Sisa dibebankan ke CO5 shift 2');
    }

    // ───────────────────────── preMapCutoff5, kasus lain ─────────────────────────

    public function test_sisa_masih_muat_di_nominal_co5_shift_pertama()
    {
        config(['sirep.capacity.max_shift' => 2]);

        config(['sirep.capacity.co5_ratio' => 7 / 8]);

        // 300 tidak muat di 2 × 136 = 272. Sisa 28 masih di bawah nominal 30,
        // jadi seluruhnya masuk CO5 shift 1 — lembur implisit.
        $shifts = $this->calculator->resolveShiftCount(136, 300, false);
        $caps   = $this->calculator->calculateShiftCapacities(136, [], $shifts);
        $this->calculator->preMapCutoff5($caps, 136, 300);

        $this->assertEquals(2, $shifts);
        $this->assertEquals(28, $caps[1]['c5'], 'Masih muat di nominal CO5 shift 1');
        $this->assertEquals(0, $caps[2]['c5']);
    }

    /**
     * qty 310, lembur TIDAK — diperlakukan sebagai LEMBUR IMPLISIT.
     * 310 tidak muat di 2 x 136 = 272. Kekurangan 38 dibagi: CO5 shift 1 dibatasi
     * nominal 30, sisa 8 dibebankan ke CO5 shift 2. Hari itu tetap ditandai
     * "over tanpa OT" di layar verifikasi.
     */
    public function test_qty_310_without_overtime_keeps_every_unit_scheduled()
    {
        config(['sirep.capacity.max_shift' => 2, 'sirep.capacity.co5_ratio' => 7 / 8]);

        $shifts = $this->calculator->resolveShiftCount(136, 310, false);
        $caps   = $this->calculator->calculateShiftCapacities(136, [], $shifts);
        $this->calculator->preMapCutoff5($caps, 136, 310);

        $this->assertEquals(2, $shifts, 'Dibatasi max_shift meski ceil(310/136) = 3');
        $this->assertEquals(30, $caps[1]['c5'], 'CO5 shift 1 dibatasi nominal 7/8 = 30');
        $this->assertEquals(8, $caps[2]['c5'], 'Sisanya dibebankan ke CO5 shift 2');

        $total = array_sum(array_map(
            fn ($c) => $c['c1'] + $c['c2'] + $c['c3'] + $c['c4'] + $c['c5'],
            $caps
        ));
        $this->assertEquals(310, $total, 'Seluruh listing harus terjadwal, tidak ada yang hilang');
    }

    public function test_overtime_locked_last_shift_falls_back_to_shift_one_catch_all()
    {
        $caps = $this->calculator->calculateShiftCapacities(100, [2 => true], 2);
        $this->calculator->preMapCutoff5($caps, 100, 140);

        $this->assertEquals(40, $caps[1]['c5']);
        $this->assertEquals(0, $caps[2]['c5']);
    }

    public function test_exact_fit_produces_no_co5()
    {
        $caps = $this->calculator->calculateShiftCapacities(100, [], 2);
        $this->calculator->preMapCutoff5($caps, 100, 200);

        $this->assertEquals(0, $caps[1]['c5']);
        $this->assertEquals(0, $caps[2]['c5']);
    }
}
