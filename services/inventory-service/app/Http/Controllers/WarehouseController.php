<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class WarehouseController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /api/v1/warehouses
    // -------------------------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $perPage  = min(max((int) $request->query('per_page', 15), 1), 100);

        $warehouses = Warehouse::byTenant($tenantId)
            ->when($request->query('active') !== null, function ($q) use ($request) {
                $active = filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN);
                return $q->where('is_active', $active);
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'data' => $warehouses->map(fn (Warehouse $w) => $w->toArray()),
            'meta' => [
                'current_page' => $warehouses->currentPage(),
                'last_page'    => $warehouses->lastPage(),
                'per_page'     => $warehouses->perPage(),
                'total'        => $warehouses->total(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/warehouses/{id}
    // -------------------------------------------------------------------------

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $warehouse = Warehouse::byTenant($tenantId)->find($id);

        if ($warehouse === null) {
            return response()->json(['message' => 'Warehouse not found.'], 404);
        }

        return response()->json(['data' => $warehouse->toArray()]);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/warehouses
    // -------------------------------------------------------------------------

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:50',
                Rule::unique('warehouses')->where('tenant_id', $tenantId)],
            'address'   => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $warehouse = Warehouse::create([
                'tenant_id' => $tenantId,
                'name'      => $data['name'],
                'code'      => strtoupper($data['code']),
                'address'   => $data['address'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            return response()->json(['message' => 'Warehouse created.', 'data' => $warehouse], 201);
        } catch (Throwable $e) {
            Log::error('[WarehouseController] store failed.', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to create warehouse.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal error.',
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/warehouses/{id}
    // -------------------------------------------------------------------------

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $warehouse = Warehouse::byTenant($tenantId)->find($id);

        if ($warehouse === null) {
            return response()->json(['message' => 'Warehouse not found.'], 404);
        }

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:255'],
            'code'      => ['sometimes', 'string', 'max:50',
                Rule::unique('warehouses')->where('tenant_id', $tenantId)->ignore($id)],
            'address'   => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        try {
            if (isset($data['code'])) {
                $data['code'] = strtoupper($data['code']);
            }
            $warehouse->update($data);

            return response()->json(['message' => 'Warehouse updated.', 'data' => $warehouse]);
        } catch (Throwable $e) {
            Log::error('[WarehouseController] update failed.', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to update warehouse.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal error.',
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/warehouses/{id}
    // -------------------------------------------------------------------------

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $warehouse = Warehouse::byTenant($tenantId)->find($id);

        if ($warehouse === null) {
            return response()->json(['message' => 'Warehouse not found.'], 404);
        }

        try {
            $warehouse->delete();
            return response()->json(['message' => 'Warehouse deleted.']);
        } catch (Throwable $e) {
            Log::error('[WarehouseController] destroy failed.', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to delete warehouse.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal error.',
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function resolveTenantId(Request $request): string
    {
        return (string) ($request->header('X-Tenant-ID') ?? $request->query('tenant_id', 'default'));
    }
}
