<?php

namespace App\Services;

use App\Models\BalanceResetLog;
use App\Models\BalanceResetSnapshot;
use App\Models\MasterConveyor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Menyamakan saldo kanban sistem ini dengan sistem pembanding (v1).
 *
 * ── Kenapa menyalin, bukan menjalankan ulang ────────────────────────────
 * Saldo (`sisa`) adalah hasil TIGA sumber perubahan:
 *   1. generate kanban  -> kanban_generation_log  (punya delta, bisa dibalik)
 *   2. penambahan manual-> addition_log_*         (di luar ledger generate)
 *   3. defect           -> defect_log_*           (di luar ledger generate)
 * Hanya sumber pertama yang bisa dihasilkan ulang oleh proses generate.
 * Karena itu menjalankan ulang generate tidak akan pernah mereproduksi saldo
 * yang sudah dipengaruhi penambahan manual — satu-satunya cara yang tepat
 * adalah menyalin nilai saldonya langsung.
 *
 * ── Kenapa nomor urut memakai nilai TERTINGGI ───────────────────────────
 * `last_nomor_urut` ikut tercetak ke barcode. Menyalin nilai yang lebih kecil
 * dari sistem pembanding akan membuat nomor yang sudah pernah diterbitkan di
 * sini dipakai ulang, sehingga muncul dua kartu berbeda dengan barcode sama.
 * Karena itu yang dipakai adalah MAX(sini, pembanding): tidak ada nomor yang
 * terpakai dua kali, dan kedua sistem tetap sejalan sesudahnya.
 */
class BalanceResetService
{
    private const CONN = 'mysql_reference';

