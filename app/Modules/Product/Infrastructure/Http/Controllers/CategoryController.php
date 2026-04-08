<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CategoryServiceInterface;
use Modules\Product\Application\DTOs\CategoryData;
use Modules\Product\Infrastructure\Http\Resources\CategoryResource;

class CategoryController extends AuthorizedController
{
    public function __construct(private readonly CategoryServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID', 0);
        $items    = $this->service->list(['tenant_id' => $tenantId]);
        return response()->json(CategoryResource::collection($items));
    }

    public function store(Request $request): JsonResponse
    {
        $data = array_merge($request->all(), ['tenant_id' => $request->header('X-Tenant-ID')]);
        $dto  = CategoryData::fromArray($data);
        $item = $this->service->create($dto);
        return response()->json(new CategoryResource($item), 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service->find($id);
        return response()->json(new CategoryResource($item));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = $this->service->update($id, $request->all());
        return response()->json(new CategoryResource($item));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function tree(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID', 0);
        return response()->json(['data' => $this->service->getTree($tenantId)]);
    }
}
