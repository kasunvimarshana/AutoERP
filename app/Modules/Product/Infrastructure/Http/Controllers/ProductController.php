<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\ProductServiceInterface;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Infrastructure\Http\Resources\ProductResource;

class ProductController extends AuthorizedController
{
    public function __construct(private readonly ProductServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters              = $request->only(['status', 'type', 'category_id']);
        $filters['tenant_id'] = $request->header('X-Tenant-ID');
        $items                = $this->service->list($filters);
        return response()->json(ProductResource::collection($items));
    }

    public function store(Request $request): JsonResponse
    {
        $data = array_merge($request->all(), ['tenant_id' => $request->header('X-Tenant-ID')]);
        $dto  = ProductData::fromArray($data);
        $item = $this->service->create($dto);
        return response()->json(new ProductResource($item), 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service->find($id);
        return response()->json(new ProductResource($item));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = $this->service->update($id, $request->all());
        return response()->json(new ProductResource($item));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function activate(int $id): JsonResponse
    {
        $item = $this->service->activate($id);
        return response()->json(new ProductResource($item));
    }

    public function discontinue(int $id): JsonResponse
    {
        $item = $this->service->discontinue($id);
        return response()->json(new ProductResource($item));
    }
}
