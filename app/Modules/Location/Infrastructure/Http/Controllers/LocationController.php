<?php

declare(strict_types=1);

namespace Modules\Location\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Location\Application\Contracts\CreateLocationServiceInterface;
use Modules\Location\Application\Contracts\DeleteLocationServiceInterface;
use Modules\Location\Application\Contracts\FindLocationServiceInterface;
use Modules\Location\Application\Contracts\MoveLocationServiceInterface;
use Modules\Location\Application\Contracts\UpdateLocationServiceInterface;
use Modules\Location\Application\DTOs\LocationData;
use Modules\Location\Application\DTOs\MoveLocationData;
use Modules\Location\Application\DTOs\UpdateLocationData;
use Modules\Location\Domain\Entities\Location;
use Modules\Location\Infrastructure\Http\Requests\MoveLocationRequest;
use Modules\Location\Infrastructure\Http\Requests\StoreLocationRequest;
use Modules\Location\Infrastructure\Http\Requests\UpdateLocationRequest;
use Modules\Location\Infrastructure\Http\Resources\LocationCollection;
use Modules\Location\Infrastructure\Http\Resources\LocationResource;
use Modules\Location\Infrastructure\Http\Resources\LocationTreeResource;
use OpenApi\Attributes as OA;

class LocationController extends AuthorizedController
{
    public function __construct(
        protected FindLocationServiceInterface $findService,
        protected CreateLocationServiceInterface $createService,
        protected UpdateLocationServiceInterface $updateService,
        protected DeleteLocationServiceInterface $deleteService,
        protected MoveLocationServiceInterface $moveService,
    ) {}

    #[OA\Get(
        path: '/api/locations',
        summary: 'List locations',
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'name',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'code',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'parent_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'include',   in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of locations',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: '#/components/schemas/LocationObject')),
                        new OA\Property(property: 'meta',  ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): LocationCollection
    {
        $this->authorize('viewAny', Location::class);
        $filters = $request->only(['name', 'type', 'code', 'parent_id']);

        if ($request->has('parent_id')) {
            $filters['parent_id'] = $request->integer('parent_id');
        }

        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');

        $locations = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new LocationCollection($locations);
    }

    #[OA\Post(
        path: '/api/locations',
        summary: 'Create location',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_id', 'name', 'type'],
                properties: [
                    new OA\Property(property: 'tenant_id',   type: 'integer', example: 1),
                    new OA\Property(property: 'name',        type: 'string',  maxLength: 255, example: 'United States'),
                    new OA\Property(property: 'type',        type: 'string',  maxLength: 100, example: 'country'),
                    new OA\Property(property: 'code',        type: 'string',  nullable: true, maxLength: 50, example: 'US'),
                    new OA\Property(property: 'description', type: 'string',  nullable: true),
                    new OA\Property(property: 'latitude',    type: 'number',  nullable: true, format: 'float'),
                    new OA\Property(property: 'longitude',   type: 'number',  nullable: true, format: 'float'),
                    new OA\Property(property: 'timezone',    type: 'string',  nullable: true, example: 'America/New_York'),
                    new OA\Property(property: 'parent_id',   type: 'integer', nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Location created',
                content: new OA\JsonContent(ref: '#/components/schemas/LocationObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreLocationRequest $request): JsonResponse
    {
        $this->authorize('create', Location::class);
        $dto      = LocationData::fromArray($request->validated());
        $location = $this->createService->execute($dto->toArray());

        return (new LocationResource($location))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/locations/{id}',
        summary: 'Get location',
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Location details',
                content: new OA\JsonContent(ref: '#/components/schemas/LocationObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): LocationResource
    {
        $location = $this->findService->find($id);
        if (! $location) {
            abort(404);
        }
        $this->authorize('view', $location);

        return new LocationResource($location);
    }

    #[OA\Put(
        path: '/api/locations/{id}',
        summary: 'Update location',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name',        type: 'string'),
                    new OA\Property(property: 'type',        type: 'string'),
                    new OA\Property(property: 'code',        type: 'string',  nullable: true),
                    new OA\Property(property: 'description', type: 'string',  nullable: true),
                    new OA\Property(property: 'latitude',    type: 'number',  nullable: true, format: 'float'),
                    new OA\Property(property: 'longitude',   type: 'number',  nullable: true, format: 'float'),
                    new OA\Property(property: 'timezone',    type: 'string',  nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated location',
                content: new OA\JsonContent(ref: '#/components/schemas/LocationObject')),
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
    public function update(UpdateLocationRequest $request, int $id): LocationResource
    {
        $location = $this->findService->find($id);
        if (! $location) {
            abort(404);
        }
        $this->authorize('update', $location);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = UpdateLocationData::fromArray($validated);
        $updated         = $this->updateService->execute($dto->toArray());

        return new LocationResource($updated);
    }

    #[OA\Delete(
        path: '/api/locations/{id}',
        summary: 'Delete location',
        tags: ['Locations'],
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
        $location = $this->findService->find($id);
        if (! $location) {
            abort(404);
        }
        $this->authorize('delete', $location);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Location deleted successfully']);
    }

    #[OA\Get(
        path: '/api/locations/tree',
        summary: 'Get location tree',
        description: 'Retrieve the full hierarchical tree of locations.',
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'root_id', in: 'query', required: false, description: 'Optional root node ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hierarchical tree of locations',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LocationObject')),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function tree(Request $request): LocationTreeResource
    {
        $this->authorize('viewAny', Location::class);
        $tenantId = (int) tenant_id();
        $rootId   = $request->input('root_id') !== null
            ? (int) $request->input('root_id')
            : null;
        $tree = $this->findService->getTree($tenantId, $rootId);

        return new LocationTreeResource(collect($tree));
    }

    #[OA\Patch(
        path: '/api/locations/{id}/move',
        summary: 'Move location',
        description: 'Move a location to a new parent.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: 5),
                ],
            ),
        ),
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Moved',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
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
    public function move(MoveLocationRequest $request, int $id): JsonResponse
    {
        $location = $this->findService->find($id);
        if (! $location) {
            abort(404);
        }
        $this->authorize('move', $location);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = MoveLocationData::fromArray($validated);
        $this->moveService->execute($dto->toArray());

        return response()->json(['message' => 'Location moved successfully']);
    }

    #[OA\Get(
        path: '/api/locations/{id}/descendants',
        summary: 'Get descendants of a location',
        description: 'Returns all direct and indirect children of the given location in nested-set order.',
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of descendant locations',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LocationObject')),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function descendants(int $id): JsonResponse
    {
        $location = $this->findService->find($id);
        if (! $location) {
            abort(404);
        }
        $this->authorize('view', $location);
        $items = $this->findService->getDescendants($id);

        return response()->json([
            'data' => LocationResource::collection(collect($items)),
        ]);
    }

    #[OA\Get(
        path: '/api/locations/{id}/ancestors',
        summary: 'Get ancestors of a location',
        description: 'Returns the parent chain (root → direct parent) of the given location.',
        tags: ['Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of ancestor locations',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LocationObject')),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function ancestors(int $id): JsonResponse
    {
        $location = $this->findService->find($id);
        if (! $location) {
            abort(404);
        }
        $this->authorize('view', $location);
        $items = $this->findService->getAncestors($id);

        return response()->json([
            'data' => LocationResource::collection(collect($items)),
        ]);
    }
}
