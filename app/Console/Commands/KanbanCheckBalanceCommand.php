<?php

namespace App\Console\Commands;

use App\Services\BalanceIntegrityService;
use Illuminate\Console\Command;

/**
 * Kroscek keutuhan saldo kanban.
 *
 * Saldo seharusnya = Σ delta generate + Σ addition − Σ defect. Perintah ini
 * memeriksanya, menunjukkan kapan carry-over terputus, dan (dengan --fix)
 * menyelaraskan saldo ke angka yang dapat dipertanggungjawabkan.
 */
class KanbanCheckBalanceCommand extends Command
{
    protected $signature = 'kanban:check-balance
                            {--type=all : circuit, shikake, atau all}
                            {--conveyor= : Batasi ke satu conveyor_id}
                            {--fix : Selaraskan saldo tersimpan ke hasil penjumlahan mutasi}
                            {--breaks : Tampilkan titik putus carry-over}
                            {--limit=15 : Jumlah baris contoh yang ditampilkan}';

    protected $description = 'Periksa kecocokan saldo kanban dengan catatan mutasinya';

    public function handle(BalanceIntegrityService $pemeriksa): int
    {
        $types = $this->option('type') === 'all' ? ['circuit', 'shikake'] : [$this->option('type')];
        $conveyorId = $this->option('conveyor') ? (int) $this->option('conveyor') : null;
        $limit = (int) $this->option('limit');
        $adaMasalah = false;

        foreach ($types as $type) {
            $this->newLine();
            $this->line(str_repeat('─', 78));
            $this->info(strtoupper($type));
            $this->line(str_repeat('─', 78));

            $hasil = $pemeriksa->check($type, $conveyorId);

            $this->line(sprintf(
                '  Item diperiksa : %d      Menyimpang : %d      Selisih total : %+d unit',
                $hasil['diperiksa'],
                $hasil['menyimpang'],
                $hasil['selisih_total']
            ));

            if (($hasil['tanpa_ledger'] ?? 0) > 0) {
                $this->line(sprintf(
                    '  <fg=gray>%d item dilewati: belum pernah masuk ledger, tidak ada dasar pembanding.</>',
                    $hasil['tanpa_ledger']
                ));
            }

            if ($hasil['menyimpang'] > 0) {
                $adaMasalah = true;

                $this->newLine();
                $this->table(
                    ['Conveyor', 'Master', 'Sejak', 'Saldo awal', 'Tersimpan', 'Seharusnya', 'Selisih', 'Generate', 'Addition', 'Defect'],
                    array_map(fn ($b) => [
                        $b['conveyor'], $b['master_id'], $b['sejak'], $b['saldo_awal'],
                        $b['tersimpan'], $b['seharusnya'], sprintf('%+d', $b['selisih']),
                        $b['dari_generate'], $b['dari_addition'], $b['dari_defect'],
                    ], array_slice($hasil['baris'], 0, $limit))
                );

                if (count($hasil['baris']) > $limit) {
                    $this->line('  ... dan ' . (count($hasil['baris']) - $limit) . ' item lain.');
                }
            } else {
                $this->line('  <fg=green>Saldo cocok dengan catatan mutasinya.</>');
            }

            if ($this->option('breaks')) {
                $putus = $pemeriksa->carryOverBreaks($type, $conveyorId, $limit);

                $this->newLine();
                $this->line('  Titik putus carry-over (sisa_before tidak sama dengan sisa_after sebelumnya):');

                if (empty($putus)) {
                    $this->line('    <fg=green>Tidak ada.</>');
                } else {
                    $adaMasalah = true;
                    $this->table(
                        ['Conveyor', 'Master', 'Tanggal', 'Shift', 'Dari tanggal', 'Sisa akhir sebelumnya', 'Sisa awal di sini'],
                        array_map(fn ($p) => [
                            $p['conveyor'], $p['master_id'], $p['schedule_date'], $p['shift'],
                            $p['tgl_sebelumnya'], $p['after_sebelumnya'], $p['sisa_before'],
                        ], $putus)
                    );
                }
            }

            $dup = $pemeriksa->duplicateBarcodes($type);

            if ($dup['total'] > 0) {
                $adaMasalah = true;
                $this->newLine();
                $this->warn(sprintf(
                    '  %d barcode kanban dipakai lebih dari sekali — tanda nomor urut pernah di-reset ke 0.',
                    $dup['total']
                ));
            }

            if ($this->option('fix')) {
                $perbaikan = $pemeriksa->repair($type, $conveyorId);
                $this->newLine();
                $this->info(sprintf(
                    '  Diselaraskan: %d item (%+d unit). Nomor urut TIDAK disentuh.',
                    $perbaikan['diperbaiki'],
                    $perbaikan['selisih_total']
                ));
            }
        }

        $this->newLine();

        if (!$this->option('fix') && $adaMasalah) {
            $this->warn('Ditemukan penyimpangan. Jalankan ulang dengan --fix untuk menyelaraskan saldo,');
            $this->warn('setelah memastikan penyebabnya (reset saldo / kanban terhapus) sudah tidak berulang.');
        }

        return self::SUCCESS;
    }
}
