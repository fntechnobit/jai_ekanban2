<?php

namespace App\Console\Commands;

use App\Models\MasterConveyor;
use App\Services\Listing\SirepApiClient;
use App\Services\Schedule\ShiftCapacityCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Tarik kapasitas conveyor dari API SIREP ke master lokal.
 *
 * Menurut tim PPC, `normal_capacity` adalah kapasitas conveyor untuk SATU shift —
 * makna yang sama dengan kolom `capacity` di master_conveyor. `overtime_capacity`
 * disimpan sebagai pembanding terhadap ambang CO5 yang dihitung sendiri.
 *
 * Secara bawaan perintah ini hanya menampilkan pratinjau. Tambahkan --apply
 * untuk benar-benar menulis ke master.
 */
class SirepSyncConveyorCommand extends Command
{
    protected $signature = 'sirep:sync-conveyor
                            {--apply : Tulis perubahan ke master_conveyor (tanpa ini hanya pratinjau)}';

    protected $description = 'Sinkronkan kapasitas conveyor dari API SIREP ke master_conveyor';

    public function handle(SirepApiClient $client, ShiftCapacityCalculator $calculator): int
    {
        try {
            $apiConveyors = $client->fetchConveyors();
        } catch (\Throwable $e) {
            $this->error('Gagal mengambil daftar conveyor: ' . $e->getMessage());

            return self::FAILURE;
        }

        $apply   = (bool) $this->option('apply');
        $rows    = [];
        $updates = [];

        foreach ($apiConveyors as $item) {
            $name             = trim((string) ($item['name'] ?? ''));
            $normalCapacity   = $item['normal_capacity'] ?? null;
            $overtimeCapacity = $item['overtime_capacity'] ?? null;

            if ($name === '') {
                continue;
            }

            $conveyor = MasterConveyor::whereRaw('TRIM(conveyor) = ?', [$name])
                ->orWhereRaw('TRIM(sirep_conveyor_code) = ?', [$name])
                ->first();

            if (!$conveyor) {
                $rows[] = [$name, $normalCapacity ?? '-', $overtimeCapacity ?? '-', '-', '-', 'TIDAK ADA DI MASTER'];
                continue;
            }

            if ($normalCapacity === null) {
                $rows[] = [$name, '-', $overtimeCapacity ?? '-', $conveyor->capacity, '-', 'kapasitas SIREP kosong'];
                continue;
            }

            // Ambang over kami sendiri, untuk dibandingkan dengan overtime_capacity SIREP.
            $ownOvertime = $calculator->calculateOvertimeCapacity((int) $normalCapacity);

            $status = 'sama';

            if ((int) $conveyor->capacity !== (int) $normalCapacity) {
                $status = "kapasitas {$conveyor->capacity} -> {$normalCapacity}";
                $updates[] = $conveyor->id;
            }

            if ($overtimeCapacity !== null && (int) $overtimeCapacity !== $ownOvertime) {
                $status .= ($status === 'sama' ? '' : '; ')
                    . "ambang over SIREP {$overtimeCapacity} vs hitungan kami {$ownOvertime}";
            }

            $rows[] = [
                $name,
                $normalCapacity,
                $overtimeCapacity ?? '-',
                $conveyor->capacity,
                $ownOvertime,
                $status,
            ];

            if ($apply) {
                $conveyor->capacity           = (int) $normalCapacity;
                $conveyor->overtime_capacity  = $overtimeCapacity !== null ? (int) $overtimeCapacity : null;
                $conveyor->capacity_synced_at = now();
                $conveyor->save();
            }
        }

        $this->table(
            ['Conveyor', 'Normal (SIREP)', 'Over (SIREP)', 'Kapasitas (master)', 'Ambang over (kami)', 'Status'],
            $rows
        );

        if (!$apply) {
            $this->newLine();
            $this->warn('Pratinjau saja — tidak ada yang ditulis. Tambahkan --apply untuk menerapkan.');

            return self::SUCCESS;
        }

        Log::info('Kapasitas conveyor disinkronkan dari SIREP', ['diperbarui' => count($updates)]);
        $this->newLine();
        $this->info('Selesai. Conveyor dengan kapasitas berubah: ' . count($updates));
        $this->line('Jadwal yang sudah dibuat TIDAK ikut berubah — jalankan generate ulang bila perlu.');

        return self::SUCCESS;
    }
}
