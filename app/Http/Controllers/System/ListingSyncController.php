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
            'days' => 'required|integer|min:1|max:30'
        ]);

        $days = $request->input('days', 7);

        try {
            $result = $this->listingSyncService->syncListingData($days);

            if ($result['success']) {
                $message = "Synchronization completed successfully! Synced: {$result['synced']}, Skipped: {$result['skipped']}";
                
                if (!empty($result['errors'])) {
                    $message .= " (with " . count($result['errors']) . " errors)";
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
