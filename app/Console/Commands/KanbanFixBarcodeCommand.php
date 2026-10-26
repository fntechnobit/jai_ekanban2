<?php

namespace App\Console\Commands;

use App\Support\KanbanBarcode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Selaraskan barcode kanban yang sudah tergenerate ke format scan STO Circuit.
 *
 * Isi QR dikunci saat generate, jadi kanban yang terlanjur tergenerate dengan
 * format lama tetap tidak bisa discan STO walau dicetak ulang. Perintah ini
 * menulis ulang barcode-nya dari kode yang sudah tercetak (bukan dari master
 * terkini) dan melaporkan kanban yang tetap tidak terbaca STO.
 */
class KanbanFixBarcodeCommand extends Command
{
    protected $signature = 'kanban:fix-barcode
                            {--type=all : circuit, shikake, atau all}
                            {--since= : Hanya kanban dengan release_date >= tanggal ini (YYYY-MM-DD)}
                            {--fix : Simpan perubahan; tanpa opsi ini hanya ditampilkan}
                            {--limit=15 : Jumlah baris contoh yang ditampilkan}';

    protected $description = 'Selaraskan barcode kanban ke format scan STO Circuit (circuit tanpa titik, drawing bertitik)';

    private int $limit;

    public function handle(): int
    {
        $types = $this->option('type') === 'all' ? ['circuit', 'shikake'] : [$this->option('type')];
        $this->limit = (int) $this->option('limit');

        foreach ($types as $type) {
            $this->newLine();
            $this->line(str_repeat('─', 78));
            $this->info(strtoupper($type));
            $this->line(str_repeat('─', 78));

            $hasil = $type === 'circuit' ? $this->scanCircuit() : $this->scanShikake();
            $this->report($hasil);
        }

        $this->newLine();
        if (!$this->option('fix')) {
            $this->warn('Belum ada yang disimpan. Jalankan ulang dengan --fix untuk memperbarui barcode,');
            $this->warn('lalu cetak ulang kanban yang sudah tercetak agar QR di kertasnya ikut berubah.');
        }

        return self::SUCCESS;
    }

    private function scanShikake(): array
    {
        $hasil = $this->emptyResult();

        $this->rows('assy_schedule_shikake', 'master_shikake', 'master_shikake_id', [])
            ->chunkById(1000, function ($rows) use (&$hasil) {
                foreach ($rows as $row) {
                    $this->check($hasil, 'assy_schedule_shikake', $row, 'barcode_kanban', fn ($carline, $code) => KanbanBarcode::drawing(
                        $carline, $code, $row->issue, (int) $row->qty_kanban, (int) $row->nomor_urut
                    ));
                }
            }, 'k.id', 'id');

        return $hasil;
    }

    private function scanCircuit(): array
    {
        $hasil = $this->emptyResult();

        $this->rows('assy_schedule_circuit', 'master_circuit', 'master_circuit_id', ['k.cct_code', 'k.qrcode_shikake'])
            ->chunkById(1000, function ($rows) use (&$hasil) {
                foreach ($rows as $row) {
                    $qty = (int) $row->qty_kanban;
                    $nys = (int) $row->nomor_urut;

                    // Kode selain CCT di barcode kanban circuit adalah Twist No (JAM Twist),
                    // yang di STO terdaftar sebagai drawing.
                    $this->check($hasil, 'assy_schedule_circuit', $row, 'barcode_kanban', fn ($carline, $code) => $code === $row->cct_code
                        ? KanbanBarcode::circuit($carline, $code, $row->issue, $qty, $nys)
                        : KanbanBarcode::drawing($carline, $code, $row->issue, $qty, $nys));

                    if ($row->qrcode_shikake !== null && $row->qrcode_shikake !== '') {
                        $this->check($hasil, 'assy_schedule_circuit', $row, 'qrcode_shikake', fn ($carline, $code) => KanbanBarcode::drawing(
                            $carline, $code, $row->issue, $qty, $nys
                        ));
                    }
                }
            }, 'k.id', 'id');

        return $hasil;
    }

