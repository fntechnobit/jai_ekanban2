<?php

namespace Tests\Unit\Services\Schedule;

use App\Models\ListingStage;
use App\Services\Schedule\DayScheduleBuilder;
use App\Services\Schedule\ListingAllocator;
use App\Services\Schedule\ScheduleCleanupService;
use App\Services\Schedule\ShiftCapacityCalculator;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Penyusunan jadwal satu hari ketika sebagian shift sudah diverifikasi.
 *
 * Angka acuan B3-ENG — normal 78, overtime 96:
 *
 *  30-09-2026 (hari normal): listing urut seq SIREP 7YA1E 112 (seq 1) + 7YA6A 6
 *  (seq 2) + 7YA1A 22 (seq 3) = 140, muat di 2 x 78 sehingga tanpa lembur:
 *  S1 = 7YA1E 78, S2 = 7YA1E 34 + 7YA6A 6 + 7YA1A 22 (62).
 *
 *  07-10-2026 (hari lembur): 7YA1E 96 (seq 1, id_listing 7818) + 7YA1B 80
 *  (seq 2, id_listing 7817) = 176 > 2 x 78, sehingga shift 1 penuh sampai 96:
 *  S1 = 7YA1E 96, S2 = 7YA1B 80.
 */
class DayScheduleBuilderTest extends TestCase
{
    private DayScheduleBuilder $builder;

    private const NORMAL   = 78;
    private const OVERTIME = 96;

