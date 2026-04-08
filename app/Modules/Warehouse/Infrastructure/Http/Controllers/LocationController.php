<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Warehouse\Application\Contracts\LocationServiceInterface;
use Modules\Warehouse\Application\DTOs\LocationData;
use Modules\Warehouse\Infrastructure\Http\Requests\StoreLocationRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\UpdateLocationRequest;
use Modules\Warehouse\Infrastructure\Http\Resources\LocationResource;

/**
 * @OA\Tag(name="Warehouse - Locations", description="Warehouse location management")
 */
final class LocationController extends AuthorizedController
{
    public function __construct(private readonly LocationServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/warehouse/locations",
     *     tags={"Warehouse - Locations"},
     *     summary="List all locations",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="warehouse_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_active", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated location list")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['warehouse_id', 'type', 'is_active']));
        $perPage = (int) $request->query('per_page', 15);

        return LocationResource::collection(
            $this->service->list($filters, $perPage)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/warehouse/locations",
     *     tags={"Warehouse - Locations"},
     *     summary="Create a new location",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreLocationRequest")),
     *     @OA\Response(response=201, description="Location created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreLocationRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $dto      = LocationData::fromArray($request->validated());
        $location = $this->service->create($dto, $tenantId);

        return (new LocationResource($location))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/warehouse/locations/{id}",
     *     tags={"Warehouse - Locations"},
     *     summary="Get location by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Location details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        return (new LocationResource($this->service->find($id)))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/warehouse/locations/{id}",
     *     tags={"Warehouse - Locations"},
     *     summary="Update a location",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateLocationRequest")),
     *     @OA\Response(response=200, description="Location updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateLocationRequest $request, int $id): JsonResponse
    {
        $dto      = LocationData::fromArray($request->validated());
        $location = $this->service->update($id, $dto);

        return (new LocationResource($location))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/warehouse/locations/{id}",
     *     tags={"Warehouse - Locations"},
     *     summary="Delete a location",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Location deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/warehouse/locations/{id}/tree",
     *     tags={"Warehouse - Locations"},
     *     summary="Get hierarchical location tree for a warehouse",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Warehouse ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Location tree")
     * )
     */
    public function getTree(int $id): JsonResponse
    {
        $tree = $this->service->getTree($id);

        return response()->json([
            'data' => LocationResource::collection($tree),
        ]);
    }
}
