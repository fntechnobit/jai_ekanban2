<?php

namespace Tests\Unit\Services\Schedule;

use App\Services\Schedule\ShiftCapacityCalculator;
use Tests\TestCase;

/**
 * Aturan kapasitas versi API SIREP.
 *
 * Angka acuan sepanjang berkas ini memakai B3-EGI: normal 136, overtime 160,
 * sehingga CO1-CO4 = 34 dan CO5 satu shift = 24.
 */
class ShiftCapacityCalculatorTest extends TestCase
{
    private ShiftCapacityCalculator $calc;

    private const NORMAL   = 136;
    private const OVERTIME = 160;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new ShiftCapacityCalculator();
    }

    /**
     * Jalankan satu hari penuh: tentukan shift, susun CO1-4, lalu jatah CO5.
     *
     * @return array{shift:int, caps:array<int, array<string,int>>}
     */
    private function jalankan(int $qty, int $normal = self::NORMAL, ?int $overtime = self::OVERTIME, array $lock = []): array
    {
        $shift = $this->calc->resolveShiftCount($overtime, $qty);
        $caps  = $this->calc->calculateShiftCapacities($normal, $lock, $shift);
        $this->calc->preMapCutoff5($caps, $normal, $qty);

        return ['shift' => $shift, 'caps' => $caps];
    }

    /** Jumlah seluruh cutoff pada seluruh shift — harus selalu sama dengan qty. */
    private function totalTeralokasi(array $caps): int
    {
        return array_sum(array_map(
            fn ($c) => $c['c1'] + $c['c2'] + $c['c3'] + $c['c4'] + $c['c5'],
            $caps
        ));
    }

    // ───────────────────────── pembagian cutoff ─────────────────────────

    public function test_sisa_pembagian_masuk_ke_co1(): void
    {
        // 135 / 4 = 33 sisa 3 -> CO1 menampung 36
        $this->assertSame(
            ['c1' => 36, 'c2' => 33, 'c3' => 33, 'c4' => 33, 'total' => 135],
            $this->calc->calculateCutoffDistribution(135)
        );
    }

    public function test_kapasitas_habis_dibagi_empat_terbagi_rata(): void
    {
        $d = $this->calc->calculateCutoffDistribution(136);

        $this->assertSame([34, 34, 34, 34], [$d['c1'], $d['c2'], $d['c3'], $d['c4']]);
    }

    // ───────────────────────── jumlah shift ─────────────────────────

    public function test_qty_sampai_ambang_tetap_satu_shift(): void
    {
        $this->assertSame(1, $this->calc->resolveShiftCount(self::OVERTIME, 159));
        $this->assertSame(1, $this->calc->resolveShiftCount(self::OVERTIME, 160), 'Tepat di ambang belum pecah');
    }

    public function test_qty_melebihi_ambang_jadi_dua_shift(): void
    {
        $this->assertSame(2, $this->calc->resolveShiftCount(self::OVERTIME, 161));
        $this->assertSame(2, $this->calc->resolveShiftCount(self::OVERTIME, 1000), 'Tidak pernah lebih dari dua');
    }

    public function test_tanpa_ambang_tidak_pecah_shift(): void
    {
        // Pemanggil wajib melewati conveyor seperti ini; nilainya hanya pengaman.
        $this->assertSame(1, $this->calc->resolveShiftCount(null, 9999));
        $this->assertSame(1, $this->calc->resolveShiftCount(0, 9999));
    }

    // ───────────────────────── kapasitas CO5 ─────────────────────────

    public function test_co5_satu_shift_mengikuti_data_sirep(): void
    {
        $this->assertSame(24, $this->calc->cutoff5ForSingleShift(136, 160));
        $this->assertSame(18, $this->calc->cutoff5ForSingleShift(78, 96), 'B3-ENG');
    }

    public function test_co5_shift_pertama_dua_shift_memakai_tujuh_perdelapan(): void
    {
        // 7/8 x (136/4) = 29,75 -> 30
        $this->assertSame(30, $this->calc->calculateCutoff5Capacity(136));
    }

    public function test_nominal_co5_berbeda_antara_satu_dan_dua_shift(): void
    {
        $this->assertSame(24, $this->calc->cutoff5Nominal(136, 160, 1));
        $this->assertSame(30, $this->calc->cutoff5Nominal(136, 160, 2));
    }

    public function test_kapasitas_nominal_hari(): void
    {
        $this->assertSame(160, $this->calc->nominalDayCapacity(136, 160, 1), 'Satu shift = overtime_capacity');
        $this->assertSame(302, $this->calc->nominalDayCapacity(136, 160, 2), '2 x 136 + 30');
    }

    // ─────────────── skenario acuan PPC ───────────────

    /** qty 160 tepat di ambang -> 1 shift, CO1-4 = 34, CO5 = 24. */
    public function test_acuan_ppc_satu_shift(): void
    {
        $h = $this->jalankan(160);

        $this->assertSame(1, $h['shift']);
        $this->assertSame([34, 34, 34, 34], array_map(
            fn ($k) => $h['caps'][1][$k],
            ['c1', 'c2', 'c3', 'c4']
        ));
        $this->assertSame(24, $h['caps'][1]['c5']);
        $this->assertSame(160, $this->totalTeralokasi($h['caps']));
    }

    /** qty 310 -> 2 shift, S1.CO5 dibatasi 30, S2.CO5 menampung sisa 8. */
    public function test_acuan_ppc_dua_shift(): void
    {
        $h = $this->jalankan(310);

        $this->assertSame(2, $h['shift']);
        $this->assertSame(30, $h['caps'][1]['c5'], 'Shift pertama dibatasi 7/8');
        $this->assertSame(8, $h['caps'][2]['c5'], 'Shift terakhir menampung sisa');
        $this->assertSame(310, $this->totalTeralokasi($h['caps']));
    }

    // ───────────────────────── perilaku lain ─────────────────────────

    public function test_qty_muat_di_kapasitas_normal_tidak_membuka_co5(): void
    {
        $h = $this->jalankan(136);

        $this->assertSame(1, $h['shift']);
        $this->assertSame(0, $h['caps'][1]['c5']);
    }

    public function test_seluruh_listing_selalu_terjadwal_walau_melampaui_dua_shift(): void
    {
        // 2 x 136 + 30 = 302 nominal; 500 jauh di atas itu.
        $h = $this->jalankan(500);

        $this->assertSame(2, $h['shift']);
        $this->assertSame(30, $h['caps'][1]['c5']);
        $this->assertSame(500 - 272 - 30, $h['caps'][2]['c5'], 'Shift terakhir penampung');
        $this->assertSame(500, $this->totalTeralokasi($h['caps']), 'Tidak ada listing yang hilang');
    }

    public function test_shift_terkunci_tidak_ikut_dialokasi(): void
    {
        $h = $this->jalankan(310, lock: [2 => true]);

        $this->assertSame(0, $h['caps'][2]['total'], 'Shift terkunci tetap nol');
        $this->assertTrue($h['caps'][2]['locked']);
        // Shift 1 jadi satu-satunya yang tidak terkunci, sehingga CO5-nya penampung.
        $this->assertSame(310 - 136, $h['caps'][1]['c5']);
    }

    // ───────────────────────── ambang cadangan ─────────────────────────

    public function test_ambang_memakai_overtime_capacity_bila_ada(): void
    {
        $this->assertSame(160, $this->calc->effectiveOvertimeCapacity(136, 160));
        $this->assertFalse($this->calc->overtimeCapacityIsFallback(160));
    }

    public function test_ambang_jatuh_ke_normal_capacity_bila_belum_ada(): void
    {
        $this->assertSame(136, $this->calc->effectiveOvertimeCapacity(136, null));
        $this->assertSame(136, $this->calc->effectiveOvertimeCapacity(136, 0));
        $this->assertTrue($this->calc->overtimeCapacityIsFallback(null));
    }

    /**
     * Conveyor tanpa jatah lembur: hari yang melampaui kapasitas normal pecah jadi
     * dua shift, bukan menumpuk di CO5 satu shift.
     *
     * Kasus nyata B1-J42U: kapasitas 160, listing 720. Dipaksa satu shift, CO5-nya
     * menampung 560 unit — 14x cutoff normal.
     */
    public function test_tanpa_jatah_lembur_beban_pecah_ke_dua_shift(): void
    {
        $ambang = $this->calc->effectiveOvertimeCapacity(160, null);
        $h = $this->jalankan(720, normal: 160, overtime: $ambang);

        $this->assertSame(2, $h['shift']);
        // CO1-4 dua shift = 320, sisa 400. CO5 shift 1 dibatasi 7/8 x 40 = 35,
        // sisanya 365 jatuh ke CO5 shift terakhir sebagai penampung.
        $this->assertSame(35, $h['caps'][1]['c5'], 'Shift pertama tetap dibatasi 7/8');
        $this->assertSame(365, $h['caps'][2]['c5'], 'Hari 720 unit memang melampaui rencana');
        $this->assertSame(720, $this->totalTeralokasi($h['caps']), 'Tidak ada listing yang hilang');
    }

    public function test_tanpa_jatah_lembur_hari_yang_muat_tetap_satu_shift(): void
    {
        $ambang = $this->calc->effectiveOvertimeCapacity(140, null);
        $h = $this->jalankan(126, normal: 140, overtime: $ambang);

        $this->assertSame(1, $h['shift'], 'C4: listing tidak pernah melampaui kapasitas');
        $this->assertSame(0, $h['caps'][1]['c5']);
    }
}
