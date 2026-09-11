<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Concerns\GuardsGenerate;
use App\Http\Controllers\Controller;
use App\Services\AssySchedulerService;
use App\Models\MasterConveyor;
use App\Models\AssySchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class AssySchedulerController extends Controller
{
    use GuardsGenerate;

    protected $assySchedulerService;

    public function __construct(AssySchedulerService $assySchedulerService)
    {
        $this->assySchedulerService = $assySchedulerService;
    }

    /**
     * Display the scheduler page
     */
    public function index()
    {
        $conveyors = MasterConveyor::orderBy('conveyor', 'asc')->get();

        return view('schedule.assy_scheduler.index', compact('conveyors'));
    }

    /**
     * Get datatable data
     */
    public function datatable(Request $request)
    {
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'conveyor_id' => $request->input('conveyor_id'),
        ];

        $query = $this->assySchedulerService->getSchedulesQuery($filters);
        $schedules = $query->get();

        // Group schedules by conveyor, date, and shift
        $grouped = $schedules->groupBy(function ($schedule) {
            return $schedule->conveyor_id . '_' . $schedule->schedule->format('Y-m-d') . '_' . $schedule->shift;
        })->map(function ($group) {
            $first = $group->first();
            
            return (object) [
                'id' => $first->id, // Use first record's ID for actions
                'conveyor' => $first->conveyor,
                'conveyor_id' => $first->conveyor_id,
                'schedule' => $first->schedule,
                'shift' => $first->shift,
                'listing_count' => $group->sum('qty'), // Total qty for all listings in this shift
                'assy_list' => $group->pluck('assy')->filter()->unique()->implode(', '), // All unique assy codes
                'is_lock' => $group->every('is_lock'), // All verified if every item is locked
                'group_ids' => $group->pluck('id')->toArray(), // All IDs in this group for bulk operations
            ];
        })->values();

        return DataTables::of($grouped)
            ->addIndexColumn()
            ->addColumn('conveyor_name', function ($schedule) {
                return $schedule->conveyor ? $schedule->conveyor->conveyor : '-';
            })
            ->addColumn('date', function ($schedule) {
                return $schedule->schedule->format('Y-m-d');
            })
            ->addColumn('shift_name', function ($schedule) {
                return 'Shift ' . $schedule->shift;
            })
            ->addColumn('capacity', function ($schedule) {
                return $schedule->conveyor ? $schedule->conveyor->capacity : 0;
            })
            ->addColumn('listing_count', function ($schedule) {
                return $schedule->listing_count;
            })
            ->addColumn('assy_list', function ($schedule) {
                return $schedule->assy_list ?: '-';
            })
            // ->addColumn('status', function ($schedule) {
            //     if ($schedule->is_lock) {
            //         return '<span class="badge badge-success">Verified</span>';
            //     }
            //     return '<span class="badge badge-warning">Pending</span>';
            // })
            ->addColumn('action', function ($schedule) {
                $manageBtn = '<div class="btn-group" role="group"><button type="button" class="btn btn-soft-primary btn-sm btn-manage" 
                    data-conveyor-id="' . $schedule->conveyor_id . '" 
                    data-conveyor-name="' . ($schedule->conveyor ? $schedule->conveyor->conveyor : '') . '" 
                    data-date="' . $schedule->schedule->format('Y-m-d') . '" 
                    data-capacity="' . ($schedule->conveyor ? $schedule->conveyor->capacity : 0) . '" 
                    data-max-shifts="' . \App\Services\Schedule\ShiftCapacityCalculator::MAX_SHIFT . '">
                    <i class="ti ti-settings"></i> Manage
                </button></div>';
                
                return $manageBtn;
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Daftar jadwal assy, satu baris per (assy x cutoff).
     *
     * Selain kolom jadwal, setiap baris membawa konteks SIREP yang dipakai saat
     * jadwal itu dibentuk: kapasitas per shift, jumlah shift, penanda lembur, dan
     * kapan listing ditarik dari API. Tanpa itu operator tidak punya cara menilai
     * apakah pembagian cutoff yang terlihat masih sesuai keadaan SIREP terkini.
     *
     * Baris yang SUDAH terkunci memakai snapshot yang tersimpan saat verifikasi,
     * bukan nilai SIREP terkini — sama seperti layar verifikasi. Nilai terkini
     * dipakai hanya untuk baris yang belum terkunci dan untuk baris lama yang
     * tidak punya snapshot.
     */
    public function getAssyScheduleList(Request $request)
    {
        $query = AssySchedule::query()
            ->select('assy_schedule.*')
            ->with('conveyor')
            ->join('master_conveyor AS mc', 'mc.id', '=', 'assy_schedule.conveyor_id')
            ->addSelect([
                'mc.capacity AS cv_capacity',
                'mc.overtime_capacity AS cv_overtime_capacity',
                'mc.overtime_capacity AS cv_overtime_capacity',
                'mc.capacity_synced_at AS cv_capacity_synced_at',
                'mc.is_active AS cv_is_active',
            ])
            ->orderBy('assy_schedule.schedule', 'asc')
            ->orderBy('mc.conveyor', 'asc')
            ->orderBy('assy_schedule.shift', 'asc')
            ->orderBy('assy_schedule.cutoff', 'asc')
            ->orderBy('assy_schedule.listing_id', 'asc');

        if ($request->start_date) {
            $query->whereDate('assy_schedule.schedule', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('assy_schedule.schedule', '<=', $request->end_date);
        }
        if ($request->conveyor_id) {
            $query->where('assy_schedule.conveyor_id', $request->conveyor_id);
        }
        if ($request->status === 'verified') {
            $query->where('assy_schedule.is_lock', 1);
        } elseif ($request->status === 'pending') {
            $query->where('assy_schedule.is_lock', 0);
        }

        // Penanda lembur & waktu tarik listing per (conveyor x tanggal). Diambil
        // sekali di luar loop supaya tidak ada kueri per baris.
        $sirep = $this->sirepContext($request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('conveyor_name', function ($s) {
                $nonaktif = $s->cv_is_active ? '' :
                    ' <span class="badge bg-secondary" title="Conveyor sudah tidak ada di SIREP">nonaktif</span>';

                return e($s->conveyor->conveyor ?? '-') . $nonaktif;
            })
            ->addColumn('dates', fn ($s) => $s->schedule->format('d M Y'))
            ->addColumn('shift_name', fn ($s) => 'Shift ' . (int) $s->shift)
            ->addColumn('cutoff_label', function ($s) {
                $co = (int) ($s->cutoff ?? 0);

                if ($co === 0) {
                    return '<span class="text-muted">-</span>';
                }

                // CO5 adalah cutoff lembur — dibedakan supaya langsung terlihat.
                $kelas = $co === 5 ? 'bg-warning text-dark' : 'bg-light text-dark border';

                return '<span class="badge ' . $kelas . '">CO' . $co . '</span>';
            })
            ->editColumn('qty', fn ($s) => number_format((int) $s->qty))
            ->addColumn('capacity', function ($s) {
                $terkunci = (int) $s->is_lock === 1 && $s->verified_capacity !== null;
                $cap      = $terkunci ? (int) $s->verified_capacity : (int) ($s->cv_capacity ?? 0);

                if (empty($cap)) {
                    return '<span class="badge bg-danger" title="Kapasitas belum pernah ditarik dari SIREP. '
                        . 'Conveyor ini dilewati saat generate.">belum sinkron</span>';
                }

                $judul = 'Kapasitas SIREP ' . number_format($cap) . '/shift';

                if (!$terkunci && !empty($s->cv_overtime_capacity)) {
                    $judul .= ' · overtime SIREP ' . number_format($s->cv_overtime_capacity);
                }

                $judul .= $terkunci
                    ? ' · nilai yang berlaku saat jadwal ini diverifikasi'
                    : ($s->cv_capacity_synced_at
                        ? ' · disinkron ' . Carbon::parse($s->cv_capacity_synced_at)->format('d M Y H:i')
                        : ' · waktu sinkron tidak tercatat');

                return '<span class="fw-semibold text-warning-emphasis" title="' . e($judul) . '">'
                    . number_format($cap) . '</span>';
            })
            ->addColumn('over_time', function ($s) use ($sirep) {
                $terkunci = (int) $s->is_lock === 1 && $s->verified_capacity !== null;
                $info     = $sirep[$s->conveyor_id . '|' . $s->schedule->format('Y-m-d')] ?? null;

                if ($terkunci) {
                    $ot = (bool) $s->verified_is_overtime;
                } elseif ($info) {
                    $ot = (bool) $info->is_overtime;
                } else {
                    return '<span class="badge bg-light text-dark" title="Tidak ada baris listing SIREP untuk tanggal ini">tanpa listing</span>';
                }

                return $ot
                    ? '<span class="badge bg-warning text-dark" title="SIREP menyatakan hari ini overtime — CO5 dibuka">Yes</span>'
                    : '<span class="badge bg-success" title="SIREP tidak menyatakan overtime — CO5 tertutup">No</span>';
            })
            ->addColumn('api_time', function ($s) use ($sirep) {
                $terkunci = (int) $s->is_lock === 1 && $s->verified_listing_synced_at !== null;

                if ($terkunci) {
                    $sumber = $s->verified_listing_source
                        ? ' · sumber ' . strtoupper($s->verified_listing_source)
                        : '';

                    return '<span class="text-nowrap" title="Waktu listing ditarik dari API SIREP, tercatat saat verifikasi'
                        . e($sumber) . '">'
                        . Carbon::parse($s->verified_listing_synced_at)->format('d M y H:i:s') . '</span>';
                }

                $info = $sirep[$s->conveyor_id . '|' . $s->schedule->format('Y-m-d')] ?? null;

                if (!$info || !$info->synced_at) {
                    return '<span class="text-muted">-</span>';
                }

                $sumber = $info->source ? ' · sumber ' . strtoupper($info->source) : '';

                return '<span class="text-nowrap" title="Waktu listing ini ditarik dari API SIREP' . e($sumber) . '">'
                    . Carbon::parse($info->synced_at)->format('d M y H:i:s') . '</span>';
            })
            ->addColumn('status', function ($s) {
                return (int) $s->is_lock === 1
                    ? '<span class="badge bg-success">Verified</span>'
                    : '<span class="badge bg-danger">Pending</span>';
            })
            ->rawColumns(['conveyor_name', 'cutoff_label', 'capacity', 'over_time', 'api_time', 'status'])
            ->make(true);
    }

    /**
     * Penanda lembur dan waktu tarik listing per (conveyor x tanggal).
     *
     * is_overtime seragam dalam satu conveyor x tanggal, jadi MAX() sudah mewakili.
     *
     * @return \Illuminate\Support\Collection
     */
    private function sirepContext(Request $request)
    {
        $q = \Illuminate\Support\Facades\DB::table('listing_stage AS ls')
            ->join('master_conveyor AS mc', 'mc.conveyor', '=', 'ls.conveyor')
            ->whereNull('mc.deleted_at')
            ->selectRaw('mc.id AS conveyor_id, DATE(ls.listing_date_time) AS d,
                         MAX(ls.is_overtime) AS is_overtime, MAX(ls.synced_at) AS synced_at,
                         MIN(ls.source) AS source')
            ->groupByRaw('mc.id, DATE(ls.listing_date_time)');

        if ($request->start_date) {
            $q->whereDate('ls.listing_date_time', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $q->whereDate('ls.listing_date_time', '<=', $request->end_date);
        }
        if ($request->conveyor_id) {
            $q->where('mc.id', $request->conveyor_id);
        }

        return $q->get()->keyBy(fn ($r) => $r->conveyor_id . '|' . $r->d);
    }

    /**
     * Ringkasan satu rentang: dipakai strip di atas tabel supaya operator melihat
     * gambaran hari itu tanpa harus menelusuri seluruh baris.
     */
    public function summary(Request $request)
    {
        $q = AssySchedule::query()
            ->join('master_conveyor AS mc', 'mc.id', '=', 'assy_schedule.conveyor_id');

        if ($request->start_date) {
            $q->whereDate('assy_schedule.schedule', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $q->whereDate('assy_schedule.schedule', '<=', $request->end_date);
        }
        if ($request->conveyor_id) {
            $q->where('assy_schedule.conveyor_id', $request->conveyor_id);
        }

        $baris = (clone $q)->selectRaw('
            COUNT(*) AS baris,
            COALESCE(SUM(assy_schedule.qty), 0) AS total_qty,
            COUNT(DISTINCT assy_schedule.assy) AS jumlah_assy,
            COUNT(DISTINCT assy_schedule.conveyor_id) AS jumlah_conveyor,
            COUNT(DISTINCT DATE(assy_schedule.schedule)) AS jumlah_hari,
            SUM(CASE WHEN assy_schedule.is_lock = 1 THEN 1 ELSE 0 END) AS terverifikasi,
            SUM(CASE WHEN assy_schedule.cutoff = 5 THEN assy_schedule.qty ELSE 0 END) AS qty_co5,
            SUM(CASE WHEN mc.capacity IS NULL OR mc.capacity <= 0 THEN 1 ELSE 0 END) AS tanpa_kapasitas,
            SUM(CASE WHEN mc.capacity > 0
                      AND (mc.overtime_capacity IS NULL OR mc.overtime_capacity <= 0)
                     THEN 1 ELSE 0 END) AS tanpa_ambang_ot
        ')->first();

        return response()->json(['success' => true, 'data' => $baris]);
    }

    /**
     * Generate schedules
     */
    public function generate(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'conveyor_id' => 'nullable|exists:master_conveyor,id',
        ]);

        // Pengaman yang sama dengan Dashboard, dan berbagi ruang kunci dengannya.
        // Halaman ini menjalankan generate otomatis begitu dibuka, sementara tombol
        // Generate memicu proses kedua yang sama beratnya; tanpa kunci keduanya
        // berjalan bersamaan di atas tabel yang sama dan saling memperlambat.
        $scope  = $this->generateScope($request);
        $recent = $this->generateBaruSelesai($request, $scope);

        if ($recent) {
            return $this->skippedResponse(
                'Jadwal untuk rentang ini baru saja disinkronkan pukul ' . $recent['at']
                . ' (' . $recent['generated'] . ' schedule). Sinkronisasi otomatis dilewati.',
                $recent['generated']
            );
        }

        $lock = $this->ambilGenerateLock($scope);

        if (!$lock->get()) {
            return $this->skippedResponse(
                'Sinkron & generate untuk rentang ini sedang berjalan. Tunggu sampai proses tersebut selesai.'
            );
        }

        try {
            $result = $this->assySchedulerService->generateSchedules(
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('conveyor_id')
            );

            if ($result['success']) {
                $this->catatGenerateSelesai($scope, (int) $result['generated']);

                return response()->json([
                    'success'     => true,
                    'message'     => $result['message'],
                    'step_failed' => null,
                    'data'        => [
                        'generated'   => $result['generated'],
                        'sync_detail' => $result['sync_detail'] ?? null,
                    ],
                ]);
            } else {
                return response()->json([
                    'success'     => false,
                    'step_failed' => $result['step_failed'] ?? 'unknown',
                    'message'     => $result['message'],
                    'data'        => [
                        'generated'   => 0,
                        'sync_detail' => $result['sync_detail'] ?? null,
                    ],
                ], 400);
            }
        // \Throwable, bukan \Exception: kekeliruan kode (mis. memanggil method yang
        // sudah dihapus) adalah \Error dan sebelumnya lolos ke handler bawaan Laravel
        // sebagai 500 mentah. Layar lalu menerjemahkannya jadi "gagal mengambil data
        // listing dari PPC" — menuduh pihak yang tidak bersalah dan menyesatkan
        // penelusuran. Ditangkap di sini supaya pesannya jujur dan tercatat.
        } catch (\Throwable $e) {
            Log::error('Schedule generation error', ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);

            return response()->json([
                'success'     => false,
                'step_failed' => 'unknown',
                'message'     => 'Terjadi kesalahan: ' . $e->getMessage(),
                'data'        => ['generated' => 0, 'sync_detail' => null],
            ], 500);
        } finally {
            $lock->release();
        }
    }

    /**
     * Verify a schedule or multiple schedules
     */
    public function verify(Request $request, $id = null)
    {
        try {
            // Check if we're verifying multiple IDs (comma-separated)
            $ids = $request->input('ids');
            
            if ($ids) {
                // Bulk verification
                $idArray = explode(',', $ids);
                $results = [];
                
                foreach ($idArray as $scheduleId) {
                    $result = $this->assySchedulerService->verifySchedule(trim($scheduleId));
                    $results[] = $result;
                }
                
                // Check if all succeeded
                $allSuccess = collect($results)->every('success');
                
                return response()->json([
                    'success' => $allSuccess,
                    'message' => $allSuccess ? 'All schedules verified successfully' : 'Some schedules failed to verify',
                    'results' => $results
                ], $allSuccess ? 200 : 400);
            } else {
                // Single verification
                $result = $this->assySchedulerService->verifySchedule($id);
                return response()->json($result, $result['success'] ? 200 : 400);
            }
        } catch (\Exception $e) {
            Log::error("Schedule verification error", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete schedules
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'conveyor_id' => 'nullable|exists:master_conveyor,id',
        ]);

        try {
            $result = $this->assySchedulerService->deleteSchedules(
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('conveyor_id')
            );

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error("Schedule deletion error", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedules: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get manage data for a specific conveyor and date
     */
    public function manageData(Request $request)
    {
        $request->validate([
            'conveyor_id' => 'required|exists:master_conveyor,id',
            'date' => 'required|date',
        ]);

        try {
            $result = $this->assySchedulerService->getManageData(
                $request->input('conveyor_id'),
                $request->input('date')
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Manage data retrieval error", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load manage data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save manage changes
     */
    public function saveManage(Request $request)
    {
        $request->validate([
            'conveyor_id' => 'required|exists:master_conveyor,id',
            'date' => 'required|date',
            'shifts' => 'required|array',
        ]);

        try {
            $result = $this->assySchedulerService->saveManageData(
                $request->input('conveyor_id'),
                $request->input('date'),
                $request->input('shifts')
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Manage save error", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save manage changes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available assy data with date range and pagination
     */
    public function availableAssyData(Request $request)
    {
        $request->validate([
            'conveyor_id' => 'required|exists:master_conveyor,id',
            'selected_date' => 'required|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'page' => 'nullable|integer|min:1',
        ]);

        // Validate 7-day maximum range
        // if ($request->filled('start_date') && $request->filled('end_date')) {
        //     $start = Carbon::parse($request->input('start_date'));
        //     $end = Carbon::parse($request->input('end_date'));
        //     if ($start->diffInDays($end) > 7) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Date range cannot exceed 7 days'
        //         ], 400);
        //     }
        // }

        try {
            $result = $this->assySchedulerService->getAvailableAssyData(
                $request->input('conveyor_id'),
                $request->input('selected_date'),
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('page', 1)
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Available assy data retrieval error", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load available assy data: ' . $e->getMessage()
            ], 500);
        }
    }
}
