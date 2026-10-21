<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingStage;
use App\Models\MasterConveyor;
use App\Services\Listing\ListingFetchResult;
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
 *   - kunci ada di API, isi berbeda -> DIPERBARUI di baris yang sama (menangkap revisi)
 *   - kunci tidak ada di API        -> DIHAPUS      (menangkap pembatalan)
 *   - kunci baru                    -> DITAMBAHKAN
 *
 * Kuncinya id SIREP pada tanggal × conveyor (SirepListingAdapter::keyOf), sehingga
 * perubahan seq, qty, maupun assy code memperbarui baris yang sama — id baris staging
 * yang dirujuk jadwal tetap, dan tidak ada listing dobel walaupun jadwalnya sudah
 * terkunci.
 *
 * Rekonsiliasi dihitung lebih dulu sebagai rencana (reconcile), baru kemudian ditulis.
 * Rencana yang sama dipakai pemeriksaan kesegaran layar verifikasi (compare) untuk
 * mengetahui apakah staging masih sama dengan SIREP tanpa menulis apa pun.
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
 * pernah dihapus maupun dipindah; kondisi itu dilaporkan sebagai peringatan agar
 * operator tahu PPC mengubah sesuatu yang sudah telanjur diproses.
 *
 * Listing manual (source = 'manual', diinput lewat menu Listing Manual) berada di
 * luar rekonsiliasi: tidak pernah dicocokkan, diperbarui, maupun dihapus oleh
 * sinkronisasi — SIREP memang tidak mengenalnya.
 */
class ListingSyncService
{
    /** Field yang menentukan isi jadwal; perubahan di sini pada baris terkunci dilaporkan. */
    private const SCHEDULE_FIELDS = ['seq', 'assycode', 'assy', 'qty'];

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

