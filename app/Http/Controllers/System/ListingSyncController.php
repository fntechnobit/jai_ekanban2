<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\ListingSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ListingSyncController extends Controller
{
    protected $listingSyncService;

    public function __construct(ListingSyncService $listingSyncService)
    {
        $this->listingSyncService = $listingSyncService;
    }

    /**
     * Display the synchronization page
     */
    public function index()
    {
        $statistics = $this->listingSyncService->getSyncStatistics();
        return view('system.listing_sync.index', compact('statistics'));
    }

    /**
     * Perform synchronization
     */
    public function sync(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        try {
            $result = $this->listingSyncService->syncListingData($startDate, $endDate);

            if ($result['success']) {
                // Sinkronisasi bersifat rekonsiliasi: selain menambah, ia juga
                // memperbarui baris yang direvisi dan menghapus yang dibatalkan
                // di SIREP. Ketiganya perlu terlihat oleh operator.
                $message = sprintf(
                    'Sinkronisasi selesai (sumber: %s). Baru: %d, Diperbarui: %d, Dihapus: %d, Tidak berubah: %d',
                    $result['source'] ?? '-',
                    $result['inserted'] ?? 0,
                    $result['updated'] ?? 0,
                    $result['deleted'] ?? 0,
                    $result['skipped'] ?? 0
                );

                if (!empty($result['warnings'])) {
                    $message .= ' — ' . count($result['warnings']) . ' peringatan, mohon diperiksa';
                }

                if (!empty($result['errors'])) {
                    $message .= ' — ' . count($result['errors']) . ' conveyor gagal diambil';
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => $result
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error("Listing sync error", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Synchronization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current statistics
     */
    public function statistics()
    {
        $statistics = $this->listingSyncService->getSyncStatistics();
        
        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}
