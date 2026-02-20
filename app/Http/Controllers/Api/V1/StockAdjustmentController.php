<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\StockAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    public function __construct(
        private readonly StockAdjustmentService $stockAdjustmentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['warehouse_id', 'reason']);

        return response()->json($this->stockAdjustmentService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.adjust'), 403);

        $data = $request->validate([
            'warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'reference_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'reason' => ['required', 'in:damage,theft,expiry,correction,audit,other'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'lines.*.product_variant_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_variants,id'],
            'lines.*.quantity' => ['required', 'numeric', 'not_in:0'],
            'lines.*.unit_cost' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $adjustment = $this->stockAdjustmentService->create(
            $data,
            $request->user()->tenant_id,
            $request->user()->id
        );

        return response()->json($adjustment, 201);
    }
}
