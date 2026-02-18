<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Finance\DTOs\CreateJournalEntryDTO;
use App\Modules\Finance\Enums\JournalEntryStatus;
use App\Modules\Finance\Services\JournalEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

/**
 * Journal Entry API Controller
 *
 * Handles HTTP requests for journal entry management.
 */
class JournalEntryController extends BaseController
{
    public function __construct(
        protected JournalEntryService $journalEntryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $journalEntries = $this->journalEntryService->getAllJournalEntries($perPage);

        return $this->successResponse($journalEntries, 'Journal entries retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_number' => 'required|string|max:50',
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'status' => ['nullable', new Enum(JournalEntryStatus::class)],
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
            'lines.*.cost_center_id' => 'nullable|integer|exists:cost_centers,id',
        ]);

        $dto = CreateJournalEntryDTO::fromArray($validated);
        $journalEntry = $this->journalEntryService->createJournalEntry($dto);

        return $this->createdResponse($journalEntry, 'Journal entry created successfully');
    }

    public function show(int $id): JsonResponse
    {
        $journalEntry = $this->journalEntryService->getJournalEntryById($id);

        return $this->successResponse($journalEntry, 'Journal entry retrieved successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'entry_number' => 'required|string|max:50',
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
            'lines.*.cost_center_id' => 'nullable|integer|exists:cost_centers,id',
        ]);

        $dto = CreateJournalEntryDTO::fromArray($validated);
        $journalEntry = $this->journalEntryService->updateJournalEntry($id, $dto);

        return $this->successResponse($journalEntry, 'Journal entry updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->journalEntryService->deleteJournalEntry($id);

        return $this->successResponse(null, 'Journal entry deleted successfully');
    }

    public function post(int $id): JsonResponse
    {
        $journalEntry = $this->journalEntryService->postJournalEntry($id);

        return $this->successResponse($journalEntry, 'Journal entry posted successfully');
    }

    public function void(int $id): JsonResponse
    {
        $journalEntry = $this->journalEntryService->voidJournalEntry($id);

        return $this->successResponse($journalEntry, 'Journal entry voided successfully');
    }

    public function byStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', new Enum(JournalEntryStatus::class)],
        ]);

        $status = JournalEntryStatus::from($validated['status']);
        $journalEntries = $this->journalEntryService->getJournalEntriesByStatus($status);

        return $this->successResponse($journalEntries, 'Journal entries retrieved successfully');
    }

    public function draft(): JsonResponse
    {
        $journalEntries = $this->journalEntryService->getDraftEntries();

        return $this->successResponse($journalEntries, 'Draft journal entries retrieved successfully');
    }

    public function posted(): JsonResponse
    {
        $journalEntries = $this->journalEntryService->getPostedEntries();

        return $this->successResponse($journalEntries, 'Posted journal entries retrieved successfully');
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'required|string|min:1',
        ]);

        $journalEntries = $this->journalEntryService->searchJournalEntries($validated['search']);

        return $this->successResponse($journalEntries, 'Search results retrieved successfully');
    }

    public function generateEntryNumber(): JsonResponse
    {
        $entryNumber = $this->journalEntryService->generateEntryNumber();

        return $this->successResponse(
            ['entry_number' => $entryNumber],
            'Entry number generated successfully'
        );
    }
}