    /**
     * Status koneksi ke database pembanding.
     */
    public function referenceStatus(): array
    {
        $db = config('database.connections.' . self::CONN . '.database');

        if (empty($db)) {
            return [
                'ok'       => false,
                'database' => null,
                'message'  => 'Database pembanding belum diatur. Isi DB_REFERENCE_DATABASE pada file .env.',
            ];
        }

        try {
            DB::connection(self::CONN)->getPdo();

            // Pastikan tabel yang dibutuhkan memang ada, bukan sekadar koneksi hidup.
            foreach (['kanban_balance_circuit', 'kanban_balance_shikake', 'master_conveyor'] as $t) {
                DB::connection(self::CONN)->table($t)->limit(1)->get();
            }

            return ['ok' => true, 'database' => $db, 'message' => 'Terhubung.'];
        } catch (\Throwable $e) {
            return [
                'ok'       => false,
                'database' => $db,
                'message'  => 'Tidak dapat membaca database pembanding: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Conveyor yang ada di KEDUA sistem — hanya ini yang bisa disamakan.
     */
    public function selectableConveyors(): array
    {
        // Shift tidak lagi tersimpan per conveyor di sini, jadi yang dibandingkan
        // dengan sistem pembanding hanya kapasitas.
        $local = MasterConveyor::orderBy('conveyor')->get(['id', 'conveyor', 'capacity']);

        if (!$this->referenceStatus()['ok']) {
            return $local->map(fn ($c) => [
                'id' => $c->id, 'conveyor' => $c->conveyor,
                'capacity' => (int) $c->capacity,
                'ref_capacity' => null, 'available' => false,
            ])->all();
        }

        $ref = DB::connection(self::CONN)->table('master_conveyor')
            ->get(['id', 'conveyor', 'capacity'])
            ->keyBy('conveyor');

        return $local->map(function ($c) use ($ref) {
            $r = $ref->get($c->conveyor);

            return [
                'id'           => $c->id,
                'conveyor'     => $c->conveyor,
                'capacity'     => (int) $c->capacity,
                'ref_capacity' => $r ? (int) $r->capacity : null,
                'available'    => (bool) $r,
            ];
        })->all();
    }

    /**
     * Saldo sistem pembanding pada AKHIR tanggal acuan.
     *
     * Nilai saat ini dikurangi seluruh perubahan yang terjadi SESUDAH tanggal itu.
     * Ketiga log mencatat before/after, jadi setiap perubahan punya delta yang jelas:
     *   generate  -> kolom delta
     *   addition  -> +qty_addition
     *   defect    -> -qty_defect
     *
     * @return array<int,array{sisa:int,nomor_urut:int}> dipetakan per master_id
     */
    private function referenceBalanceAt(string $type, array $refConveyorIds, string $cutoff): array
    {
        $isCircuit = $type === 'circuit';
        $table     = $isCircuit ? 'kanban_balance_circuit' : 'kanban_balance_shikake';
        $masterCol = $isCircuit ? 'master_circuit_id' : 'master_shikake_id';
        $addTable  = $isCircuit ? 'addition_log_circuit' : 'addition_log_shikake';
        $defTable  = $isCircuit ? 'defect_log_circuit' : 'defect_log_shikake';

        $conn = DB::connection(self::CONN);

        $rows = $conn->table($table)
            ->whereIn('conveyor_id', $refConveyorIds)
            ->get(['conveyor_id', $masterCol . ' as master_id', 'sisa', 'last_nomor_urut']);

        // Perubahan dari generate sesudah tanggal acuan.
        $genAfter = $conn->table('kanban_generation_log')
            ->whereIn('conveyor_id', $refConveyorIds)
            ->where('item_type', $type)
            ->whereDate('schedule_date', '>', $cutoff)
            ->groupBy('conveyor_id', 'master_id')
            ->get([
                'conveyor_id', 'master_id',
                DB::raw('SUM(delta) as d'),
            ])
            ->keyBy(fn ($r) => $r->conveyor_id . ':' . $r->master_id);

        // Penambahan manual sesudah tanggal acuan.
        $addAfter = $conn->table($addTable)
            ->whereIn('conveyor_id', $refConveyorIds)
            ->whereDate('addition_date', '>', $cutoff)
            ->groupBy('conveyor_id', $masterCol)
            ->get([
                'conveyor_id', $masterCol . ' as master_id',
                DB::raw('SUM(qty_addition) as d'),
            ])
            ->keyBy(fn ($r) => $r->conveyor_id . ':' . $r->master_id);

        // Defect sesudah tanggal acuan (mengurangi saldo).
        $defAfter = $conn->table($defTable)
            ->whereIn('conveyor_id', $refConveyorIds)
            ->whereDate('defect_date', '>', $cutoff)
            ->groupBy('conveyor_id', $masterCol)
            ->get([
                'conveyor_id', $masterCol . ' as master_id',
                DB::raw('SUM(qty_defect) as d'),
            ])
            ->keyBy(fn ($r) => $r->conveyor_id . ':' . $r->master_id);

        // Nomor urut tertinggi yang SUDAH diterbitkan sampai tanggal acuan.
        $kanbanTable = $isCircuit ? 'assy_schedule_circuit' : 'assy_schedule_shikake';
        $nomorAt = $conn->table($kanbanTable . ' as k')
            ->join('assy_schedule as s', 's.id', '=', 'k.assy_schedule_id')
            ->whereIn('s.conveyor_id', $refConveyorIds)
            ->whereDate('s.schedule', '<=', $cutoff)
            ->groupBy('s.conveyor_id', 'k.' . $masterCol)
            ->get([
                's.conveyor_id',
                'k.' . $masterCol . ' as master_id',
                DB::raw('MAX(CAST(k.nomor_urut AS UNSIGNED)) as nu'),
            ])
            ->keyBy(fn ($r) => $r->conveyor_id . ':' . $r->master_id);

        $out = [];

        foreach ($rows as $r) {
            $key = $r->conveyor_id . ':' . $r->master_id;

            $sisa = (int) $r->sisa
                - (int) ($genAfter[$key]->d ?? 0)
                - (int) ($addAfter[$key]->d ?? 0)
                + (int) ($defAfter[$key]->d ?? 0);

            $out[$key] = [
                'conveyor_id' => (int) $r->conveyor_id,
                'master_id'   => (int) $r->master_id,
                // Saldo tidak pernah negatif; nilai minus berarti ada mutasi di luar
                // ketiga log (mis. koreksi manual di database) — dijepit ke 0 dan
                // dilaporkan lewat penanda `clamped` agar terlihat saat pratinjau.
                'sisa'        => max(0, $sisa),
                'clamped'     => $sisa < 0,
                'nomor_urut'  => (int) ($nomorAt[$key]->nu ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Pratinjau: apa yang akan berubah bila penyamaan dijalankan.
     */
    public function preview(array $conveyorIds, string $cutoff): array
    {
        $status = $this->referenceStatus();

        if (!$status['ok']) {
            return ['ok' => false, 'message' => $status['message'], 'conveyors' => []];
        }

        $cutoff = Carbon::parse($cutoff)->toDateString();

        $local = MasterConveyor::whereIn('id', $conveyorIds)->orderBy('conveyor')->get();

        if ($local->isEmpty()) {
            return ['ok' => false, 'message' => 'Tidak ada conveyor yang dipilih.', 'conveyors' => []];
        }

        // Conveyor dicocokkan berdasarkan NAMA, bukan id — id di kedua sistem
        // tidak dijamin sama.
        $refConveyors = DB::connection(self::CONN)->table('master_conveyor')
            ->whereIn('conveyor', $local->pluck('conveyor')->all())
            ->get(['id', 'conveyor'])
            ->keyBy('conveyor');

        $result   = [];
        $totals   = ['circuit' => 0, 'shikake' => 0, 'kanban' => 0, 'schedule' => 0, 'printed' => 0];
        $warnings = [];

        foreach (['circuit', 'shikake'] as $type) {
            $refIds = $local->map(fn ($c) => $refConveyors[$c->conveyor]->id ?? null)->filter()->unique()->values()->all();

            if (empty($refIds)) {
                continue;
            }

            $refBalance[$type] = $this->referenceBalanceAt($type, $refIds, $cutoff);
        }

        foreach ($local as $cv) {
            $refCv = $refConveyors[$cv->conveyor] ?? null;

            if (!$refCv) {
                $warnings[] = "Conveyor {$cv->conveyor} tidak ada di sistem pembanding — dilewati.";
                continue;
            }

            $row = [
                'conveyor_id' => $cv->id,
                'conveyor'    => $cv->conveyor,
                'items'       => [],
            ];

            foreach (['circuit', 'shikake'] as $type) {
                $row['items'][$type] = $this->diffForConveyor(
                    $type, $cv->id, (int) $refCv->id, $refBalance[$type] ?? []
                );
                $totals[$type] += $row['items'][$type]['akan_diubah'];
            }

            $purge = $this->purgeScope($cv->id, $cutoff);
            $row['purge'] = $purge;
            $totals['kanban']   += $purge['kanban'];
            $totals['schedule'] += $purge['schedule'];
            $totals['printed']  += $purge['printed'];

            $result[] = $row;
        }

        $clamped = 0;
        foreach ($result as $r) {
            foreach ($r['items'] as $it) {
                $clamped += $it['dijepit_nol'];
            }
        }

        if ($clamped > 0) {
            $warnings[] = "{$clamped} item saldonya menjadi negatif saat dihitung mundur ke tanggal acuan, "
                . 'dan dijepit ke 0. Biasanya karena item tersebut baru dibuat setelah tanggal acuan, '
                . 'atau ada koreksi saldo di luar riwayat generate/addition/defect. '
                . 'Pilih tanggal acuan yang lebih baru bila angka ini besar.';
        }

        if ($totals['printed'] > 0) {
            $warnings[] = "{$totals['printed']} kartu kanban yang AKAN DIHAPUS sudah pernah dicetak. "
                . 'Kartu fisiknya tidak lagi dikenali sistem setelah penyamaan dan perlu ditarik atau dicetak ulang.';
        }

        return [
            'ok'          => true,
            'cutoff'      => $cutoff,
            'reference'   => $status['database'],
            'conveyors'   => $result,
            'totals'      => $totals,
            'warnings'    => $warnings,
        ];
    }

    /**
     * Bandingkan saldo lokal dengan saldo acuan untuk satu conveyor.
     */
    private function diffForConveyor(string $type, int $localCvId, int $refCvId, array $refBalance): array
    {
        $isCircuit = $type === 'circuit';
        $table     = $isCircuit ? 'kanban_balance_circuit' : 'kanban_balance_shikake';
        $masterCol = $isCircuit ? 'master_circuit_id' : 'master_shikake_id';

        $localRows = DB::table($table)->where('conveyor_id', $localCvId)
            ->get(['id', $masterCol . ' as master_id', 'sisa', 'last_nomor_urut']);

        $sisaBeda = 0; $nuBeda = 0; $tidakAdaAcuan = 0; $clamped = 0; $akanDiubah = 0;
        $sisaLokal = 0; $sisaAcuan = 0;

        foreach ($localRows as $r) {
            $ref = $refBalance[$refCvId . ':' . $r->master_id] ?? null;
            $sisaLokal += (int) $r->sisa;

            if (!$ref) {
                $tidakAdaAcuan++;
                continue;
            }

            $sisaAcuan += $ref['sisa'];

            $sisaGeser = (int) $r->sisa !== $ref['sisa'];
            $nuGeser   = max((int) $r->last_nomor_urut, $ref['nomor_urut']) !== (int) $r->last_nomor_urut;

            if ($sisaGeser) {
                $sisaBeda++;
            }

            if ($nuGeser) {
                $nuBeda++;
            }

            // Satu baris ditulis sekali walaupun kedua kolomnya bergeser —
            // hitungan ini harus cocok dengan jumlah baris yang benar-benar
            // di-update oleh writeBalance(), bukan penjumlahan dua kolom.
            if ($sisaGeser || $nuGeser) {
                $akanDiubah++;
            }

            if (!empty($ref['clamped'])) {
                $clamped++;
            }
        }

        return [
            'total'           => $localRows->count(),
            'akan_diubah'     => $akanDiubah,
            'sisa_beda'       => $sisaBeda,
            'nomor_urut_naik' => $nuBeda,
            'tanpa_acuan'     => $tidakAdaAcuan,
            'dijepit_nol'     => $clamped,
            'sisa_lokal'      => $sisaLokal,
            'sisa_acuan'      => $sisaAcuan,
        ];
    }

    /**
     * Kanban & jadwal terverifikasi SESUDAH tanggal acuan — yang akan dibersihkan.
     */
    private function purgeScope(int $conveyorId, string $cutoff): array
    {
        $scheduleIds = DB::table('assy_schedule')
            ->where('conveyor_id', $conveyorId)
            ->whereDate('schedule', '>', $cutoff)
            ->where('is_lock', '<>', 0)
            ->pluck('id');

        if ($scheduleIds->isEmpty()) {
            return ['schedule' => 0, 'kanban' => 0, 'printed' => 0, 'schedule_ids' => []];
        }

        $circuit = DB::table('assy_schedule_circuit')->whereIn('assy_schedule_id', $scheduleIds);
        $shikake = DB::table('assy_schedule_shikake')->whereIn('assy_schedule_id', $scheduleIds);

        return [
            'schedule'     => $scheduleIds->count(),
            'kanban'       => (clone $circuit)->count() + (clone $shikake)->count(),
            'printed'      => (clone $circuit)->where('is_printed', 1)->count()
                            + (clone $shikake)->where('is_printed', 1)->count(),
            'schedule_ids' => $scheduleIds->all(),
        ];
    }

    /**
     * Jalankan penyamaan. Seluruhnya dalam satu transaksi — gagal di tengah
     * berarti tidak ada yang berubah sama sekali.
     */
    public function apply(array $conveyorIds, string $cutoff, ?string $note = null): array
    {
        $preview = $this->preview($conveyorIds, $cutoff);

        if (!$preview['ok']) {
            return ['success' => false, 'message' => $preview['message']];
        }

        $cutoff = Carbon::parse($cutoff)->toDateString();

        $local = MasterConveyor::whereIn('id', $conveyorIds)->orderBy('conveyor')->get();
        $refConveyors = DB::connection(self::CONN)->table('master_conveyor')
            ->whereIn('conveyor', $local->pluck('conveyor')->all())
            ->get(['id', 'conveyor'])->keyBy('conveyor');

        $refBalance = [];
        $refIds = $local->map(fn ($c) => $refConveyors[$c->conveyor]->id ?? null)->filter()->unique()->values()->all();

        foreach (['circuit', 'shikake'] as $type) {
            $refBalance[$type] = empty($refIds) ? [] : $this->referenceBalanceAt($type, $refIds, $cutoff);
        }

        try {
            DB::beginTransaction();

            $log = BalanceResetLog::create([
                'cutoff_date'  => $cutoff,
                'conveyor_ids' => $local->pluck('id')->all(),
                'reference_db' => config('database.connections.' . self::CONN . '.database'),
                'note'         => $note,
                'created_by'   => Auth::id(),
            ]);

            $counts = ['circuit' => 0, 'shikake' => 0, 'kanban' => 0, 'schedule' => 0];

            foreach ($local as $cv) {
                $refCv = $refConveyors[$cv->conveyor] ?? null;

                if (!$refCv) {
                    continue;
                }

                // 1) Bersihkan kanban & buka verifikasi jadwal sesudah tanggal acuan.
                $purge = $this->purgeScope($cv->id, $cutoff);

                if (!empty($purge['schedule_ids'])) {
                    $counts['kanban'] += DB::table('assy_schedule_circuit')
                        ->whereIn('assy_schedule_id', $purge['schedule_ids'])->delete();
                    $counts['kanban'] += DB::table('assy_schedule_shikake')
                        ->whereIn('assy_schedule_id', $purge['schedule_ids'])->delete();

                    DB::table('kanban_generation_log')
                        ->where('conveyor_id', $cv->id)
                        ->whereDate('schedule_date', '>', $cutoff)
                        ->delete();

                    $counts['schedule'] += DB::table('assy_schedule')
                        ->whereIn('id', $purge['schedule_ids'])
                        ->update(['is_lock' => 0, 'verified_at' => null, 'verified_by' => null]);
                }

                // 2) Tulis saldo dari acuan.
                foreach (['circuit', 'shikake'] as $type) {
                    $counts[$type] += $this->writeBalance(
                        $type, $cv->id, (int) $refCv->id, $refBalance[$type] ?? [], $log->id
                    );
                }
            }

            $log->update([
                'circuits_updated'     => $counts['circuit'],
                'shikakes_updated'     => $counts['shikake'],
                'kanban_deleted'       => $counts['kanban'],
                'schedules_unverified' => $counts['schedule'],
            ]);

            DB::commit();

            Log::info('Penyamaan saldo kanban dijalankan', [
                'reset_log_id' => $log->id,
                'cutoff'       => $cutoff,
                'conveyors'    => $local->pluck('conveyor')->all(),
                'counts'       => $counts,
            ]);

            return [
                'success' => true,
                'log_id'  => $log->id,
                'counts'  => $counts,
                'message' => sprintf(
                    'Penyamaan selesai. Saldo circuit: %d, shikake: %d, kanban dihapus: %d, jadwal dibuka: %d.',
                    $counts['circuit'], $counts['shikake'], $counts['kanban'], $counts['schedule']
                ),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Penyamaan saldo kanban gagal', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Penyamaan gagal, tidak ada perubahan yang disimpan: ' . $e->getMessage()];
        }
    }

    /**
     * Tulis saldo satu conveyor, sambil merekam nilai lama untuk pembatalan.
     */
    private function writeBalance(string $type, int $localCvId, int $refCvId, array $refBalance, int $logId): int
    {
        $isCircuit = $type === 'circuit';
        $table     = $isCircuit ? 'kanban_balance_circuit' : 'kanban_balance_shikake';
        $masterCol = $isCircuit ? 'master_circuit_id' : 'master_shikake_id';

        $rows = DB::table($table)->where('conveyor_id', $localCvId)
            ->get(['id', $masterCol . ' as master_id', 'sisa', 'last_nomor_urut']);

        $changed   = 0;
        $snapshots = [];
        $now       = now();

        foreach ($rows as $r) {
            $ref = $refBalance[$refCvId . ':' . $r->master_id] ?? null;

            if (!$ref) {
                continue; // tidak ada acuan — biarkan apa adanya
            }

            $sisaBaru = $ref['sisa'];
            // Lihat catatan kelas: nomor urut TIDAK PERNAH mundur.
            $nuBaru   = max((int) $r->last_nomor_urut, $ref['nomor_urut']);

            if ($sisaBaru === (int) $r->sisa && $nuBaru === (int) $r->last_nomor_urut) {
                continue;
            }

            $snapshots[] = [
                'reset_log_id'      => $logId,
                'item_type'         => $type,
                'conveyor_id'       => $localCvId,
                'master_id'         => $r->master_id,
                'sisa_before'       => (int) $r->sisa,
                'sisa_after'        => $sisaBaru,
                'nomor_urut_before' => (int) $r->last_nomor_urut,
                'nomor_urut_after'  => $nuBaru,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];

            DB::table($table)->where('id', $r->id)->update([
                'sisa'            => $sisaBaru,
                'last_nomor_urut' => $nuBaru,
                'updated_at'      => $now,
            ]);

            $changed++;
        }

        foreach (array_chunk($snapshots, 500) as $chunk) {
            BalanceResetSnapshot::insert($chunk);
        }

        return $changed;
    }

    /**
     * Batalkan satu penyamaan: kembalikan saldo ke nilai sebelum ditulis.
     *
     * Kanban yang sudah dihapus TIDAK dibuat ulang — jadwalnya tinggal
     * diverifikasi kembali seperti biasa.
     */
    public function undo(int $logId): array
    {
        $log = BalanceResetLog::find($logId);

        if (!$log) {
            return ['success' => false, 'message' => 'Riwayat penyamaan tidak ditemukan.'];
        }

        if ($log->status === 'undone') {
            return ['success' => false, 'message' => 'Penyamaan ini sudah dibatalkan sebelumnya.'];
        }

        try {
            DB::beginTransaction();

            $restored = 0;

            foreach ($log->snapshots()->cursor() as $s) {
                $table = $s->item_type === 'circuit' ? 'kanban_balance_circuit' : 'kanban_balance_shikake';
                $col   = $s->item_type === 'circuit' ? 'master_circuit_id' : 'master_shikake_id';

                $restored += DB::table($table)
                    ->where('conveyor_id', $s->conveyor_id)
                    ->where($col, $s->master_id)
                    ->update([
                        'sisa'            => $s->sisa_before,
                        'last_nomor_urut' => $s->nomor_urut_before,
                        'updated_at'      => now(),
                    ]);
            }

            $log->update([
                'status'    => 'undone',
                'undone_at' => now(),
                'undone_by' => Auth::id(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Pembatalan selesai. {$restored} baris saldo dikembalikan ke nilai semula. "
                    . 'Kanban yang terhapus tidak dibuat ulang — verifikasi kembali jadwalnya bila diperlukan.',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            return ['success' => false, 'message' => 'Pembatalan gagal: ' . $e->getMessage()];
        }
    }
}
