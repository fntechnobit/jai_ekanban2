<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingStage;
use App\Services\Listing\ListingSourceInterface;
use App\Services\Listing\SirepListingAdapter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sinkronisasi listing dari SIREP ke listing_stage.
 *
 * ── Perbedaan dengan jai_ekanban ─────────────────────────────────────────────
 *
 * jai_ekanban membaca database SIREP lama dan bekerja dengan pola "tambah saja":
 * baris yang kombinasinya sudah ada di staging dilewati, bukan diperbarui. Pola itu
 * tidak dapat mendeteksi revisi maupun pembatalan.
 *
 * jai_ekanban2 membaca REST API SIREP. Karena API belum menyediakan `updated_at`
 * maupun penanda pembatalan, sinkronisasi di sini bekerja sebagai REKONSILIASI:
 * isi staging dicerminkan terhadap respons API untuk rentang yang diminta.
 *
 *   - kunci ada di API, isi sama    -> dibiarkan
 *   - kunci ada di API, isi berbeda -> DIPERBARUI   (menangkap revisi)
 *   - kunci tidak ada di API        -> DIHAPUS      (menangkap pembatalan)
 *   - kunci baru                    -> DITAMBAHKAN
 *
 * Perbandingan isi memakai sidik jari dari field yang dapat direvisi tim PPC
 * (lihat SirepListingAdapter::fingerprintOf).
 *
 * ── Pengaman ────────────────────────────────────────────────────────────────
 *
 * Penghapusan hanya dilakukan untuk conveyor yang datanya berhasil diambil
 * SELURUHNYA. Conveyor yang panggilannya gagal dilewati sepenuhnya, agar gangguan
 * jaringan tidak terbaca sebagai "semua listing dibatalkan". Ditambah dua lapis
 * pengaman lain: respons kosong dan penyusutan jumlah baris yang tidak wajar
 * (lihat config/sirep.php bagian `reconcile`).
 *
 * Baris yang jadwalnya sudah terkunci atau sudah menjadi kanban tercetak tidak
 * pernah dihapus; kondisi itu dilaporkan sebagai peringatan agar operator tahu
 * PPC membatalkan sesuatu yang sudah telanjur diproses.
 */
class ListingSyncService
{
    public function __construct(
        private readonly ListingSourceInterface $source,
        private readonly SirepListingAdapter $adapter,
    ) {
    }

