<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Http\Requests\StoreSalesOrderLineRequest;
use Modules\Sales\Http\Requests\UpdateSalesOrderLineRequest;
use Modules\Sales\Http\Resources\SalesOrderLineResource;
use Modules\Sales\Repositories\Contracts\SalesOrderLineRepositoryInterface;

/**
 * Sales Order Line API Controller
 *
 * Handles API requests for sales order line management.
 */
class SalesOrderLineController extends Controller
{
    /**
     * SalesOrderLineController constructor.
     */
    public function __construct(
        protected SalesOrderLineRepositoryInterface $salesOrderLineRepository
    ) {}

    /**
     * Display a listing of sales order lines.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $lines = $this->salesOrderLineRepository->paginate($perPage);

        return SalesOrderLineResource::collection($lines)
            ->response();
    }

    /**
     * Store a newly created sales order line.
     */
    public function store(StoreSalesOrderLineRequest $request): JsonResponse
    {
        $line = $this->salesOrderLineRepository->create($request->validated());

        return (new SalesOrderLineResource($line))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified sales order line.
     */
    public function show(int $id): JsonResponse
    {
        $line = $this->salesOrderLineRepository->findById($id);

        if (! $line) {
            return response()->json([
                'message' => 'Sales order line not found',
            ], 404);
        }

        return (new SalesOrderLineResource($line))->response();
    }

    /**
     * Update the specified sales order line.
     */
    public function update(UpdateSalesOrderLineRequest $request, int $id): JsonResponse
    {
        $line = $this->salesOrderLineRepository->update($id, $request->validated());

        return (new SalesOrderLineResource($line))->response();
    }

    /**
     * Remove the specified sales order line.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->salesOrderLineRepository->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Sales order line not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Sales order line deleted successfully',
        ], 200);
    }

    /**
     * Get lines by sales order.
     */
    public function getByOrder(int $salesOrderId): JsonResponse
    {
        $lines = $this->salesOrderLineRepository->getLinesByOrder($salesOrderId);

        return SalesOrderLineResource::collection($lines)
            ->response();
    }
}
