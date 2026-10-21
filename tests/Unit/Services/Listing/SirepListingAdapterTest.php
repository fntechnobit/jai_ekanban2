<?php

namespace Tests\Unit\Services\Listing;

use App\Models\ListingStage;
use App\Services\Listing\SirepListingAdapter;
use Tests\TestCase;

/**
 * Kunci identitas dan deteksi revisi baris listing SIREP.
 *
 * Acuan: B3-ENG 26-10-2026. Listing ditarik dengan 7YA1B seq 4 (id 9526), lalu PPC
 * menaikkannya ke seq 1 — id SIREP-nya tetap.
 */
class SirepListingAdapterTest extends TestCase
{
    private SirepListingAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new SirepListingAdapter();
    }

    /** Baris SIREP B3-ENG 26-10 seperti hasil toStageAttributes(). */
    private function baris(array $ubah = []): array
    {
        return $ubah + [
            'id_listing'        => 9526,
            'source'            => 'api',
            'listing_date_time' => '2026-10-26 00:00:00',
            'conveyor'          => 'B3-ENG',
            'shift'             => 0,
            'assycode'          => 'E797',
            'assy'              => '24012-7YA1B',
            'carline'           => 'J42U-ENG',
            'qty'               => 24,
            'seq'               => 4,
            'plt'               => 8,
            'mode'              => 1,
            'is_overtime'       => true,
            'snp'               => 1,
            'snpa'              => 1,
        ];
    }

    private function staging(array $ubah = []): ListingStage
    {
        return (new ListingStage())->forceFill($this->baris($ubah));
    }

    public function test_perubahan_seq_tidak_mengubah_kunci(): void
    {
        $this->assertSame(
            $this->adapter->keyOf($this->baris()),
            $this->adapter->keyOf($this->baris(['seq' => 1])),
            'Baris yang sama di SIREP harus tetap dikenali walau seq-nya berubah'
        );
    }

    public function test_kunci_baris_staging_sama_dengan_baris_masuk(): void
    {
        $this->assertSame(
            $this->adapter->keyOf($this->baris(['seq' => 1])),
            $this->adapter->keyOfModel($this->staging())
        );
    }

    public function test_tanggal_conveyor_dan_sumber_tetap_bagian_kunci(): void
    {
        $kunci = $this->adapter->keyOf($this->baris());

        $this->assertNotSame($kunci, $this->adapter->keyOf($this->baris(['listing_date_time' => '2026-10-27 00:00:00'])));
        $this->assertNotSame($kunci, $this->adapter->keyOf($this->baris(['conveyor' => 'B3-EGI'])));
        $this->assertNotSame($kunci, $this->adapter->keyOf($this->baris(['source' => 'db'])),
            'id SIREP baru dan id database lama adalah deret berbeda');
    }

    public function test_baris_tanpa_id_memakai_kunci_lama(): void
    {
        $tanpaId = $this->baris(['id_listing' => 0]);

        $this->assertSame('2026-10-26|B3-ENG|E797|4', $this->adapter->keyOf($tanpaId));
        $this->assertNotSame($this->adapter->keyOf($tanpaId), $this->adapter->keyOf($this->baris(['id_listing' => 0, 'seq' => 1])));
    }

    public function test_perubahan_seq_terdeteksi_sebagai_revisi(): void
    {
        $this->assertSame(
            ['seq' => [4, 1]],
            $this->adapter->changedFields($this->staging(), $this->baris(['seq' => 1]))
        );
    }

    public function test_baris_sama_tidak_dianggap_berubah(): void
    {
        $this->assertSame([], $this->adapter->changedFields($this->staging(), $this->baris()));
        $this->assertFalse($this->adapter->overtimeChanged($this->staging(), $this->baris()));
    }

    public function test_penanda_lembur_dipisah_dari_isi(): void
    {
        $baru = $this->baris(['is_overtime' => false]);

        $this->assertSame([], $this->adapter->changedFields($this->staging(), $baru), 'Bukan revisi isi listing');
        $this->assertTrue($this->adapter->overtimeChanged($this->staging(), $baru));
    }

    public function test_beberapa_field_berubah_sekaligus(): void
    {
        $berubah = $this->adapter->changedFields(
            $this->staging(),
            $this->baris(['qty' => 30, 'assycode' => 'E798 '])
        );

        $this->assertSame(['assycode' => ['E797', 'E798 '], 'qty' => [24, 30]], $berubah);
    }
}
