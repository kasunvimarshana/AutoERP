<?php

namespace Modules\Inventory\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\UseCases\GetInventoryValuationReportUseCase;
use Modules\Inventory\Application\UseCases\RecordValuationEntryUseCase;
use Modules\Inventory\Domain\Contracts\InventoryValuationRepositoryInterface;
use Modules\Inventory\Presentation\Requests\StoreValuationEntryRequest;

class InventoryValuationController extends Controller
{
    public function __construct(
        private InventoryValuationRepositoryInterface $valuationRepo,
        private RecordValuationEntryUseCase           $recordUseCase,
        private GetInventoryValuationReportUseCase    $reportUseCase,
    ) {}

    /** Paginated ledger of all valuation entries for the tenant. */
    public function index(): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;
        $filters  = request()->only(['product_id', 'movement_type', 'valuation_method']);
        $perPage  = (int) request()->get('per_page', 20);

        return response()->json($this->valuationRepo->paginate($tenantId, $filters, $perPage));
    }

    /** Record a manual valuation entry (e.g. after a stock adjustment). */
    public function store(StoreValuationEntryRequest $request): JsonResponse
    {
        $entry = $this->recordUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($entry, 201);
    }

    /** Current stock valuation report: latest running balance per product. */
    public function report(): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;

        return response()->json($this->reportUseCase->execute($tenantId));
    }
}
