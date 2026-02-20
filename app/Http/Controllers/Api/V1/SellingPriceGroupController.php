<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SellingPriceGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellingPriceGroupController extends Controller
{
    public function __construct(
        private readonly SellingPriceGroupService $sellingPriceGroupService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['is_active']);

        return response()->json($this->sellingPriceGroupService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'prices' => ['sometimes', 'array'],
            'prices.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'prices.*.product_variant_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_variants,id'],
            'prices.*.price' => ['required', 'numeric', 'min:0'],
            'prices.*.currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->sellingPriceGroupService->create($data), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return response()->json($this->sellingPriceGroupService->update($id, $data));
    }

    public function upsertPrice(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'product_variant_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_variants,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $price = $this->sellingPriceGroupService->upsertPrice($id, $data);

        return response()->json($price);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);
        $this->sellingPriceGroupService->delete($id);

        return response()->json(null, 204);
    }
}
