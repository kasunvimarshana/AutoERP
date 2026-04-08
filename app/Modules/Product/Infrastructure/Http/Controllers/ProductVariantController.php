<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\ProductVariantServiceInterface;
use Modules\Product\Application\DTOs\ProductVariantData;
use Modules\Product\Infrastructure\Http\Resources\ProductVariantResource;

class ProductVariantController extends AuthorizedController
{
    public function __construct(private readonly ProductVariantServiceInterface $service) {}

    public function index(Request $request, int $productId): JsonResponse
    {
        $items = $this->service->getByProduct($productId);
        return response()->json(ProductVariantResource::collection($items));
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        $data               = array_merge($request->all(), [
            'product_id' => $productId,
            'tenant_id'  => $request->header('X-Tenant-ID'),
        ]);
        $dto  = ProductVariantData::fromArray($data);
        $item = $this->service->create($dto);
        return response()->json(new ProductVariantResource($item), 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service->find($id);
        return response()->json(new ProductVariantResource($item));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = $this->service->update($id, $request->all());
        return response()->json(new ProductVariantResource($item));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}
