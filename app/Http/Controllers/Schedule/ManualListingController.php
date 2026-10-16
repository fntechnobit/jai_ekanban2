<?php

namespace App\Http\Controllers\Schedule;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ManualListingRequest;
use App\Imports\ManualListingImport;
use App\Models\MasterConveyor;
use App\Services\ManualListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Listing manual: input listing langsung di e-Kanban, di samping listing SIREP.
 * Lihat ManualListingService untuk aturan mainnya.
 */
class ManualListingController extends Controller
{
    public function __construct(private readonly ManualListingService $service)
    {
        $this->middleware('check.menu:listing_manual,can_read')
            ->only(['index', 'datatable', 'show', 'assyOptions', 'dayListing', 'downloadTemplate']);
        $this->middleware('check.menu:listing_manual,can_create')->only(['store', 'import']);
        $this->middleware('check.menu:listing_manual,can_update')->only(['update']);
        $this->middleware('check.menu:listing_manual,can_delete')->only(['destroy']);
    }

    public function index()
    {
        $conveyors = MasterConveyor::active()->orderBy('conveyor')->get(['id', 'conveyor', 'capacity']);

        return view('schedule.listing_manual.index', compact('conveyors'));
    }

    public function datatable(Request $request)
    {
        return $this->service->getDatatable($request->only(['start_date', 'end_date', 'conveyor']));
    }

    public function show($id)
    {
        $listing = $this->service->findById($id);

        return ResponseHelper::success([
            'id'           => $listing->id,
            'listing_date' => $listing->listing_date_time->format('Y-m-d'),
            'conveyor'     => $listing->conveyor,
            'assy'         => $listing->assy,
            'assycode'     => $listing->assycode,
            'qty'          => $listing->qty,
            'seq'          => $listing->seq,
            'carline'      => $listing->carline,
            'snp'          => $listing->snp,
            'snpa'         => $listing->snpa,
            'plt'          => $listing->plt,
            'mode'         => $listing->mode,
            'remark'       => $listing->remark,
            'locked'       => $this->service->isLocked($listing),
        ]);
    }

    public function store(ManualListingRequest $request)
    {
        return $this->respond(fn () => $this->service->create($request->validated()), 'Listing manual ditambahkan.');
    }

    public function update(ManualListingRequest $request, $id)
    {
        $listing = $this->service->findById($id);

        return $this->respond(fn () => $this->service->update($listing, $request->validated()), 'Listing manual diperbarui.');
    }

    public function destroy($id)
    {
        $listing = $this->service->findById($id);

        return $this->respond(fn () => $this->service->delete($listing), 'Listing manual dihapus.');
    }

    public function assyOptions(Request $request)
    {
        $request->validate(['conveyor' => 'required|string', 'q' => 'nullable|string|max:100']);

        return response()->json([
            'results' => $this->service->assyOptions($request->input('conveyor'), $request->input('q')),
        ]);
    }

    public function dayListing(Request $request)
    {
        $request->validate(['date' => 'required|date', 'conveyor' => 'required|string']);

        return ResponseHelper::success($this->service->dayListing($request->input('date'), $request->input('conveyor')));
    }

    public function import(Request $request, ManualListingImport $importer)
    {
        $request->validate([
            'file'       => 'required|file|mimes:xlsx,xls|max:10240',
            'rows_start' => 'required|integer|min:2',
        ]);

        try {
            $result = $importer->import($request->file('file')->getRealPath(), (int) $request->input('rows_start'));
        } catch (\Throwable $e) {
            Log::error('Import listing manual gagal', ['error' => $e->getMessage()]);

            return ResponseHelper::error($e->getMessage(), 422);
        }

        if (!$result['success']) {
            return ResponseHelper::error(
                'Import dibatalkan: ' . count($result['errors']) . ' baris bermasalah. Tidak ada data yang disimpan.',
                422,
                $result['errors']
            );
        }

        $message = "Import selesai: {$result['inserted']} listing baru, {$result['updated']} diperbarui";
        $message .= $result['unchanged'] ? ", {$result['unchanged']} tidak berubah." : '.';

        return ResponseHelper::success($result, $message);
    }

    public function downloadTemplate()
    {
        return response()
            ->download($this->service->generateTemplateFile(), 'Template_Listing_Manual.xlsx')
            ->deleteFileAfterSend(true);
    }

    /**
     * Jalankan aksi tulis dan seragamkan balasannya. Validasi tetap dilempar supaya
     * Laravel membalas 422 per field; listing terkunci dibalas 422 dengan pesan.
     */
    private function respond(callable $action, string $message)
    {
        try {
            $result = $action();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\DomainException $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('Listing manual gagal disimpan', ['error' => $e->getMessage()]);

            return ResponseHelper::error('Gagal menyimpan listing manual: ' . $e->getMessage());
        }

        if (($result['status'] ?? null) === 'unchanged') {
            $message = 'Tidak ada perubahan.';
        }

        return ResponseHelper::success([
            'warnings'   => $result['warnings'] ?? [],
            'regenerate' => $result['regenerate'] ?? [],
        ], $message);
    }
}