        return $this->applyFetched($fetched, $from, $to);
    }

    /**
     * Rencana rekonsiliasi untuk hasil pengambilan, TANPA menulis apa pun.
     *
     * @return array<string, mixed>  Lihat reconcile()
     */
    public function compare(ListingFetchResult $fetched, Carbon $from, Carbon $to): array
    {
        return $this->reconcile(
            $this->keyIncoming($fetched),
            $from->copy()->startOfDay(),
            $to->copy()->endOfDay(),
            $fetched->scopeConveyors
        );
    }

    /**
     * Tulis hasil pengambilan ke staging.
     *
     * Dipisah dari syncListingData supaya pemeriksaan kesegaran yang sudah mengambil
     * data SIREP tidak perlu mengambilnya untuk kedua kalinya.
     *
     * @return array<string, mixed>
     */
    public function applyFetched(ListingFetchResult $fetched, Carbon $from, Carbon $to): array
    {
        $from     = $from->copy()->startOfDay();
        $to       = $to->copy()->endOfDay();
        $incoming = $this->keyIncoming($fetched);
        $errors   = $fetched->errors;
        $plan     = null;

        try {
            DB::transaction(function () use ($incoming, $from, $to, $fetched, &$plan) {
                // Dua sinkronisasi yang berjalan bersamaan untuk conveyor yang sama (mis.
                // generate otomatis dan pemeriksaan layar verifikasi) sama-sama melihat
                // baris yang belum ada lalu sama-sama menambahkannya. Kunci baris conveyor
                // lebih dulu — urut id seperti generate dan verify — dan baca staging
                // sesudahnya, supaya yang datang belakangan melihat hasil yang pertama.
                $this->lockConveyors($incoming, $fetched->scopeConveyors);

                $plan = $this->reconcile($incoming, $from, $to, $fetched->scopeConveyors);

                foreach ($plan['delete'] as $stage) {
                    $stage->delete();
                }

                foreach ($plan['update'] as $u) {
                    $u['stage']->fill($u['attributes'] + ['synced_at' => now()])->save();
                }

                // Penanda lembur saja: bukan revisi isi, jadi synced_at tidak disentuh —
                // synced_at menandai kapan isi listing terakhir berubah.
                foreach ($plan['overtime'] as $o) {
                    $o['stage']->is_overtime = $o['value'];
                    $o['stage']->save();
                }

                // Baris baru ditulis massal: pada rentang yang belum pernah ditarik hampir
                // semua baris masuk lewat jalur ini, dan satu INSERT per baris berarti
                // ribuan perjalanan ke database.
                $now     = now();
                $newRows = array_map(fn ($attributes) => $attributes + [
                    'synced_at'  => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $plan['insert']);

                foreach (array_chunk($newRows, 500) as $chunk) {
                    ListingStage::insert($chunk);
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

        $inserted = count($plan['insert']);
        $updated  = count($plan['update']) + count($plan['overtime']);
        $deleted  = count($plan['delete']);

        Log::info('Listing sync completed', [
            'source'   => $this->source->name(),
            'range'    => $from->format('Y-m-d') . '..' . $to->format('Y-m-d'),
            'inserted' => $inserted,
            'updated'  => $updated,
            'deleted'  => $deleted,
            'skipped'  => $plan['skipped'],
            'warnings' => $plan['warnings'],
        ]);

        return [
            'success'       => true,
            'source'        => $this->source->name(),
            'total_records' => count($incoming),
            'synced'        => $inserted,
            'inserted'      => $inserted,
            'updated'       => $updated,
            'deleted'       => $deleted,
            'skipped'       => $plan['skipped'],
            'conveyors'     => $fetched->scopeConveyors,
            'warnings'      => $plan['warnings'],
            'errors'        => $errors,
            'changes'       => $this->describe($plan),
            'notes'         => $this->describeOvertime($plan),
            'date_range'    => [
                'from' => $from->format('Y-m-d'),
                'to'   => $to->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Apakah sebuah rencana mengubah isi staging.
     *
     * @param  array<string, mixed>  $plan  Hasil compare()/reconcile()
     */
    public function planChangesStaging(array $plan): bool
    {
        return $plan['insert'] !== [] || $plan['update'] !== [] || $plan['delete'] !== [] || $plan['overtime'] !== [];
    }

    /**
     * Uraian perubahan isi listing untuk operator, satu kalimat per baris listing.
     *
     * @param  array<string, mixed>  $plan  Hasil compare()/reconcile()
     * @return array<int, string>
     */
    public function describe(array $plan): array
    {
        $baris = [];

        foreach ($plan['update'] as $u) {
            $rincian = [];

            foreach ($u['fields'] as $field => [$lama, $baru]) {
                $rincian[] = $this->fieldLabel($field) . ' ' . $this->nilai($lama) . ' → ' . $this->nilai($baru);
            }

            $baris[] = $this->labelListing($u['stage']->assy, $u['stage']) . ': ' . implode(', ', $rincian)
                . ($u['protected'] ? ' (jadwalnya sudah terkunci — tidak ikut berubah)' : '');
        }

        foreach ($plan['insert'] as $attributes) {
            $baris[] = $this->labelListing($attributes['assy'] ?? '-', $attributes)
                . ': listing baru, qty ' . (int) ($attributes['qty'] ?? 0) . ', seq ' . (int) ($attributes['seq'] ?? 0);
        }

        foreach ($plan['delete'] as $stage) {
            $baris[] = $this->labelListing($stage->assy, $stage) . ': '
                . ($plan['moved'][$stage->id] ?? 'dibatalkan di SIREP') . ', qty ' . (int) $stage->qty;
        }

        return $baris;
    }

    /**
     * Uraian perubahan penanda lembur saja — tidak memengaruhi isi jadwal.
     *
     * @param  array<string, mixed>  $plan  Hasil compare()/reconcile()
     * @return array<int, string>
     */
    public function describeOvertime(array $plan): array
    {
        $baris = [];

        foreach ($plan['overtime'] as $o) {
            $baris[] = 'Penanda lembur SIREP ' . trim((string) $o['stage']->conveyor) . ' '
                . Carbon::parse($o['stage']->listing_date_time)->format('d/m/Y') . ': '
                . ($o['value'] ? 'tidak → ya' : 'ya → tidak');
        }

        return array_values(array_unique($baris));
    }

    /**
     * Baris masuk di-key dengan kunci identitas.
     *
     * @return array<string, array<string, mixed>>
     */
    private function keyIncoming(ListingFetchResult $fetched): array
    {
        $incoming = [];

        foreach ($fetched->rows as $attributes) {
            $incoming[$this->adapter->keyOf($attributes)] = $attributes;
        }

        return $incoming;
    }

    /**
     * Hitung rencana rekonsiliasi.
     *
     * @param  array<string, array<string, mixed>>  $incoming
     * @param  array<int, string>|null  $scopeConveyors
     * @return array{
     *     insert: array<int, array<string, mixed>>,
     *     update: array<int, array{stage: ListingStage, attributes: array, fields: array, protected: bool}>,
     *     overtime: array<int, array{stage: ListingStage, value: bool}>,
     *     delete: array<int, ListingStage>,
     *     moved: array<int, string>,
     *     warnings: array<int, string>,
     *     skipped: int
     * }
     */
    private function reconcile(array $incoming, Carbon $from, Carbon $to, ?array $scopeConveyors): array
    {
        ['keyed' => $existing, 'duplicates' => $duplicates] = $this->existingInScope($from, $to, $scopeConveyors);

        $plan = [
            'insert'   => [],
            'update'   => [],
            'overtime' => [],
            'delete'   => [],
            'moved'    => [],
            'warnings' => [],
            'skipped'  => 0,
        ];

        $baru = [];

        foreach ($incoming as $key => $attributes) {
            $stage = $existing[$key] ?? null;

            if ($stage === null) {
                $baru[$key] = $attributes;
                continue;
            }

            $fields = $this->adapter->changedFields($stage, $attributes);

            if ($fields !== []) {
                $protected = $this->isProtected($stage);

                $plan['update'][] = [
                    'stage'      => $stage,
                    'attributes' => $attributes,
                    'fields'     => $fields,
                    'protected'  => $protected,
                ];

                if ($protected && array_intersect(array_keys($fields), self::SCHEDULE_FIELDS)) {
                    $plan['warnings'][] = $this->labelListing($stage->assy, $stage)
                        . ' diubah di SIREP setelah jadwalnya diverifikasi ('
                        . collect($fields)->only(self::SCHEDULE_FIELDS)
                            ->map(fn ($v, $f) => $this->fieldLabel($f) . ' ' . $this->nilai($v[0]) . ' → ' . $this->nilai($v[1]))
                            ->implode(', ')
                        . '). Jadwal yang sudah diverifikasi tidak ikut berubah — unverify bila kanbannya belum dicetak.';
                }

                continue;
            }

            if ($this->adapter->overtimeChanged($stage, $attributes)) {
                $plan['overtime'][] = ['stage' => $stage, 'value' => (bool) ($attributes['is_overtime'] ?? false)];
                continue;
            }

            $plan['skipped']++;
        }

        $sudahDiputuskan = [];
        $this->planMoves($baru, $plan, $sudahDiputuskan);

        // Salinan dobel dari masa sinkron berkunci seq. Kembarannya sudah dicocokkan di
        // atas, jadi salinan ini bukan pembatalan — buang bila belum terlindungi.
        foreach ($duplicates as $stage) {
            if (isset($sudahDiputuskan[$stage->id])) {
                continue;
            }

            $sudahDiputuskan[$stage->id] = true;

            if ($this->isProtected($stage)) {
                $plan['warnings'][] = $this->labelListing($stage->assy, $stage)
                    . ' tercatat dobel di staging dan salinannya sudah terkunci — tidak dihapus. Mohon diperiksa.';
                continue;
            }

            $plan['delete'][]             = $stage;
            $plan['moved'][$stage->id]    = 'salinan dobel dihapus';
        }

        if (config('sirep.reconcile.delete_missing', true)) {
            $missing = array_filter(
                $existing,
                fn ($stage, $key) => !isset($incoming[$key]) && !isset($sudahDiputuskan[$stage->id]),
                ARRAY_FILTER_USE_BOTH
            );

            $this->planDeletions($missing, $existing, $incoming, $plan);
        }

        return $plan;
    }

    /**
     * Baris baru yang id SIREP-nya sudah ada di staging pada tanggal/conveyor lain berarti
     * listing itu dipindah PPC. Baris lama yang belum terlindungi dihapus dan baris baru
     * ditambahkan. Bila baris lama sudah terlindungi, baris baru TIDAK ditambahkan —
     * listing itu sudah dijadwalkan dan dikunci di tempat lamanya, dan menambahkannya lagi
     * berarti memproduksinya dua kali.
     *
     * @param  array<string, array<string, mixed>>  $baru     Baris masuk tanpa padanan kunci
     * @param  array<int, bool>                     $sudahDiputuskan  Diisi id staging yang sudah diputuskan nasibnya
     */
    private function planMoves(array $baru, array &$plan, array &$sudahDiputuskan): void
    {
        $perSumber = [];

        foreach ($baru as $attributes) {
            $id = (int) ($attributes['id_listing'] ?? 0);

            if ($id > 0) {
                $perSumber[$this->adapter->sourceOf($attributes)][] = $id;
            }
        }

        $lamaPerId = [];

        foreach ($perSumber as $sumber => $ids) {
            $rows = ListingStage::where('source', $sumber)->whereIn('id_listing', array_unique($ids))->get();

            foreach ($rows as $stage) {
                $lamaPerId[$sumber . '|' . $stage->id_listing][] = $stage;
            }
        }

        foreach ($baru as $attributes) {
            $id   = (int) ($attributes['id_listing'] ?? 0);
            $lama = $id > 0 ? ($lamaPerId[$this->adapter->sourceOf($attributes) . '|' . $id] ?? []) : [];

            if ($lama === []) {
                $plan['insert'][] = $attributes;
                continue;
            }

            $tujuan     = Carbon::parse($attributes['listing_date_time'])->format('d/m/Y')
                . ' conveyor ' . trim((string) $attributes['conveyor']);
            $terlindung = array_filter($lama, fn ($stage) => $this->isProtected($stage));

            foreach ($lama as $stage) {
                $sudahDiputuskan[$stage->id] = true;
            }

            if ($terlindung !== []) {
                $asal = reset($terlindung);
                $plan['warnings'][] = $this->labelListing($asal->assy, $asal)
                    . " dipindah PPC ke {$tujuan} setelah jadwalnya diverifikasi. Listing tetap di tempat lama "
                    . 'agar tidak dijadwalkan dua kali — unverify jadwal lama bila pemindahan ini harus diikuti.';
                continue;
            }

            foreach ($lama as $stage) {
                $plan['delete'][]          = $stage;
                $plan['moved'][$stage->id] = "dipindah PPC ke {$tujuan}";
            }

            $plan['insert'][] = $attributes;
        }
    }

    /**
     * Tentukan baris yang sudah tidak ada di sumber dan boleh dihapus.
     *
     * @param  array<string, ListingStage>  $missing
     * @param  array<string, ListingStage>  $existing
     * @param  array<string, array<string, mixed>>  $incoming
     */
    private function planDeletions(array $missing, array $existing, array $incoming, array &$plan): void
    {
        if ($missing === []) {
            return;
        }

        // Kumpulkan calon penghapusan per conveyor agar pengaman dapat dievaluasi
        // per conveyor, bukan sekaligus untuk seluruh rentang.
        $candidates = [];

        foreach ($missing as $stage) {
            $candidates[trim((string) $stage->conveyor)][] = $stage;
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

        $maxShrink       = (int) config('sirep.reconcile.max_shrink_percent', 50);
        $skipDeleteEmpty = (bool) config('sirep.reconcile.skip_delete_on_empty', true);

        foreach ($candidates as $conveyor => $stages) {
            $incomingCount = $incomingPerConveyor[$conveyor] ?? 0;
            $existingCount = $existingPerConveyor[$conveyor] ?? 0;

            // Pengaman 1 — API tidak mengembalikan apa pun untuk conveyor ini,
            // padahal staging berisi data. Lebih mungkin gangguan daripada
            // pembatalan massal.
            if ($skipDeleteEmpty && $incomingCount === 0 && $existingCount > 0) {
                $plan['warnings'][] = "Conveyor {$conveyor}: API tidak mengembalikan baris apa pun, "
                    . "{$existingCount} baris staging dibiarkan (tidak dihapus). Mohon diperiksa manual.";
                continue;
            }

            // Pengaman 2 — penyusutan jumlah baris tidak wajar.
            if ($existingCount > 0 && $maxShrink > 0) {
                $shrinkPercent = (int) round((1 - ($incomingCount / $existingCount)) * 100);

                if ($shrinkPercent > $maxShrink) {
                    $plan['warnings'][] = "Conveyor {$conveyor}: jumlah baris dari API turun {$shrinkPercent}% "
                        . "({$existingCount} -> {$incomingCount}), melewati batas {$maxShrink}%. "
                        . 'Penghapusan dibatalkan untuk conveyor ini.';
                    continue;
                }
            }

            foreach ($stages as $stage) {
                if ($this->isProtected($stage)) {
                    $plan['warnings'][] = $this->labelListing($stage->assy, $stage)
                        . ' sudah dibatalkan di SIREP tetapi jadwalnya sudah terkunci atau kanbannya '
                        . 'sudah tercetak — tidak dihapus.';
                    continue;
                }

                $plan['delete'][] = $stage;
            }
        }
    }

    /**
     * Baris staging dalam lingkup sinkronisasi, di-key dengan kunci identitas
     * yang sama seperti baris masuk.
     *
     * Satu kunci bisa muncul lebih dari sekali pada data lama: sinkron berkunci seq
     * menambahkan baris baru setiap kali PPC mengubah seq listing yang jadwalnya sudah
     * terkunci. Baris yang terlindungi diutamakan sebagai wakil kunci; sisanya
     * dikembalikan sebagai `duplicates`.
     *
     * @param  array<int, string>|null  $scopeConveyors
     * @return array{keyed: array<string, ListingStage>, duplicates: array<int, ListingStage>}
     */
    private function existingInScope(Carbon $from, Carbon $to, ?array $scopeConveyors): array
    {
        // Listing manual tidak ada di SIREP; ikut dicocokkan berarti terhapus sebagai
        // "dibatalkan" atau tertimpa baris SIREP yang kebetulan berkunci sama.
        $query = ListingStage::fromSirep()->whereBetween('listing_date_time', [$from, $to]);

        if ($scopeConveyors !== null) {
            // Conveyor yang gagal diambil tidak masuk lingkup sama sekali.
            if ($scopeConveyors === []) {
                return ['keyed' => [], 'duplicates' => []];
            }

            $query->whereIn(DB::raw('TRIM(conveyor)'), $scopeConveyors);
        }

        $perKunci = [];

        foreach ($query->orderBy('id')->get() as $stage) {
            $perKunci[$this->adapter->keyOfModel($stage)][] = $stage;
        }

        $keyed      = [];
        $duplicates = [];

        foreach ($perKunci as $key => $stages) {
            if (count($stages) > 1) {
                // Wakil kunci: yang terlindungi lebih dulu, lalu yang tertua.
                usort($stages, fn ($a, $b) => [(int) !$this->isProtected($a), $a->id]
                                          <=> [(int) !$this->isProtected($b), $b->id]);
            }

            $keyed[$key] = array_shift($stages);
            array_push($duplicates, ...$stages);
        }

        return ['keyed' => $keyed, 'duplicates' => $duplicates];
    }

    /**
     * Kunci baris master conveyor yang tersentuh sinkronisasi, urut id.
     *
     * @param  array<string, array<string, mixed>>  $incoming
     * @param  array<int, string>|null  $scopeConveyors
     */
    private function lockConveyors(array $incoming, ?array $scopeConveyors): void
    {
        $names = collect($scopeConveyors ?? [])
            ->merge(array_map(fn ($a) => trim((string) $a['conveyor']), $incoming))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return;
        }

        MasterConveyor::whereIn('conveyor', $names)->orderBy('id')->lockForUpdate()->get(['id']);
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
     * "Listing 24012-7YA1B (B3-ENG, 26/10/2026)"
     *
     * @param  ListingStage|array<string, mixed>  $row
     */
    private function labelListing(?string $assy, $row): string
    {
        $conveyor = trim((string) ($row instanceof ListingStage ? $row->conveyor : ($row['conveyor'] ?? '')));
        $tanggal  = $row instanceof ListingStage ? $row->listing_date_time : ($row['listing_date_time'] ?? null);

        return 'Listing ' . ($assy ?: '-') . " ({$conveyor}, "
            . ($tanggal ? Carbon::parse($tanggal)->format('d/m/Y') : '-') . ')';
    }

    private function fieldLabel(string $field): string
    {
        return [
            'seq'      => 'seq',
            'assycode' => 'assy code',
            'assy'     => 'assy',
            'qty'      => 'qty',
            'carline'  => 'carline',
            'snp'      => 'SNP',
            'snpa'     => 'SNP alternatif',
            'plt'      => 'palet',
            'mode'     => 'pengiriman',
        ][$field] ?? $field;
    }

    private function nilai($value): string
    {
        return $value === null || $value === '' ? '-' : (string) $value;
    }

    /**
     * Statistik sinkronisasi.
     *
     * Pada mode API tidak ada tabel sumber yang dapat dibaca langsung, sehingga
     * `latest_in_listing` hanya terisi bila jalur database lama masih tersedia.
     */
    public function getSyncStatistics()
    {
        // Statistik sinkronisasi: listing manual bukan hasil sinkron, jadi tidak dihitung.
        $latestInStage = ListingStage::fromSirep()->orderBy('listing_date_time', 'desc')->first();
        $totalInStage  = ListingStage::fromSirep()->count();

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
