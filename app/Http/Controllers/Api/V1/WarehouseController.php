<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);

        $warehouses = Warehouse::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($warehouses);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'organization_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
            'address' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['tenant_id'] = $request->user()->tenant_id;

        $warehouse = Warehouse::create($data);

        return response()->json($warehouse, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.update'), 403);

        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        return response()->json($warehouse->fresh());
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.delete'), 403);
        Warehouse::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
