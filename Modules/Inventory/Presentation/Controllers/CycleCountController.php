<?php

namespace Modules\Inventory\Presentation\Controllers;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\UseCases\CreateCycleCountUseCase;
use Modules\Inventory\Application\UseCases\PostCycleCountUseCase;
use Modules\Inventory\Application\UseCases\RecordCountedQtyUseCase;
use Modules\Inventory\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Inventory\Presentation\Requests\RecordCountedQtyRequest;
use Modules\Inventory\Presentation\Requests\StoreCycleCountRequest;

class CycleCountController extends Controller
{
    public function __construct(
        private CycleCountRepositoryInterface $cycleCountRepo,
        private CreateCycleCountUseCase       $createCycleCount,
        private RecordCountedQtyUseCase       $recordCountedQty,
        private PostCycleCountUseCase         $postCycleCount,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', auth()->user()?->tenant_id ?? 'default');

        $filters = array_filter([
            'status'          => $request->query('status'),
            'warehouse_id'    => $request->query('warehouse_id'),
            'count_date_from' => $request->query('count_date_from'),
            'count_date_to'   => $request->query('count_date_to'),
        ]);

        $paginator = $this->cycleCountRepo->paginate($tenantId, $filters, (int) $request->query('per_page', 20));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
            ],
        ]);
    }

    public function store(StoreCycleCountRequest $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', auth()->user()?->tenant_id ?? 'default');

        try {
            $cycleCount = $this->createCycleCount->execute(
                array_merge($request->validated(), ['tenant_id' => $tenantId])
            );
            return response()->json(['data' => $cycleCount], 201);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $cycleCount = $this->cycleCountRepo->findById($id);
        if (! $cycleCount) {
            return response()->json(['message' => 'Cycle count not found.'], 404);
        }

        $lines = $this->cycleCountRepo->linesForCount($id);

        return response()->json(['data' => $cycleCount, 'lines' => $lines]);
    }

    public function recordLine(RecordCountedQtyRequest $request, string $id): JsonResponse
    {
        try {
            $line = $this->recordCountedQty->execute(
                array_merge($request->validated(), ['cycle_count_id' => $id])
            );
            return response()->json(['data' => $line]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function post(Request $request, string $id): JsonResponse
    {
        try {
            $postedBy   = auth()->id();
            $cycleCount = $this->postCycleCount->execute([
                'cycle_count_id' => $id,
                'posted_by'      => $postedBy,
            ]);
            return response()->json(['data' => $cycleCount]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
