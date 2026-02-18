<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Core\Http\Controllers\BaseController;

/**
 * Journal Entry Controller
 *
 * Manages double-entry journal entries for general ledger accounting.
 * Ensures debit and credit balance before posting to the ledger.
 */
class JournalEntryController extends BaseController
{
    /**
     * Constructor
     */
    public function __construct(
        private JournalEntryService $journalEntryService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/accounting/journal-entries",
     *     summary="List all journal entries",
     *     description="Retrieve paginated list of journal entries with filtering by posting status, date range, and search capabilities",
     *     operationId="journalEntriesIndex",
     *     tags={"Accounting-Journals"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="is_posted",
     *         in="query",
     *         description="Filter by posting status (true=posted, false=draft, null=all)",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter entries from this date (inclusive)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter entries to this date (inclusive)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in entry number, reference, or description",
     *         required=false,
     *         @OA\Schema(type="string", example="INV-2024")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Journal entries retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/JournalEntry")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string", example="http://api.example.com/api/accounting/journal-entries?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://api.example.com/api/accounting/journal-entries?page=10"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", example="http://api.example.com/api/accounting/journal-entries?page=2")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="to", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=150)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'is_posted' => $request->input('is_posted') !== null ? $request->boolean('is_posted') : null,
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'search' => $request->input('search'),
                'per_page' => $request->integer('per_page', 15),
            ];

            $entries = $this->journalEntryService->getAll($filters);

            return $this->success($entries);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch journal entries: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/accounting/journal-entries",
     *     summary="Create a new journal entry",
     *     description="Create a new double-entry journal entry with at least 2 lines. Total debits must equal total credits. Entry can be created as draft or posted immediately.",
     *     operationId="journalEntriesStore",
     *     tags={"Accounting-Journals"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Journal entry data with at least 2 lines (debits must equal credits)",
     *         @OA\JsonContent(ref="#/components/schemas/StoreJournalEntryRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Journal entry created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Journal entry created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/JournalEntry")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Debits do not equal credits or invalid balance",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid account IDs or amounts",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'entry_date' => 'required|date',
                'reference' => 'required|string|max:255',
                'description' => 'nullable|string',
                'currency_code' => 'nullable|string|size:3',
                'is_posted' => 'nullable|boolean',
                'lines' => 'required|array|min:2',
                'lines.*.account_id' => 'required|uuid|exists:accounts,id',
                'lines.*.debit_amount' => 'required|numeric|min:0',
                'lines.*.credit_amount' => 'required|numeric|min:0',
                'lines.*.description' => 'nullable|string',
            ]);

            $entry = $this->journalEntryService->create($validated);

            return $this->created($entry, 'Journal entry created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to create journal entry: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/accounting/journal-entries/{id}",
     *     summary="Get journal entry details",
     *     description="Retrieve detailed information for a specific journal entry including all debit and credit lines",
     *     operationId="journalEntriesShow",
     *     tags={"Accounting-Journals"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Journal entry ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Journal entry retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/JournalEntry")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Journal entry not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $entry = $this->journalEntryService->getById($id);

            return $this->success($entry);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Journal entry not found');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch journal entry: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/accounting/journal-entries/{id}",
     *     summary="Update a journal entry",
     *     description="Update an existing journal entry. Only draft (unposted) entries can be updated. Posted entries are immutable.",
     *     operationId="journalEntriesUpdate",
     *     tags={"Accounting-Journals"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Journal entry ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Journal entry data to update (debits must equal credits)",
     *         @OA\JsonContent(ref="#/components/schemas/UpdateJournalEntryRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Journal entry updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Journal entry updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/JournalEntry")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Cannot update posted entry or debits do not equal credits",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Journal entry not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'entry_date' => 'sometimes|date',
                'reference' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'lines' => 'sometimes|array|min:2',
                'lines.*.account_id' => 'required|uuid|exists:accounts,id',
                'lines.*.debit_amount' => 'required|numeric|min:0',
                'lines.*.credit_amount' => 'required|numeric|min:0',
                'lines.*.description' => 'nullable|string',
            ]);

            $entry = $this->journalEntryService->update($id, $validated);

            return $this->updated($entry, 'Journal entry updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Journal entry not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update journal entry: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/accounting/journal-entries/{id}/post",
     *     summary="Post a journal entry",
     *     description="Post a draft journal entry to the general ledger. This action is irreversible and updates all account balances. Entry must be balanced (debits = credits).",
     *     operationId="journalEntriesPost",
     *     tags={"Accounting-Journals"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Journal entry ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Journal entry posted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Journal entry posted successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/JournalEntry")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Entry already posted or not balanced",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Journal entry not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function post(string $id): JsonResponse
    {
        try {
            $entry = $this->journalEntryService->getById($id);
            $entry = $this->journalEntryService->postEntry($entry);

            return $this->updated($entry, 'Journal entry posted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Journal entry not found');
        } catch (\Exception $e) {
            return $this->error('Failed to post journal entry: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/accounting/journal-entries/{id}",
     *     summary="Delete a journal entry",
     *     description="Delete a journal entry. Only draft (unposted) entries can be deleted. Posted entries cannot be deleted to maintain audit trail.",
     *     operationId="journalEntriesDestroy",
     *     tags={"Accounting-Journals"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Journal entry ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Journal entry deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Cannot delete posted entry",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Journal entry not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->journalEntryService->delete($id);

            return $this->deleted('Journal entry deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Journal entry not found');
        } catch (\Exception $e) {
            return $this->error('Failed to delete journal entry: '.$e->getMessage(), 500);
        }
    }
}
