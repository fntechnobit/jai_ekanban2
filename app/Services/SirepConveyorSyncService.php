<?php

namespace App\Services;

use App\Models\MasterConveyor;
use App\Services\Listing\SirepApiClient;
use App\Services\Schedule\ShiftCapacityCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sinkronisasi daftar conveyor dari API SIREP ke master lokal.
 *
 * SIREP adalah pemilik daftar conveyor. Sinkronisasi melakukan tiga hal:
 *
 *   TAMBAH     conveyor yang ada di API tetapi belum ada di sini
 *   PERBARUI   nama dan kapasitas conveyor yang sudah ada
 *   NONAKTIF   conveyor lokal yang tidak lagi muncul di API
 *
 * Yang dinonaktifkan tidak dihapus: jadwal dan kanban lamanya harus tetap dapat
 * ditelusuri. Ia hanya berhenti ikut dijadwalkan dan diverifikasi.
 *
 * Pencocokan memakai `sirep_conveyor_id` bila sudah pernah tercatat — id itu stabil
 * meski PPC mengganti nama conveyor. Untuk data lama yang belum punya id, dipakai
 * nama (atau `sirep_conveyor_code` bila diisi manual) sekali saja, lalu id-nya
 * disimpan supaya sinkronisasi berikutnya tidak lagi bergantung pada nama.
 */
class SirepConveyorSyncService
{
    public function __construct(
        private SirepApiClient $client,
        private ShiftCapacityCalculator $calculator,
    ) {
    }

    /**
     * @param  bool  $apply  false = pratinjau saja, tidak ada yang ditulis
     * @return array{
     *     success: bool, message: string, applied: bool,
     *     rows: array<int, array<string, mixed>>,
     *     ditambah: int, diperbarui: int, dinonaktifkan: int, tanpa_kapasitas: int
     * }
     */
    public function sync(bool $apply = false): array
    {
        try {
            $apiConveyors = $this->client->fetchConveyors();
        } catch (\Throwable $e) {
            Log::error('Sinkronisasi conveyor SIREP gagal', ['error' => $e->getMessage()]);

            return $this->gagal('Tidak dapat mengambil daftar conveyor dari SIREP: ' . $e->getMessage());
        }

        // Pengaman: respons kosong hampir pasti gangguan API, bukan "semua conveyor
        // dihapus". Menonaktifkan seluruh master karenanya akan menghentikan produksi.
        if (empty($apiConveyors)) {
            return $this->gagal('API SIREP tidak mengembalikan satu conveyor pun. Sinkronisasi dibatalkan agar master tidak ikut dikosongkan.');
        }

        $rows = [];
        $ditambah = $diperbarui = $tanpaKapasitas = 0;
        $idTersentuh = [];

        $jalankan = function () use ($apiConveyors, $apply, &$rows, &$ditambah, &$diperbarui, &$tanpaKapasitas, &$idTersentuh) {
            foreach ($apiConveyors as $item) {
                $nama    = trim((string) ($item['name'] ?? ''));
                $sirepId = isset($item['id']) ? (int) $item['id'] : null;

                if ($nama === '') {
                    continue;
                }

                $normal   = $item['normal_capacity']   ?? null;
                $overtime = $item['overtime_capacity'] ?? null;
                $conveyor = $this->cocokkan($sirepId, $nama);

                if ($normal === null) {
                    $tanpaKapasitas++;
                }

                // ── Conveyor baru ────────────────────────────────────────────
                if (!$conveyor) {
                    $ditambah++;
                    $rows[] = $this->baris($nama, null, $normal, $overtime, null, 'baru — akan ditambahkan', 'baru');

                    if ($apply) {
                        $baru = new MasterConveyor();
                        $baru->conveyor          = $nama;
                        $baru->sirep_conveyor_id = $sirepId;
                        $baru->is_active         = true;
                        $baru->deactivated_at    = null;
                        $baru->capacity          = $normal !== null ? (int) $normal : null;
                        $baru->overtime_capacity = $overtime !== null ? (int) $overtime : null;
                        $baru->capacity_synced_at = $normal !== null ? now() : null;
                        $baru->created_by        = Auth::id();
                        $baru->save();

                        $idTersentuh[] = $baru->id;
                    }

                    continue;
                }

                $idTersentuh[] = $conveyor->id;

                // ── Conveyor yang sudah ada ──────────────────────────────────
                $catatan = [];

                if ((int) ($conveyor->sirep_conveyor_id ?? 0) !== (int) $sirepId) {
                    $catatan[] = 'id SIREP dicatat';
                }

                if (trim((string) $conveyor->conveyor) !== $nama) {
                    $catatan[] = "nama '{$conveyor->conveyor}' -> '{$nama}'";
                }

                if (!$conveyor->is_active) {
                    $catatan[] = 'diaktifkan kembali';
                }

                $kapasitasLama = $conveyor->capacity !== null ? (int) $conveyor->capacity : null;

                if ($normal !== null && $kapasitasLama !== (int) $normal) {
                    $catatan[] = 'kapasitas ' . ($kapasitasLama ?? 'kosong') . ' -> ' . (int) $normal;
                }

                if ($normal !== null && $overtime !== null) {
                    $hitunganKami = $this->calculator->calculateOvertimeCapacity((int) $normal);

                    if ((int) $overtime !== $hitunganKami) {
                        // Informatif saja: overtime_capacity SIREP tidak dipakai sebagai batas CO5.
                        $catatan[] = "over SIREP {$overtime} vs hitungan kami {$hitunganKami}";
                    }
                }

                $berubah = !empty($catatan);

                if ($berubah) {
                    $diperbarui++;
                }

                $rows[] = $this->baris(
                    $nama,
                    $conveyor->conveyor,
                    $normal,
                    $overtime,
                    $kapasitasLama,
                    $catatan ? implode('; ', $catatan) : 'sama',
                    $berubah ? 'berubah' : 'sama'
                );

                if ($apply) {
                    $conveyor->conveyor          = $nama;
                    $conveyor->sirep_conveyor_id = $sirepId;
                    $conveyor->is_active         = true;
                    $conveyor->deactivated_at    = null;

                    if ($normal !== null) {
                        $conveyor->capacity           = (int) $normal;
                        $conveyor->overtime_capacity  = $overtime !== null ? (int) $overtime : null;
                        $conveyor->capacity_synced_at = now();
                    }

                    $conveyor->updated_by = Auth::id() ?? $conveyor->updated_by;
                    $conveyor->save();
                }
            }
        };

        // ── Conveyor lokal yang tidak ada lagi di API ────────────────────────
        $namaApi = collect($apiConveyors)
            ->map(fn ($i) => trim((string) ($i['name'] ?? '')))
            ->filter()
            ->values();

        $idApi = collect($apiConveyors)
            ->map(fn ($i) => isset($i['id']) ? (int) $i['id'] : null)
            ->filter()
            ->values();

        $hilang = MasterConveyor::where('is_active', true)
            ->where(function ($q) use ($namaApi, $idApi) {
                $q->where(function ($q2) use ($idApi) {
                    $q2->whereNotNull('sirep_conveyor_id')->whereNotIn('sirep_conveyor_id', $idApi->all());
                })->orWhere(function ($q2) use ($namaApi) {
                    $q2->whereNull('sirep_conveyor_id')->whereNotIn('conveyor', $namaApi->all());
                });
            })
            ->get();

        foreach ($hilang as $c) {
            $rows[] = $this->baris(
                null,
                $c->conveyor,
                null,
                null,
                $c->capacity !== null ? (int) $c->capacity : null,
                'tidak ada lagi di SIREP — akan dinonaktifkan',
                'nonaktif'
            );
        }

        if ($apply) {
            DB::transaction(function () use ($jalankan, $hilang) {
                $jalankan();

                foreach ($hilang as $c) {
                    $c->is_active      = false;
                    $c->deactivated_at = now();
                    $c->updated_by     = Auth::id() ?? $c->updated_by;
                    $c->save();
                }
            });

            Log::info('Daftar conveyor disinkronkan dari SIREP', [
                'ditambah'      => $ditambah,
                'diperbarui'    => $diperbarui,
                'dinonaktifkan' => $hilang->count(),
                'oleh'          => Auth::id(),
            ]);
        } else {
            $jalankan();
        }

        return [
            'success'         => true,
            'message'         => $this->pesan($apply, $ditambah, $diperbarui, $hilang->count(), $tanpaKapasitas),
            'applied'         => $apply,
            'rows'            => $rows,
            'ditambah'        => $ditambah,
            'diperbarui'      => $diperbarui,
            'dinonaktifkan'   => $hilang->count(),
            'tanpa_kapasitas' => $tanpaKapasitas,
        ];
    }

