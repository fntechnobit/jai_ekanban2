<?php

namespace App\Services;

use App\Config\ManualListingTemplateConfig;
use App\Models\AssySchedule;
use App\Models\ListingStage;
use App\Models\MasterConveyor;
use App\Services\Schedule\AssyMasterChecker;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;

/**
 * Listing manual: listing yang diinput langsung di e-Kanban, di samping listing SIREP.
 *
 * Disimpan di listing_stage dengan source = 'manual', sehingga generate, verifikasi dan
 * kanban memperlakukannya sama persis dengan listing SIREP — tidak ada jalur kedua:
 *   - urutan alokasi ke cutoff mengikuti `seq`, bercampur dengan listing SIREP hari itu;
 *   - sinkronisasi SIREP tidak pernah memperbarui maupun menghapusnya (ListingSyncService).
 *
 * Listing yang jadwalnya sudah diverifikasi atau kanbannya sudah dibuat dikunci: mengubah
 * atau menghapusnya akan memotong jadwal dan kanban yang sudah berjalan. Unverify dulu.
 *
 * Mengubah atau menghapus listing yang belum terkunci ikut menghapus baris jadwal lamanya,
 * yang sudah basi. Jadwal barunya disusun generate seperti biasa — layar menawarkan
 * generate untuk tanggal × conveyor yang terdampak.
 */
class ManualListingService
{
    /** Kolom listing yang disalin ke baris assy_schedule; perubahannya membuat jadwal basi. */
    private const SCHEDULE_FIELDS = [
        'listing_date_time', 'conveyor', 'assycode', 'assy', 'qty', 'seq', 'plt', 'mode', 'snp', 'snpa',
    ];

    public function __construct(private readonly AssyMasterChecker $masterChecker)
    {
    }

    public function getDatatable(array $filters)
    {
        $kanban = fn (string $table) => "EXISTS (SELECT 1 FROM assy_schedule a
            JOIN {$table} k ON k.assy_schedule_id = a.id WHERE a.listing_id = listing_stage.id)";

