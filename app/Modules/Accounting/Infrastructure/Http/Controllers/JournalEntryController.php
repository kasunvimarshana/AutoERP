<?php
namespace Modules\Accounting\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Accounting\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Accounting\Application\Contracts\ReverseJournalEntryServiceInterface;
use Modules\Accounting\Application\DTOs\JournalEntryData;
use Modules\Accounting\Domain\Repositories\JournalEntryRepositoryInterface;
use Modules\Accounting\Infrastructure\Http\Resources\JournalEntryResource;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;

class JournalEntryController extends Controller
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
        private readonly PostJournalEntryServiceInterface $postJournalEntryService,
        private readonly ReverseJournalEntryServiceInterface $reverseJournalEntryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $filters  = $request->only(['status', 'source_type', 'source_id']);
        $perPage  = (int) $request->query('per_page', 15);

        $paginator = $this->journalEntryRepository->findAll($tenantId, $filters, $perPage);

        return response()->json([
            'data' => JournalEntryResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'           => 'required|integer',
            'reference_number'    => 'required|string|max:100',
            'entry_date'          => 'required|date',
            'description'         => 'nullable|string',
            'source_type'         => 'nullable|string|max:100',
            'source_id'           => 'nullable|integer',
            'lines'               => 'required|array|min:2',
            'lines.*.account_id'  => 'required|integer',
            'lines.*.debit'       => 'required|numeric|min:0',
            'lines.*.credit'      => 'required|numeric|min:0',
            'lines.*.currency'    => 'nullable|string|size:3',
            'lines.*.description' => 'nullable|string',
        ]);

        $data = new JournalEntryData(
            tenantId:        $validated['tenant_id'],
            referenceNumber: $validated['reference_number'],
            entryDate:       $validated['entry_date'],
            lines:           $validated['lines'],
            description:     $validated['description'] ?? null,
            sourceType:      $validated['source_type'] ?? null,
            sourceId:        $validated['source_id']   ?? null,
        );

        $entry = $this->createJournalEntryService->execute($data);

        $model = JournalEntryModel::with('lines')->find($entry->id);

        return response()->json(new JournalEntryResource($model), 201);
    }

    public function show(int $id): JsonResponse
    {
        $model = JournalEntryModel::with('lines')->find($id);

        if ($model === null) {
            return response()->json(['message' => 'Journal entry not found.'], 404);
        }

        return response()->json(new JournalEntryResource($model));
    }

    public function post(Request $request, int $id): JsonResponse
    {
        $entry = $this->journalEntryRepository->findById($id);

        if ($entry === null) {
            return response()->json(['message' => 'Journal entry not found.'], 404);
        }

        $validated = $request->validate(['posted_by' => 'required|integer']);

        $updated = $this->postJournalEntryService->execute($entry, $validated['posted_by']);

        $model = JournalEntryModel::with('lines')->find($updated->id);

        return response()->json(new JournalEntryResource($model));
    }

    public function reverse(Request $request, int $id): JsonResponse
    {
        $entry = $this->journalEntryRepository->findById($id);

        if ($entry === null) {
            return response()->json(['message' => 'Journal entry not found.'], 404);
        }

        $validated = $request->validate(['reversed_by' => 'required|integer']);

        $reversal = $this->reverseJournalEntryService->execute($entry, $validated['reversed_by']);

        $model = JournalEntryModel::with('lines')->find($reversal->id);

        return response()->json(new JournalEntryResource($model), 201);
    }
}
