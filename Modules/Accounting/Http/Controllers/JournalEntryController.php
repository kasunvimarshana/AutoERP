<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Requests\StoreJournalEntryRequest;
use Modules\Accounting\Http\Requests\UpdateJournalEntryRequest;
use Modules\Accounting\Http\Resources\JournalEntryResource;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Repositories\JournalEntryRepository;
use Modules\Accounting\Services\GeneralLedgerService;
use Modules\Core\Http\Responses\ApiResponse;

class JournalEntryController extends Controller
{
    public function __construct(
        private JournalEntryRepository $journalEntryRepository,
        private GeneralLedgerService $generalLedgerService
    ) {}

    /**
     * Display a listing of journal entries
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JournalEntry::class);

        $query = JournalEntry::query()
            ->with(['organization', 'fiscalPeriod', 'lines.account']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('fiscal_period_id')) {
            $query->where('fiscal_period_id', $request->fiscal_period_id);
        }

        if ($request->has('from_date')) {
            $query->where('entry_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('entry_date', '<=', $request->to_date);
        }

        if ($request->has('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $entries = $query->latest('entry_date')->paginate($perPage);

        return ApiResponse::paginated(
            $entries->setCollection(
                $entries->getCollection()->map(fn ($entry) => new JournalEntryResource($entry))
            ),
            'Journal entries retrieved successfully'
        );
    }

    /**
     * Store a newly created journal entry
     */
    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['organization_id'] = $data['organization_id'] ?? $request->user()->currentOrganization()->id;

        $entry = $this->generalLedgerService->createJournalEntry($data, $lines);

        return ApiResponse::success(
            new JournalEntryResource($entry->load('lines.account', 'fiscalPeriod')),
            'Journal entry created successfully',
            201
        );
    }

    /**
     * Display the specified journal entry
     */
    public function show(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        $this->authorize('view', $journalEntry);

        $journalEntry->load(['lines.account', 'fiscalPeriod', 'organization']);

        return ApiResponse::success(
            new JournalEntryResource($journalEntry),
            'Journal entry retrieved successfully'
        );
    }

    /**
     * Update the specified journal entry
     */
    public function update(UpdateJournalEntryRequest $request, JournalEntry $journalEntry): JsonResponse
    {
        $data = $request->validated();
        $lines = $data['lines'] ?? null;

        if (isset($data['lines'])) {
            unset($data['lines']);
        }

        $entry = $this->generalLedgerService->updateJournalEntry($journalEntry->id, $data, $lines);

        return ApiResponse::success(
            new JournalEntryResource($entry->load('lines.account', 'fiscalPeriod')),
            'Journal entry updated successfully'
        );
    }

    /**
     * Remove the specified journal entry
     */
    public function destroy(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        $this->authorize('delete', $journalEntry);

        $this->generalLedgerService->deleteJournalEntry($journalEntry->id);

        return ApiResponse::success(
            null,
            'Journal entry deleted successfully'
        );
    }

    /**
     * Post a journal entry
     */
    public function post(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        $this->authorize('post', $journalEntry);

        $entry = $this->generalLedgerService->postJournalEntry(
            $journalEntry->id,
            $request->user()->id
        );

        return ApiResponse::success(
            new JournalEntryResource($entry->load('lines.account', 'fiscalPeriod')),
            'Journal entry posted successfully'
        );
    }

    /**
     * Reverse a posted journal entry
     */
    public function reverse(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        $this->authorize('reverse', $journalEntry);

        $request->validate([
            'reversal_date' => ['nullable', 'date'],
        ]);

        $reversalEntry = $this->generalLedgerService->reverseJournalEntry(
            $journalEntry->id,
            $request->user()->id,
            $request->get('reversal_date')
        );

        return ApiResponse::success(
            new JournalEntryResource($reversalEntry->load('lines.account', 'fiscalPeriod')),
            'Journal entry reversed successfully'
        );
    }
}