        $query = ListingStage::manual()
            ->leftJoin('users AS u', 'u.id', '=', 'listing_stage.created_by')
            ->select('listing_stage.*', 'u.name AS creator_name')
            ->selectRaw('(SELECT COALESCE(SUM(a.qty), 0) FROM assy_schedule a
                WHERE a.listing_id = listing_stage.id) AS scheduled_qty')
            ->selectRaw('(SELECT COALESCE(MAX(a.is_lock), 0) FROM assy_schedule a
                WHERE a.listing_id = listing_stage.id) AS max_lock')
            ->selectRaw("(SELECT GROUP_CONCAT(DISTINCT CONCAT('S', a.shift, ' CO', a.cutoff)
                ORDER BY a.shift, a.cutoff SEPARATOR ', ') FROM assy_schedule a
                WHERE a.listing_id = listing_stage.id) AS slots")
            ->selectRaw("({$kanban('assy_schedule_circuit')} OR {$kanban('assy_schedule_shikake')}) AS has_kanban")
            ->selectRaw('(' . AssyMasterChecker::existsSql(
                'listing_stage.assy',
                '(SELECT mcv.id FROM master_conveyor mcv WHERE mcv.conveyor = listing_stage.conveyor
                    AND mcv.deleted_at IS NULL ORDER BY mcv.is_active DESC LIMIT 1)'
            ) . ') AS has_master');

        if (!empty($filters['start_date'])) {
            $query->where('listing_stage.listing_date_time', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }

        if (!empty($filters['end_date'])) {
            $query->where('listing_stage.listing_date_time', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        if (!empty($filters['conveyor'])) {
            $query->where('listing_stage.conveyor', $filters['conveyor']);
        }

        $query->orderBy('listing_stage.listing_date_time')
            ->orderBy('listing_stage.conveyor')
            ->orderBy('listing_stage.seq')
            ->orderBy('listing_stage.id');

        $user      = Auth::user();
        $canUpdate = $user && $user->hasMenuPermission('listing_manual', 'can_update');
        $canDelete = $user && $user->hasMenuPermission('listing_manual', 'can_delete');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('tanggal', fn ($row) => $row->listing_date_time->format('d-m-Y'))
            ->addColumn('assy_label', function ($row) {
                $label = e($row->assy) . '<div class="small text-muted">' . e($row->assycode) . '</div>';

                if (!$row->has_master) {
                    $label .= '<span class="badge bg-warning text-dark" title="Belum ada master circuit/shikake '
                        . 'untuk assy ini di conveyor ini — kanban tidak keluar. Import master circuit/shikake '
                        . 'dengan kolom assy ini sebelum shift-nya diverifikasi.">Belum ada master</span>';
                }

                return $label;
            })
            ->addColumn('kemasan', function ($row) {
                return '<span class="small text-nowrap">SNP ' . (int) $row->snp . ' · SNPA ' . (int) $row->snpa
                    . ' · PLT ' . (int) $row->plt . ' · ' . e($this->modeLabel($row->mode)) . '</span>';
            })
            ->addColumn('status_jadwal', function ($row) {
                $locked    = (int) $row->max_lock !== 0 || (bool) $row->has_kanban;
                $scheduled = (int) $row->scheduled_qty;

                if ($locked) {
                    $badge = '<span class="badge bg-success"><i class="fa-solid fa-lock"></i> Verified</span>';
                } elseif ($scheduled > 0) {
                    $badge = '<span class="badge bg-info">Terjadwal</span>';
                } else {
                    return '<span class="badge bg-secondary" title="Belum masuk jadwal — jalankan Generate">Belum digenerate</span>';
                }

                $detail = e((string) $row->slots);

                if ($scheduled !== (int) $row->qty) {
                    $detail .= ' · ' . $scheduled . '/' . (int) $row->qty . ' pcs';
                }

                return $badge . '<div class="small text-muted text-nowrap">' . $detail . '</div>';
            })
            ->addColumn('input_by', function ($row) {
                return e($row->creator_name ?? '-')
                    . '<div class="small text-muted text-nowrap">' . $row->created_at?->format('d-m-Y H:i') . '</div>';
            })
            ->addColumn('action', function ($row) use ($canUpdate, $canDelete) {
                if ((int) $row->max_lock !== 0 || (bool) $row->has_kanban) {
                    return '<span class="text-muted" title="Jadwalnya sudah diverifikasi — unverify dulu untuk mengubah atau menghapus">'
                        . '<i class="fa-solid fa-lock"></i></span>';
                }

                $actions = '';

                if ($canUpdate) {
                    $actions .= '<button type="button" class="btn btn-soft-primary btn-sm btn-edit" data-id="' . $row->id . '" title="Edit"><i class="ti ti-pencil"></i></button>';
                }

                if ($canDelete) {
                    $actions .= '<button type="button" class="btn btn-soft-danger btn-sm btn-delete" data-id="' . $row->id . '" title="Hapus"><i class="ti ti-trash"></i></button>';
                }

                return $actions !== '' ? '<div class="btn-group" role="group">' . $actions . '</div>' : '-';
            })
            ->rawColumns(['assy_label', 'kemasan', 'status_jadwal', 'input_by', 'action'])
            ->make(true);
    }

    public function findById($id): ListingStage
    {
        return ListingStage::manual()->findOrFail($id);
    }

    /**
     * @return array{status: string, listing: ListingStage, warnings: array, regenerate: array}
     */
    public function create(array $data): array
    {
        $attributes = $this->prepare($data);
        $this->assertKeyAvailable($attributes);

        return DB::transaction(fn () => $this->persist($attributes));
    }

    /**
     * @return array{status: string, listing: ListingStage, warnings: array, regenerate: array}
     */
    public function update(ListingStage $listing, array $data): array
    {
        $this->assertEditable($listing);

        $attributes = $this->prepare($data);
        $this->assertKeyAvailable($attributes, $listing->id);

        return DB::transaction(fn () => $this->persist($attributes, $listing));
    }

    /**
     * @return array{regenerate: array}
     */
    public function delete(ListingStage $listing): array
    {
        $this->assertEditable($listing);

        $target = $this->target($listing->listing_date_time, $listing->conveyor);

        // FK ON DELETE CASCADE ikut menghapus baris jadwal listing ini (belum terkunci,
        // sudah dipastikan assertEditable).
        $listing->delete();

        return ['regenerate' => [$target]];
    }

    /**
     * Simpan satu listing yang sudah lolos prepare(). Dipakai form dan import.
     * Pemanggil yang menjamin listing lama belum terkunci dan kuncinya tidak bentrok.
     *
     * @return array{status: string, listing: ListingStage, warnings: array, regenerate: array}
     */
    public function persist(array $attributes, ?ListingStage $listing = null): array
    {
        if ($listing === null) {
            $listing = ListingStage::create($attributes + [
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            return [
                'status'     => 'created',
                'listing'    => $listing,
                'warnings'   => $this->warningsFor($listing),
                'regenerate' => [$this->target($listing->listing_date_time, $listing->conveyor)],
            ];
        }

        $before = $this->target($listing->listing_date_time, $listing->conveyor);
        $listing->fill($attributes);

        if (!$listing->isDirty()) {
            return ['status' => 'unchanged', 'listing' => $listing, 'warnings' => [], 'regenerate' => []];
        }

        $scheduleChanged     = $listing->isDirty(self::SCHEDULE_FIELDS);
        $listing->updated_by = Auth::id();
        $listing->save();

        $regenerate = [];

        if ($scheduleChanged) {
            // Baris jadwal lama memegang qty/assy versi sebelum diubah. Dihapus supaya
            // tidak ikut terverifikasi; generate menyusunnya ulang dari nilai baru.
            AssySchedule::where('listing_id', $listing->id)->delete();

            $after      = $this->target($listing->listing_date_time, $listing->conveyor);
            $regenerate = $after == $before ? [$after] : [$before, $after];
        }

        return [
            'status'     => 'updated',
            'listing'    => $listing,
            'warnings'   => $this->warningsFor($listing),
            'regenerate' => $regenerate,
        ];
    }

    /**
     * Ubah input form/Excel menjadi kolom listing_stage.
     *
     * Kolom acuan yang dikosongkan (assy code, carline, SNP, SNPA, PLT, mode) diisi dari
     * listing terakhir assy yang sama — conveyor yang sama didahulukan, listing SIREP
     * didahulukan dari listing manual — supaya operator cukup mengisi tanggal, conveyor,
     * assy, qty dan seq.
     *
     * Assy baru (belum pernah di listing) tidak punya acuan dan belum punya assy code dari
     * SIREP, jadi assy code-nya memakai assy number. Assy code wajib terisi: generate
     * melewati listing yang assy code-nya kosong.
     */
    public function prepare(array $data): array
    {
        $conveyor  = trim((string) $data['conveyor']);
        $assy      = trim((string) $data['assy']);
        $reference = $this->referenceListing($assy, $conveyor);

        $pick = function (string $key) use ($data, $reference) {
            $value = $data[$key] ?? null;

            if ($value === null || (is_string($value) && trim($value) === '')) {
                return $reference?->{$key};
            }

            return is_string($value) ? trim($value) : $value;
        };

        $assycode = trim((string) $pick('assycode'));

        if ($assycode === '') {
            $assycode = $assy;
        }

        $remark = trim((string) ($data['remark'] ?? ''));

        return [
            'id_listing'        => null,
            'source'            => ListingStage::SOURCE_MANUAL,
            'listing_date_time' => Carbon::parse($data['listing_date'])->startOfDay()->format('Y-m-d H:i:s'),
            'conveyor'          => $conveyor,
            'shift'             => 0,
            'assycode'          => $assycode,
            'assy'              => $assy,
            'carline'           => trim((string) $pick('carline')) ?: null,
            'qty'               => (int) $data['qty'],
            'seq'               => (int) $data['seq'],
            'plt'               => (int) ($pick('plt') ?? 0),
            'mode'              => (int) ($pick('mode') ?? 0),
            'snp'               => (int) ($pick('snp') ?? 0),
            'snpa'              => (int) ($pick('snpa') ?? 0),
            'is_overtime'       => false,
            'remark'            => $remark !== '' ? $remark : null,
        ];
    }

    /**
     * Listing manual lain dengan kunci yang sama (tanggal, conveyor, assy code, seq).
     */
    public function findByKey(array $attributes, ?int $ignoreId = null): ?ListingStage
    {
        return ListingStage::manual()
            ->where('listing_date_time', $attributes['listing_date_time'])
            ->where('conveyor', $attributes['conveyor'])
            ->where('assycode', $attributes['assycode'])
            ->where('seq', $attributes['seq'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->first();
    }

    public function keyOf(array $attributes): string
    {
        return implode('|', [
            $attributes['listing_date_time'],
            $attributes['conveyor'],
            $attributes['assycode'],
            $attributes['seq'],
        ]);
    }

    /**
     * Terkunci bila salah satu baris jadwalnya — tanggal mana pun, termasuk hasil pindah
     * tanggal — sudah diverifikasi atau sudah punya kanban.
     */
    public function isLocked(ListingStage $listing): bool
    {
        return DB::table('assy_schedule')
            ->where('listing_id', $listing->id)
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
     * Hal yang tidak menghalangi simpan, tetapi membuat listing ini tidak berbuah
     * seperti yang dibayangkan operator.
     *
     * @return array<int, string>
     */
    public function warningsFor(ListingStage $listing): array
    {
        $warnings = [];
        $conveyor = MasterConveyor::active()->where('conveyor', $listing->conveyor)->first();

        if (!$conveyor || !$conveyor->hasSyncedCapacity()) {
            $warnings[] = "Conveyor {$listing->conveyor} belum punya kapasitas dari SIREP — listing ini "
                . 'dilewati saat generate sampai kapasitasnya tersinkron.';
        }

        if (!$conveyor || $this->masterChecker->missing($conveyor->id, [$listing->assy]) !== []) {
            $warnings[] = "Assy {$listing->assy} belum punya master circuit/shikake di conveyor {$listing->conveyor} — "
                . 'jadwalnya terbentuk, tetapi kanban baru keluar bila master circuit/shikake dengan kolom assy '
                . 'ini sudah diimport SEBELUM shift-nya diverifikasi.';
        }

        $sameSeq = ListingStage::fromSirep()
            ->where('listing_date_time', $listing->listing_date_time)
            ->where('conveyor', $listing->conveyor)
            ->where('seq', $listing->seq)
            ->pluck('assy');

        if ($sameSeq->isNotEmpty()) {
            $warnings[] = "Seq {$listing->seq} juga dipakai listing SIREP (" . $sameSeq->implode(', ')
                . ') pada tanggal & conveyor yang sama — pakai seq lain agar urutan alokasinya jelas.';
        }

        return $warnings;
    }

    /**
     * Pilihan assy untuk satu conveyor: riwayat listing conveyor itu (beserta nilai
     * acuannya untuk mengisi form), lalu assy master circuit/shikake conveyor itu yang
     * belum pernah muncul di listing. Format grup select2.
     */
    public function assyOptions(string $conveyor, ?string $term = null): array
    {
        $term = trim((string) $term);
        $like = '%' . addcslashes($term, '%_\\') . '%';

        $latestIds = ListingStage::query()
            ->where('conveyor', $conveyor)
            ->when($term !== '', fn ($q) => $q->where(function ($w) use ($like) {
                $w->where('assy', 'like', $like)->orWhere('assycode', 'like', $like);
            }))
            ->groupBy('assy')
            ->selectRaw('MAX(id) AS id')
            ->pluck('id');

        $history = ListingStage::whereIn('id', $latestIds)
            ->orderBy('assy')
            ->limit(50)
            ->get(['assy', 'assycode', 'carline', 'snp', 'snpa', 'plt', 'mode']);

        $conveyorId = MasterConveyor::where('conveyor', $conveyor)->value('id');

        $master = $conveyorId === null ? collect() : DB::table('master_assy AS ma')
            ->whereNotNull('ma.assy')
            ->whereNotIn('ma.assy', $history->pluck('assy')->all())
            ->when($term !== '', fn ($q) => $q->where('ma.assy', 'like', $like))
            ->where(function ($q) use ($conveyorId) {
                $q->whereExists(function ($s) use ($conveyorId) {
                    $s->select(DB::raw(1))
                        ->from('master_circuit_assy AS mca')
                        ->join('master_circuit AS mc', 'mc.id', '=', 'mca.master_circuit_id')
                        ->whereColumn('mca.master_assy_id', 'ma.id')
                        ->where('mc.conveyor_id', $conveyorId)
                        ->whereNull('mc.deleted_at');
                })->orWhereExists(function ($s) use ($conveyorId) {
                    $s->select(DB::raw(1))
                        ->from('master_shikake_assy AS msa')
                        ->join('master_shikake AS ms', 'ms.id', '=', 'msa.master_shikake_id')
                        ->whereColumn('msa.master_assy_id', 'ma.id')
                        ->where('ms.conveyor_id', $conveyorId)
                        ->whereNull('ms.deleted_at');
                });
            })
            ->orderBy('ma.assy')
            ->limit(50)
            ->pluck('ma.assy');

        $groups = [];

        if ($history->isNotEmpty()) {
            $groups[] = [
                'text'     => 'Riwayat listing ' . $conveyor,
                'children' => $history->map(fn ($row) => [
                    'id'       => $row->assy,
                    'text'     => $row->assy . ' · ' . $row->assycode,
                    'assycode' => $row->assycode,
                    'carline'  => $row->carline,
                    'snp'      => $row->snp,
                    'snpa'     => $row->snpa,
                    'plt'      => $row->plt,
                    'mode'     => $row->mode,
                ])->values()->all(),
            ];
        }

        if ($master->isNotEmpty()) {
            $groups[] = [
                'text'     => 'Master circuit/shikake (belum pernah di listing)',
                'children' => $master->map(fn ($assy) => ['id' => $assy, 'text' => $assy])->values()->all(),
            ];
        }

        return $groups;
    }

    /**
     * Seluruh listing satu tanggal × conveyor (SIREP dan manual) dalam urutan
     * alokasinya, supaya operator bisa memilih seq yang tepat.
     */
    public function dayListing(string $date, string $conveyor): array
    {
        $day  = Carbon::parse($date);
        $rows = ListingStage::query()
            ->whereBetween('listing_date_time', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->where('conveyor', $conveyor)
            ->orderBy('seq')
            ->orderBy('id_listing')
            ->get(['id', 'source', 'seq', 'assy', 'assycode', 'qty']);

        $master = MasterConveyor::active()->where('conveyor', $conveyor)->first(['capacity', 'overtime_capacity']);

        return [
            'rows'              => $rows->map(fn ($row) => [
                'id'       => $row->id,
                'manual'   => $row->isManual(),
                'seq'      => $row->seq,
                'assy'     => $row->assy,
                'assycode' => $row->assycode,
                'qty'      => $row->qty,
            ])->values()->all(),
            'total_qty'         => (int) $rows->sum('qty'),
            'capacity'          => $master?->capacity,
            'overtime_capacity' => $master?->overtime_capacity,
        ];
    }

    /**
     * Template import sebagai file .xlsx sementara; mengembalikan path-nya.
     * Conveyor dan Mode diberi dropdown dari sheet tersembunyi "Lists".
     */
    public function generateTemplateFile(): string
    {
        $headers   = ManualListingTemplateConfig::getHeaders();
        $widths    = ManualListingTemplateConfig::getColumnWidths();
        $conveyors = MasterConveyor::active()->orderBy('conveyor')->pluck('conveyor')->all();
        $modes     = ['SEA', 'AIR'];

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Listing Manual');

        foreach ($headers as $i => $header) {
            $column = chr(ord('A') + $i);
            $cell   = $sheet->getCell($column . '1');
            $cell->setValue($header);
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB(in_array($header, ['Tanggal', 'Conveyor', 'Assy Number', 'Qty', 'Seq'], true) ? 'FCE4D6' : 'DDEBF7');
            $sheet->getColumnDimension($column)->setWidth($widths[$i] ?? 12);
        }

        $sheet->freezePane('A2');
        $sheet->getStyle('A2:A1001')->getNumberFormat()->setFormatCode('dd-mm-yyyy');

        $lists = new Worksheet($spreadsheet, 'Lists');
        $spreadsheet->addSheet($lists);
        $lists->setCellValue('A1', 'Conveyor');
        $lists->setCellValue('B1', 'Mode');

        foreach ($conveyors as $i => $name) {
            $lists->setCellValueExplicit('A' . ($i + 2), $name, DataType::TYPE_STRING);
        }

        foreach ($modes as $i => $mode) {
            $lists->setCellValue('B' . ($i + 2), $mode);
        }

        $lists->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);

        if ($conveyors !== []) {
            $sheet->setDataValidation('B2:B1001', $this->listValidation(
                'Lists!$A$2:$A$' . (count($conveyors) + 1),
                'Conveyor tidak dikenal',
                'Pilih conveyor dari daftar (conveyor aktif di SIREP).'
            ));
        }

        $sheet->setDataValidation('K2:K1001', $this->listValidation(
            'Lists!$B$2:$B$3',
            'Mode tidak dikenal',
            'Pilih SEA atau AIR, atau kosongkan.'
        ));

        $guide = new Worksheet($spreadsheet, 'Petunjuk');
        $spreadsheet->addSheet($guide);
        $guide->getColumnDimension('A')->setWidth(110);

        foreach ([
            'PETUNJUK IMPORT LISTING MANUAL',
            '',
            'Isi data di sheet "Listing Manual" mulai baris 2. Jangan ubah judul kolom di baris 1.',
            'Kolom wajib (oranye): Tanggal (DD-MM-YYYY), Conveyor, Assy Number, Qty, Seq.',
            'Kolom biru boleh dikosongkan: diisi otomatis dari listing terakhir assy yang sama.',
            'Assy baru (belum pernah di listing) boleh dimasukkan; Assy Code yang kosong diisi dengan Assy Number.',
            'Kanban assy baru baru keluar bila master circuit/shikake dengan kolom assy itu sudah diimport sebelum shift-nya diverifikasi.',
            'Seq menentukan urutan alokasi ke cutoff, bercampur dengan listing SIREP di tanggal & conveyor yang sama.',
            'Tanggal + Conveyor + Assy Code + Seq yang sudah ada sebagai listing manual akan DIPERBARUI, bukan ditambah.',
            'Bila ada satu baris saja yang salah, seluruh file ditolak (tidak ada yang tersimpan). Maksimal 1000 baris.',
        ] as $i => $line) {
            $guide->setCellValue('A' . ($i + 1), $line);
        }

        $guide->getStyle('A1')->getFont()->setBold(true);

        $spreadsheet->setActiveSheetIndex(0);

        $tempPath = tempnam(sys_get_temp_dir(), 'listing_manual_template_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);

        return $tempPath;
    }

    /**
     * Teks mode untuk tampilan; kebalikan dari config sirep.mapping.mode_map.
     */
    public function modeLabel($mode): string
    {
        $label = array_search((int) $mode, config('sirep.mapping.mode_map', []), true);

        return $label !== false ? strtoupper($label) : '-';
    }

    /**
     * @return array{date: string, conveyor: string, conveyor_id: int|null}
     */
    public function target($date, string $conveyor): array
    {
        return [
            'date'        => Carbon::parse($date)->format('Y-m-d'),
            'conveyor'    => $conveyor,
            'conveyor_id' => MasterConveyor::active()->where('conveyor', $conveyor)->value('id'),
        ];
    }

    /**
     * @throws \DomainException bila jadwal listing ini sudah diverifikasi/ber-kanban
     */
    public function assertEditable(ListingStage $listing): void
    {
        if ($this->isLocked($listing)) {
            throw new \DomainException(
                "Listing {$listing->assy} ({$listing->conveyor}, " . $listing->listing_date_time->format('d-m-Y')
                . ') sudah diverifikasi atau kanbannya sudah dibuat. Unverify jadwalnya dulu untuk mengubah atau menghapus.'
            );
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertKeyAvailable(array $attributes, ?int $ignoreId = null): void
    {
        if ($this->findByKey($attributes, $ignoreId)) {
            throw ValidationException::withMessages([
                'seq' => "Sudah ada listing manual {$attributes['assycode']} seq {$attributes['seq']} di "
                    . "{$attributes['conveyor']} pada tanggal ini — ubah listing itu atau pakai seq lain.",
            ]);
        }
    }

    private function referenceListing(string $assy, string $conveyor): ?ListingStage
    {
        if ($assy === '') {
            return null;
        }

        return ListingStage::where('assy', $assy)
            ->orderByRaw('CASE WHEN conveyor = ? THEN 0 ELSE 1 END', [$conveyor])
            ->orderByRaw('CASE WHEN source = ? THEN 1 ELSE 0 END', [ListingStage::SOURCE_MANUAL])
            ->orderByDesc('listing_date_time')
            ->orderByDesc('id')
            ->first();
    }

    private function listValidation(string $formula, string $errorTitle, string $error): DataValidation
    {
        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle($errorTitle);
        $validation->setError($error);
        $validation->setFormula1($formula);

        return $validation;
    }
}
