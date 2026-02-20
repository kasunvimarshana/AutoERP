<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\StockTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function __construct(
        private readonly StockTransferService $transferService
    ) {}

    /**
     * GET /api/v1/stock-transfers
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('stock_transfers.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['status', 'from_warehouse_id', 'to_warehouse_id']);

        return response()->json(
            $this->transferService->paginate($tenantId, $filters, $perPage)
        );
    }

    /**
     * POST /api/v1/stock-transfers
     * Create a draft stock transfer.
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('stock_transfers.create'), 403);

        $validated = $request->validate([
            'from_warehouse_id' => ['required', 'string', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'string', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'string', 'exists:products,id'],
            'lines.*.variant_id' => ['nullable', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.00000001'],
            'lines.*.cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.batch_number' => ['nullable', 'string', 'max:100'],
            'lines.*.lot_number' => ['nullable', 'string', 'max:100'],
            'lines.*.expiry_date' => ['nullable', 'date'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;

        return response()->json(
            ['data' => $this->transferService->create($validated)],
            201
        );
    }

    /**
     * PATCH /api/v1/stock-transfers/{id}/dispatch
     * Mark the transfer as in_transit and deduct stock from the source warehouse.
     */
    public function dispatch(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('stock_transfers.dispatch'), 403);

        $tenantId = $request->user()->tenant_id;

        return response()->json(
            ['data' => $this->transferService->dispatch($tenantId, $id)]
        );
    }

    /**
     * PATCH /api/v1/stock-transfers/{id}/receive
     * Mark the transfer as received and add stock to the destination warehouse.
     */
    public function receive(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('stock_transfers.receive'), 403);

        $tenantId = $request->user()->tenant_id;

        return response()->json(
            ['data' => $this->transferService->receive($tenantId, $id)]
        );
    }

    /**
     * PATCH /api/v1/stock-transfers/{id}/cancel
     * Cancel a draft or in_transit transfer.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('stock_transfers.cancel'), 403);

        $tenantId = $request->user()->tenant_id;

        return response()->json(
            ['data' => $this->transferService->cancel($tenantId, $id)]
        );
    }
}
