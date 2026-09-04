<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\BalanceResetLog;
use App\Services\BalanceResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BalanceResetController extends Controller
{
    public function __construct(private BalanceResetService $service)
    {
    }

    public function index()
    {
        return view('system.balance_reset.index', [
            'reference'  => $this->service->referenceStatus(),
            'conveyors'  => $this->service->selectableConveyors(),
            'history'    => BalanceResetLog::with(['creator', 'undoer'])
                                ->latest()->limit(20)->get(),
        ]);
    }

    /**
     * Pratinjau — tidak menulis apa pun.
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'cutoff_date'   => 'required|date',
            'conveyor_ids'   => 'required|array|min:1',
            'conveyor_ids.*' => 'integer|exists:master_conveyor,id',
        ], [
            'conveyor_ids.required' => 'Pilih minimal satu conveyor.',
            'cutoff_date.required'  => 'Tanggal acuan wajib diisi.',
        ]);

        try {
            return response()->json(
                $this->service->preview($data['conveyor_ids'], $data['cutoff_date'])
            );
        } catch (\Throwable $e) {
            Log::error('Pratinjau penyamaan saldo gagal', ['error' => $e->getMessage()]);

            return response()->json([
                'ok'      => false,
                'message' => 'Gagal menyusun pratinjau: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Terapkan penyamaan.
     */
    public function apply(Request $request)
    {
        $data = $request->validate([
            'cutoff_date'    => 'required|date',
            'conveyor_ids'   => 'required|array|min:1',
            'conveyor_ids.*' => 'integer|exists:master_conveyor,id',
            'note'           => 'nullable|string|max:500',
            // Pengaman terakhir: admin harus mengetik ulang kata konfirmasi.
            'confirm'        => 'required|in:SAMAKAN',
        ], [
            'confirm.in'       => 'Ketik SAMAKAN untuk menegaskan tindakan ini.',
            'confirm.required' => 'Ketik SAMAKAN untuk menegaskan tindakan ini.',
        ]);

        $result = $this->service->apply(
            $data['conveyor_ids'],
            $data['cutoff_date'],
            $data['note'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Batalkan satu penyamaan.
     */
    public function undo(Request $request, int $id)
    {
        $result = $this->service->undo($id);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
