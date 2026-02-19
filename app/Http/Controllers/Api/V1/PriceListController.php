<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PriceList;
use App\Models\PriceRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceListController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('price-lists.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);

        $priceLists = PriceList::where('tenant_id', $tenantId)
            ->paginate($perPage);

        return response()->json($priceLists);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('price-lists.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'valid_from' => ['sometimes', 'nullable', 'date'],
            'valid_until' => ['sometimes', 'nullable', 'date', 'after_or_equal:valid_from'],
            'conditions' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'organization_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        $priceList = PriceList::create($data);

        return response()->json($priceList, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('price-lists.update'), 403);

        $tenantId = $request->user()->tenant_id;
        $priceList = PriceList::where('tenant_id', $tenantId)->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'valid_from' => ['sometimes', 'nullable', 'date'],
            'valid_until' => ['sometimes', 'nullable', 'date', 'after_or_equal:valid_from'],
            'conditions' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'organization_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
        ]);

        $priceList->update($data);

        return response()->json($priceList);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('price-lists.delete'), 403);

        $tenantId = $request->user()->tenant_id;
        $priceList = PriceList::where('tenant_id', $tenantId)->findOrFail($id);
        $priceList->delete();

        return response()->json(null, 204);
    }

    public function rules(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('price-lists.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $priceList = PriceList::where('tenant_id', $tenantId)->findOrFail($id);

        $perPage = min((int) $request->query('per_page', 15), 100);
        $rules = $priceList->rules()->paginate($perPage);

        return response()->json($rules);
    }

    public function storeRule(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('price-lists.create'), 403);

        $tenantId = $request->user()->tenant_id;
        $priceList = PriceList::where('tenant_id', $tenantId)->findOrFail($id);

        $data = $request->validate([
            'product_id' => ['sometimes', 'nullable', 'uuid', 'exists:products,id'],
            'variant_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_variants,id'],
            'pricing_type' => ['required', 'string'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_quantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_quantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tiers' => ['sometimes', 'array'],
            'conditions' => ['sometimes', 'array'],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['tenant_id'] = $tenantId;
        $data['price_list_id'] = $priceList->id;

        $rule = PriceRule::create($data);

        return response()->json($rule, 201);
    }
}
