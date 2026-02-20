<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ProductRackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductRackController extends Controller
{
    public function __construct(
        private readonly ProductRackService $productRackService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 50), 100);
        $filters = $request->only(['business_location_id', 'product_id']);

        return response()->json($this->productRackService->paginate($tenantId, $filters, $perPage));
    }

    public function upsert(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.manage'), 403);

        $data = $request->validate([
            'business_location_id' => ['required', 'uuid', 'exists:business_locations,id'],
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'rack' => ['sometimes', 'nullable', 'string', 'max:100'],
            'row' => ['sometimes', 'nullable', 'string', 'max:100'],
            'position' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->productRackService->upsert($data), 200);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.manage'), 403);
        $this->productRackService->delete($id);

        return response()->json(null, 204);
    }
}