    /**
     * Cocokkan satu conveyor API ke master.
     *
     * Urutannya: id SIREP (paling stabil) → kode manual → nama. Conveyor nonaktif
     * ikut dicari agar yang muncul kembali di API dapat diaktifkan lagi, bukan
     * ditambahkan sebagai duplikat.
     */
    private function cocokkan(?int $sirepId, string $nama): ?MasterConveyor
    {
        if ($sirepId) {
            $byId = MasterConveyor::where('sirep_conveyor_id', $sirepId)->first();

            if ($byId) {
                return $byId;
            }
        }

        return MasterConveyor::whereNull('sirep_conveyor_id')
            ->where(function ($q) use ($nama) {
                $q->whereRaw('TRIM(sirep_conveyor_code) = ?', [$nama])
                    ->orWhereRaw('TRIM(conveyor) = ?', [$nama]);
            })
            ->first();
    }

    /** @return array<string, mixed> */
    private function baris(?string $sirepName, ?string $lokal, $normal, $overtime, ?int $kapasitasLama, string $status, string $state): array
    {
        return [
            'sirep_name'        => $sirepName,
            'conveyor'          => $lokal,
            'normal_capacity'   => $normal !== null ? (int) $normal : null,
            'overtime_capacity' => $overtime !== null ? (int) $overtime : null,
            'capacity_lama'     => $kapasitasLama,
            'status'            => $status,
            'state'             => $state,
        ];
    }

    private function pesan(bool $apply, int $tambah, int $ubah, int $nonaktif, int $kosong): string
    {
        $kata = $apply ? 'Sinkronisasi selesai' : 'Pratinjau';
        $p = "{$kata}: {$tambah} conveyor baru, {$ubah} diperbarui, {$nonaktif} dinonaktifkan.";

        if ($kosong > 0) {
            $p .= " {$kosong} conveyor kapasitasnya masih kosong di SIREP dan belum bisa dijadwalkan.";
        }

        if ($apply) {
            $p .= ' Jadwal yang sudah dibuat tidak ikut berubah.';
        }

        return $p;
    }

    /** @return array<string, mixed> */
    private function gagal(string $pesan): array
    {
        return [
            'success'         => false,
            'message'         => $pesan,
            'applied'         => false,
            'rows'            => [],
            'ditambah'        => 0,
            'diperbarui'      => 0,
            'dinonaktifkan'   => 0,
            'tanpa_kapasitas' => 0,
        ];
    }
}
