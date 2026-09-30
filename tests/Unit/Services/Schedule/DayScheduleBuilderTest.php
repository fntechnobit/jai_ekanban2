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
 * Angka acuan: B3-ENG 30-09-2026 — normal 78, overtime 96, listing SIREP
 * 7YA1A 22 + 7YA1E 112 + 7YA6A 6 = 140, sehingga hari itu selalu dua shift:
 * S1 = 7YA1A 22 + 7YA1E 56 (78), S2 = 7YA1E 56 + 7YA6A 6 (62).
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new DayScheduleBuilder(
            new ShiftCapacityCalculator(),
            new ListingAllocator(),
            new ScheduleCleanupService()
        );
    }

    /** Listing B3-ENG 30-09, urut FIFO id_listing seperti listingQuery(). */
    private function listingB3Eng(int $qty6A = 6): Collection
    {
        return collect([
            [self::YA1A, 7504, 'E796', '24012-7YA1A', 22, 3],
            [self::YA1E, 7505, 'E798', '24012-7YA1E', 112, 1],
            [self::YA6A, 7506, 'E799', '24012-7YA6A', $qty6A, 2],
        ])->map(fn ($r) => (new ListingStage())->forceFill([
            'id' => $r[0], 'id_listing' => $r[1], 'assycode' => $r[2], 'assy' => $r[3],
            'qty' => $r[4], 'seq' => $r[5], 'plt' => 8, 'mode' => 1, 'snp' => 0, 'snpa' => 0,
        ]));
    }

    private function susun(Collection $listings, array $held = [], array $occupied = []): array
    {
        return $this->builder->allocate(
            $listings, $held, $occupied, self::NORMAL, self::OVERTIME, 2, '2026-09-30'
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

    public function test_hari_baru_terbagi_dua_shift(): void
    {
        $plan = $this->susun($this->listingB3Eng());

        $this->assertSame(2, $plan['max_shifts']);
        $this->assertSame([
            1 => ['24012-7YA1A' => 22, '24012-7YA1E' => 56],
            2 => ['24012-7YA1E' => 56, '24012-7YA6A' => 6],
        ], $this->perShift($plan));
        $this->assertSame(0, $plan['unallocated']);
    }

    /**
     * S1 sudah diverifikasi. Dulu 7YA1A dan 7YA1E dibuang utuh, sisa 6 pcs muat di
     * satu shift, S1 terkunci — S2 hilang dan hari itu tampak "verified 1 shift".
     */
    public function test_s1_terverifikasi_s2_tetap_dibangun_utuh(): void
    {
        $plan = $this->susun(
            $this->listingB3Eng(),
            [self::YA1A => 22, self::YA1E => 56],
            [1]
        );

        $this->assertSame(2, $plan['max_shifts'], 'Jumlah shift dari demand penuh, bukan sisa');
        $this->assertSame([
            2 => ['24012-7YA1E' => 56, '24012-7YA6A' => 6],
        ], $this->perShift($plan));
        $this->assertSame(0, $plan['unallocated']);
    }

    /**
     * S2 sudah diverifikasi. Dulu 7YA1E dibuang utuh sehingga S1 tinggal 7YA1A 22 —
     * persis kondisi di backup 30-09 14:14.
     */
    public function test_s2_terverifikasi_s1_tetap_78(): void
    {
        $plan = $this->susun(
            $this->listingB3Eng(),
            [self::YA1E => 56, self::YA6A => 6],
            [2]
        );

        $this->assertSame([
            1 => ['24012-7YA1A' => 22, '24012-7YA1E' => 56],
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
            1 => ['24012-7YA1A' => 22, '24012-7YA1E' => 56],
            2 => ['24012-7YA1E' => 56],
        ], $this->perShift($plan));
    }

    /** Hari satu shift tetap memakai CO5 sebagai penampung seperti sebelumnya. */
    public function test_hari_satu_shift_co5_menampung_sisa(): void
    {
        $listings = collect([(new ListingStage())->forceFill([
            'id' => 9, 'id_listing' => 1, 'assycode' => 'E1', 'assy' => 'A', 'qty' => 90,
            'seq' => 1, 'plt' => 8, 'mode' => 1, 'snp' => 0, 'snpa' => 0,
        ])]);

        $plan = $this->susun($listings);

        $this->assertSame(1, $plan['max_shifts']);
        $this->assertSame([1 => ['A' => 90]], $this->perShift($plan));
        $co5 = array_values(array_filter($plan['schedules'], fn ($r) => $r['cutoff'] === 5));
        $this->assertSame(12, $co5[0]['qty']);
    }
}
