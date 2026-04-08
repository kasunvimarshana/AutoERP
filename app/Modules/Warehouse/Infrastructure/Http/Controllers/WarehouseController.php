<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Warehouse\Application\Contracts\WarehouseServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseData;
use Modules\Warehouse\Infrastructure\Http\Requests\StoreWarehouseRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\UpdateWarehouseRequest;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseResource;

/**
 * @OA\Tag(name="Warehouse - Warehouses", description="Warehouse management")
 */
final class WarehouseController extends AuthorizedController
{
    public function __construct(private readonly WarehouseServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/warehouse/warehouses",
     *     tags={"Warehouse - Warehouses"},
     *     summary="List all warehouses for the authenticated tenant",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_active", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated warehouse list")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['type', 'is_active']));
        $perPage = (int) $request->query('per_page', 15);

        return WarehouseResource::collection(
            $this->service->list($filters, $perPage)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/warehouse/warehouses",
     *     tags={"Warehouse - Warehouses"},
     *     summary="Create a new warehouse",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreWarehouseRequest")),
     *     @OA\Response(response=201, description="Warehouse created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $tenantId  = (int) $request->header('X-Tenant-ID');
        $dto       = WarehouseData::fromArray($request->validated());
        $warehouse = $this->service->create($dto, $tenantId);

        return (new WarehouseResource($warehouse))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/warehouse/warehouses/{id}",
     *     tags={"Warehouse - Warehouses"},
     *     summary="Get warehouse by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Warehouse details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        return (new WarehouseResource($this->service->find($id)))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/warehouse/warehouses/{id}",
     *     tags={"Warehouse - Warehouses"},
     *     summary="Update a warehouse",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateWarehouseRequest")),
     *     @OA\Response(response=200, description="Warehouse updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateWarehouseRequest $request, int $id): JsonResponse
    {
        $dto       = WarehouseData::fromArray($request->validated());
        $warehouse = $this->service->update($id, $dto);

        return (new WarehouseResource($warehouse))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/warehouse/warehouses/{id}",
     *     tags={"Warehouse - Warehouses"},
     *     summary="Delete a warehouse",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Warehouse deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
