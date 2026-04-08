<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreatePositionServiceInterface;
use Modules\HR\Application\Contracts\DeletePositionServiceInterface;
use Modules\HR\Application\Contracts\FindPositionServiceInterface;
use Modules\HR\Application\Contracts\UpdatePositionServiceInterface;
use Modules\HR\Application\DTOs\PositionData;
use Modules\HR\Application\DTOs\UpdatePositionData;
use Modules\HR\Domain\Entities\Position;
use Modules\HR\Infrastructure\Http\Requests\StorePositionRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdatePositionRequest;
use Modules\HR\Infrastructure\Http\Resources\PositionCollection;
use Modules\HR\Infrastructure\Http\Resources\PositionResource;
use OpenApi\Attributes as OA;

class PositionController extends AuthorizedController
{
    public function __construct(
        protected FindPositionServiceInterface $findService,
        protected CreatePositionServiceInterface $createService,
        protected UpdatePositionServiceInterface $updateService,
        protected DeletePositionServiceInterface $deleteService,
    ) {}

    #[OA\Get(
        path: '/api/hr/positions',
        summary: 'List positions',
        tags: ['HR - Positions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'department_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active',     in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',          in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',          in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'include',       in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of positions'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): PositionCollection
    {
        $this->authorize('viewAny', Position::class);
        $filters = $request->only(['department_id', 'is_active']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');
        $positions = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new PositionCollection($positions);
    }

    #[OA\Post(
        path: '/api/hr/positions',
        summary: 'Create position',
        tags: ['HR - Positions'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['tenant_id', 'name'],
            properties: [
                new OA\Property(property: 'tenant_id',     type: 'integer'),
                new OA\Property(property: 'name',          type: 'string',  maxLength: 255),
                new OA\Property(property: 'code',          type: 'string',  nullable: true, maxLength: 50),
                new OA\Property(property: 'description',   type: 'string',  nullable: true),
                new OA\Property(property: 'grade',         type: 'string',  nullable: true, maxLength: 50),
                new OA\Property(property: 'department_id', type: 'integer', nullable: true),
                new OA\Property(property: 'is_active',     type: 'boolean'),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Position created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(StorePositionRequest $request): JsonResponse
    {
        $this->authorize('create', Position::class);
        $dto      = PositionData::fromArray($request->validated());
        $position = $this->createService->execute($dto->toArray());

        return (new PositionResource($position))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/hr/positions/{id}',
        summary: 'Get position',
        tags: ['HR - Positions'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Position details'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(int $id): PositionResource
    {
        $position = $this->findService->find($id);
        if (! $position) {
            abort(404);
        }
        $this->authorize('view', $position);

        return new PositionResource($position);
    }

    #[OA\Put(
        path: '/api/hr/positions/{id}',
        summary: 'Update position',
        tags: ['HR - Positions'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name',      type: 'string'),
            new OA\Property(property: 'grade',     type: 'string',  nullable: true),
            new OA\Property(property: 'is_active', type: 'boolean'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Updated position'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function update(UpdatePositionRequest $request, int $id): PositionResource
    {
        $position = $this->findService->find($id);
        if (! $position) {
            abort(404);
        }
        $this->authorize('update', $position);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = UpdatePositionData::fromArray($validated);
        $updated         = $this->updateService->execute($dto->toArray());

        return new PositionResource($updated);
    }

    #[OA\Delete(
        path: '/api/hr/positions/{id}',
        summary: 'Delete position',
        tags: ['HR - Positions'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function destroy(int $id): JsonResponse
    {
        $position = $this->findService->find($id);
        if (! $position) {
            abort(404);
        }
        $this->authorize('delete', $position);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Position deleted successfully']);
    }

    #[OA\Get(
        path: '/api/hr/positions/by-department/{departmentId}',
        summary: 'Get positions by department',
        tags: ['HR - Positions'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'departmentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Positions in the department')],
    )]
    public function byDepartment(int $departmentId): JsonResponse
    {
        $this->authorize('viewAny', Position::class);
        $items = $this->findService->getByDepartment($departmentId);

        return response()->json(['data' => PositionResource::collection(collect($items))]);
    }
}
