<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Pemeriksa keutuhan saldo kanban.
 *
 * Saldo (`sisa`) seharusnya selalu sama dengan saldo pembuka ditambah seluruh
 * mutasi yang tercatat sejak itu:
 *
 *     sisa = sisa_before baris ledger PERTAMA
 *          + Σ kanban_generation_log.delta
 *          + Σ addition.qty  −  Σ defect.qty     (sejak tanggal ledger pertama)
 *
 * Titik awalnya sengaja diambil dari ledger, bukan dari nol: mutasi yang terjadi
 * sebelum ledger ada tidak punya jejak yang bisa diverifikasi, dan memasukkannya
 * hanya menghasilkan selisih palsu.
 *
 * Bila tidak sama, ada mutasi yang terjadi tanpa jejak — misalnya reset saldo yang
 * tidak ikut membersihkan ledger, atau kanban terhapus tanpa pembalikan saldo.
 * Kelas ini hanya MEMBACA; penyelarasan dilakukan lewat repair() yang dipanggil
 * eksplisit.
 */
class BalanceIntegrityService
{
    /** @var array<string, array<string, string>> */
    private const SKEMA = [
        'circuit' => [
            'balance'  => 'kanban_balance_circuit',
            'key'      => 'master_circuit_id',
            'addition' => 'addition_log_circuit',
            'defect'   => 'defect_log_circuit',
            'master'   => 'master_circuit',
            'kanban'   => 'assy_schedule_circuit',
        ],
        'shikake' => [
            'balance'  => 'kanban_balance_shikake',
            'key'      => 'master_shikake_id',
            'addition' => 'addition_log_shikake',
            'defect'   => 'defect_log_shikake',
            'master'   => 'master_shikake',
            'kanban'   => 'assy_schedule_shikake',
        ],
    ];

    /**
     * Bandingkan saldo tersimpan dengan hasil penjumlahan mutasi.
     *
     * @param  string    $type        'circuit' atau 'shikake'
     * @param  int|null  $conveyorId  Batasi ke satu conveyor
     * @return array{
     *     type: string, diperiksa: int, menyimpang: int, selisih_total: int,
     *     baris: array<int, array<string, mixed>>
     * }
     */
    public function check(string $type, ?int $conveyorId = null): array
    {
        $s = self::SKEMA[$type] ?? throw new \InvalidArgumentException("Tipe tidak dikenal: {$type}");
        $key = $s['key'];

        // Titik awal perbandingan adalah baris ledger PERTAMA tiap item: nilai
        // `sisa_before`-nya diperlakukan sebagai saldo pembuka yang sudah benar.
        // Mutasi sebelum ledger ada tidak dapat diverifikasi dan karena itu tidak
        // ikut dihitung — memasukkannya akan menghasilkan selisih palsu.
        $awal = "(SELECT l.conveyor_id, l.master_id, l.schedule_date AS tgl_awal, l.sisa_before AS saldo_awal
                  FROM kanban_generation_log l
                  JOIN (SELECT conveyor_id, master_id, MIN(CONCAT(schedule_date, '-', LPAD(shift,2,'0'))) AS k
                        FROM kanban_generation_log WHERE item_type = '{$type}'
                        GROUP BY conveyor_id, master_id) m
                    ON m.conveyor_id = l.conveyor_id AND m.master_id = l.master_id
                   AND m.k = CONCAT(l.schedule_date, '-', LPAD(l.shift,2,'0'))
                  WHERE l.item_type = '{$type}')";

        $filter = $conveyorId ? ' AND b.conveyor_id = ' . (int) $conveyorId : '';

        $rows = DB::select("
            SELECT b.conveyor_id, mc.conveyor, b.{$key} AS master_id, b.sisa AS tersimpan,
                   aw.saldo_awal, aw.tgl_awal,
                   COALESCE(g.d, 0)  AS dari_generate,
                   COALESCE(ad.a, 0) AS dari_addition,
                   COALESCE(df.f, 0) AS dari_defect
            FROM {$s['balance']} b
            JOIN master_conveyor mc ON mc.id = b.conveyor_id
            LEFT JOIN {$awal} aw ON aw.conveyor_id = b.conveyor_id AND aw.master_id = b.{$key}
            LEFT JOIN (SELECT conveyor_id, master_id, SUM(delta) AS d FROM kanban_generation_log
                       WHERE item_type = '{$type}' GROUP BY conveyor_id, master_id) g
                   ON g.conveyor_id = b.conveyor_id AND g.master_id = b.{$key}
            LEFT JOIN (SELECT x.conveyor_id, x.{$key} AS mid, SUM(x.qty_addition) AS a
                       FROM {$s['addition']} x
                       JOIN {$awal} w ON w.conveyor_id = x.conveyor_id AND w.master_id = x.{$key}
                       WHERE x.addition_date >= w.tgl_awal
                       GROUP BY x.conveyor_id, x.{$key}) ad
                   ON ad.conveyor_id = b.conveyor_id AND ad.mid = b.{$key}
            LEFT JOIN (SELECT x.conveyor_id, x.{$key} AS mid, SUM(x.qty_defect) AS f
                       FROM {$s['defect']} x
                       JOIN {$awal} w ON w.conveyor_id = x.conveyor_id AND w.master_id = x.{$key}
                       WHERE x.defect_date >= w.tgl_awal
                       GROUP BY x.conveyor_id, x.{$key}) df
                   ON df.conveyor_id = b.conveyor_id AND df.mid = b.{$key}
            WHERE 1 = 1 {$filter}
            ORDER BY mc.conveyor, b.{$key}
        ");

        $baris = [];
        $menyimpang = 0;
        $selisih = 0;
        $diperiksa = 0;
        $tanpaLedger = 0;

        foreach ($rows as $r) {
            // Item yang belum pernah masuk ledger tidak punya dasar pembanding.
            if ($r->tgl_awal === null) {
                $tanpaLedger++;
                continue;
            }

            $diperiksa++;

            $seharusnya = (int) $r->saldo_awal + (int) $r->dari_generate
                        + (int) $r->dari_addition - (int) $r->dari_defect;
            $beda = (int) $r->tersimpan - $seharusnya;

            if ($beda === 0) {
                continue;
            }

            $menyimpang++;
            $selisih += $beda;

            $baris[] = [
                'conveyor_id'   => (int) $r->conveyor_id,
                'conveyor'      => $r->conveyor,
                'master_id'     => (int) $r->master_id,
                'tersimpan'     => (int) $r->tersimpan,
                'seharusnya'    => $seharusnya,
                'selisih'       => $beda,
                'saldo_awal'    => (int) $r->saldo_awal,
                'sejak'         => $r->tgl_awal,
                'dari_generate' => (int) $r->dari_generate,
                'dari_addition' => (int) $r->dari_addition,
                'dari_defect'   => (int) $r->dari_defect,
            ];
        }

        return [
            'type'          => $type,
            'diperiksa'     => $diperiksa,
            'tanpa_ledger'  => $tanpaLedger,
            'menyimpang'    => $menyimpang,
            'selisih_total' => $selisih,
            'baris'         => $baris,
        ];
    }

