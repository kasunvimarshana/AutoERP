<?php
declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Application\Contracts\ProductVariantServiceInterface;
use Modules\Product\Infrastructure\Http\Resources\ProductVariantResource;

class ProductVariantController extends Controller
{
    public function __construct(
        private readonly ProductVariantServiceInterface $variantService,
    ) {}

    public function index(Request $request, string $productId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $variants = $this->variantService->getVariantsByProduct($tenantId, $productId);
        return response()->json(ProductVariantResource::collection($variants));
    }

    public function store(Request $request, string $productId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $variant = $this->variantService->createVariant($tenantId, $productId, $request->all());
        return response()->json(new ProductVariantResource($variant), 201);
    }

    public function show(Request $request, string $productId, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $variant = $this->variantService->getVariant($tenantId, $id);
        return response()->json(new ProductVariantResource($variant));
    }

    public function update(Request $request, string $productId, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $variant = $this->variantService->updateVariant($tenantId, $id, $request->all());
        return response()->json(new ProductVariantResource($variant));
    }

    public function destroy(Request $request, string $productId, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->variantService->deleteVariant($tenantId, $id);
        return response()->json(null, 204);
    }
}
