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

    /**
     * Isi kapasitas ke daftar slot berurutan, meniru cara ListingAllocator bekerja.
     * Dipakai untuk menguji URUTAN pengisian, bukan sekadar besaran jatahnya.
     *
     * @param  array<int, array{0:int,1:int,2:int}>  $slots  [shift, cutoff, jatah]
     * @return array<string, int>                            "shift.cutoff" => terisi
     */
    private function isi(array $slots, int $qty): array
    {
        $hasil = [];

        foreach ($slots as [$shift, $cutoff, $jatah]) {
            $ambil = max(0, min($qty, $jatah));

            if ($ambil > 0) {
                $hasil["{$shift}.{$cutoff}"] = $ambil;
                $qty -= $ambil;
            }
        }

        return $hasil;
    }

    /** Urutan pengisian resmi: CO1-4 semua shift dulu, baru CO5 shift demi shift. */
    private function urutanSlot(array $caps): array
    {
        $slots = [];

        foreach ($caps as $shift => $c) {
            foreach ([1, 2, 3, 4] as $co) {
                $slots[] = [$shift, $co, $c["c{$co}"]];
            }
        }

        foreach ($caps as $shift => $c) {
            $slots[] = [$shift, 5, $c['c5'] ?? 0];
        }

        return $slots;
    }

    /** Jalankan satu skenario penuh dan kembalikan isi tiap cutoff. */
    private function jadwalkan(int $cap, int $qty, bool $lembur, int $shiftQtyMaster): array
    {
        $shifts = $this->calculator->resolveShiftCount($shiftQtyMaster);
        $caps   = $this->calculator->calculateShiftCapacities($cap, [], $shifts);
        $this->calculator->preMapCutoff5($caps, $cap, $qty, $lembur);

        return [$shifts, $this->isi($this->urutanSlot($caps), $qty), $caps];
    }

    // ───────────── Pembagian CO1-CO4: sisa ke CO1 ─────────────

    public function test_sisa_pembagian_masuk_ke_co1()
    {
        // 135 / 4 = 33 sisa 3 -> CO1 menampung 36
        $d = $this->calculator->calculateCutoffDistribution(135);
        $this->assertSame([36, 33, 33, 33], [$d['c1'], $d['c2'], $d['c3'], $d['c4']]);
        $this->assertSame(135, array_sum([$d['c1'], $d['c2'], $d['c3'], $d['c4']]));

        // 78 / 4 = 19 sisa 3 -> CO1 menampung 21
        $d = $this->calculator->calculateCutoffDistribution(78);
        $this->assertSame([21, 19, 19, 19], [$d['c1'], $d['c2'], $d['c3'], $d['c4']]);
    }

    public function test_kapasitas_habis_dibagi_empat_terbagi_rata()
    {
        $d = $this->calculator->calculateCutoffDistribution(136);
        $this->assertSame([34, 34, 34, 34], [$d['c1'], $d['c2'], $d['c3'], $d['c4']]);
    }

    // ───────────── CO5 nominal ─────────────

    public function test_co5_nominal_adalah_7_per_8_co_normal()
    {
        // 7/8 × (136/4) = 29.75 -> round = 30
        $this->assertSame(30, $this->calculator->calculateCutoff5Capacity(136));
        // 7/8 × (100/4) = 21.875 -> round = 22
        $this->assertSame(22, $this->calculator->calculateCutoff5Capacity(100));
    }

    // ───────────── Jumlah shift dibaca dari master ─────────────

    public function test_jumlah_shift_dibaca_dari_master()
    {
        $this->assertSame(1, $this->calculator->resolveShiftCount(1));
        $this->assertSame(2, $this->calculator->resolveShiftCount(2));
    }

    public function test_jumlah_shift_dijepit_satu_sampai_dua()
    {
        $this->assertSame(1, $this->calculator->resolveShiftCount(0));
        $this->assertSame(1, $this->calculator->resolveShiftCount(null));
        $this->assertSame(2, $this->calculator->resolveShiftCount(3), 'Tidak ada shift 3');
        $this->assertSame(2, ShiftCapacityCalculator::MAX_SHIFT);
    }

    public function test_config_tidak_bisa_menaikkan_batas_di_atas_dua()
    {
        config(['sirep.capacity.max_shift' => 5]);

        $this->assertSame(2, $this->calculator->resolveShiftCount(5));
    }

    // ───────────── Tiga skenario acuan PPC (kapasitas 136) ─────────────

    /** shift_qty 1, qty 160, lembur YA -> CO1-4 = 34, CO5 = 24. */
    public function test_ppc_1_satu_shift_dengan_lembur()
    {
        [$shifts, $isi] = $this->jadwalkan(136, 160, true, 1);

        $this->assertSame(1, $shifts);
        $this->assertSame(['1.1' => 34, '1.2' => 34, '1.3' => 34, '1.4' => 34, '1.5' => 24], $isi);
    }

    /** shift_qty 2, qty 160, lembur TIDAK -> CO5 tertutup, sisa 24 ke CO1 shift 2. */
    public function test_ppc_2_dua_shift_tanpa_lembur_mengalir_ke_shift_berikutnya()
    {
        [$shifts, $isi, $caps] = $this->jadwalkan(136, 160, false, 2);

        $this->assertSame(2, $shifts);
        $this->assertSame(['1.1' => 34, '1.2' => 34, '1.3' => 34, '1.4' => 34, '2.1' => 24], $isi);
        $this->assertSame(0, $caps[1]['c5'], 'Tanpa lembur CO5 tidak boleh terbuka');
        $this->assertSame(0, $caps[2]['c5'], 'Tanpa lembur CO5 tidak boleh terbuka');
    }

    /** shift_qty 2, qty 310, lembur YA -> S1.CO5 = 30 (dibatasi), S2.CO5 = 8 (sisa). */
    public function test_ppc_3_dua_shift_dengan_lembur()
    {
        [$shifts, $isi, $caps] = $this->jadwalkan(136, 310, true, 2);

        $this->assertSame(2, $shifts);
        $this->assertSame(30, $caps[1]['c5'], 'CO5 shift 1 dibatasi nominal 7/8');
        $this->assertSame(8, $caps[2]['c5'], 'Sisanya dibebankan ke CO5 shift 2');
        $this->assertSame(310, array_sum($isi), 'Seluruh listing terjadwal');
        $this->assertSame(
            ['1.1', '1.2', '1.3', '1.4', '2.1', '2.2', '2.3', '2.4', '1.5', '2.5'],
            array_keys($isi),
            'Urutan: CO1-4 kedua shift dulu, baru CO5 shift 1, lalu CO5 shift 2'
        );
    }

    // ───────────── Perilaku lain ─────────────

    public function test_satu_shift_tidak_pernah_menghasilkan_shift_dua()
    {
        [$shifts, $isi, $caps] = $this->jadwalkan(136, 500, true, 1);

        $this->assertSame(1, $shifts);
        $this->assertArrayNotHasKey(2, $caps);
        $this->assertSame(364, $caps[1]['c5'], 'CO5 shift terakhir menampung seluruh sisa');
        $this->assertSame(500, array_sum($isi));
    }

    public function test_tanpa_lembur_tetapi_seluruh_shift_penuh_ditampung_co5_terakhir()
    {
        // 2 x 136 = 272; sisa 28 tidak punya tempat lain selain CO5 shift terakhir.
        [$shifts, $isi, $caps] = $this->jadwalkan(136, 300, false, 2);

        $this->assertSame(2, $shifts);
        $this->assertSame(0, $caps[1]['c5']);
        $this->assertSame(28, $caps[2]['c5']);
        $this->assertSame(300, array_sum($isi), 'Tidak ada listing yang hilang');
    }

    public function test_muat_pas_tidak_membuka_co5()
    {
        [, , $caps] = $this->jadwalkan(136, 272, true, 2);

        $this->assertSame(0, $caps[1]['c5']);
        $this->assertSame(0, $caps[2]['c5']);
    }

    public function test_shift_terkunci_dinolkan_dan_co5_jatuh_ke_shift_tersisa()
    {
        $caps = $this->calculator->calculateShiftCapacities(100, [2 => true], 2);
        $this->calculator->preMapCutoff5($caps, 100, 140, true);

        $this->assertTrue($caps[2]['locked']);
        $this->assertSame(0, $caps[2]['total']);
        $this->assertSame(40, $caps[1]['c5']);
    }
}
