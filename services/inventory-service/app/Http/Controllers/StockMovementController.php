<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StockMovementController extends BaseController
{
    // -------------------------------------------------------------------------
    // GET /api/stock-movements
    // -------------------------------------------------------------------------

    /**
     * List all stock movements for the authenticated tenant.
     * Accepts: per_page, inventory_id, product_id, type, from_date, to_date
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->attributes->get('tenant_id');

            $query = StockMovement::query()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->when($request->input('inventory_id'), fn ($q, $v) => $q->where('inventory_id', $v))
                ->when($request->input('product_id'),   fn ($q, $v) => $q->where('product_id', $v))
                ->when($request->input('warehouse_id'), fn ($q, $v) => $q->where('warehouse_id', $v))
                ->when($request->input('type'),         fn ($q, $v) => $q->where('type', $v))
                ->when($request->input('from_date'),    fn ($q, $v) => $q->where('created_at', '>=', $v))
                ->when($request->input('to_date'),      fn ($q, $v) => $q->where('created_at', '<=', $v))
                ->orderBy('created_at', 'desc');

            if ($request->has('per_page')) {
                $perPage = max(1, $request->integer('per_page', 15));
                $result  = $query->paginate($perPage);
            } else {
                $result = $query->get();
            }

            return $this->paginatedResponse($result, 'Stock movements retrieved.');
        } catch (\Throwable $e) {
            Log::error('StockMovementController@index', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to retrieve stock movements.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/stock-movements/{id}
    // -------------------------------------------------------------------------

    public function show(string $id): JsonResponse
    {
        $movement = StockMovement::find($id);

        if (! $movement) {
            return $this->notFoundResponse('Stock movement not found.');
        }

        $movement->load('inventory', 'warehouse');

        return $this->successResponse($movement, 'Stock movement retrieved.');
    }

    // -------------------------------------------------------------------------
    // GET /api/stock-movements/by-reference
    // -------------------------------------------------------------------------

    /**
     * Find all movements associated with a given reference (e.g., order ID).
     */
    public function byReference(Request $request): JsonResponse
    {
        $request->validate([
            'reference_type' => ['required', 'string'],
            'reference_id'   => ['required', 'string'],
        ]);

        try {
            $tenantId = $request->attributes->get('tenant_id');

            $movements = StockMovement::where('reference_type', $request->input('reference_type'))
                ->where('reference_id', $request->input('reference_id'))
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse($movements, 'Stock movements by reference retrieved.');
        } catch (\Throwable $e) {
            Log::error('StockMovementController@byReference', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to retrieve movements by reference.', null, 500);
        }
    }
}
