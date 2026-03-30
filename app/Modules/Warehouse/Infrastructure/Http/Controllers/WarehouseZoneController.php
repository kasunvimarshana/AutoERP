<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Warehouse\Application\Contracts\CreateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\DTOs\UpdateWarehouseZoneData;
use Modules\Warehouse\Application\DTOs\WarehouseZoneData;
use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Infrastructure\Http\Requests\StoreWarehouseZoneRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\UpdateWarehouseZoneRequest;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseZoneCollection;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseZoneResource;
use OpenApi\Attributes as OA;

class WarehouseZoneController extends AuthorizedController
{
    public function __construct(
        protected FindWarehouseZoneServiceInterface $findService,
        protected CreateWarehouseZoneServiceInterface $createService,
        protected UpdateWarehouseZoneServiceInterface $updateService,
        protected DeleteWarehouseZoneServiceInterface $deleteService,
    ) {}

    #[OA\Get(
        path: '/api/warehouses/{warehouse}/zones',
        summary: 'List zones for a warehouse',
        tags: ['Warehouse Zones'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'warehouse', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of zones',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: '#/components/schemas/WarehouseZoneObject')),
                        new OA\Property(property: 'meta',  ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request, int $warehouse): WarehouseZoneCollection
    {
        $this->authorize('viewAny', WarehouseZone::class);
        $filters             = $request->only(['name', 'type', 'code', 'is_active']);
        $filters['warehouse_id'] = $warehouse;

        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');

        $zones = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new WarehouseZoneCollection($zones);
    }

    #[OA\Post(
        path: '/api/warehouses/{warehouse}/zones',
        summary: 'Create a zone in a warehouse',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_id', 'name', 'type'],
                properties: [
                    new OA\Property(property: 'tenant_id',      type: 'integer', example: 1),
                    new OA\Property(property: 'name',           type: 'string',  maxLength: 255, example: 'Receiving Dock'),
                    new OA\Property(property: 'type',           type: 'string',  maxLength: 100, example: 'receiving'),
                    new OA\Property(property: 'code',           type: 'string',  nullable: true, maxLength: 50, example: 'RCV-01'),
                    new OA\Property(property: 'description',    type: 'string',  nullable: true),
                    new OA\Property(property: 'capacity',       type: 'number',  nullable: true, format: 'float'),
                    new OA\Property(property: 'metadata',       type: 'object',  nullable: true),
                    new OA\Property(property: 'is_active',      type: 'boolean', example: true),
                    new OA\Property(property: 'parent_zone_id', type: 'integer', nullable: true),
                ],
            ),
        ),
        tags: ['Warehouse Zones'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'warehouse', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 201, description: 'Zone created',
                content: new OA\JsonContent(ref: '#/components/schemas/WarehouseZoneObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreWarehouseZoneRequest $request, int $warehouse): JsonResponse
    {
        $this->authorize('create', WarehouseZone::class);
        $validated                 = $request->validated();
        $validated['warehouse_id'] = $warehouse;
        $dto                       = WarehouseZoneData::fromArray($validated);
        $zone                      = $this->createService->execute($dto->toArray());

        return (new WarehouseZoneResource($zone))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/warehouses/{warehouse}/zones/{zone}',
        summary: 'Get a warehouse zone',
        tags: ['Warehouse Zones'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'warehouse', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'zone',      in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Zone details',
                content: new OA\JsonContent(ref: '#/components/schemas/WarehouseZoneObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $warehouse, int $zone): WarehouseZoneResource
    {
        $zoneEntity = $this->findService->find($zone);
        if (! $zoneEntity) {
            abort(404);
        }
        $this->authorize('view', $zoneEntity);

        return new WarehouseZoneResource($zoneEntity);
    }

    #[OA\Put(
        path: '/api/warehouses/{warehouse}/zones/{zone}',
        summary: 'Update a warehouse zone',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name',           type: 'string'),
                    new OA\Property(property: 'type',           type: 'string'),
                    new OA\Property(property: 'code',           type: 'string',  nullable: true),
                    new OA\Property(property: 'description',    type: 'string',  nullable: true),
                    new OA\Property(property: 'capacity',       type: 'number',  nullable: true, format: 'float'),
                    new OA\Property(property: 'metadata',       type: 'object',  nullable: true),
                    new OA\Property(property: 'is_active',      type: 'boolean'),
                    new OA\Property(property: 'parent_zone_id', type: 'integer', nullable: true),
                ],
            ),
        ),
        tags: ['Warehouse Zones'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'warehouse', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'zone',      in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated zone',
                content: new OA\JsonContent(ref: '#/components/schemas/WarehouseZoneObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdateWarehouseZoneRequest $request, int $warehouse, int $zone): WarehouseZoneResource
    {
        $zoneEntity = $this->findService->find($zone);
        if (! $zoneEntity) {
            abort(404);
        }
        $this->authorize('update', $zoneEntity);
        $validated       = $request->validated();
        $validated['id'] = $zone;
        $dto             = UpdateWarehouseZoneData::fromArray($validated);
        $updated         = $this->updateService->execute($dto->toArray());

        return new WarehouseZoneResource($updated);
    }

    #[OA\Delete(
        path: '/api/warehouses/{warehouse}/zones/{zone}',
        summary: 'Delete a warehouse zone',
        tags: ['Warehouse Zones'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'warehouse', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'zone',      in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(int $warehouse, int $zone): JsonResponse
    {
        $zoneEntity = $this->findService->find($zone);
        if (! $zoneEntity) {
            abort(404);
        }
        $this->authorize('delete', $zoneEntity);
        $this->deleteService->execute(['id' => $zone]);

        return response()->json(['message' => 'Warehouse zone deleted successfully']);
    }
}
