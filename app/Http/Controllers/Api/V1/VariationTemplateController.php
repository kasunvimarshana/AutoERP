<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\VariationTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VariationTemplateController extends Controller
{
    public function __construct(
        private readonly VariationTemplateService $variationTemplateService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 50), 200);

        return response()->json($this->variationTemplateService->paginate($tenantId, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'values' => ['sometimes', 'array'],
            'values.*' => ['string', 'max:100'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->variationTemplateService->create($data), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'values' => ['sometimes', 'array'],
            'values.*' => ['string', 'max:100'],
        ]);

        return response()->json($this->variationTemplateService->update($id, $data));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);
        $this->variationTemplateService->delete($id);

        return response()->json(null, 204);
    }
}
