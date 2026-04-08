<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\UnitOfMeasureServiceInterface;
use Modules\Product\Application\DTOs\UnitOfMeasureData;
use Modules\Product\Infrastructure\Http\Resources\UnitOfMeasureResource;

class UnitOfMeasureController extends AuthorizedController
{
    public function __construct(private readonly UnitOfMeasureServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->service->list(['tenant_id' => $request->header('X-Tenant-ID')]);
        return response()->json(UnitOfMeasureResource::collection($items));
    }

    public function store(Request $request): JsonResponse
    {
        $data = array_merge($request->all(), ['tenant_id' => $request->header('X-Tenant-ID')]);
        $dto  = UnitOfMeasureData::fromArray($data);
        $item = $this->service->create($dto);
        return response()->json(new UnitOfMeasureResource($item), 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service->find($id);
        return response()->json(new UnitOfMeasureResource($item));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = $this->service->update($id, $request->all());
        return response()->json(new UnitOfMeasureResource($item));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}
