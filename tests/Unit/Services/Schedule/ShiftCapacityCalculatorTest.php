<?php

namespace Tests\Unit\Services\Schedule;

use App\Services\Schedule\ShiftCapacityCalculator;
use Tests\TestCase;

/**
 * Aturan kapasitas versi API SIREP.
 *
 * Angka acuan sepanjang berkas ini memakai B3-EGI: normal 136, overtime 160,
 * sehingga CO1-CO4 = 34 dan CO5 = 24 di setiap shift.
 *
 * Pembagian shift (Okt 2026):
 *   lembur (qty > shift x normal) : S1.CO1-CO5 sampai overtime -> S2.CO1-CO5 (sisa)
 *   normal                        : S1.CO1-CO4 sampai normal   -> S2.CO1-CO4 (sisa)
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
     * @return array{shift:int, lembur:bool, caps:array<int, array<string,int>>}
     */
    private function jalankan(int $qty, int $normal = self::NORMAL, ?int $overtime = self::OVERTIME, array $lock = []): array
    {
        $shift  = $this->calc->resolveShiftCount($overtime, $qty);
        $lembur = $this->calc->isOvertimeDay($normal, $shift, $qty);
        $caps   = $this->calc->calculateShiftCapacities($normal, $lock, $shift);
        $this->calc->preMapCutoff5($caps, $normal, $overtime, $qty, $lembur);

        return ['shift' => $shift, 'lembur' => $lembur, 'caps' => $caps];
    }

    /** Jumlah seluruh cutoff pada seluruh shift — pada hari lembur harus sama dengan qty. */
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

    // ───────────────────────── lembur atau normal ─────────────────────────

    public function test_satu_shift_lembur_bila_melampaui_kapasitas_normal(): void
    {
        $this->assertFalse($this->calc->isOvertimeDay(136, 1, 136));
        $this->assertTrue($this->calc->isOvertimeDay(136, 1, 137));
    }

    public function test_dua_shift_lembur_bila_melampaui_dua_kali_kapasitas_normal(): void
    {
        $this->assertFalse($this->calc->isOvertimeDay(78, 2, 140), 'B3-ENG 30-09: muat di 2 x 78');
        $this->assertFalse($this->calc->isOvertimeDay(78, 2, 156), 'Tepat 2 x normal belum lembur');
        $this->assertTrue($this->calc->isOvertimeDay(78, 2, 176), 'B3-ENG 07-10');
    }

    // ───────────────────────── kapasitas CO5 ─────────────────────────

    public function test_co5_mengikuti_data_sirep(): void
    {
        $this->assertSame(24, $this->calc->cutoff5Nominal(136, 160));
        $this->assertSame(18, $this->calc->cutoff5Nominal(78, 96), 'B3-ENG');
        $this->assertSame(0, $this->calc->cutoff5Nominal(136, 136), 'Ambang cadangan: tanpa jatah lembur');
    }

    public function test_kapasitas_nominal_hari(): void
    {
        $this->assertSame(160, $this->calc->nominalDayCapacity(136, 160, 1), 'Satu shift = overtime_capacity');
        $this->assertSame(320, $this->calc->nominalDayCapacity(136, 160, 2), '2 x overtime_capacity');
        $this->assertSame(272, $this->calc->nominalDayCapacity(136, 160, 2, false), 'Lembur belum dinyatakan: 2 x normal');
    }

    // ─────────────── skenario acuan ───────────────

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

    /** qty 310 > 2 x 136 -> lembur: S1 penuh 160 dulu, S2 menampung sisa 150. */
    public function test_dua_shift_lembur_shift_pertama_penuh_sampai_overtime(): void
    {
        $h = $this->jalankan(310);

        $this->assertSame(2, $h['shift']);
        $this->assertTrue($h['lembur']);
        $this->assertSame(24, $h['caps'][1]['c5'], 'Shift pertama penuh sampai overtime');
        $this->assertSame(160, $h['caps'][1]['total']);
        $this->assertSame(14, $h['caps'][2]['c5'], 'Shift terakhir menampung sisa');
        $this->assertSame(150, $h['caps'][2]['total']);
        $this->assertSame(310, $this->totalTeralokasi($h['caps']));
    }

    /** qty 250 muat di 2 x 136 -> normal: tidak ada CO5 sama sekali. */
    public function test_dua_shift_normal_tidak_membuka_co5(): void
    {
        $h = $this->jalankan(250);

        $this->assertSame(2, $h['shift']);
        $this->assertFalse($h['lembur']);
        $this->assertSame(0, $h['caps'][1]['c5']);
        $this->assertSame(0, $h['caps'][2]['c5']);
        $this->assertSame(136, $h['caps'][1]['total'], 'Shift pertama sampai kapasitas normal');
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
        // 2 x 160 = 320 nominal; 500 jauh di atas itu.
        $h = $this->jalankan(500);

        $this->assertSame(2, $h['shift']);
        $this->assertSame(24, $h['caps'][1]['c5']);
        $this->assertSame(500 - 160 - 136, $h['caps'][2]['c5'], 'Shift terakhir penampung');
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
        // Tanpa jatah lembur, shift pertama berhenti di kapasitas normal; sisa 400
        // jatuh ke CO5 shift terakhir sebagai penampung.
        $this->assertSame(0, $h['caps'][1]['c5'], 'Tidak ada jatah lembur');
        $this->assertSame(400, $h['caps'][2]['c5'], 'Hari 720 unit memang melampaui rencana');
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