    /**
     * Titik putus carry-over: `sisa_before` sebuah generate tidak sama dengan
     * `sisa_after` generate sebelumnya untuk item yang sama.
     *
     * Ini melengkapi check(): check() melihat total, sedangkan ini menunjukkan
     * KAPAN saldo terputus sehingga penyebabnya dapat ditelusuri.
     *
     * @return array<int, array<string, mixed>>
     */
    public function carryOverBreaks(string $type, ?int $conveyorId = null, int $limit = 100): array
    {
        $s = self::SKEMA[$type] ?? throw new \InvalidArgumentException("Tipe tidak dikenal: {$type}");

        $filter = $conveyorId ? 'AND g.conveyor_id = ' . (int) $conveyorId : '';

        $rows = DB::select("
            SELECT * FROM (
                SELECT g.conveyor_id, mc.conveyor, g.master_id, g.schedule_date, g.shift,
                       g.sisa_before, g.sisa_after,
                       LAG(g.sisa_after)    OVER w AS after_sebelumnya,
                       LAG(g.schedule_date) OVER w AS tgl_sebelumnya
                FROM kanban_generation_log g
                JOIN master_conveyor mc ON mc.id = g.conveyor_id
                WHERE g.item_type = ? {$filter}
                WINDOW w AS (PARTITION BY g.conveyor_id, g.master_id ORDER BY g.schedule_date, g.shift)
            ) t
            WHERE after_sebelumnya IS NOT NULL AND sisa_before <> after_sebelumnya
            ORDER BY schedule_date DESC, conveyor, master_id
            LIMIT {$limit}
        ", [$type]);

        return array_map(fn ($r) => (array) $r, $rows);
    }

    /** Barcode kanban yang dipakai lebih dari satu kali — tanda nomor urut pernah di-reset. */
    public function duplicateBarcodes(string $type, int $limit = 20): array
    {
        $s = self::SKEMA[$type] ?? throw new \InvalidArgumentException("Tipe tidak dikenal: {$type}");

        $rows = DB::table($s['kanban'])
            ->select('barcode_kanban', DB::raw('COUNT(*) AS jumlah'), DB::raw('MIN(release_date) AS pertama'), DB::raw('MAX(release_date) AS terakhir'))
            ->groupBy('barcode_kanban')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('jumlah')
            ->limit($limit)
            ->get();

        $total = DB::table(DB::raw("(SELECT barcode_kanban FROM {$s['kanban']} GROUP BY barcode_kanban HAVING COUNT(*) > 1) x"))
            ->count();

        return ['total' => $total, 'contoh' => $rows->map(fn ($r) => (array) $r)->all()];
    }

    /**
     * Selaraskan saldo tersimpan ke hasil penjumlahan mutasi.
     *
     * Dipakai sesudah penyebab penyimpangan diketahui dan diperbaiki. Tidak
     * menyentuh `last_nomor_urut` — menurunkannya akan membuat barcode kanban
     * bertabrakan dengan yang sudah tercetak.
     *
     * @return array{diperbaiki: int, selisih_total: int}
     */
    public function repair(string $type, ?int $conveyorId = null): array
    {
        $s = self::SKEMA[$type];
        $hasil = $this->check($type, $conveyorId);

        DB::transaction(function () use ($hasil, $s) {
            foreach ($hasil['baris'] as $b) {
                DB::table($s['balance'])
                    ->where('conveyor_id', $b['conveyor_id'])
                    ->where($s['key'], $b['master_id'])
                    ->update(['sisa' => $b['seharusnya'], 'updated_at' => now()]);
            }
        });

        return ['diperbaiki' => $hasil['menyimpang'], 'selisih_total' => $hasil['selisih_total']];
    }
}
