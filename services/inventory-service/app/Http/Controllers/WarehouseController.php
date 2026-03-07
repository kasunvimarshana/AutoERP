<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WarehouseController extends BaseController
{
    // -------------------------------------------------------------------------
    // GET /api/warehouses
    // -------------------------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->attributes->get('tenant_id');

            $query = Warehouse::query()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
                ->orderBy('name');

            if ($request->has('per_page')) {
                $perPage = max(1, $request->integer('per_page', 15));
                $result  = $query->paginate($perPage);
            } else {
                $result = $query->get();
            }

            return $this->paginatedResponse($result, 'Warehouses retrieved.');
        } catch (\Throwable $e) {
            Log::error('WarehouseController@index', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to retrieve warehouses.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/warehouses
    // -------------------------------------------------------------------------

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'code'       => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'address'    => ['sometimes', 'nullable', 'array'],
            'is_active'  => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        try {
            $tenantId = $request->attributes->get('tenant_id');

            // Only one default warehouse per tenant
            if (! empty($validated['is_default']) && $validated['is_default']) {
                Warehouse::where('tenant_id', $tenantId)->update(['is_default' => false]);
            }

            $warehouse = Warehouse::create(array_merge($validated, ['tenant_id' => $tenantId]));

            return $this->createdResponse($warehouse, 'Warehouse created.');
        } catch (\Throwable $e) {
            Log::error('WarehouseController@store', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to create warehouse.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/warehouses/{id}
    // -------------------------------------------------------------------------

    public function show(string $id): JsonResponse
    {
        $warehouse = Warehouse::with('inventoryItems')->find($id);

        if (! $warehouse) {
            return $this->notFoundResponse('Warehouse not found.');
        }

        return $this->successResponse($warehouse, 'Warehouse retrieved.');
    }

    // -------------------------------------------------------------------------
    // PUT|PATCH /api/warehouses/{id}
    // -------------------------------------------------------------------------

    public function update(Request $request, string $id): JsonResponse
    {
        $warehouse = Warehouse::find($id);

        if (! $warehouse) {
            return $this->notFoundResponse('Warehouse not found.');
        }

        $validated = $request->validate([
            'name'       => ['sometimes', 'string', 'max:255'],
            'code'       => ['sometimes', 'string', 'max:50', 'unique:warehouses,code,' . $id],
            'address'    => ['sometimes', 'nullable', 'array'],
            'is_active'  => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        try {
            $tenantId = $request->attributes->get('tenant_id');

            if (! empty($validated['is_default']) && $validated['is_default']) {
                Warehouse::where('tenant_id', $tenantId)
                         ->where('id', '!=', $id)
                         ->update(['is_default' => false]);
            }

            $warehouse->fill($validated)->save();

            return $this->successResponse($warehouse->fresh(), 'Warehouse updated.');
        } catch (\Throwable $e) {
            Log::error('WarehouseController@update', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to update warehouse.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // DELETE /api/warehouses/{id}
    // -------------------------------------------------------------------------

    public function destroy(string $id): JsonResponse
    {
        $warehouse = Warehouse::find($id);

        if (! $warehouse) {
            return $this->notFoundResponse('Warehouse not found.');
        }

        try {
            $warehouse->delete();

            return $this->successResponse(null, 'Warehouse deleted.');
        } catch (\Throwable $e) {
            Log::error('WarehouseController@destroy', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to delete warehouse.', null, 500);
        }
    }
}
