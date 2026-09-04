<?php

namespace App\Console\Commands;

use App\Services\SirepConveyorSyncService;
use Illuminate\Console\Command;

/**
 * Samakan daftar conveyor dengan API SIREP: tambah yang baru, perbarui yang ada,
 * nonaktifkan yang sudah tidak dikirim API.
 *
 * Aturan dan pencocokannya ada di SirepConveyorSyncService, dipakai bersama tombol
 * Sync di layar Master Conveyor supaya keduanya tidak pernah berbeda hasil.
 *
 * Secara bawaan perintah ini hanya menampilkan pratinjau. Tambahkan --apply
 * untuk benar-benar menulis ke master.
 */
class SirepSyncConveyorCommand extends Command
{
    protected $signature = 'sirep:sync-conveyor
                            {--apply : Tulis perubahan ke master_conveyor (tanpa ini hanya pratinjau)}';

    protected $description = 'Samakan daftar & kapasitas conveyor dengan API SIREP';

    public function handle(SirepConveyorSyncService $syncer): int
    {
        $apply  = (bool) $this->option('apply');
        $result = $syncer->sync($apply);

        if (!$result['success']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->table(
            ['Conveyor (SIREP)', 'Di master', 'Normal', 'Over (SIREP)', 'Kapasitas lama', 'Keterangan'],
            array_map(fn ($r) => [
                $r['sirep_name'] ?? '-',
                $r['conveyor'] ?? '-',
                $r['normal_capacity'] ?? '-',
                $r['overtime_capacity'] ?? '-',
                $r['capacity_lama'] ?? '-',
                $r['status'],
            ], $result['rows'])
        );

        $this->newLine();

        if (!$apply) {
            $this->warn('Pratinjau saja — tidak ada yang ditulis. Tambahkan --apply untuk menerapkan.');
        }

        $this->info($result['message']);

        return self::SUCCESS;
    }
}