    /**
     * Sinkronkan listing untuk satu rentang tanggal.
     *
     * @param  string  $startDate  Format Y-m-d
     * @param  string  $endDate    Format Y-m-d
     * @param  array<int, string>  $conveyorCodes  Kosong = seluruh conveyor aktif
     * @return array<string, mixed>
     */
    public function syncListingData($startDate, $endDate, array $conveyorCodes = [])
    {
        $from = Carbon::parse($startDate)->startOfDay();
        $to   = Carbon::parse($endDate)->endOfDay();

        // Uji ketersediaan sumber lebih dulu — lebih baik berhenti dengan pesan jelas
        // daripada menghasilkan staging yang terisi separuh.
        $ping = $this->source->ping();

        if (!$ping['ok']) {
            Log::error('Listing source unreachable', ['source' => $this->source->name(), 'message' => $ping['message']]);

            return [
                'success' => false,
                'source'  => $this->source->name(),
                'message' => $ping['message'],
                'errors'  => [$ping['message']],
            ];
        }

        try {
            $fetched = $this->source->fetch($from->format('Y-m-d'), $to->format('Y-m-d'), $conveyorCodes);
        } catch (\Throwable $e) {
            Log::error('Listing fetch failed', ['source' => $this->source->name(), 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'source'  => $this->source->name(),
                'message' => 'Gagal mengambil data listing: ' . $e->getMessage(),
                'errors'  => [$e->getMessage()],
            ];
        }

        // Kelompokkan baris masuk berdasarkan kunci identitas.
        $incoming = [];

        foreach ($fetched->rows as $attributes) {
            $incoming[$this->adapter->keyOf($attributes)] = $attributes;
        }

        $inserted = 0;
        $updated  = 0;
        $deleted  = 0;
        $skipped  = 0;
        $warnings = [];
        $errors   = $fetched->errors;

        try {
            DB::transaction(function () use (
                $incoming, $from, $to, $fetched,
                &$inserted, &$updated, &$deleted, &$skipped, &$warnings
            ) {
                $existing = $this->existingInScope($from, $to, $fetched->scopeConveyors);

                // ── Tambah & perbarui ────────────────────────────────────────
                foreach ($incoming as $key => $attributes) {
                    $stage = $existing[$key] ?? null;

                    if ($stage === null) {
                        ListingStage::create($attributes + ['synced_at' => now()]);
                        $inserted++;
                        continue;
                    }

                    if ($this->adapter->fingerprintOfModel($stage) === $this->adapter->fingerprintOf($attributes)) {
                        $skipped++;
                        continue;
                    }

                    $stage->fill($attributes + ['synced_at' => now()])->save();
                    $updated++;
                }

                // ── Hapus baris yang sudah tidak ada di sumber ───────────────
                if (config('sirep.reconcile.delete_missing', true)) {
                    $outcome = $this->deleteMissing($existing, $incoming, $fetched->scopeConveyors);
                    $deleted  = $outcome['deleted'];
                    $warnings = array_merge($warnings, $outcome['warnings']);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Listing sync failed', ['source' => $this->source->name(), 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'source'  => $this->source->name(),
                'message' => 'Sinkronisasi gagal: ' . $e->getMessage(),
                'errors'  => array_merge($errors, [$e->getMessage()]),
            ];
        }

        Log::info('Listing sync completed', [
            'source'   => $this->source->name(),
            'range'    => $from->format('Y-m-d') . '..' . $to->format('Y-m-d'),
            'inserted' => $inserted,
            'updated'  => $updated,
            'deleted'  => $deleted,
            'skipped'  => $skipped,
        ]);

        return [
            'success'       => true,
            'source'        => $this->source->name(),
            'total_records' => count($incoming),
            'synced'        => $inserted,
            'inserted'      => $inserted,
            'updated'       => $updated,
            'deleted'       => $deleted,
            'skipped'       => $skipped,
            'conveyors'     => $fetched->scopeConveyors,
            'warnings'      => $warnings,
            'errors'        => $errors,
            'date_range'    => [
                'from' => $from->format('Y-m-d'),
                'to'   => $to->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Baris staging dalam lingkup sinkronisasi, di-key dengan kunci identitas
     * yang sama seperti baris masuk.
     *
     * @param  array<int, string>|null  $scopeConveyors
     * @return array<string, ListingStage>
     */
    private function existingInScope(Carbon $from, Carbon $to, ?array $scopeConveyors): array
    {
        $query = ListingStage::whereBetween('listing_date_time', [$from, $to]);

        if ($scopeConveyors !== null) {
            // Conveyor yang gagal diambil tidak masuk lingkup sama sekali.
            if ($scopeConveyors === []) {
                return [];
            }

            $query->whereIn(DB::raw('TRIM(conveyor)'), $scopeConveyors);
        }

        $keyed = [];

        foreach ($query->get() as $stage) {
            $keyed[$this->adapter->keyOf([
                'listing_date_time' => $stage->listing_date_time,
                'conveyor'          => $stage->conveyor,
                'assycode'          => $stage->assycode,
                'seq'               => $stage->seq,
            ])] = $stage;
        }

        return $keyed;
    }

    /**
     * Hapus baris staging yang tidak lagi muncul di sumber.
     *
     * @param  array<string, ListingStage>  $existing
     * @param  array<string, array<string, mixed>>  $incoming
     * @param  array<int, string>|null  $scopeConveyors
     * @return array{deleted: int, warnings: array<int, string>}
     */
    private function deleteMissing(array $existing, array $incoming, ?array $scopeConveyors): array
    {
        $warnings = [];
        $deleted  = 0;

        // Kumpulkan calon penghapusan per conveyor agar pengaman dapat dievaluasi
        // per conveyor, bukan sekaligus untuk seluruh rentang.
        $candidates = [];

        foreach ($existing as $key => $stage) {
            if (isset($incoming[$key])) {
                continue;
            }

            $candidates[trim((string) $stage->conveyor)][] = $stage;
        }

        if ($candidates === []) {
            return ['deleted' => 0, 'warnings' => []];
        }

        $incomingPerConveyor = [];

        foreach ($incoming as $attributes) {
            $conveyor = trim((string) $attributes['conveyor']);
            $incomingPerConveyor[$conveyor] = ($incomingPerConveyor[$conveyor] ?? 0) + 1;
        }

        $existingPerConveyor = [];

        foreach ($existing as $stage) {
            $conveyor = trim((string) $stage->conveyor);
            $existingPerConveyor[$conveyor] = ($existingPerConveyor[$conveyor] ?? 0) + 1;
        }

        $maxShrink        = (int) config('sirep.reconcile.max_shrink_percent', 50);
        $skipDeleteEmpty  = (bool) config('sirep.reconcile.skip_delete_on_empty', true);

        foreach ($candidates as $conveyor => $stages) {
            $incomingCount = $incomingPerConveyor[$conveyor] ?? 0;
            $existingCount = $existingPerConveyor[$conveyor] ?? 0;

            // Pengaman 1 — API tidak mengembalikan apa pun untuk conveyor ini,
            // padahal staging berisi data. Lebih mungkin gangguan daripada
            // pembatalan massal.
            if ($skipDeleteEmpty && $incomingCount === 0 && $existingCount > 0) {
                $warnings[] = "Conveyor {$conveyor}: API tidak mengembalikan baris apa pun, "
                    . "{$existingCount} baris staging dibiarkan (tidak dihapus). Mohon diperiksa manual.";
                continue;
            }

            // Pengaman 2 — penyusutan jumlah baris tidak wajar.
            if ($existingCount > 0 && $maxShrink > 0) {
                $shrinkPercent = (int) round((1 - ($incomingCount / $existingCount)) * 100);

                if ($shrinkPercent > $maxShrink) {
                    $warnings[] = "Conveyor {$conveyor}: jumlah baris dari API turun {$shrinkPercent}% "
                        . "({$existingCount} -> {$incomingCount}), melewati batas {$maxShrink}%. "
                        . 'Penghapusan dibatalkan untuk conveyor ini.';
                    continue;
                }
            }

            foreach ($stages as $stage) {
                if ($this->isProtected($stage)) {
                    $warnings[] = "Listing {$stage->assy} (conveyor {$conveyor}, "
                        . Carbon::parse($stage->listing_date_time)->format('d/m/Y')
                        . ') sudah dibatalkan di SIREP tetapi jadwalnya sudah terkunci atau kanbannya '
                        . 'sudah tercetak — tidak dihapus.';
                    continue;
                }

                $stage->delete();
                $deleted++;
            }
        }

        return ['deleted' => $deleted, 'warnings' => $warnings];
    }

    /**
     * Baris staging dilindungi bila terikat jadwal yang terkunci/terverifikasi
     * ATAU sudah memiliki kanban tercetak.
     *
     * Penjaga kanban mencegah rantai FK ON DELETE CASCADE
     * (listing_stage -> assy_schedule -> assy_schedule_circuit/shikake) menghapus
     * daftar kanban yang sudah dicetak, meskipun penanda kunci jadwalnya tidak konsisten.
     */
    private function isProtected(ListingStage $stage): bool
    {
        return DB::table('assy_schedule')
            ->where('listing_id', $stage->id)
            ->where(function ($q) {
                $q->where('is_lock', '!=', 0)
                    ->orWhereExists(function ($k) {
                        $k->select(DB::raw(1))
                            ->from('assy_schedule_circuit')
                            ->whereColumn('assy_schedule_circuit.assy_schedule_id', 'assy_schedule.id');
                    })
                    ->orWhereExists(function ($k) {
                        $k->select(DB::raw(1))
                            ->from('assy_schedule_shikake')
                            ->whereColumn('assy_schedule_shikake.assy_schedule_id', 'assy_schedule.id');
                    });
            })
            ->exists();
    }

    /**
     * Hapus data listing_stage untuk rentang tanggal tertentu.
     *
     * Baris yang terikat jadwal terkunci atau sudah ber-kanban tetap dilindungi.
     */
    public function deleteListingStageData($startDate, $endDate)
    {
        try {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();

            $protectionFilter = function ($query) {
                $query->select(DB::raw(1))
                    ->from('assy_schedule')
                    ->whereColumn('assy_schedule.listing_id', 'listing_stage.id')
                    ->where(function ($q) {
                        $q->where('assy_schedule.is_lock', '!=', 0)
                            ->orWhereExists(function ($k) {
                                $k->select(DB::raw(1))
                                    ->from('assy_schedule_circuit')
                                    ->whereColumn('assy_schedule_circuit.assy_schedule_id', 'assy_schedule.id');
                            })
                            ->orWhereExists(function ($k) {
                                $k->select(DB::raw(1))
                                    ->from('assy_schedule_shikake')
                                    ->whereColumn('assy_schedule_shikake.assy_schedule_id', 'assy_schedule.id');
                            });
                    });
            };

            $deletedCount = ListingStage::whereBetween('listing_date_time', [$startDate, $endDate])
                ->whereNotExists($protectionFilter)
                ->delete();

            $protectedCount = ListingStage::whereBetween('listing_date_time', [$startDate, $endDate])
                ->whereExists($protectionFilter)
                ->count();

            Log::info("Deleted listing_stage records (protected locked schedules)", [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'deleted_count' => $deletedCount,
                'protected_count' => $protectedCount
            ]);

            return [
                'success' => true,
                'deleted_count' => $deletedCount,
                'protected_count' => $protectedCount,
                'date_range' => [
                    'from' => $startDate->format('Y-m-d'),
                    'to' => $endDate->format('Y-m-d')
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Failed to delete listing_stage data", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to delete listing_stage data: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Statistik sinkronisasi.
     *
     * Pada mode API tidak ada tabel sumber yang dapat dibaca langsung, sehingga
     * `latest_in_listing` hanya terisi bila jalur database lama masih tersedia.
     */
    public function getSyncStatistics()
    {
        $latestInStage = ListingStage::orderBy('listing_date_time', 'desc')->first();
        $totalInStage  = ListingStage::count();

        $latestInListing = null;

        if ($this->source->name() === 'db') {
            try {
                $latest = Listing::orderBy('time', 'desc')->first();
                $latestInListing = $latest?->time;
            } catch (\Throwable $e) {
                Log::warning('Tidak dapat membaca statistik dari database listing', ['error' => $e->getMessage()]);
            }
        }

        return [
            'source'            => $this->source->name(),
            'latest_in_listing' => $latestInListing,
            'latest_in_stage'   => $latestInStage ? $latestInStage->listing_date_time : null,
            'total_in_stage'    => $totalInStage,
        ];
    }
}
