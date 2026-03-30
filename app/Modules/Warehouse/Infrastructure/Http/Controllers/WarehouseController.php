<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseData;
use Modules\Warehouse\Application\DTOs\UpdateWarehouseData;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Infrastructure\Http\Requests\StoreWarehouseRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\UpdateWarehouseRequest;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseCollection;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseResource;
use OpenApi\Attributes as OA;

class WarehouseController extends AuthorizedController
{
    public function __construct(
        protected FindWarehouseServiceInterface $findService,
        protected CreateWarehouseServiceInterface $createService,
        protected UpdateWarehouseServiceInterface $updateService,
        protected DeleteWarehouseServiceInterface $deleteService,
    ) {}

    #[OA\Get(
        path: '/api/warehouses',
        summary: 'List warehouses',
        tags: ['Warehouses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'name',        in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type',        in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'code',        in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'location_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active',   in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page',    in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',        in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',        in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'include',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of warehouses',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: '#/components/schemas/WarehouseObject')),
                        new OA\Property(property: 'meta',  ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): WarehouseCollection
    {
        $this->authorize('viewAny', Warehouse::class);
        $filters = $request->only(['name', 'type', 'code', 'location_id', 'is_active']);

        if ($request->has('location_id')) {
            $filters['location_id'] = $request->integer('location_id');
        }

        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');

        $warehouses = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new WarehouseCollection($warehouses);
    }

    #[OA\Post(
        path: '/api/warehouses',
        summary: 'Create warehouse',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_id', 'name', 'type'],
                properties: [
                    new OA\Property(property: 'tenant_id',   type: 'integer', example: 1),
                    new OA\Property(property: 'name',        type: 'string',  maxLength: 255, example: 'Main Warehouse'),
                    new OA\Property(property: 'type',        type: 'string',  maxLength: 100, example: 'standard'),
                    new OA\Property(property: 'code',        type: 'string',  nullable: true, maxLength: 50, example: 'WH-001'),
                    new OA\Property(property: 'description', type: 'string',  nullable: true),
                    new OA\Property(property: 'address',     type: 'string',  nullable: true),
                    new OA\Property(property: 'capacity',    type: 'number',  nullable: true, format: 'float'),
                    new OA\Property(property: 'location_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
                    new OA\Property(property: 'is_active',   type: 'boolean', example: true),
                ],
            ),
        ),
        tags: ['Warehouses'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Warehouse created',
                content: new OA\JsonContent(ref: '#/components/schemas/WarehouseObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $this->authorize('create', Warehouse::class);
        $dto       = WarehouseData::fromArray($request->validated());
        $warehouse = $this->createService->execute($dto->toArray());

        return (new WarehouseResource($warehouse))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/warehouses/{id}',
        summary: 'Get warehouse',
        tags: ['Warehouses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Warehouse details',
                content: new OA\JsonContent(ref: '#/components/schemas/WarehouseObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): WarehouseResource
    {
        $warehouse = $this->findService->find($id);
        if (! $warehouse) {
            abort(404);
        }
        $this->authorize('view', $warehouse);

        return new WarehouseResource($warehouse);
    }

    #[OA\Put(
        path: '/api/warehouses/{id}',
        summary: 'Update warehouse',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name',        type: 'string'),
                    new OA\Property(property: 'type',        type: 'string'),
                    new OA\Property(property: 'code',        type: 'string',  nullable: true),
                    new OA\Property(property: 'description', type: 'string',  nullable: true),
                    new OA\Property(property: 'address',     type: 'string',  nullable: true),
                    new OA\Property(property: 'capacity',    type: 'number',  nullable: true, format: 'float'),
                    new OA\Property(property: 'location_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
                    new OA\Property(property: 'is_active',   type: 'boolean'),
                ],
            ),
        ),
        tags: ['Warehouses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated warehouse',
                content: new OA\JsonContent(ref: '#/components/schemas/WarehouseObject')),
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
    public function update(UpdateWarehouseRequest $request, int $id): WarehouseResource
    {
        $warehouse = $this->findService->find($id);
        if (! $warehouse) {
            abort(404);
        }
        $this->authorize('update', $warehouse);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = UpdateWarehouseData::fromArray($validated);
        $updated         = $this->updateService->execute($dto->toArray());

        return new WarehouseResource($updated);
    }

    #[OA\Delete(
        path: '/api/warehouses/{id}',
        summary: 'Delete warehouse',
        tags: ['Warehouses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
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
    public function destroy(int $id): JsonResponse
    {
        $warehouse = $this->findService->find($id);
        if (! $warehouse) {
            abort(404);
        }
        $this->authorize('delete', $warehouse);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Warehouse deleted successfully']);
    }

    #[OA\Get(
        path: '/api/warehouses/by-location/{locationId}',
        summary: 'Get warehouses by location',
        description: 'Retrieve all warehouses associated with a given location.',
        tags: ['Warehouses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'locationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of warehouses for the location',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WarehouseObject')),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function byLocation(int $locationId): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);
        $items = $this->findService->getByLocation($locationId);

        return response()->json([
            'data' => WarehouseResource::collection(collect($items)),
        ]);
    }
}
