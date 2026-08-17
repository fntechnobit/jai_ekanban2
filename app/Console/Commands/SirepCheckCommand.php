<?php

namespace App\Console\Commands;

use App\Models\MasterConveyor;
use App\Services\Listing\SirepApiClient;
use App\Services\Listing\SirepListingAdapter;
use Illuminate\Console\Command;

/**
 * Alat diagnosa integrasi API SIREP.
 *
 * Menjawab tiga pertanyaan yang selalu muncul saat integrasi bermasalah:
 *   1. Apakah API dapat dihubungi dari server ini?
 *   2. Apakah nama conveyor di SIREP cocok dengan master lokal?
 *   3. Apakah baris listing dapat dipetakan ke bentuk staging dengan benar?
 */
class SirepCheckCommand extends Command
{
    protected $signature = 'sirep:check
                            {--conveyor= : Uji ambil listing untuk satu conveyor}
                            {--from= : Tanggal awal (Y-m-d), default hari ini}
                            {--to= : Tanggal akhir (Y-m-d), default sama dengan --from}';

    protected $description = 'Uji koneksi API SIREP, cocokkan master conveyor, dan pratinjau pemetaan listing';

    public function handle(SirepApiClient $client, SirepListingAdapter $adapter): int
    {
        $this->info('Sumber listing aktif : ' . config('sirep.listing_source'));
        $this->info('Base URL             : ' . config('sirep.api.base_url'));
        $this->newLine();

        // ── 1. Ketersediaan ──────────────────────────────────────────────────
        $this->comment('1. Uji koneksi');
        $ping = $client->ping();

        if (!$ping['ok']) {
            $this->error('   GAGAL — ' . $ping['message']);

            return self::FAILURE;
        }

        $this->line('   OK — ' . $ping['message']);
        $this->newLine();

        // ── 2. Kecocokan conveyor ────────────────────────────────────────────
        $this->comment('2. Kecocokan master conveyor');

        try {
            $apiConveyors = $client->fetchConveyors();
        } catch (\Throwable $e) {
            $this->error('   GAGAL mengambil daftar conveyor: ' . $e->getMessage());

            return self::FAILURE;
        }

        $apiNames = collect($apiConveyors)
            ->map(fn ($c) => trim((string) ($c['name'] ?? '')))
            ->filter()
            ->unique();

        $localNames = MasterConveyor::pluck('conveyor')
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique();

        $this->line('   Conveyor di API SIREP : ' . $apiNames->count());
        $this->line('   Conveyor di master    : ' . $localNames->count());

        $onlyApi   = $apiNames->diff($localNames);
        $onlyLocal = $localNames->diff($apiNames);

        if ($onlyApi->isNotEmpty()) {
            $this->warn('   Ada di SIREP tetapi tidak di master : ' . $onlyApi->implode(', '));
        }

        if ($onlyLocal->isNotEmpty()) {
            $this->warn('   Ada di master tetapi tidak di SIREP : ' . $onlyLocal->implode(', '));
        }

        if ($onlyApi->isEmpty() && $onlyLocal->isEmpty()) {
            $this->line('   Seluruh nama conveyor cocok.');
        }

        // Kapasitas dari SIREP — berguna untuk membandingkan dengan master lokal.
        $withCapacity = collect($apiConveyors)->filter(fn ($c) => ($c['normal_capacity'] ?? null) !== null);

        if ($withCapacity->isNotEmpty()) {
            $this->newLine();
            $this->line('   Kapasitas dari SIREP (pembanding master lokal):');
            $rows = $withCapacity->take(10)->map(function ($c) use ($localNames) {
                $name  = trim((string) ($c['name'] ?? ''));
                $local = MasterConveyor::whereRaw('TRIM(conveyor) = ?', [$name])->first();

                return [
                    $name,
                    $c['normal_capacity'] ?? '-',
                    $c['overtime_capacity'] ?? '-',
                    $local->capacity ?? 'tidak ada di master',
                ];
            })->all();

            $this->table(['Conveyor', 'Normal (SIREP)', 'Lembur (SIREP)', 'Kapasitas (master)'], $rows);
        }

        $this->newLine();

        // ── 3. Pratinjau pemetaan listing ────────────────────────────────────
        $conveyor = $this->option('conveyor');

        if (!$conveyor) {
            $this->comment('3. Pratinjau listing dilewati (gunakan --conveyor=NAMA untuk mengujinya)');

            return self::SUCCESS;
        }

        $from = $this->option('from') ?: now()->format('Y-m-d');
        $to   = $this->option('to') ?: $from;

        $this->comment("3. Pratinjau listing — {$conveyor} ({$from} s.d. {$to})");

        try {
            $rows = $client->fetchListing($conveyor, $from, $to);
        } catch (\Throwable $e) {
            $this->error('   GAGAL: ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($rows === []) {
            $this->warn('   API tidak mengembalikan baris apa pun untuk rentang ini.');

            return self::SUCCESS;
        }

        $this->line('   Jumlah baris diterima: ' . count($rows));
        $this->newLine();

        $preview = array_map(function ($row) use ($adapter) {
            $mapped = $adapter->toStageAttributes($row);

            return [
                $mapped['id_listing'],
                substr($mapped['listing_date_time'], 0, 10),
                $mapped['conveyor'],
                $mapped['assycode'],
                $mapped['assy'],
                $mapped['qty'],
                $mapped['seq'],
                $mapped['snp'],
                $mapped['snpa'],
                $mapped['plt'],
                $mapped['mode'],
            ];
        }, array_slice($rows, 0, 10));

        $this->table(
            ['id', 'tanggal', 'conveyor', 'assycode', 'assy', 'qty', 'seq', 'snp', 'snpa', 'plt', 'mode'],
            $preview
        );

        $this->line('   Periksa kolom snp/snpa/plt/mode terhadap SIREP lama untuk memastikan');
        $this->line('   aturan konversi sea/air di config/sirep.php sudah benar.');

        return self::SUCCESS;
    }
}