    // listing_stage.id
    private const YA1A = 3;
    private const YA1E = 1;
    private const YA6A = 2;
    private const YA1B = 4;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new DayScheduleBuilder(
            new ShiftCapacityCalculator(),
            new ListingAllocator(),
            new ScheduleCleanupService()
        );
    }

    /** @param array<int, array{0:int,1:int,2:string,3:string,4:int,5:int}> $rows */
    private function listing(array $rows): Collection
    {
        return collect($rows)->map(fn ($r) => (new ListingStage())->forceFill([
            'id' => $r[0], 'id_listing' => $r[1], 'assycode' => $r[2], 'assy' => $r[3],
            'qty' => $r[4], 'seq' => $r[5], 'plt' => 8, 'mode' => 1, 'snp' => 0, 'snpa' => 0,
        ]));
    }

    /** Listing B3-ENG 30-09, urut seq SIREP seperti listingQuery(). */
    private function listingB3Eng(int $qty6A = 6): Collection
    {
        return $this->listing([
            [self::YA1E, 7505, 'E798', '24012-7YA1E', 112, 1],
            [self::YA6A, 7506, 'E799', '24012-7YA6A', $qty6A, 2],
            [self::YA1A, 7504, 'E796', '24012-7YA1A', 22, 3],
        ]);
    }

    /** Listing B3-ENG 07-10, urut seq SIREP — id_listing justru terbalik. */
    private function listingB3EngLembur(): Collection
    {
        return $this->listing([
            [self::YA1E, 7818, 'E798', '24012-7YA1E', 96, 1],
            [self::YA1B, 7817, 'E797', '24012-7YA1B', 80, 2],
        ]);
    }

    private function susun(Collection $listings, array $held = [], array $occupied = [], int $shiftStart = 1): array
    {
        return $this->builder->allocate(
            $listings, $held, $occupied, self::NORMAL, self::OVERTIME, 2, '2026-09-30', $shiftStart
        );
    }

    /** @return array<int, array<string, int>> shift => assy => qty */
    private function perShift(array $plan): array
    {
        $hasil = [];
        foreach ($plan['schedules'] as $row) {
            $hasil[$row['shift']][$row['assy']] = ($hasil[$row['shift']][$row['assy']] ?? 0) + $row['qty'];
        }
        ksort($hasil);

        return $hasil;
    }

    /** @return array<int, int> cutoff => qty untuk satu shift */
    private function perCutoff(array $plan, int $shift): array
    {
        $hasil = [];
        foreach ($plan['schedules'] as $row) {
            if ($row['shift'] === $shift) {
                $hasil[$row['cutoff']] = ($hasil[$row['cutoff']] ?? 0) + $row['qty'];
            }
        }
        ksort($hasil);

        return $hasil;
    }

    public function test_listing_diurutkan_dengan_seq_terkecil_lebih_dulu(): void
    {
        $orders = array_map(
            fn ($o) => $o['column'],
            $this->builder->listingQuery()->getQuery()->orders
        );

        $this->assertSame(['seq', 'id_listing'], $orders, 'seq utama, id_listing hanya pemecah seri');
    }

    public function test_hari_baru_terbagi_dua_shift(): void
    {
        $plan = $this->susun($this->listingB3Eng());

        $this->assertSame(2, $plan['max_shifts']);
        $this->assertFalse($plan['overtime_day'], '140 muat di 2 x 78');
        $this->assertSame([
            1 => ['24012-7YA1E' => 78],
            2 => ['24012-7YA1E' => 34, '24012-7YA6A' => 6, '24012-7YA1A' => 22],
        ], $this->perShift($plan));
        $this->assertSame([1 => 21, 2 => 19, 3 => 19, 4 => 19], $this->perCutoff($plan, 1), 'Hari normal tanpa CO5');
        $this->assertSame(0, $plan['unallocated']);
    }

    /**
     * S1 sudah diverifikasi. Dulu listing yang sebagian terkunci dibuang utuh, sisa
     * muat di satu shift, S1 terkunci — S2 hilang dan hari itu tampak "verified 1 shift".
     */
    public function test_s1_terverifikasi_s2_tetap_dibangun_utuh(): void
    {
        $plan = $this->susun(
            $this->listingB3Eng(),
            [self::YA1E => 78],
            [1]
        );

        $this->assertSame(2, $plan['max_shifts'], 'Jumlah shift dari demand penuh, bukan sisa');
        $this->assertSame([
            2 => ['24012-7YA1E' => 34, '24012-7YA6A' => 6, '24012-7YA1A' => 22],
        ], $this->perShift($plan));
        $this->assertSame(0, $plan['unallocated']);
    }

    /** S2 sudah diverifikasi: potongan 7YA1E milik S1 tidak boleh ikut hilang. */
    public function test_s2_terverifikasi_s1_tetap_78(): void
    {
        $plan = $this->susun(
            $this->listingB3Eng(),
            [self::YA1E => 34, self::YA6A => 6, self::YA1A => 22],
            [2]
        );

        $this->assertSame([
            1 => ['24012-7YA1E' => 78],
        ], $this->perShift($plan));

        $cutoff = array_sum(array_map(
            fn ($r) => $r['cutoff'] === 5 ? $r['qty'] : 0,
            $plan['schedules']
        ));
        $this->assertSame(0, $cutoff, '78 pas di CO1-CO4, tidak perlu CO5');
    }

    public function test_hari_penuh_terverifikasi_tidak_menambah_apa_pun(): void
    {
        $plan = $this->susun(
            $this->listingB3Eng(),
            [self::YA1A => 22, self::YA1E => 112, self::YA6A => 6],
            [1, 2]
        );

        $this->assertSame([], $plan['schedules']);
        $this->assertSame(0, $plan['unallocated']);
    }

    /** SIREP menambah qty setelah kedua shift terkunci: dilaporkan, bukan dibuang diam-diam. */
    public function test_tambahan_sirep_setelah_semua_shift_terkunci_dilaporkan(): void
    {
        $plan = $this->susun(
            $this->listingB3Eng(10),
            [self::YA1A => 22, self::YA1E => 112, self::YA6A => 6],
            [1, 2]
        );

        $this->assertSame([], $plan['schedules']);
        $this->assertSame(4, $plan['unallocated']);
    }

    /** Qty yang sudah dipindah ke tanggal lain tetap dihitung terpegang. */
    public function test_item_yang_dipindah_ke_tanggal_lain_tidak_dijadwalkan_ulang(): void
    {
        $plan = $this->susun($this->listingB3Eng(), [self::YA6A => 6]);

        $this->assertSame(2, $plan['max_shifts']);
        $this->assertSame([
            1 => ['24012-7YA1E' => 78],
            2 => ['24012-7YA1E' => 34, '24012-7YA1A' => 22],
        ], $this->perShift($plan));
    }

    /** Hari satu shift tetap memakai CO5 sebagai penampung seperti sebelumnya. */
    public function test_hari_satu_shift_co5_menampung_sisa(): void
    {
        $listings = $this->listing([[9, 1, 'E1', 'A', 90, 1]]);

        $plan = $this->susun($listings);

        $this->assertSame(1, $plan['max_shifts']);
        $this->assertSame([1 => ['A' => 90]], $this->perShift($plan));
        $co5 = array_values(array_filter($plan['schedules'], fn ($r) => $r['cutoff'] === 5));
        $this->assertSame(12, $co5[0]['qty']);
    }

    // ─────────────── hari lembur: shift 1 penuh sampai overtime ───────────────

    /**
     * Permintaan user B3-ENG 07-10. Dulu (urut id_listing, CO1-4 kedua shift dulu,
     * CO5 shift 1 dibatasi 7/8): S1 = 7YA1B 78 + 7YA1E 17 = 95,
     * S2 = 7YA1B 2 + 7YA1E 79 = 81.
     */
    public function test_hari_lembur_shift_pertama_penuh_sampai_overtime_urut_seq(): void
    {
        $plan = $this->susun($this->listingB3EngLembur());

        $this->assertSame(2, $plan['max_shifts']);
        $this->assertTrue($plan['overtime_day'], '176 > 2 x 78');
        $this->assertSame([
            1 => ['24012-7YA1E' => 96],
            2 => ['24012-7YA1B' => 80],
        ], $this->perShift($plan));
        $this->assertSame([1 => 21, 2 => 19, 3 => 19, 4 => 19, 5 => 18], $this->perCutoff($plan, 1));
        $this->assertSame([1 => 21, 2 => 19, 3 => 19, 4 => 19, 5 => 2], $this->perCutoff($plan, 2));
        $this->assertSame(0, $plan['unallocated']);
    }

    public function test_hari_lembur_s1_terverifikasi_s2_menampung_sisa(): void
    {
        $plan = $this->susun($this->listingB3EngLembur(), [self::YA1E => 96], [1]);

        $this->assertSame([2 => ['24012-7YA1B' => 80]], $this->perShift($plan));
        $this->assertSame([1 => 21, 2 => 19, 3 => 19, 4 => 19, 5 => 2], $this->perCutoff($plan, 2));
    }

    public function test_hari_lembur_s2_terverifikasi_s1_tetap_penuh_96(): void
    {
        $plan = $this->susun($this->listingB3EngLembur(), [self::YA1B => 80], [2]);

        $this->assertSame([1 => ['24012-7YA1E' => 96]], $this->perShift($plan));
        $this->assertSame([1 => 21, 2 => 19, 3 => 19, 4 => 19, 5 => 18], $this->perCutoff($plan, 1));
    }

    // ─────────────── shift mulai dari master conveyor ───────────────

    /** Conveyor mulai shift 2: hari satu shift seluruhnya di S2, CO5 tetap penampung. */
    public function test_hari_satu_shift_mulai_shift_2_dijadwalkan_di_shift_2(): void
    {
        $listings = $this->listing([[9, 1, 'E1', 'A', 90, 1]]);

        $plan = $this->susun($listings, shiftStart: 2);

        $this->assertSame(1, $plan['max_shifts']);
        $this->assertSame([2 => ['A' => 90]], $this->perShift($plan));
        $this->assertSame([1 => 21, 2 => 19, 3 => 19, 4 => 19, 5 => 12], $this->perCutoff($plan, 2));
        $this->assertSame(0, $plan['unallocated']);
    }

    /** Mulai shift 2 tidak berlaku pada hari dua shift: tetap S1 lalu S2. */
    public function test_hari_dua_shift_mulai_shift_2_tetap_dari_shift_1(): void
    {
        $plan = $this->susun($this->listingB3Eng(), shiftStart: 2);

        $this->assertSame(2, $plan['max_shifts']);
        $this->assertSame([
            1 => ['24012-7YA1E' => 78],
            2 => ['24012-7YA1E' => 34, '24012-7YA6A' => 6, '24012-7YA1A' => 22],
        ], $this->perShift($plan));
    }
}