    private function rows(string $table, string $master, string $foreignKey, array $extra)
    {
        return DB::table("{$table} as k")
            ->leftJoin("{$master} as m", 'm.id', '=', "k.{$foreignKey}")
            ->select(array_merge(
                ['k.id', 'k.barcode_kanban', 'k.issue', 'k.qty_kanban', 'k.nomor_urut', 'k.release_date', 'm.carline'],
                $extra
            ))
            ->when($this->option('since'), fn ($q, $since) => $q->where('k.release_date', '>=', $since));
    }

    /**
     * Periksa satu kolom barcode: hitung barcode seharusnya, simpan bila --fix,
     * dan catat kanban yang tidak dikenali atau tetap tidak terbaca STO.
     */
    private function check(array &$hasil, string $table, object $row, string $column, callable $format): void
    {
        $hasil['diperiksa']++;
        $current = (string) $row->{$column};

        $parsed = KanbanBarcode::parse($current, trim((string) $row->carline), $row->issue, (int) $row->qty_kanban, (int) $row->nomor_urut);
        if ($parsed === null) {
            $this->sample($hasil, 'tidak_dikenali', [$row->id, $column, $row->release_date, $current]);
            return;
        }

        $target = $format(...$parsed);

        if ($problem = KanbanBarcode::stoProblem($target)) {
            $this->sample($hasil, 'tidak_terbaca', [$row->id, $column, $row->release_date, $target, $problem]);
        }

        if ($target === $current) {
            return;
        }

        $this->sample($hasil, 'diubah', [$row->id, $column, $row->release_date, $current, $target]);

        if ($this->option('fix')) {
            DB::table($table)->where('id', $row->id)->update([$column => $target]);
        }
    }

    private function emptyResult(): array
    {
        return [
            'diperiksa' => 0,
            'diubah' => ['total' => 0, 'baris' => []],
            'tidak_dikenali' => ['total' => 0, 'baris' => []],
            'tidak_terbaca' => ['total' => 0, 'baris' => []],
        ];
    }

    private function sample(array &$hasil, string $key, array $baris): void
    {
        $hasil[$key]['total']++;
        if (count($hasil[$key]['baris']) < $this->limit) {
            $hasil[$key]['baris'][] = $baris;
        }
    }

    private function report(array $hasil): void
    {
        $this->line(sprintf(
            '  Barcode diperiksa : %d      %s : %d      Tidak dikenali : %d      Tetap tidak terbaca STO : %d',
            $hasil['diperiksa'],
            $this->option('fix') ? 'Diperbarui' : 'Perlu diubah',
            $hasil['diubah']['total'],
            $hasil['tidak_dikenali']['total'],
            $hasil['tidak_terbaca']['total']
        ));

        $sections = [
            'diubah' => ['Perubahan', ['ID', 'Kolom', 'Release', 'Sebelum', 'Sesudah']],
            'tidak_dikenali' => ['Barcode tidak dikenali (carline master berubah / format lama), tidak disentuh', ['ID', 'Kolom', 'Release', 'Barcode']],
            'tidak_terbaca' => ['Tetap tidak terbaca STO setelah diselaraskan', ['ID', 'Kolom', 'Release', 'Barcode', 'Sebab']],
        ];

        foreach ($sections as $key => [$title, $headers]) {
            if ($hasil[$key]['total'] === 0) {
                continue;
            }

            $this->newLine();
            $this->line("  {$title}:");
            $this->table($headers, $hasil[$key]['baris']);

            if ($hasil[$key]['total'] > count($hasil[$key]['baris'])) {
                $this->line('  ... dan ' . ($hasil[$key]['total'] - count($hasil[$key]['baris'])) . ' baris lain.');
            }
        }
    }
}
