<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Application\Services\JournalEntryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FinanceController extends Controller {
    public function __construct(private JournalEntryService $journalService) {}

    public function storeJournalEntry(Request $request): JsonResponse {
        $validated = $request->validate([
            'posting_date' => 'required|date',
            'reference_no' => 'nullable|string',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.memo' => 'nullable|string',
        ]);

        try {
            $entry = $this->journalService->createBalancedEntry($validated);
            return response()->json([
                'message' => 'Journal entry created successfully',
                'data' => $entry->load('lines')
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
